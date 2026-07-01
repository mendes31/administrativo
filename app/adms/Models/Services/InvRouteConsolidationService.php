<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\InvItemBomExplosionHelper;
use App\adms\Helpers\InvRoutePiExplosionHelper;
use App\adms\Models\Repository\inventory\InvItemOperationsRepository;
use App\adms\Models\Repository\inventory\InvItemRouteConsolidatedRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvLaborRolesRepository;
use App\adms\Models\Repository\inventory\InvProductionResourcesRepository;

/**
 * Consolida linhas da rota SAP em operações de custeio.
 *
 * Regra principal: MASTER_POS_ID (BEAS_APL) — linhas com master_pos_id > 0
 * pertencem ao grupo da posição mestre; senão cada POS_ID é um grupo.
 *
 * Fallback: mesma operação em sequência contígua (sem vínculo master).
 */
class InvRouteConsolidationService
{
    /**
     * Garante rota consolidada para custeio a partir da rota SAP local.
     * Cria quando ausente; com $forceRegenerate substitui após importação SAP.
     *
     * @return array{created: bool, lines: int}
     */
    public function ensureForItem(int $invItemId, bool $forceRegenerate = false): array
    {
        $consolidatedRepo = new InvItemRouteConsolidatedRepository();
        if (!$forceRegenerate && $consolidatedRepo->hasForItem($invItemId)) {
            $existing = $consolidatedRepo->getByItem($invItemId);
            $needsPiMerge = $this->shouldMergePiRoutesForItem($invItemId)
                && InvRoutePiExplosionHelper::paHasIntermediateProducts($invItemId)
                && !InvRoutePiExplosionHelper::consolidatedIncludesPiRoutes($existing);
            if (!$needsPiMerge) {
                return [
                    'created' => false,
                    'lines' => count($existing),
                ];
            }
            $forceRegenerate = true;
        }

        $sapRoute = (new InvItemOperationsRepository())->getByItem($invItemId);
        if ($sapRoute === [] && !InvRoutePiExplosionHelper::paHasIntermediateProducts($invItemId)) {
            return ['created' => false, 'lines' => 0];
        }

        $lines = $sapRoute !== []
            ? $this->buildFromSapRoute($sapRoute)
            : [];
        if ($this->shouldMergePiRoutesForItem($invItemId)) {
            $lines = InvRoutePiExplosionHelper::mergePiRoutesIntoPa($invItemId, $lines);
        }
        if ($lines === [] || !$consolidatedRepo->replaceForItem($invItemId, $lines)) {
            return ['created' => false, 'lines' => 0];
        }

        return ['created' => true, 'lines' => count($lines)];
    }

    /**
     * @param list<array<string, mixed>> $sapLines Linhas de InvItemOperationsRepository::getByItem
     * @return list<array<string, mixed>> Linhas prontas para InvItemRouteConsolidatedRepository::replaceForItem
     */
    public function buildFromSapRoute(array $sapLines): array
    {
        if ($sapLines === []) {
            return [];
        }

        require_once __DIR__ . '/../../Views/inventory/partials/sap_pos_display.php';

        $posTextMap = invBuildSapPosTextMap($sapLines);

        $sorted = $sapLines;
        usort($sorted, 'invSapCompareRouteLines');

        $hasMasterLinks = false;
        foreach ($sorted as $line) {
            if ((int)($line['sap_master_pos_id'] ?? 0) > 0) {
                $hasMasterLinks = true;
                break;
            }
        }

        $groups = $hasMasterLinks
            ? $this->groupByMasterPosId($sorted)
            : $this->groupByConsecutiveOperation($sorted);

        $resourcesRepo = new InvProductionResourcesRepository();
        $laborRolesRepo = new InvLaborRolesRepository();

        $result = [];
        $sequence = 1;
        foreach ($groups as $group) {
            $merged = $this->mergeGroup($group, $resourcesRepo, $laborRolesRepo, $posTextMap);
            if ($merged === null) {
                continue;
            }
            $merged['sequence'] = $sequence++;
            $result[] = $merged;
        }

        return $result;
    }

