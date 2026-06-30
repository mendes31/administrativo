<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\InvCostEnergyClassHelper;
use App\adms\Helpers\InvCostProductionLineHelper;
use App\adms\Models\Repository\inventory\InvCostPeriodItemsRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;

/**
 * Drivers e percentuais dos 8 critérios de rateio por período/SKU.
 */
class InvCostCriterionDriversService
{
    /**
     * @param list<string>|null $warehouseCodes
     * @return array{
     *   period: array<string, mixed>|null,
     *   warehouse_codes: list<string>|null,
     *   items: list<array<string, mixed>>
     * }
     */
    public function aggregateAllCriteria(int $periodId, ?array $warehouseCodes = null): array
    {
        $periodDrivers = (new InvCostPeriodDriversService())->aggregateDriversByPeriod($periodId, $warehouseCodes);
        $periodItemsMap = (new InvCostPeriodItemsRepository())->getMapByPeriod($periodId);

        $items = [];
        $totals = [
            'driver_1' => 0.0,
            'driver_2' => 0.0,
            'driver_3' => 0.0,
            'driver_4' => 0.0,
            'driver_5' => 0.0,
            'driver_6' => 0.0,
            'driver_7' => 0.0,
            'driver_8' => 0.0,
        ];

        foreach ($periodDrivers['items'] ?? [] as $baseRow) {
            $itemId = (int)($baseRow['inv_item_id'] ?? 0);
            $erpCode = trim((string)($baseRow['erp_code'] ?? ''));
            if ($itemId <= 0 && $erpCode !== '') {
                $found = (new InvItemsRepository())->findByErpCode($erpCode);
                if ($found !== null) {
                    $itemId = (int)($found['id'] ?? 0);
                }
            }
            $savedPeriodItem = $itemId > 0 ? ($periodItemsMap[$itemId] ?? null) : null;
            $periodItem = $itemId > 0
                ? (new InvCostPeriodItemDefaultsService())->mergeWithDefaults($itemId, is_array($savedPeriodItem) ? $savedPeriodItem : null)
                : null;

            $driver1 = (float)($baseRow['qty_produced'] ?? 0);
            $driver2 = (float)($baseRow['hh_period'] ?? 0);
            $driver3 = (float)($baseRow['hm_period'] ?? 0);
            $driver4 = (new InvCostAnalysisDriverService())->complexityFactor($periodItem);
            $batchesCount = max(0, (int)($baseRow['batches_count'] ?? 0));
            $efficiencyRatio = isset($baseRow['efficiency_ratio']) ? (float)$baseRow['efficiency_ratio'] : null;
            $qtyProduced = (float)($baseRow['qty_produced'] ?? 0);
            $qtyPlanned = isset($baseRow['qty_theoretical']) ? (float)$baseRow['qty_theoretical'] : null;
            $efficiencyForAnalysis = (new InvCostAnalysisDriverService())->normalizeEfficiencyRatio(
                $efficiencyRatio,
                $qtyProduced > 0 ? $qtyProduced : null,
                $qtyPlanned
            );
            $analysisDriver = new InvCostAnalysisDriverService();
            $driver6 = $itemId > 0
                ? $analysisDriver->driver6($itemId, $batchesCount, $periodItem, $efficiencyForAnalysis)
                : 0.0;
            $driver5 = $itemId > 0 ? (float)$analysisDriver->countMpLines($itemId) : 0.0;
            $periodMeta = $periodDrivers['period'] ?? null;
            $hmPerBatch = 0.0;
            if ($itemId > 0) {
                $hmPerBatch = (float)(InventoryCostService::calculateBreakdown($itemId, [])['machine_hours'] ?? 0);
            }
            $driver7 = 0.0;
            if ($itemId > 0 && InvCostProductionLineHelper::isPeriodItemEligibleForDirectEnergy($periodItem)) {
                $driver7 = (new InvCostVariableEnergyService())->computePeriodEnergyDriver(
                    $itemId,
                    (float)($baseRow['hm_period'] ?? 0),
                    $hmPerBatch,
                    (float)(is_array($periodMeta) ? ($periodMeta['kwh_tariff'] ?? 0) : 0),
                    $periodItem
                );
            }
            $driver8 = $this->hvacDriver($periodItem);

            $totals['driver_1'] += $driver1;
            $totals['driver_2'] += $driver2;
            $totals['driver_3'] += $driver3;
            $totals['driver_4'] += $driver4;
            $totals['driver_5'] += $driver5;
            $totals['driver_6'] += $driver6;
            $totals['driver_7'] += $driver7;
            $totals['driver_8'] += $driver8;

            $items[] = array_merge($baseRow, [
                'production_line' => InvCostProductionLineHelper::fromPeriodItem($periodItem),
                'driver_1' => round($driver1, 6),
                'driver_2' => round($driver2, 6),
                'driver_3' => round($driver3, 6),
                'driver_4' => round($driver4, 6),
                'driver_5' => round($driver5, 6),
                'driver_6' => round($driver6, 6),
                'driver_7' => round($driver7, 6),
                'driver_8' => round($driver8, 6),
                'analysis_count_total' => $itemId > 0
                    ? $analysisDriver->analysisCountTotal($itemId, $batchesCount, $efficiencyForAnalysis)
                    : 0.0,
                'complexity_factor' => $driver4,
            ]);
        }

        $kwhTariff = (float)(is_array($periodDrivers['period'] ?? null) ? ($periodDrivers['period']['kwh_tariff'] ?? 0) : 0);
        $this->applyCriterion7HmFallback($items, $totals, $kwhTariff);

        foreach ($items as &$item) {
            for ($k = 1; $k <= 8; $k++) {
                $total = $totals['driver_' . $k];
                $driver = (float)($item['driver_' . $k] ?? 0);
                $item['share_criterion_' . $k] = $total > 0
                    ? round(($driver / $total) * 100, 4)
                    : 0.0;
            }
        }
        unset($item);

        return [
            'period' => $periodDrivers['period'] ?? null,
            'warehouse_codes' => $periodDrivers['warehouse_codes'] ?? null,
            'totals' => $totals,
            'items' => $items,
        ];
    }

