<?php

declare(strict_types=1);

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use PDO;

class InvCostPeriodItemsRepository extends DbConnection
{
    /**
     * @return array<int, array<string, mixed>> keyed by inv_item_id
     */
    public function getMapByPeriod(int $periodId): array
    {
        $sql = 'SELECT * FROM inv_cost_period_items WHERE inv_cost_period_id = :period_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['inv_item_id']] = $row;
        }

        return $map;
    }

    public function getOne(int $periodId, int $itemId): array|false
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM inv_cost_period_items WHERE inv_cost_period_id = :period_id AND inv_item_id = :item_id LIMIT 1'
        );
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function upsertCalculatedEfficiency(
        int $periodId,
        int $itemId,
        float $efficiencyRatio,
        ?float $batchSizeAdopted = null
    ): void {
        if ($periodId <= 0 || $itemId <= 0 || $efficiencyRatio <= 0) {
            return;
        }

        $existing = $this->getOne($periodId, $itemId);
        $now = date('Y-m-d H:i:s');
        $efficiencyPct = round($efficiencyRatio * 100, 4);

        if ($existing !== false) {
            $sql = 'UPDATE inv_cost_period_items
                    SET efficiency_pct = :efficiency_pct,
                        batch_size_adopted = COALESCE(batch_size_adopted, :batch_size_adopted),
                        updated_at = :updated_at
                    WHERE inv_cost_period_id = :period_id AND inv_item_id = :item_id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':efficiency_pct', $efficiencyPct);
            $stmt->bindValue(
                ':batch_size_adopted',
                $batchSizeAdopted,
                $batchSizeAdopted !== null && $batchSizeAdopted > 0 ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(':updated_at', $now);
            $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
            $stmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
            $stmt->execute();

            return;
        }

        $sql = 'INSERT INTO inv_cost_period_items
                (inv_cost_period_id, inv_item_id, batch_size_adopted, efficiency_pct, analysis_count, created_at, updated_at)
                VALUES (:period_id, :item_id, :batch_size_adopted, :efficiency_pct, 0, :created_at, :updated_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $stmt->bindValue(
            ':batch_size_adopted',
            $batchSizeAdopted,
            $batchSizeAdopted !== null && $batchSizeAdopted > 0 ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $stmt->bindValue(':efficiency_pct', $efficiencyPct);
        $stmt->bindValue(':created_at', $now);
        $stmt->bindValue(':updated_at', $now);
        $stmt->execute();
    }
}
