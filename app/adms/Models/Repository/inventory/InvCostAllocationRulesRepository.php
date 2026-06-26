<?php

declare(strict_types=1);

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use PDO;

class InvCostAllocationRulesRepository extends DbConnection
{
    /**
     * @param list<array{criterion: int, weight_pct: float}> $rules
     */
    public function replaceRulesForPool(int $poolId, array $rules): void
    {
        $del = $this->getConnection()->prepare('DELETE FROM inv_cost_allocation_rules WHERE expense_pool_id = :pool_id');
        $del->bindValue(':pool_id', $poolId, PDO::PARAM_INT);
        $del->execute();

        if ($rules === []) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO inv_cost_allocation_rules (expense_pool_id, criterion, weight_pct, created_at, updated_at)
                VALUES (:pool_id, :criterion, :weight_pct, :created_at, :updated_at)';
        $stmt = $this->getConnection()->prepare($sql);

        foreach ($rules as $rule) {
            $criterion = (int)($rule['criterion'] ?? 0);
            if ($criterion < 1 || $criterion > 8) {
                continue;
            }
            $weight = (float)($rule['weight_pct'] ?? 100);
            if ($weight <= 0) {
                continue;
            }
            $stmt->bindValue(':pool_id', $poolId, PDO::PARAM_INT);
            $stmt->bindValue(':criterion', $criterion, PDO::PARAM_INT);
            $stmt->bindValue(':weight_pct', round($weight, 4));
            $stmt->bindValue(':created_at', $now);
            $stmt->bindValue(':updated_at', $now);
            $stmt->execute();
        }
    }

    /**
     * @param array<int, list<array{criterion: int, weight_pct: float}>> $rulesByPoolId
     */
    public function replaceRulesForPeriodPools(array $rulesByPoolId): void
    {
        foreach ($rulesByPoolId as $poolId => $rules) {
            $this->replaceRulesForPool((int)$poolId, $rules);
        }
    }
}
