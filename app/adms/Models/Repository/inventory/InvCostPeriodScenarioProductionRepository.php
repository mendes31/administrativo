<?php

declare(strict_types=1);

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use PDO;

class InvCostPeriodScenarioProductionRepository extends DbConnection
{
    /**
     * @return list<array<string, mixed>>
     */
    public function getByPeriod(int $periodId): array
    {
        if ($periodId <= 0) {
            return [];
        }

        $sql = 'SELECT s.*, i.description AS item_name, c.name AS category_name
                FROM inv_cost_period_scenario_production s
                LEFT JOIN inv_items i ON i.id = s.inv_item_id
                LEFT JOIN inv_categories c ON c.id = i.inv_category_id
                WHERE s.inv_cost_period_id = :period_id
                ORDER BY s.erp_code ASC, s.id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function insert(int $periodId, array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO inv_cost_period_scenario_production (
                    inv_cost_period_id, inv_item_id, erp_code, item_description,
                    batches_count, qty_produced, notes, created_by, created_at, updated_at
                ) VALUES (
                    :period_id, :item_id, :erp_code, :description,
                    :batches_count, :qty_produced, :notes, :created_by, :created_at, :updated_at
                )';
        $stmt = $this->getConnection()->prepare($sql);
        $itemId = isset($data['inv_item_id']) ? (int)$data['inv_item_id'] : null;
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        if ($itemId !== null && $itemId > 0) {
            $stmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':item_id', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':erp_code', trim((string)($data['erp_code'] ?? '')));
        $stmt->bindValue(':description', trim((string)($data['item_description'] ?? '')) ?: null);
        $stmt->bindValue(':batches_count', max(0, (int)($data['batches_count'] ?? 0)), PDO::PARAM_INT);
        $stmt->bindValue(':qty_produced', max(0.0, (float)($data['qty_produced'] ?? 0)));
        $stmt->bindValue(':notes', trim((string)($data['notes'] ?? '')) ?: null);
        $createdBy = isset($data['created_by']) ? (int)$data['created_by'] : null;
        if ($createdBy !== null && $createdBy > 0) {
            $stmt->bindValue(':created_by', $createdBy, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':created_by', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':created_at', $now);
        $stmt->bindValue(':updated_at', $now);
        $stmt->execute();

        return (int)$this->getConnection()->lastInsertId();
    }

    public function delete(int $id, int $periodId): bool
    {
        if ($id <= 0 || $periodId <= 0) {
            return false;
        }

        $stmt = $this->getConnection()->prepare(
            'DELETE FROM inv_cost_period_scenario_production WHERE id = :id AND inv_cost_period_id = :period_id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
