<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\inventory\InvCostExpensePoolsRepository;

/**
 * Rateio de despesas fixas (CFIX) pelos critérios 1–8.
 */
class InvCostFixedAllocationEngine
{
    /**
     * @param list<string>|null $warehouseCodes
     * @return array{
     *   period_id: int,
     *   total_expense: float,
     *   total_cfix_allocated: float,
     *   by_item: array<int, array{cfix_total: float, details: list<array<string, mixed>>}>,
     *   unallocated_expense: float
     * }
     */
    public function allocateByPeriod(int $periodId, ?array $warehouseCodes = null): array
    {
        $empty = [
            'period_id' => $periodId,
            'total_expense' => 0.0,
            'total_cfix_allocated' => 0.0,
            'by_item' => [],
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

        $criteria = (new InvCostCriterionDriversService())->aggregateAllCriteria($periodId, $warehouseCodes);
        $shareByItem = [];
        foreach ($criteria['items'] ?? [] as $row) {
            $itemId = (int)($row['inv_item_id'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }
            $shareByItem[$itemId] = $row;
        }

        $byItem = [];
        $totalExpense = 0.0;
        $totalAllocated = 0.0;
        $unallocated = 0.0;

        foreach ($pools as $pool) {
            $amount = (float)($pool['amount'] ?? 0);
            $totalExpense += $amount;
            $rules = $pool['rules'] ?? [];

            if ($amount <= 0) {
                continue;
            }

            if ($rules === []) {
                $unallocated += $amount;
                continue;
            }

            $weightSum = array_sum(array_map(static fn(array $r): float => (float)($r['weight_pct'] ?? 0), $rules));
            if ($weightSum <= 0) {
                $unallocated += $amount;
                continue;
            }

            foreach ($rules as $rule) {
                $criterion = (int)($rule['criterion'] ?? 0);
                $weightPct = (float)($rule['weight_pct'] ?? 0);
                if ($criterion < 1 || $criterion > 8 || $weightPct <= 0) {
                    continue;
                }

                $poolSlice = $amount * ($weightPct / $weightSum);
                $shareKey = 'share_criterion_' . $criterion;

                foreach ($shareByItem as $itemId => $itemRow) {
                    $sharePct = (float)($itemRow[$shareKey] ?? 0);
                    if ($sharePct <= 0) {
                        continue;
                    }
                    $allocated = round($poolSlice * ($sharePct / 100), 6);
                    if ($allocated <= 0) {
                        continue;
                    }

                    if (!isset($byItem[$itemId])) {
                        $byItem[$itemId] = ['cfix_total' => 0.0, 'details' => []];
                    }
                    $byItem[$itemId]['cfix_total'] = round($byItem[$itemId]['cfix_total'] + $allocated, 6);
                    $byItem[$itemId]['details'][] = [
                        'pool_id' => (int)$pool['id'],
                        'account_code' => (string)($pool['account_code'] ?? ''),
                        'description' => (string)($pool['description'] ?? ''),
                        'criterion' => $criterion,
                        'weight_pct' => $weightPct,
                        'allocated' => $allocated,
                    ];
                    $totalAllocated += $allocated;
                }
            }
        }

        return [
            'period_id' => $periodId,
            'total_expense' => round($totalExpense, 4),
            'total_cfix_allocated' => round($totalAllocated, 4),
            'by_item' => $byItem,
            'unallocated_expense' => round(max(0, $totalExpense - $totalAllocated), 4),
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
