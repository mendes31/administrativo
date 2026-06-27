<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\inventory\InvItemOperationsRepository;
use App\adms\Models\Repository\inventory\InvCostPeriodItemsRepository;

/**
 * Drivers e percentuais dos 8 critérios de rateio por período/SKU.
 */
class InvCostCriterionDriversService
{
    /** @var array<string, float> */
    private const COMPLEXITY_WEIGHTS = [
        'baixa' => 2.0,
        'low' => 2.0,
        'media' => 5.0,
        'média' => 5.0,
        'medium' => 5.0,
        'alta' => 8.0,
        'high' => 8.0,
    ];

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
            $periodItem = $itemId > 0 ? ($periodItemsMap[$itemId] ?? null) : null;

            $driver1 = (float)($baseRow['qty_produced'] ?? 0);
            $driver2 = (float)($baseRow['hh_period'] ?? 0);
            $driver3 = (float)($baseRow['hm_period'] ?? 0);
            $driver4 = $this->complexityDriver($periodItem);
            $driver5 = $itemId > 0 ? (float)$this->countMpComponents($itemId) : 0.0;
            $driver6 = $driver4 * max(0, (int)($periodItem['analysis_count'] ?? 0));
            $driver7 = $itemId > 0 ? $this->kwhDriver($itemId, (float)($baseRow['hm_period'] ?? 0), $periodDrivers['period'] ?? null) : 0.0;
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
                'driver_1' => round($driver1, 6),
                'driver_2' => round($driver2, 6),
                'driver_3' => round($driver3, 6),
                'driver_4' => round($driver4, 6),
                'driver_5' => round($driver5, 6),
                'driver_6' => round($driver6, 6),
                'driver_7' => round($driver7, 6),
                'driver_8' => round($driver8, 6),
            ]);
        }

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
     * @param array<string, mixed>|null $periodItem
     */
    private function complexityDriver(?array $periodItem): float
    {
        $level = mb_strtolower(trim((string)($periodItem['complexity_level'] ?? 'media')), 'UTF-8');

        return self::COMPLEXITY_WEIGHTS[$level] ?? 5.0;
    }

    /**
     * @param array<string, mixed>|null $periodItem
     */
    private function hvacDriver(?array $periodItem): float
    {
        $class = mb_strtoupper(trim((string)($periodItem['energy_class'] ?? '')), 'UTF-8');
        if ($class === '') {
            return 0.0;
        }

        return match ($class) {
            'CM', 'CAPSULA', 'CAPSULA MOLE' => 1.0,
            'PROB', 'PROBIOTICO', 'PROBIÓTICO' => 1.0,
            'OTHER', 'OUTRO' => 1.0,
            default => 0.0,
        };
    }

    private function countMpComponents(int $itemId): int
    {
        $breakdown = InventoryCostService::calculateBreakdown($itemId, []);
        $count = 0;
        foreach ($breakdown['materials'] ?? [] as $line) {
            if ((string)($line['group_name'] ?? '') === 'Matéria Prima') {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array<string, mixed>|null $period
     */
    private function kwhDriver(int $itemId, float $hmPeriod, ?array $period): float
    {
        if ($hmPeriod <= 0 || $itemId <= 0) {
            return 0.0;
        }

        $breakdown = InventoryCostService::calculateBreakdown($itemId, []);
        $hmPerBatch = (float)($breakdown['machine_hours'] ?? 0);
        if ($hmPerBatch <= 0) {
            return 0.0;
        }

        $operations = (new InvItemOperationsRepository())->getByItem($itemId);
        $kwhPerBatch = 0.0;

        foreach ($operations as $op) {
            $rawTime = (float)($op['time_per_batch_hours'] ?? 0);
            $timeUnit = strtoupper((string)($op['time_unit'] ?? 'MIN'));
            $timeMinutes = $timeUnit === 'H' ? $rawTime * 60.0 : $rawTime;
            $timeHours = $timeMinutes / 60.0;
            if ($timeHours <= 0) {
                continue;
            }

            $resourceLines = is_array($op['resource_lines'] ?? null) ? $op['resource_lines'] : [];
            $hasMachineDriver = $resourceLines !== []
                || (float)($op['machine_cost_per_min'] ?? 0) > 0
                || (float)($op['energy_cost_per_min'] ?? 0) > 0;
            if (!$hasMachineDriver) {
                continue;
            }

            $opKw = 0.0;
            foreach ($resourceLines as $res) {
                $qty = max(1, (int)($res['qty'] ?? 1));
                $opKw += $qty * max(0.0, (float)($res['power_kw'] ?? 0));
            }
            if ($opKw <= 0) {
                continue;
            }

            $kwhPerBatch += $timeHours * $opKw;
        }

        if ($kwhPerBatch <= 0) {
            return 0.0;
        }

        $kwhPeriod = $kwhPerBatch * ($hmPeriod / $hmPerBatch);
        $tariff = (float)($period['kwh_tariff'] ?? 0);

        return $tariff > 0 ? round($kwhPeriod * $tariff, 6) : round($kwhPeriod, 6);
    }
}
