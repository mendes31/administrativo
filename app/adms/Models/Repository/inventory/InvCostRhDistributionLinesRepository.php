<?php

declare(strict_types=1);

namespace App\adms\Models\Repository\inventory;

use App\adms\Helpers\InvCostRhDistributionHelper;
use App\adms\Models\Services\DbConnection;
use PDO;

class InvCostRhDistributionLinesRepository extends DbConnection
{
    /**
     * @return list<array<string, mixed>>
     */
    public function getByPeriod(int $periodId): array
    {
        if ($periodId <= 0) {
            return [];
        }

        $stmt = $this->getConnection()->prepare(
            'SELECT id, inv_cost_period_id, rh_import_id, area_name, amount, share_pct, criterion, sort_order
             FROM inv_cost_rh_distribution_lines
             WHERE inv_cost_period_id = :period_id
             ORDER BY sort_order ASC, area_name ASC, id ASC'
        );
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return InvCostRhDistributionHelper::withComputedShares($rows);
    }

    public function deleteByPeriod(int $periodId): void
    {
        if ($periodId <= 0) {
            return;
        }

        $stmt = $this->getConnection()->prepare(
            'DELETE FROM inv_cost_rh_distribution_lines WHERE inv_cost_period_id = :period_id'
        );
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * @param list<array{area_name: string, amount: float, criterion: int}> $rows
     */
    public function insertBatch(int $periodId, ?int $importId, array $rows): int
    {
        if ($periodId <= 0 || $rows === []) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $withShares = InvCostRhDistributionHelper::withComputedShares($rows);
        $sql = 'INSERT INTO inv_cost_rh_distribution_lines
                (inv_cost_period_id, rh_import_id, area_name, amount, share_pct, criterion, sort_order, created_at, updated_at)
                VALUES (:period_id, :import_id, :area_name, :amount, :share_pct, :criterion, :sort_order, :created_at, :updated_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $count = 0;
        foreach ($withShares as $i => $row) {
            $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
            if ($importId !== null && $importId > 0) {
                $stmt->bindValue(':import_id', $importId, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':import_id', null, PDO::PARAM_NULL);
            }
            $stmt->bindValue(':area_name', (string)($row['area_name'] ?? ''));
            $stmt->bindValue(':amount', (float)($row['amount'] ?? 0));
            $stmt->bindValue(':share_pct', (float)($row['share_pct'] ?? 0));
            $stmt->bindValue(':criterion', (int)($row['criterion'] ?? 2), PDO::PARAM_INT);
            $stmt->bindValue(':sort_order', $i + 1, PDO::PARAM_INT);
            $stmt->bindValue(':created_at', $now);
            $stmt->bindValue(':updated_at', $now);
            $stmt->execute();
            $count++;
        }

        return $count;
    }

    /**
     * @param list<array{id?: int, area_name: string, amount: float, criterion: int}> $rows
     */
    public function replaceManualLines(int $periodId, array $rows): int
    {
        $this->deleteByPeriod($periodId);

        return $this->insertBatch($periodId, null, $rows);
    }
}
