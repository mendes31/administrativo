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
}
