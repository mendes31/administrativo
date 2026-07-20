<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class SuccessionSuccessorsRepository extends DbConnection
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_succession_successors
                (critical_position_id, user_id, readiness, priority_order, notes, nominated_by)
                VALUES
                (:critical_position_id, :user_id, :readiness, :priority_order, :notes, :nominated_by)';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':critical_position_id', (int) $data['critical_position_id'], PDO::PARAM_INT);
        $stmt->bindValue(':user_id', (int) $data['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':readiness', $data['readiness'] ?? 'ready_1_2y');
        $stmt->bindValue(':priority_order', (int) ($data['priority_order'] ?? 1), PDO::PARAM_INT);
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $stmt->bindValue(':nominated_by', (int) $data['nominated_by'], PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->getConnection()->lastInsertId();
    }

    public function getByCriticalPositionId(int $criticalPositionId): array
    {
        $sql = 'SELECT s.*, u.name AS user_name, u.email AS user_email, n.name AS nominated_by_name
                FROM adms_succession_successors s
                INNER JOIN adms_users u ON u.id = s.user_id
                LEFT JOIN adms_users n ON n.id = s.nominated_by
                WHERE s.critical_position_id = :cp_id
                ORDER BY s.priority_order ASC, u.name ASC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':cp_id', $criticalPositionId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT * FROM adms_succession_successors WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE adms_succession_successors SET
                    readiness = :readiness,
                    priority_order = :priority_order,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id AND critical_position_id = :critical_position_id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':critical_position_id', (int) $data['critical_position_id'], PDO::PARAM_INT);
        $stmt->bindValue(':readiness', $data['readiness'] ?? 'ready_1_2y');
        $stmt->bindValue(':priority_order', (int) ($data['priority_order'] ?? 1), PDO::PARAM_INT);
        $stmt->bindValue(':notes', $data['notes'] ?? null);

        return $stmt->execute();
    }

    public function delete(int $id, int $criticalPositionId): bool
    {
        $sql = 'DELETE FROM adms_succession_successors
                WHERE id = :id AND critical_position_id = :cp_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':cp_id', $criticalPositionId, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
