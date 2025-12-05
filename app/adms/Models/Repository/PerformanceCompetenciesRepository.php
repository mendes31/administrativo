<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Repository para gerenciar competências de avaliações de desempenho
 */
class PerformanceCompetenciesRepository extends DbConnection
{
    /**
     * Criar competência para avaliação
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_performance_competencies 
                (performance_review_id, competency_id, current_level, target_level, 
                 assessed_level, comments)
                VALUES 
                (:performance_review_id, :competency_id, :current_level, :target_level,
                 :assessed_level, :comments)";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':performance_review_id', $data['performance_review_id'], PDO::PARAM_INT);
        $stmt->bindValue(':competency_id', $data['competency_id'], PDO::PARAM_INT);
        $stmt->bindValue(':current_level', $data['current_level'] ?? 1, PDO::PARAM_INT);
        $stmt->bindValue(':target_level', $data['target_level'] ?? 3, PDO::PARAM_INT);
        $stmt->bindValue(':assessed_level', $data['assessed_level'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':comments', $data['comments'] ?? null);
        
        $stmt->execute();
        
        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * Buscar competências por avaliação
     */
    public function getByReviewId(int $reviewId): array
    {
        $sql = "SELECT pc.*, c.name as competency_name, c.competency_type, c.description
                FROM adms_performance_competencies pc
                INNER JOIN adms_competencies c ON pc.competency_id = c.id
                WHERE pc.performance_review_id = :review_id
                ORDER BY c.competency_type, c.name";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':review_id', $reviewId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualizar competência
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        
        $allowedFields = ['current_level', 'target_level', 'assessed_level', 'comments'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                $values[":{$field}"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[':id'] = $id;
        $fields[] = "updated_at = NOW()";
        
        $sql = "UPDATE adms_performance_competencies SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($values as $key => $value) {
            $type = PDO::PARAM_STR;
            if ($key === ':id' || in_array($key, [':current_level', ':target_level', ':assessed_level'])) {
                $type = PDO::PARAM_INT;
            }
            $stmt->bindValue($key, $value, $type);
        }
        
        return $stmt->execute();
    }
}

