<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\inventory\InvCostPeriodItemsRepository;
use PDO;

/**
 * Eficiência de produção (Pasta 5 / planilha linha 1094):
 * rendimento = qty produzida ÷ lote mínimo (por lote ou agregado no período).
 */
class InvCostProductionEfficiencyService extends DbConnection
{
    /**
     * @return array{efficiency_ratio: float, efficiency_pct: float, min_batch_size: float}|null
     */
    public function efficiencyForBatch(float $quantityProduced, float $minBatchSize): ?array
    {
        if ($minBatchSize <= 0 || $quantityProduced < 0) {
            return null;
        }

        $ratio = round($quantityProduced / $minBatchSize, 6);

        return [
            'efficiency_ratio' => $ratio,
            'efficiency_pct' => round($ratio * 100, 2),
            'min_batch_size' => round($minBatchSize, 4),
        ];
    }

    /**
     * @param array<string, mixed> $batchRow
     * @return array<string, mixed>
     */
    public function enrichBatchRow(array $batchRow): array
    {
        $minBatch = $this->resolveMinBatchSize(
            $batchRow['inv_item_id'] !== null ? (int)$batchRow['inv_item_id'] : null,
            trim((string)($batchRow['erp_code'] ?? '')),
            isset($batchRow['standard_batch_size']) ? (float)$batchRow['standard_batch_size'] : null
        );

        $qty = (float)($batchRow['quantity'] ?? 0);
        $eff = $minBatch !== null ? $this->efficiencyForBatch($qty, $minBatch) : null;

        $batchRow['min_batch_size'] = $minBatch;
        $batchRow['efficiency_ratio'] = $eff['efficiency_ratio'] ?? null;
        $batchRow['efficiency_pct'] = $eff['efficiency_pct'] ?? null;

        return $batchRow;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    public function enrichBatchRows(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $missingItemIds = [];
        $missingErpCodes = [];
        foreach ($rows as $row) {
            if (!empty($row['standard_batch_size'])) {
                continue;
            }
            $itemId = (int)($row['inv_item_id'] ?? 0);
            if ($itemId > 0) {
                $missingItemIds[$itemId] = $itemId;
            } else {
                $erp = trim((string)($row['erp_code'] ?? ''));
                if ($erp !== '') {
                    $missingErpCodes[$erp] = $erp;
                }
            }
        }

        $batchSizesByItem = $this->loadBatchSizesByItemIds(array_values($missingItemIds));
        $batchSizesByErp = $this->loadBatchSizesByErpCodes(array_values($missingErpCodes));

        foreach ($rows as &$row) {
            if (empty($row['standard_batch_size'])) {
                $itemId = (int)($row['inv_item_id'] ?? 0);
                if ($itemId > 0 && isset($batchSizesByItem[$itemId])) {
                    $row['standard_batch_size'] = $batchSizesByItem[$itemId];
                } else {
                    $erp = trim((string)($row['erp_code'] ?? ''));
                    if ($erp !== '' && isset($batchSizesByErp[$erp])) {
                        $row['standard_batch_size'] = $batchSizesByErp[$erp];
                    }
                }
            }
            $row = $this->enrichBatchRow($row);
        }
        unset($row);

        return $rows;
    }

    /**
     * Eficiência agregada do item no intervalo de datas.
     *
     * @param list<string>|null $warehouseCodes
     * @return array{
     *   efficiency_ratio: float,
     *   efficiency_pct: float,
     *   min_batch_size: float,
     *   qty_produced: float,
     *   batches_count: int,
     *   qty_theoretical: float
     * }|null
     */
    public function aggregateForItemInPeriod(
        ?int $itemId,
        ?string $erpCode,
        string $dateFrom,
        string $dateTo,
        ?array $warehouseCodes = null,
        ?int $periodId = null
    ): ?array {
        if ($dateFrom === '' || $dateTo === '') {
            return null;
        }

        $adoptedOverride = null;
        if ($periodId !== null && $periodId > 0 && $itemId !== null && $itemId > 0) {
            $periodItem = (new InvCostPeriodItemsRepository())->getOne($periodId, $itemId);
            if (is_array($periodItem) && !empty($periodItem['batch_size_adopted'])) {
                $adoptedOverride = (float)$periodItem['batch_size_adopted'];
            }
        }

        $minBatch = $this->resolveMinBatchSize($itemId, $erpCode, $adoptedOverride);
        if ($minBatch === null || $minBatch <= 0) {
            return null;
        }

        [$warehouseSql, $params] = $this->warehouseClause($warehouseCodes);
        $itemSql = '';
        if ($itemId !== null && $itemId > 0) {
            $itemSql = ' AND (b.inv_item_id = :item_id OR (b.inv_item_id IS NULL AND b.erp_code = :erp_code))';
            $params[':item_id'] = $itemId;
            $params[':erp_code'] = trim((string)$erpCode);
        } elseif ($erpCode !== null && trim($erpCode) !== '') {
            $itemSql = ' AND b.erp_code = :erp_code_only';
            $params[':erp_code_only'] = trim($erpCode);
        } else {
            return null;
        }

        $sql = "SELECT COALESCE(SUM(b.quantity), 0) AS qty_produced, COUNT(*) AS batches_count
                FROM inv_cost_production_batches b
                WHERE b.production_date BETWEEN :date_from AND :date_to
                {$warehouseSql}{$itemSql}";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':date_from', $dateFrom);
        $stmt->bindValue(':date_to', $dateTo);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $qty = (float)($row['qty_produced'] ?? 0);
        $batches = (int)($row['batches_count'] ?? 0);
        if ($batches <= 0 || $qty <= 0) {
            return null;
        }

        $theoretical = round($batches * $minBatch, 4);
        $ratio = round($qty / $theoretical, 6);

        return [
            'efficiency_ratio' => $ratio,
            'efficiency_pct' => round($ratio * 100, 2),
            'min_batch_size' => round($minBatch, 4),
            'qty_produced' => round($qty, 4),
            'batches_count' => $batches,
            'qty_theoretical' => $theoretical,
        ];
    }

    public function resolveMinBatchSize(?int $itemId, ?string $erpCode, mixed $prefetchedOrOverride = null): ?float
    {
        if ($prefetchedOrOverride !== null && $prefetchedOrOverride !== '') {
            $f = (float)$prefetchedOrOverride;
            if ($f > 0) {
                return round($f, 4);
            }
        }

        if ($itemId !== null && $itemId > 0) {
            $stmt = $this->getConnection()->prepare(
                'SELECT standard_batch_size FROM inv_items WHERE id = :id LIMIT 1'
            );
            $stmt->bindValue(':id', $itemId, PDO::PARAM_INT);
            $stmt->execute();
            $size = (float)($stmt->fetchColumn() ?: 0);

            return $size > 0 ? round($size, 4) : null;
        }

        $erp = trim((string)$erpCode);
        if ($erp === '') {
            return null;
        }

        $stmt = $this->getConnection()->prepare(
            'SELECT standard_batch_size FROM inv_items WHERE erp_code = :erp_code LIMIT 1'
        );
        $stmt->bindValue(':erp_code', $erp);
        $stmt->execute();
        $size = (float)($stmt->fetchColumn() ?: 0);

        return $size > 0 ? round($size, 4) : null;
    }

    /**
     * @param list<int> $itemIds
     * @return array<int, float>
     */
    private function loadBatchSizesByItemIds(array $itemIds): array
    {
        $itemIds = array_values(array_filter(array_map('intval', $itemIds)));
        if ($itemIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $stmt = $this->getConnection()->prepare(
            "SELECT id, standard_batch_size FROM inv_items WHERE id IN ({$placeholders})"
        );
        foreach ($itemIds as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $size = (float)($row['standard_batch_size'] ?? 0);
            if ($size > 0) {
                $map[(int)$row['id']] = round($size, 4);
            }
        }

        return $map;
    }

    /**
     * @param list<string> $erpCodes
     * @return array<string, float>
     */
    private function loadBatchSizesByErpCodes(array $erpCodes): array
    {
        $erpCodes = array_values(array_unique(array_filter(array_map(
            static fn(string $c): string => trim($c),
            $erpCodes
        ))));
        if ($erpCodes === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($erpCodes), '?'));
        $stmt = $this->getConnection()->prepare(
            "SELECT erp_code, standard_batch_size FROM inv_items WHERE erp_code IN ({$placeholders})"
        );
        foreach ($erpCodes as $i => $code) {
            $stmt->bindValue($i + 1, $code);
        }
        $stmt->execute();
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $size = (float)($row['standard_batch_size'] ?? 0);
            $erp = trim((string)($row['erp_code'] ?? ''));
            if ($size > 0 && $erp !== '') {
                $map[$erp] = round($size, 4);
            }
        }

        return $map;
    }

    /**
     * @param list<string>|null $warehouseCodes
     * @return array{0: string, 1: array<string, string>}
     */
    private function warehouseClause(?array $warehouseCodes): array
    {
        if ($warehouseCodes === null || $warehouseCodes === []) {
            return ['', []];
        }

        $placeholders = [];
        $params = [];
        foreach ($warehouseCodes as $index => $code) {
            $key = ':wh_' . $index;
            $placeholders[] = $key;
            $params[$key] = strtoupper(trim((string)$code));
        }

        return [' AND b.warehouse_code IN (' . implode(', ', $placeholders) . ')', $params];
    }
}