    /**
     * @param array<string, mixed> $aggregation
     */
    public function findItemShares(array $aggregation, ?int $itemId, ?string $erpCode): ?array
    {
        foreach ($aggregation['items'] ?? [] as $item) {
            if ($itemId !== null && $itemId > 0 && (int)($item['inv_item_id'] ?? 0) === $itemId) {
                return $item;
            }
            if ($erpCode !== null && $erpCode !== '' && (string)($item['erp_code'] ?? '') === $erpCode) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $items
     * @param array<string, float> $totals
     */
    private function applyCriterion7HmFallback(array &$items, array &$totals, float $kwhTariff): void
    {
        $energyService = new InvCostVariableEnergyService();
        $routeKwhPeriod = 0.0;
        $routeHmPeriod = 0.0;

        foreach ($items as $item) {
            if (InvCostProductionLineHelper::normalize($item['production_line'] ?? null) !== InvCostProductionLineHelper::LINE_TIARAJU) {
                continue;
            }

            $driver7 = (float)($item['driver_7'] ?? 0);
            $hmPeriod = (float)($item['hm_period'] ?? 0);
            if ($driver7 <= 0 || $hmPeriod <= 0) {
                continue;
            }

            $routeKwhPeriod += $kwhTariff > 0 ? ($driver7 / $kwhTariff) : $driver7;
            $routeHmPeriod += $hmPeriod;
        }

        $kwhPerHm = $routeHmPeriod > 0.0
            ? ($routeKwhPeriod / $routeHmPeriod)
            : 1.0;

        foreach ($items as &$item) {
            if (InvCostProductionLineHelper::normalize($item['production_line'] ?? null) !== InvCostProductionLineHelper::LINE_TIARAJU) {
                continue;
            }
            if ((float)($item['driver_7'] ?? 0) > 0) {
                continue;
            }

            $hmPeriod = (float)($item['hm_period'] ?? 0);
            if ($hmPeriod <= 0) {
                continue;
            }

            $driver7 = $energyService->computeHmFallbackDriver($hmPeriod, $kwhPerHm, $kwhTariff);
            if ($driver7 <= 0) {
                continue;
            }

            $item['driver_7'] = $driver7;
            $totals['driver_7'] += $driver7;
        }
        unset($item);
    }

    /**
     * @param array<string, mixed>|null $periodItem
     */
    private function hvacDriver(?array $periodItem): float
    {
        if (!is_array($periodItem)) {
            return InvCostEnergyClassHelper::multiplier(InvCostEnergyClassHelper::CLASS_NA);
        }

        return InvCostEnergyClassHelper::hvacDriverWeight($periodItem['energy_class'] ?? null);
    }
}
