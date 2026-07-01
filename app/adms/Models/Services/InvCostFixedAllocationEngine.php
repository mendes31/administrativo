<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\InvCostRhDistributionHelper;
use App\adms\Helpers\InvCostAllocationSplitHelper;
use App\adms\Models\Repository\inventory\InvCostExpensePoolsRepository;

/**
 * Rateio de despesas fixas (CFIX) pelos critérios 1–8.
 */
class InvCostFixedAllocationEngine
{
    /**
     * @param list<string>|null $warehouseCodes
     * @param array{rh_simulation?: bool} $options rh_simulation=true aplica rh_simulation_increase_pct do período
     * @return array{
     *   period_id: int,
     *   total_expense: float,
     *   total_cfix_allocated: float,
     *   by_item: array<int, array{cfix_total: float, details: list<array<string, mixed>>}>,
     *   unallocated_expense: float,
     *   pools_without_criterion: float,
     *   unallocated_without_recipient: float,
     *   rh_personnel_pool_total?: float,
     *   rh_simulation_active?: bool
     * }
     */
    public function allocateByPeriod(int $periodId, ?array $warehouseCodes = null, array $options = []): array
    {
        $empty = [
            'period_id' => $periodId,
            'total_expense' => 0.0,
            'total_cfix_allocated' => 0.0,
            'by_item' => [],
            'pools_without_criterion' => 0.0,
            'unallocated_without_recipient' => 0.0,
            'unallocated_expense' => 0.0,
        ];

        if ($periodId <= 0) {
            return $empty;
        }

        $poolsRepo = new InvCostExpensePoolsRepository();
        $pools = $poolsRepo->getByPeriodWithRules($periodId);
        if ($pools === []) {
            return $empty;
        }

        $rhService = new InvCostRhDistributionService();
        $useRhDistribution = $rhService->hasDistribution($periodId);
        $rhSimulation = !empty($options['rh_simulation']);
        $rhPreview = $useRhDistribution
            ? $rhService->buildPreview($periodId, $rhSimulation ? null : 0.0)
            : null;

        $criteria = (new InvCostCriterionDriversService())->aggregateAllCriteria($periodId, $warehouseCodes);
        $shareByItem = [];
        foreach ($criteria['items'] ?? [] as $row) {
            $itemId = (int)($row['inv_item_id'] ?? 0);
            if ($itemId > 0) {
                $shareByItem[$itemId] = $row;
            }
        }

        $byItem = [];
        $totalExpense = 0.0;
        $totalAllocated = 0.0;
        $poolsWithoutCriterion = 0.0;

        foreach ($pools as $pool) {
            $amount = (float)($pool['amount'] ?? 0);
            $totalExpense += $amount;
            if ($amount <= 0) {
                continue;
            }

            if ($useRhDistribution && InvCostRhDistributionHelper::isPersonnelPoolRow($pool)) {
                continue;
            }

            $poolAllocated = $this->allocatePoolAmount(
                $pool,
                $amount,
                $shareByItem,
                $byItem,
                $totalAllocated,
                $poolsWithoutCriterion
            );
            $totalAllocated = $poolAllocated['total_allocated'];
            $poolsWithoutCriterion = $poolAllocated['pools_without_criterion'];
        }

        if ($useRhDistribution && is_array($rhPreview)) {
            foreach ($rhPreview['slices'] ?? [] as $slice) {
                $sliceAmount = (float)($slice['slice_amount'] ?? 0);
                $criterion = (int)($slice['criterion'] ?? 0);
                if ($sliceAmount <= 0 || $criterion < 1 || $criterion > 8) {
                    continue;
                }

                $shareKey = 'share_criterion_' . $criterion;
                $weights = [];
                foreach ($shareByItem as $itemId => $itemRow) {
                    $sharePct = (float)($itemRow[$shareKey] ?? 0);
                    if ($sharePct > 0) {
                        $weights[$itemId] = $sharePct;
                    }
                }

                foreach (InvCostAllocationSplitHelper::split($sliceAmount, $weights) as $itemId => $allocated) {
                    if (!isset($byItem[$itemId])) {
                        $byItem[$itemId] = ['cfix_total' => 0.0, 'details' => []];
                    }
                    $byItem[$itemId]['cfix_total'] = round($byItem[$itemId]['cfix_total'] + $allocated, 4);
                    $byItem[$itemId]['details'][] = [
                        'pool_id' => 0,
                        'account_code' => 'RH',
                        'description' => 'Folha — ' . (string)($slice['area_name'] ?? ''),
                        'criterion' => $criterion,
                        'weight_pct' => (float)($slice['share_pct'] ?? 0),
                        'allocated' => $allocated,
                        'rh_area' => (string)($slice['area_name'] ?? ''),
                        'rh_simulation' => $rhSimulation,
                    ];
                    $totalAllocated += $allocated;
                }
            }
        }

        $unallocatedTotal = max(0, $totalExpense - $totalAllocated);
        $unallocatedWithoutRecipient = max(0, $unallocatedTotal - $poolsWithoutCriterion);

        $result = [
            'period_id' => $periodId,
            'total_expense' => round($totalExpense, 4),
            'total_cfix_allocated' => round($totalAllocated, 4),
            'by_item' => $byItem,
            'pools_without_criterion' => round($poolsWithoutCriterion, 4),
            'unallocated_without_recipient' => round($unallocatedWithoutRecipient, 4),
            'unallocated_expense' => round($unallocatedTotal, 4),
        ];

        if ($useRhDistribution && is_array($rhPreview)) {
            $result['rh_personnel_pool_total'] = (float)($rhPreview['effective_pool_total'] ?? 0);
            $result['rh_simulation_active'] = $rhSimulation;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $pool
     * @param array<int, array<string, mixed>> $shareByItem
     * @param array<int, array{cfix_total: float, details: list<array<string, mixed>>}> $byItem
     * @return array{total_allocated: float, pools_without_criterion: float}
     */
    private function allocatePoolAmount(
        array $pool,
        float $amount,
        array $shareByItem,
        array &$byItem,
        float $totalAllocated,
        float $poolsWithoutCriterion
    ): array {
        $rules = $pool['rules'] ?? [];
        if ($rules === []) {
            $poolsWithoutCriterion += $amount;

            return [
                'total_allocated' => $totalAllocated,
                'pools_without_criterion' => $poolsWithoutCriterion,
            ];
        }

        $weightSum = array_sum(array_map(static fn(array $r): float => (float)($r['weight_pct'] ?? 0), $rules));
        if ($weightSum <= 0) {
            $poolsWithoutCriterion += $amount;

            return [
                'total_allocated' => $totalAllocated,
                'pools_without_criterion' => $poolsWithoutCriterion,
            ];
        }

        foreach ($rules as $rule) {
            $criterion = (int)($rule['criterion'] ?? 0);
            $weightPct = (float)($rule['weight_pct'] ?? 0);
            if ($criterion < 1 || $criterion > 8 || $weightPct <= 0) {
                continue;
            }

            $poolSlice = round($amount * ($weightPct / $weightSum), 4);
            $shareKey = 'share_criterion_' . $criterion;
            $weights = [];
            foreach ($shareByItem as $itemId => $itemRow) {
                $sharePct = (float)($itemRow[$shareKey] ?? 0);
                if ($sharePct > 0) {
                    $weights[$itemId] = $sharePct;
                }
            }

            foreach (InvCostAllocationSplitHelper::split($poolSlice, $weights) as $itemId => $allocated) {
                if (!isset($byItem[$itemId])) {
                    $byItem[$itemId] = ['cfix_total' => 0.0, 'details' => []];
                }
                $byItem[$itemId]['cfix_total'] = round($byItem[$itemId]['cfix_total'] + $allocated, 4);
                $byItem[$itemId]['details'][] = [
                    'pool_id' => (int)($pool['id'] ?? 0),
                    'account_code' => (string)($pool['account_code'] ?? ''),
                    'description' => (string)($pool['description'] ?? ''),
                    'criterion' => $criterion,
                    'weight_pct' => $weightPct,
                    'allocated' => $allocated,
                ];
                $totalAllocated += $allocated;
            }
        }

        return [
            'total_allocated' => $totalAllocated,
            'pools_without_criterion' => $poolsWithoutCriterion,
        ];
    }

    /**
     * @param list<string>|null $warehouseCodes
     * @return array{cfix_total: float, details: list<array<string, mixed>>}
     */
    public function allocateForItem(int $periodId, int $itemId, ?array $warehouseCodes = null): array
    {
        $all = $this->allocateByPeriod($periodId, $warehouseCodes);

        return $all['by_item'][$itemId] ?? ['cfix_total' => 0.0, 'details' => []];
    }
}
