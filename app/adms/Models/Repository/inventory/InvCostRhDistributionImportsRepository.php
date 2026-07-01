<?php

declare(strict_types=1);

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use PDO;

class InvCostRhDistributionImportsRepository extends DbConnection
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO inv_cost_rh_distribution_imports
                (inv_cost_period_id, filename, rows_imported, replace_previous, imported_by, imported_at, notes)
                VALUES (:period_id, :filename, :rows_imported, :replace_previous, :imported_by, :imported_at, :notes)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':period_id', (int)$data['inv_cost_period_id'], PDO::PARAM_INT);
        $stmt->bindValue(':filename', $data['filename'] ?? null);
        $stmt->bindValue(':rows_imported', (int)($data['rows_imported'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':replace_previous', !empty($data['replace_previous']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':imported_by', !empty($data['imported_by']) ? (int)$data['imported_by'] : null, PDO::PARAM_INT);
        $stmt->bindValue(':imported_at', $now);
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $stmt->execute();

        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getByPeriod(int $periodId): array
    {
        if ($periodId <= 0) {
            return [];
        }

        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM inv_cost_rh_distribution_imports
             WHERE inv_cost_period_id = :period_id
             ORDER BY imported_at DESC, id DESC'
        );
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
