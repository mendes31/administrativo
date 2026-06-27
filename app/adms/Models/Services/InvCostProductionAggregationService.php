<?php

namespace App\adms\Models\Services;

use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;
use PDO;

class InvCostProductionAggregationService extends DbConnection
{
    /**
     * @param list<string>|null $warehouseCodes null ou [] = todos os depósitos
     * @return array{
     *   period: array<string, mixed>|null,
     *   warehouse_codes: list<string>|null,
     *   total_qty: float,
     *   total_batches: int,
     *   items: list<array<string, mixed>>,
     *   by_warehouse: list<array<string, mixed>>
     * }
     */
    public function aggregateByPeriod(int $periodId, ?array $warehouseCodes = null): array
    {
        $empty = [
            'period' => null,
            'warehouse_codes' => $this->normalizeWarehouseCodes($warehouseCodes),
            'total_qty' => 0.0,
            'total_batches' => 0,
            'items' => [],
            'by_warehouse' => [],
        ];

        if ($periodId <= 0) {
            return $empty;
        }

        $periodRepo = new InvCostPeriodsRepository();
        $period = $periodRepo->getOne($periodId);
        if ($period === false) {
            return $empty;
        }

        $normalizedWarehouses = $this->normalizeWarehouseCodes($warehouseCodes);
        $items = $this->fetchAggregatedItems(
            (string)$period['date_from'],
            (string)$period['date_to'],
            $normalizedWarehouses
        );

        $totalQty = array_sum(array_map(static fn(array $row): float => (float)($row['qty_produced'] ?? 0), $items));
        $totalBatches = (int)array_sum(array_map(static fn(array $row): int => (int)($row['batches_count'] ?? 0), $items));

        foreach ($items as &$item) {
            $qty = (float)($item['qty_produced'] ?? 0);
            $item['share_criterion_1'] = $totalQty > 0 ? round(($qty / $totalQty) * 100, 4) : 0.0;
        }
        unset($item);

        usort($items, static fn(array $a, array $b): int => ((float)($b['qty_produced'] ?? 0)) <=> ((float)($a['qty_produced'] ?? 0)));

        $efficiencyService = new InvCostProductionEfficiencyService();
        foreach ($items as &$item) {
            $itemId = $item['inv_item_id'] !== null ? (int)$item['inv_item_id'] : null;
            $eff = $efficiencyService->aggregateForItemInPeriod(
                $itemId,
                (string)($item['erp_code'] ?? ''),
                (string)$period['date_from'],
                (string)$period['date_to'],
                $normalizedWarehouses,
                $periodId
            );
            if ($eff !== null) {
                $item['efficiency_ratio'] = $eff['efficiency_ratio'];
                $item['efficiency_pct'] = $eff['efficiency_pct'];
                $item['min_batch_size'] = $eff['min_batch_size'];
                $item['qty_theoretical'] = $eff['qty_theoretical'];
            } else {
                $item['efficiency_ratio'] = null;
                $item['efficiency_pct'] = null;
                $item['min_batch_size'] = null;
                $item['qty_theoretical'] = null;
            }
        }
        unset($item);

        return [
            'period' => $period,
            'warehouse_codes' => $normalizedWarehouses,
            'total_qty' => round($totalQty, 6),
            'total_batches' => $totalBatches,
            'items' => $items,
            'by_warehouse' => $this->fetchWarehouseBreakdown(
                (string)$period['date_from'],
                (string)$period['date_to'],
                $normalizedWarehouses
            ),
        ];
    }

    public function findItemInAggregation(array $aggregation, ?int $invItemId, ?string $erpCode): ?array
    {
        foreach ($aggregation['items'] ?? [] as $item) {
            if ($invItemId !== null && $invItemId > 0 && (int)($item['inv_item_id'] ?? 0) === $invItemId) {
                return $item;
            }
            if ($erpCode !== null && $erpCode !== '' && (string)($item['erp_code'] ?? '') === $erpCode) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param list<string>|null $warehouseCodes
     * @return list<array<string, mixed>>
     */
    private function fetchAggregatedItems(string $dateFrom, string $dateTo, ?array $warehouseCodes): array
    {
        [$warehouseSql, $params] = $this->warehouseClause($warehouseCodes);
        $sql = "SELECT
                    b.erp_code,
                    MAX(b.item_description) AS description,
                    MAX(b.inv_item_id) AS inv_item_id,
                    SUM(b.quantity) AS qty_produced,
                    COUNT(*) AS batches_count,
                    COUNT(DISTINCT b.batch_number) AS distinct_batch_numbers,
                    COUNT(DISTINCT b.goods_receipt_doc_num) AS entries_count
                FROM inv_cost_production_batches b
                WHERE b.production_date BETWEEN :date_from AND :date_to
                {$warehouseSql}
                GROUP BY b.erp_code
                ORDER BY qty_produced DESC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':date_from', $dateFrom);
        $stmt->bindValue(':date_to', $dateTo);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static function (array $row): array {
            $row['qty_produced'] = round((float)($row['qty_produced'] ?? 0), 6);
            $row['batches_count'] = (int)($row['batches_count'] ?? 0);
            $row['entries_count'] = (int)($row['entries_count'] ?? 0);
            $row['inv_item_id'] = $row['inv_item_id'] !== null ? (int)$row['inv_item_id'] : null;

            return $row;
        }, $rows);
    }

    /**
     * @param list<string>|null $warehouseCodes
     * @return list<array<string, mixed>>
     */
    private function fetchWarehouseBreakdown(string $dateFrom, string $dateTo, ?array $warehouseCodes): array
    {
        [$warehouseSql, $params] = $this->warehouseClause($warehouseCodes);
        $sql = "SELECT
                    b.warehouse_code,
                    MAX(b.warehouse_name) AS warehouse_name,
                    SUM(b.quantity) AS qty_produced,
                    COUNT(DISTINCT b.batch_number) AS batches_count
                FROM inv_cost_production_batches b
                WHERE b.production_date BETWEEN :date_from AND :date_to
                {$warehouseSql}
                GROUP BY b.warehouse_code
                ORDER BY b.warehouse_code ASC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':date_from', $dateFrom);
        $stmt->bindValue(':date_to', $dateTo);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
            $params[$key] = $code;
        }

        return [' AND b.warehouse_code IN (' . implode(', ', $placeholders) . ')', $params];
    }

    /**
     * @param list<string>|null $codes
     * @return list<string>|null
     */
    private function normalizeWarehouseCodes(?array $codes): ?array
    {
        if ($codes === null) {
            return null;
        }

        $normalized = [];
        foreach ($codes as $code) {
            $value = strtoupper(trim((string)$code));
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return $normalized === [] ? null : array_values(array_unique($normalized));
    }
}
