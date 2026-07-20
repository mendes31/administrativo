<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class PdiGoalsRepository extends DbConnection
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_pdi_goals
                (pdi_plan_id, goal_title, goal_description, target_value, current_value, unit, deadline, status)
                VALUES
                (:pdi_plan_id, :goal_title, :goal_description, :target_value, :current_value, :unit, :deadline, :status)';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':pdi_plan_id', (int) $data['pdi_plan_id'], PDO::PARAM_INT);
        $stmt->bindValue(':goal_title', $data['goal_title']);
        $stmt->bindValue(':goal_description', $data['goal_description'] ?? null);
        $stmt->bindValue(':target_value', $data['target_value'] ?? null);
        $stmt->bindValue(':current_value', $data['current_value'] ?? 0);
        $stmt->bindValue(':unit', $data['unit'] ?? null);
        $stmt->bindValue(':deadline', $data['deadline'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'pending');
        $stmt->execute();

        return (int) $this->getConnection()->lastInsertId();
    }

    public function getByPlanId(int $planId): array
    {
        $sql = 'SELECT * FROM adms_pdi_goals WHERE pdi_plan_id = :plan_id
                ORDER BY FIELD(status, \'in_progress\', \'pending\', \'achieved\', \'failed\'), deadline IS NULL, deadline ASC, id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':plan_id', $planId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT * FROM adms_pdi_goals WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE adms_pdi_goals SET
                    goal_title = :goal_title,
                    goal_description = :goal_description,
                    target_value = :target_value,
                    current_value = :current_value,
                    unit = :unit,
                    deadline = :deadline,
                    status = :status,
                    achieved_at = :achieved_at,
                    updated_at = NOW()
                WHERE id = :id AND pdi_plan_id = :pdi_plan_id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':pdi_plan_id', (int) $data['pdi_plan_id'], PDO::PARAM_INT);
        $stmt->bindValue(':goal_title', $data['goal_title']);
        $stmt->bindValue(':goal_description', $data['goal_description'] ?? null);
        $stmt->bindValue(':target_value', $data['target_value'] ?? null);
        $stmt->bindValue(':current_value', $data['current_value'] ?? 0);
        $stmt->bindValue(':unit', $data['unit'] ?? null);
        $stmt->bindValue(':deadline', $data['deadline'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'pending');
        $stmt->bindValue(':achieved_at', $data['achieved_at'] ?? null);

        return $stmt->execute();
    }

    public function delete(int $id, int $planId): bool
    {
        $sql = 'DELETE FROM adms_pdi_goals WHERE id = :id AND pdi_plan_id = :plan_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':plan_id', $planId, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
