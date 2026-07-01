<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\inventory\InvItemBomRepository;
use App\adms\Models\Repository\inventory\InvItemOperationsRepository;
use App\adms\Models\Repository\inventory\InvItemRouteConsolidatedRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InvRouteConsolidationService;

/**
 * Incorpora rotas consolidadas dos produtos intermediários (PI) na rota do PA.
 *
 * Espelha a FORMPROD: PIs aninhados na BOM (ex. gelatina 60500005 dentro do óleo 61000095)
 * têm rota própria, incorporada antes da rota do PI pai (ordem da BOM).
 */
final class InvRoutePiExplosionHelper
{
    private const NOTES_PREFIX = 'PI:';
    private const MAX_DEPTH = 8;

    /**
     * @return list<array{item_id: int, code: string, description: string, quantity_per_batch: float}>
     */
    public static function collectIntermediateProducts(int $paItemId): array
    {
        if ($paItemId <= 0) {
            return [];
        }

        $pis = [];
        foreach ((new InvItemBomRepository())->getCostingRowsByItem($paItemId) as $row) {
            if ((string)($row['line_source'] ?? 'catalog') !== 'catalog') {
                continue;
            }
            $componentId = (int)($row['component_item_id'] ?? 0);
            if ($componentId <= 0 || !InvItemBomExplosionHelper::isIntermediateCategory((string)($row['component_category'] ?? ''))) {
                continue;
            }
            $pis[] = self::piMetaFromRow($row);
        }

        return $pis;
    }

    public static function paHasIntermediateProducts(int $paItemId): bool
    {
        return self::collectIntermediateProducts($paItemId) !== [];
    }