    /**
     * @param list<array<string, mixed>> $lines
     * @return list<list<array<string, mixed>>>
     */
    private function groupByMasterPosId(array $lines): array
    {
        $byPos = [];
        foreach ($lines as $line) {
            $posId = (int)($line['sap_pos_id'] ?? $line['sequence'] ?? 0);
            if ($posId > 0) {
                $byPos[$posId] = $line;
            }
        }

        $groupMap = [];
        foreach ($lines as $line) {
            $posId = (int)($line['sap_pos_id'] ?? $line['sequence'] ?? 0);
            $masterPosId = (int)($line['sap_master_pos_id'] ?? 0);
            $groupKey = $masterPosId > 0 ? $masterPosId : $posId;
            if ($groupKey <= 0) {
                $groupKey = $posId > 0 ? $posId : count($groupMap) + 1;
            }
            $groupMap[$groupKey][] = $line;
        }

        $groups = array_values($groupMap);
        usort($groups, static fn(array $a, array $b): int => invSapGroupMinSortKey($a) <=> invSapGroupMinSortKey($b));

        return $groups;
    }

    /**
     * @param list<array<string, mixed>> $lines
     * @return list<list<array<string, mixed>>>
     */
    private function groupByConsecutiveOperation(array $lines): array
    {
        $groups = [];
        $current = [];
        $currentOpId = null;

        foreach ($lines as $line) {
            $opId = (int)($line['inv_operation_id'] ?? 0);
            if ($current !== [] && $opId !== $currentOpId) {
                $groups[] = $current;
                $current = [];
            }
            $current[] = $line;
            $currentOpId = $opId;
        }
        if ($current !== []) {
            $groups[] = $current;
        }

        return $groups;
    }

