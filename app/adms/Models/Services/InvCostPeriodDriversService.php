<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\InvCostBatchAdoptedHelper;
use App\adms\Models\Repository\inventory\InvCostPeriodItemsRepository;

/**
 * Drivers de rateio por período (HH, HM) com base na produção (production_date).
 */
class InvCostPeriodDriversService
{
    /**
     * @param list<string>|null $warehouseCodes
     * @return array{
     *   period: array<string, mixed>|null,
     *   warehouse_codes: list<string>|null,
     *   total_hh_period: float,
     *   total_hm_period: float,
     *   items: list<array<string, mixed>>
     * }
     */
    public function aggregateDriversByPeriod(int $periodId, ?array $warehouseCodes = null): array
    {
        $empty = [
            'period' => null,
            'warehouse_codes' => $warehouseCodes,
            'total_hh_period' => 0.0,
            'total_hm_period' => 0.0,
            'items' => [],
        ];

        if ($periodId <= 0) {
            return $empty;
        }

        $productionService = new InvCostProductionAggregationService();
        $production = $productionService->aggregateByPeriod($periodId, $warehouseCodes);
        if (!is_array($production['period'] ?? null)) {
            return $empty;
        }

        $periodItemsMap = (new InvCostPeriodItemsRepository())->getMapByPeriod($periodId);
        $defaultsService = new InvCostPeriodItemDefaultsService();
        $breakdownCache = [];
        $items = [];

        foreach ($production['items'] ?? [] as $prodRow) {
            $itemId = (int)($prodRow['inv_item_id'] ?? 0);
            $qtyProduced = (float)($prodRow['qty_produced'] ?? 0);
            $catalogBatch = $prodRow['catalog_standard_batch_size'] ?? null;

            $periodItem = $itemId > 0
                ? $defaultsService->mergeWithDefaults($itemId, $periodItemsMap[$itemId] ?? null)
                : null;
            $metrics = InvCostBatchAdoptedHelper::resolveProductionMetrics(
                $periodItem,
                $qtyProduced,
                (int)($prodRow['batches_count'] ?? 0),
                $catalogBatch
            );
            $adoptedBatch = (float)$metrics['batch_size_adopted'];
            $batchesProduced = (float)$metrics['batches_produced'];

            $hhPerBatch = 0.0;
            $hmPerBatch = 0.0;
            $hmRateioPerBatch = 0.0;

            if ($itemId > 0 && $batchesProduced > 0 && $adoptedBatch > 0) {
                $effRatio = (float)$metrics['efficiency_ratio'];
                $cacheKey = $itemId . ':' . round($adoptedBatch, 4) . ':' . round($effRatio, 4);
                if (!isset($breakdownCache[$cacheKey])) {
                    $scenario = ['standard_batch_size' => $adoptedBatch];
                    if ($effRatio > 0) {
                        $scenario['production_efficiency_ratio'] = $effRatio;
                    }
                    $breakdownCache[$cacheKey] = InventoryCostService::calculateBreakdown($itemId, $scenario);
                }
                $hhPerBatch = (float)($breakdownCache[$cacheKey]['rateio_labor_hours'] ?? 0);
                $hmPerBatch = (float)($breakdownCache[$cacheKey]['machine_hours'] ?? 0);
                $hmRateioPerBatch = (float)($breakdownCache[$cacheKey]['rateio_machine_hours'] ?? 0);
            }

            $hhPeriod = round($hhPerBatch * $batchesProduced, 6);
            $hmPeriod = round($hmPerBatch * $batchesProduced, 6);

            $items[] = [
                'inv_item_id' => $itemId > 0 ? $itemId : null,
                'erp_code' => (string)($prodRow['erp_code'] ?? ''),
                'description' => (string)($prodRow['description'] ?? ''),
                'qty_produced' => $qtyProduced,
                'batches_count' => (int)($prodRow['batches_count'] ?? 0),
                'batches_produced' => $batchesProduced,
                'batch_size_adopted' => $adoptedBatch,
                'efficiency_ratio' => (float)$metrics['efficiency_ratio'],
                'efficiency_pct' => (float)$metrics['efficiency_pct'],
                'qty_theoretical' => (float)($metrics['qty_planned'] ?? 0),
                'qty_planned' => $metrics['qty_planned'],
                'hh_per_batch' => round($hhPerBatch, 6),
                'hm_per_batch' => round($hmPerBatch, 6),
                'hm_rateio_per_batch' => round($hmRateioPerBatch ?? 0.0, 6),
                'hh_period' => $hhPeriod,
                'hm_period' => $hmPeriod,
                'share_criterion_1' => (float)($prodRow['share_criterion_1'] ?? 0),
                'share_criterion_2' => 0.0,
                'share_criterion_3' => 0.0,
                'is_scenario' => !empty($prodRow['is_scenario']),
            ];
        }

        $totalHh = array_sum(array_map(static fn(array $r): float => (float)($r['hh_period'] ?? 0), $items));
        $totalHm = array_sum(array_map(static fn(array $r): float => (float)($r['hm_period'] ?? 0), $items));

        foreach ($items as &$item) {
            $item['share_criterion_2'] = $totalHh > 0
                ? round(((float)$item['hh_period'] / $totalHh) * 100, 4)
                : 0.0;
            $item['share_criterion_3'] = $totalHm > 0
                ? round(((float)$item['hm_period'] / $totalHm) * 100, 4)
                : 0.0;
        }
        unset($item);

        return [
            'period' => $production['period'],
            'warehouse_codes' => $production['warehouse_codes'] ?? null,
            'total_hh_period' => round($totalHh, 6),
            'total_hm_period' => round($totalHm, 6),
            'items' => $items,
        ];
    }

    /**
     * @param array<string, mixed> $driversAggregation
     */
    public function findItemInDrivers(array $driversAggregation, ?int $invItemId, ?string $erpCode): ?array
    {
        foreach ($driversAggregation['items'] ?? [] as $item) {
            if ($invItemId !== null && $invItemId > 0 && (int)($item['inv_item_id'] ?? 0) === $invItemId) {
                return $item;
            }
            if ($erpCode !== null && $erpCode !== '' && (string)($item['erp_code'] ?? '') === $erpCode) {
                return $item;
            }
        }

        return null;
    }
}
