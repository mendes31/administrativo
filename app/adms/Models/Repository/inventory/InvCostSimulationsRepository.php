<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

class InvCostSimulationsRepository extends DbConnection
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO inv_cost_simulations (
                    inv_item_id, title, material_adjust_pct, operations_adjust_pct, global_adjust_pct,
                    standard_batch_size, base_total_unit, base_total_batch,
                    simulated_total_unit, simulated_total_batch, breakdown_json, created_by, created_at
                ) VALUES (
                    :inv_item_id, :title, :material_adjust_pct, :operations_adjust_pct, :global_adjust_pct,
                    :standard_batch_size, :base_total_unit, :base_total_batch,
                    :simulated_total_unit, :simulated_total_batch, :breakdown_json, :created_by, :created_at
                )';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':inv_item_id', (int)$data['inv_item_id'], PDO::PARAM_INT);
        $stmt->bindValue(':title', (string)$data['title']);
        $stmt->bindValue(':material_adjust_pct', (float)($data['material_adjust_pct'] ?? 0));
        $stmt->bindValue(':operations_adjust_pct', (float)($data['operations_adjust_pct'] ?? 0));
        $stmt->bindValue(':global_adjust_pct', (float)($data['global_adjust_pct'] ?? 0));
        $stmt->bindValue(':standard_batch_size', (float)($data['standard_batch_size'] ?? 1));
        $stmt->bindValue(':base_total_unit', (float)($data['base_total_unit'] ?? 0));
        $stmt->bindValue(':base_total_batch', (float)($data['base_total_batch'] ?? 0));
        $stmt->bindValue(':simulated_total_unit', (float)($data['simulated_total_unit'] ?? 0));
        $stmt->bindValue(':simulated_total_batch', (float)($data['simulated_total_batch'] ?? 0));
        $stmt->bindValue(':breakdown_json', (string)$data['breakdown_json']);
        $createdBy = $data['created_by'] ?? null;
        $stmt->bindValue(':created_by', $createdBy !== null ? (int)$createdBy : null, $createdBy !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':created_at', date('Y-m-d H:i:s'));
        $stmt->execute();

        return (int)$this->getConnection()->lastInsertId();
    }

    public function getOne(int $id): array|false
    {
        $sql = 'SELECT s.*, i.code AS item_code, i.description AS item_description, i.erp_code AS item_erp_code,
                       u.name AS unit_name, c.name AS category_name,
                       usr.name AS created_by_name
                FROM inv_cost_simulations s
                INNER JOIN inv_items i ON i.id = s.inv_item_id
                LEFT JOIN inv_units u ON u.id = i.inv_unit_id
                LEFT JOIN inv_categories c ON c.id = i.inv_category_id
                LEFT JOIN adms_users usr ON usr.id = s.created_by
                WHERE s.id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getByItem(int $itemId, int $limit = 20): array
    {
        $sql = 'SELECT id, title, material_adjust_pct, operations_adjust_pct, global_adjust_pct,
                       standard_batch_size, base_total_unit, base_total_batch,
                       simulated_total_unit, simulated_total_batch, created_at
                FROM inv_cost_simulations
                WHERE inv_item_id = :item_id
                ORDER BY created_at DESC, id DESC
                LIMIT :limit';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function decodeBreakdown(array $row): ?array
    {
        $json = (string)($row['breakdown_json'] ?? '');
        if ($json === '') {
            return null;
        }
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }
}