    /**
     * @param list<array<string, mixed>> $group
     * @return array<string, mixed>|null
     */
    private function mergeGroup(
        array $group,
        InvProductionResourcesRepository $resourcesRepo,
        InvLaborRolesRepository $laborRolesRepo,
        array $posTextMap = []
    ): ?array {
        if ($group === []) {
            return null;
        }

        $masterLine = $this->resolveMasterLine($group);
        $groupPosId = (int)($masterLine['sap_pos_id'] ?? $masterLine['sequence'] ?? 0);
        $groupPosText = invSapResolvePosText($groupPosId, $posTextMap);

        $resources = [];
        $labor = [];
        $maxTimeMinutes = 0.0;
        $sapPosNotes = [];

        foreach ($group as $line) {
            $maxTimeMinutes = max($maxTimeMinutes, $this->lineTimeMinutes($line));
            $sapCode = $this->extractSapResourceCode($line);
            if ($sapCode !== '') {
                $sapPosNotes[] = $sapCode;
            }

            foreach ($line['resource_lines'] ?? [] as $resourceLine) {
                $type = strtoupper((string)($resourceLine['resource_type'] ?? 'MACHINE'));
                $resourceId = (int)($resourceLine['inv_production_resource_id'] ?? 0);
                $erpCode = strtoupper(trim((string)($resourceLine['resource_erp_code'] ?? '')));

                if ($type === 'LABOR' || $this->isLaborErpCode($erpCode)) {
                    $role = $laborRolesRepo->findByCodeOrName($erpCode !== '' ? $erpCode : $sapCode);
                    if ($role !== null) {
                        $labor[] = [
                            'inv_labor_role_id' => (int)$role['id'],
                            'qty' => max(1, (int)($resourceLine['qty'] ?? 1)),
                            'cost_per_min' => (float)($role['default_cost_per_min'] ?? $resourceLine['machine_cost_per_min'] ?? 0),
                        ];
                    }
                    continue;
                }

                if ($resourceId > 0) {
                    $resources[] = [
                        'inv_production_resource_id' => $resourceId,
                        'qty' => max(1, (int)($resourceLine['qty'] ?? 1)),
                        'machine_cost_per_min' => (float)($resourceLine['machine_cost_per_min'] ?? 0),
                        'energy_cost_per_min' => (float)($resourceLine['energy_cost_per_min'] ?? 0),
                    ];
                }
            }

            if (($line['resource_lines'] ?? []) === [] && $sapCode !== '') {
                $resolved = $resourcesRepo->resolveRouteCosts($sapCode, (string)($line['operation_name'] ?? ''));
                $resourceId = (int)($resolved['resource_id'] ?? 0);
                if ($resourceId > 0) {
                    $resourceRow = $resourcesRepo->getOne($resourceId);
                    $type = strtoupper((string)($resourceRow['resource_type'] ?? 'MACHINE'));
                    if ($type === 'LABOR') {
                        $role = $laborRolesRepo->findByCodeOrName($sapCode);
                        if ($role !== null) {
                            $labor[] = [
                                'inv_labor_role_id' => (int)$role['id'],
                                'qty' => 1,
                                'cost_per_min' => (float)($role['default_cost_per_min'] ?? $resolved['labor_cost_per_min'] ?? 0),
                            ];
                        }
                    } else {
                        $resources[] = [
                            'inv_production_resource_id' => $resourceId,
                            'qty' => 1,
                            'machine_cost_per_min' => (float)($resolved['machine_cost_per_min'] ?? 0),
                            'energy_cost_per_min' => (float)($resolved['energy_cost_per_min'] ?? 0),
                        ];
                    }
                } elseif ($this->isLaborErpCode($sapCode)) {
                    $role = $laborRolesRepo->findByCodeOrName($sapCode);
                    if ($role !== null) {
                        $labor[] = [
                            'inv_labor_role_id' => (int)$role['id'],
                            'qty' => 1,
                            'cost_per_min' => (float)($role['default_cost_per_min'] ?? 0),
                        ];
                    }
                }
            }
        }

        return [
            'inv_operation_id' => (int)($masterLine['inv_operation_id'] ?? 0),
            'sap_group_pos_id' => $groupPosId > 0 ? $groupPosId : null,
            'sap_group_pos_text' => $groupPosText > 0 ? $groupPosText : null,
            'time_per_batch_hours' => round($maxTimeMinutes, 6),
            'time_unit' => 'MIN',
            'notes' => $sapPosNotes !== []
                ? 'Consolidado SAP: ' . implode(', ', array_unique($sapPosNotes))
                : null,
            'resources' => $this->dedupeResources($resources),
            'labor' => $this->dedupeLabor($labor),
        ];
    }

    /**
     * @param list<array<string, mixed>> $group
     * @return array<string, mixed>
     */
    private function resolveMasterLine(array $group): array
    {
        foreach ($group as $line) {
            $posId = (int)($line['sap_pos_id'] ?? $line['sequence'] ?? 0);
            $masterPosId = (int)($line['sap_master_pos_id'] ?? 0);
            if ($masterPosId <= 0 && $posId > 0) {
                return $line;
            }
        }

        foreach ($group as $line) {
            $masterPosId = (int)($line['sap_master_pos_id'] ?? 0);
            if ($masterPosId > 0) {
                foreach ($group as $candidate) {
                    if ((int)($candidate['sap_pos_id'] ?? $candidate['sequence'] ?? 0) === $masterPosId) {
                        return $candidate;
                    }
                }
            }
        }

        return $group[0];
    }

    /**
     * @param array<string, mixed> $line
     */
    private function lineTimeMinutes(array $line): float
    {
        $raw = (float)($line['time_per_batch_hours'] ?? 0);
        $unit = strtoupper((string)($line['time_unit'] ?? 'MIN'));

        return $unit === 'H' ? $raw * 60.0 : $raw;
    }

