<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Ações de um plano PDI.
 */
class PdiActionsRepository extends DbConnection
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_pdi_actions
                (pdi_plan_id, title, description, action_type, category, priority,
                 start_date, end_date, expected_hours, status, progress_percentage,
                 training_id, resource_url, notes)
                VALUES
                (:pdi_plan_id, :title, :description, :action_type, :category, :priority,
                 :start_date, :end_date, :expected_hours, :status, :progress_percentage,
                 :training_id, :resource_url, :notes)';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':pdi_plan_id', (int) $data['pdi_plan_id'], PDO::PARAM_INT);
        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':action_type', $data['action_type'] ?? 'other');
        $stmt->bindValue(':category', $data['category'] ?? null);
        $stmt->bindValue(':priority', $data['priority'] ?? 'medium');
        $stmt->bindValue(':start_date', $data['start_date'] ?? null);
        $stmt->bindValue(':end_date', $data['end_date'] ?? null);
        $stmt->bindValue(':expected_hours', $data['expected_hours'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'pending');
        $stmt->bindValue(':progress_percentage', (int) ($data['progress_percentage'] ?? 0), PDO::PARAM_INT);
        $this->bindNullableInt($stmt, ':training_id', $data['training_id'] ?? null);
        $stmt->bindValue(':resource_url', $data['resource_url'] ?? null);
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $stmt->execute();

        return (int) $this->getConnection()->lastInsertId();
    }

    public function getByPlanId(int $planId): array
    {
        $sql = 'SELECT a.*, t.nome AS training_name
                FROM adms_pdi_actions a
                LEFT JOIN adms_trainings t ON t.id = a.training_id
                WHERE a.pdi_plan_id = :plan_id
                ORDER BY
                    FIELD(a.status, \'in_progress\', \'pending\', \'completed\', \'cancelled\'),
                    a.end_date IS NULL, a.end_date ASC, a.id ASC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':plan_id', $planId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT * FROM adms_pdi_actions WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE adms_pdi_actions SET
                    title = :title,
                    description = :description,
                    action_type = :action_type,
                    category = :category,
                    priority = :priority,
                    start_date = :start_date,
                    end_date = :end_date,
                    expected_hours = :expected_hours,
                    actual_hours = :actual_hours,
                    status = :status,
                    progress_percentage = :progress_percentage,
                    training_id = :training_id,
                    resource_url = :resource_url,
                    notes = :notes,
                    completed_at = :completed_at,
                    updated_at = NOW()
                WHERE id = :id AND pdi_plan_id = :pdi_plan_id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':pdi_plan_id', (int) $data['pdi_plan_id'], PDO::PARAM_INT);
        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':action_type', $data['action_type'] ?? 'other');
        $stmt->bindValue(':category', $data['category'] ?? null);
        $stmt->bindValue(':priority', $data['priority'] ?? 'medium');
        $stmt->bindValue(':start_date', $data['start_date'] ?? null);
        $stmt->bindValue(':end_date', $data['end_date'] ?? null);
        $stmt->bindValue(':expected_hours', $data['expected_hours'] ?? null);
        $stmt->bindValue(':actual_hours', $data['actual_hours'] ?? 0);
        $stmt->bindValue(':status', $data['status'] ?? 'pending');
        $stmt->bindValue(':progress_percentage', (int) ($data['progress_percentage'] ?? 0), PDO::PARAM_INT);
        $this->bindNullableInt($stmt, ':training_id', $data['training_id'] ?? null);
        $stmt->bindValue(':resource_url', $data['resource_url'] ?? null);
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $stmt->bindValue(':completed_at', $data['completed_at'] ?? null);

        return $stmt->execute();
    }

    private function bindNullableInt(\PDOStatement $stmt, string $param, mixed $value): void
    {
        if ($value === null || $value === '' || (int) $value <= 0) {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue($param, (int) $value, PDO::PARAM_INT);
        }
    }

    public function avgProgressForActivePlans(): ?float
    {
        $sql = "SELECT AVG(a.progress_percentage)
                FROM adms_pdi_actions a
                INNER JOIN adms_pdi_plans p ON p.id = a.pdi_plan_id
                WHERE p.status = 'active'";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $val = $stmt->fetchColumn();
        if ($val === false || $val === null) {
            return null;
        }

        return round((float) $val, 1);
    }
}
