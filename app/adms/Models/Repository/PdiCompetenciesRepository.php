<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Competências vinculadas a um plano PDI.
 */
class PdiCompetenciesRepository extends DbConnection
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_pdi_competencies
                (pdi_plan_id, competency_id, competency_name, competency_type,
                 current_level, target_level, description)
                VALUES
                (:pdi_plan_id, :competency_id, :competency_name, :competency_type,
                 :current_level, :target_level, :description)';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':pdi_plan_id', (int) $data['pdi_plan_id'], PDO::PARAM_INT);
        $this->bindNullableInt($stmt, ':competency_id', $data['competency_id'] ?? null);
        $stmt->bindValue(':competency_name', $data['competency_name']);
        $stmt->bindValue(':competency_type', $data['competency_type'] ?? 'behavioral');
        $stmt->bindValue(':current_level', (int) ($data['current_level'] ?? 1), PDO::PARAM_INT);
        $stmt->bindValue(':target_level', (int) ($data['target_level'] ?? 3), PDO::PARAM_INT);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->execute();

        return (int) $this->getConnection()->lastInsertId();
    }

    public function getByPlanId(int $planId): array
    {
        $sql = 'SELECT pc.*, c.name AS catalog_name
                FROM adms_pdi_competencies pc
                LEFT JOIN adms_competencies c ON c.id = pc.competency_id
                WHERE pc.pdi_plan_id = :plan_id
                ORDER BY pc.competency_type, pc.competency_name';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':plan_id', $planId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT * FROM adms_pdi_competencies WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function delete(int $id, int $planId): bool
    {
        $sql = 'DELETE FROM adms_pdi_competencies WHERE id = :id AND pdi_plan_id = :plan_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':plan_id', $planId, PDO::PARAM_INT);

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
}