    /**
     * @param array<string, mixed> $line
     */
    private function extractSapResourceCode(array $line): string
    {
        $notes = (string)($line['notes'] ?? '');
        if (preg_match('/Recurso SAP:\s*(.+)$/i', $notes, $m)) {
            return strtoupper(trim($m[1]));
        }

        $resourceLines = $line['resource_lines'] ?? [];
        if ($resourceLines !== []) {
            return strtoupper(trim((string)($resourceLines[0]['resource_erp_code'] ?? '')));
        }

        return '';
    }

    private function isLaborErpCode(string $code): bool
    {
        $upper = mb_strtoupper($code, 'UTF-8');

        return str_contains($upper, 'OPERADOR')
            || str_contains($upper, 'MANIPULADOR')
            || str_contains($upper, 'AUXILIAR')
            || str_contains($upper, 'FACILITADOR')
            || str_contains($upper, 'CARTONAGEM');
    }

    /**
     * @param list<array<string, mixed>> $resources
     * @return list<array<string, mixed>>
     */
    private function dedupeResources(array $resources): array
    {
        $map = [];
        foreach ($resources as $row) {
            $id = (int)($row['inv_production_resource_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            if (!isset($map[$id])) {
                $map[$id] = $row;
                continue;
            }
            $map[$id]['qty'] = max(1, (int)$map[$id]['qty']) + max(1, (int)($row['qty'] ?? 1));
        }

        return array_values($map);
    }

    /**
     * @param list<array<string, mixed>> $labor
     * @return list<array<string, mixed>>
     */
    private function dedupeLabor(array $labor): array
    {
        $map = [];
        foreach ($labor as $row) {
            $id = (int)($row['inv_labor_role_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            if (!isset($map[$id])) {
                $map[$id] = $row;
                continue;
            }
            $map[$id]['qty'] = max(1, (int)$map[$id]['qty']) + max(1, (int)($row['qty'] ?? 1));
        }

        return array_values($map);
    }

    /**
     * Converte linhas consolidadas gravadas para o formato de operação usado no custeio.
     *
     * @param list<array<string, mixed>> $consolidatedLines
     * @return list<array<string, mixed>>
     */
    public static function toCostingOperationRows(array $consolidatedLines): array
    {
        $rows = [];
        foreach ($consolidatedLines as $line) {
            $laborLines = is_array($line['labor_lines'] ?? null) ? $line['labor_lines'] : [];
            $resourceLines = is_array($line['resource_lines'] ?? null) ? $line['resource_lines'] : [];
            $manualLaborPerMin = InvItemOperationsRepository::sumLaborCostPerMinute($laborLines);

            $rows[] = [
                'id' => (int)($line['id'] ?? 0),
                'inv_operation_id' => (int)($line['inv_operation_id'] ?? 0),
                'sequence' => (int)($line['sequence'] ?? 1),
                'time_per_batch_hours' => (float)($line['time_per_batch_hours'] ?? 0),
                'time_unit' => (string)($line['time_unit'] ?? 'MIN'),
                'operators_qty' => 1,
                'labor_cost_per_min' => $manualLaborPerMin,
                'machine_cost_per_min' => 0,
                'energy_cost_per_min' => 0,
                'notes' => (string)($line['notes'] ?? ''),
                'operation_code' => (string)($line['operation_code'] ?? ''),
                'operation_name' => (string)($line['operation_name'] ?? ''),
                'default_cost_per_hour' => (float)($line['operation_cost_per_hour'] ?? 0),
                'labor_lines' => $laborLines,
                'resource_lines' => $resourceLines,
            ];
        }

        return $rows;
    }

    /**
     * Só o PA recebe rota consolidada com PIs incorporados; o PI guarda apenas a rota própria.
     */
    private function shouldMergePiRoutesForItem(int $invItemId): bool
    {
        $item = (new InvItemsRepository())->getOne($invItemId);
        if (!is_array($item)) {
            return false;
        }

        return !InvItemBomExplosionHelper::isIntermediateCategory((string)($item['category_name'] ?? ''));
    }
}