    /**
     * @param list<array<string, mixed>> $consolidatedLines
     */
    public static function consolidatedIncludesPiRoutes(array $consolidatedLines): bool
    {
        foreach ($consolidatedLines as $line) {
            if (self::parseSourcePiCode((string)($line['notes'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array<string, mixed>> $paLines Linhas no formato replaceForItem
     * @return list<array<string, mixed>>
     */
    public static function mergePiRoutesIntoPa(int $paItemId, array $paLines): array
    {
        $merged = [];
        foreach (self::collectIntermediateProducts($paItemId) as $pi) {
            $piItemId = (int)($pi['item_id'] ?? 0);
            if ($piItemId <= 0) {
                continue;
            }
            $merged = array_merge($merged, self::collectRoutesForPiTree($piItemId));
        }

        foreach ($paLines as $line) {
            $line['notes'] = self::stripPiPrefix((string)($line['notes'] ?? ''));
            $merged[] = $line;
        }

        return self::renumberSequences($merged);
    }

    /**
     * Rota do PI e de PIs aninhados na BOM (filhos antes do pai — como na FORMPROD).
     *
     * @return list<array<string, mixed>>
     */
    public static function collectRoutesForPiTree(int $piItemId, int $depth = 0): array
    {
        if ($piItemId <= 0 || $depth >= self::MAX_DEPTH) {
            return [];
        }

        $bomRepo = new InvItemBomRepository();
        $piMeta = self::resolvePiMeta($piItemId);
        $merged = [];

        foreach ($bomRepo->getCostingRowsByItem($piItemId) as $row) {
            if ((string)($row['line_source'] ?? 'catalog') !== 'catalog') {
                continue;
            }
            $childId = (int)($row['component_item_id'] ?? 0);
            if ($childId <= 0 || !InvItemBomExplosionHelper::isIntermediateCategory((string)($row['component_category'] ?? ''))) {
                continue;
            }
            $merged = array_merge($merged, self::collectRoutesForPiTree($childId, $depth + 1));
        }

        foreach (self::loadConsolidatedLinesForPi($piItemId) as $line) {
            $merged[] = self::tagPiLine($line, $piMeta);
        }

        return $merged;
    }

    public static function parseSourcePiCode(string $notes): string
    {
        if (preg_match('/\bPI:([A-Za-z0-9][A-Za-z0-9._\-]*)/', $notes, $m)) {
            return trim((string)($m[1] ?? ''));
        }

        return '';
    }

    /**
     * @param array<string, mixed> $row
     * @return array{item_id: int, code: string, description: string, quantity_per_batch: float}
     */
    private static function piMetaFromRow(array $row): array
    {
        return [
            'item_id' => (int)($row['component_item_id'] ?? 0),
            'code' => trim((string)($row['component_code'] ?? '')),
            'description' => trim((string)($row['component_description'] ?? '')),
            'quantity_per_batch' => (float)($row['quantity_per_batch'] ?? 0),
        ];
    }

    /**
     * @return array{item_id: int, code: string, description: string}
     */
    private static function resolvePiMeta(int $piItemId): array
    {
        $item = (new InvItemsRepository())->getOne($piItemId);

        return [
            'item_id' => $piItemId,
            'code' => trim((string)($item['code'] ?? '')),
            'description' => trim((string)($item['description'] ?? '')),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function loadConsolidatedLinesForPi(int $piItemId): array
    {
        $consolidation = new InvRouteConsolidationService();
        $consolidatedRepo = new InvItemRouteConsolidatedRepository();
        $opsRepo = new InvItemOperationsRepository();

        $consolidation->ensureForItem($piItemId);
        $piLines = $consolidatedRepo->getByItem($piItemId);
        if ($piLines === []) {
            $piSap = $opsRepo->getByItem($piItemId);
            if ($piSap === []) {
                return [];
            }

            return $consolidation->buildFromSapRoute($piSap);
        }

        return array_map(static fn(array $line): array => self::normalizeStoredLine($line), $piLines);
    }

    /**
     * @param array<string, mixed> $line
     * @param array{item_id: int, code: string, description: string} $pi
     * @return array<string, mixed>
     */
    private static function tagPiLine(array $line, array $pi): array
    {
        $code = trim((string)($pi['code'] ?? ''));
        if ($code === '') {
            $code = (string)(int)($pi['item_id'] ?? 0);
        }
        $desc = trim((string)($pi['description'] ?? ''));
        $prefix = self::NOTES_PREFIX . $code;
        if ($desc !== '') {
            $prefix .= ' — ' . $desc;
        }

        $notes = trim((string)($line['notes'] ?? ''));
        if ($notes !== '' && !str_starts_with($notes, self::NOTES_PREFIX)) {
            $line['notes'] = $prefix . ' | ' . $notes;
        } elseif ($notes === '') {
            $line['notes'] = $prefix;
        }

        $line['source_pi_item_id'] = (int)($pi['item_id'] ?? 0);
        $line['source_pi_code'] = $code;

        return $line;
    }

    /**
     * @param array<string, mixed> $line
     * @return array<string, mixed>
     */
    private static function normalizeStoredLine(array $line): array
    {
        $resources = [];
        foreach ($line['resource_lines'] ?? [] as $resourceLine) {
            $resourceId = (int)($resourceLine['inv_production_resource_id'] ?? 0);
            if ($resourceId <= 0) {
                continue;
            }
            $resources[] = [
                'inv_production_resource_id' => $resourceId,
                'qty' => max(1, (int)($resourceLine['qty'] ?? 1)),
                'line_time_minutes' => $resourceLine['line_time_minutes'] ?? null,
                'machine_cost_per_min' => (float)($resourceLine['machine_cost_per_min'] ?? 0),
                'energy_cost_per_min' => (float)($resourceLine['energy_cost_per_min'] ?? 0),
            ];
        }

        $labor = [];
        foreach ($line['labor_lines'] ?? [] as $laborLine) {
            $roleId = (int)($laborLine['inv_labor_role_id'] ?? 0);
            if ($roleId <= 0) {
                continue;
            }
            $labor[] = [
                'inv_labor_role_id' => $roleId,
                'qty' => max(1, (int)($laborLine['qty'] ?? 1)),
                'line_time_minutes' => $laborLine['line_time_minutes'] ?? null,
                'cost_per_min' => (float)($laborLine['cost_per_min'] ?? 0),
            ];
        }

        return [
            'inv_operation_id' => (int)($line['inv_operation_id'] ?? 0),
            'sap_group_pos_id' => isset($line['sap_group_pos_id']) ? (int)$line['sap_group_pos_id'] : null,
            'sap_group_pos_text' => isset($line['sap_group_pos_text']) ? (int)$line['sap_group_pos_text'] : null,
            'time_per_batch_hours' => (float)($line['time_per_batch_hours'] ?? 0),
            'time_unit' => (string)($line['time_unit'] ?? 'MIN'),
            'notes' => (string)($line['notes'] ?? ''),
            'resources' => $resources,
            'labor' => $labor,
        ];
    }

    private static function stripPiPrefix(string $notes): string
    {
        $notes = trim($notes);
        if ($notes === '') {
            return '';
        }
        if (preg_match('/^PI:[^|]+(?:\s*\|\s*)?(.*)$/u', $notes, $m)) {
            return trim((string)($m[1] ?? ''));
        }

        return $notes;
    }

    /**
     * @param list<array<string, mixed>> $lines
     * @return list<array<string, mixed>>
     */
    private static function renumberSequences(array $lines): array
    {
        $sequence = 1;
        foreach ($lines as &$line) {
            $line['sequence'] = $sequence++;
        }
        unset($line);

        return $lines;
    }
}
