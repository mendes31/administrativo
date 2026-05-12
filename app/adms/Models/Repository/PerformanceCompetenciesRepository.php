<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
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
        
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->getById($newId);
            if (is_array($row)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_performance_competencies',
                    $newId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $row
                );
            }
        }

        return $newId;
    }

    /**
     * Buscar registro por ID (tabela adms_performance_competencies).
     *
     * @return array<string, mixed>|null
     */
    public function getById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $sql = 'SELECT * FROM adms_performance_competencies WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Buscar competências por avaliação
     */
    public function getByReviewId(int $reviewId): array
    {
        $sql = "SELECT pc.*, c.name as competency_name, c.competency_type, c.description,
                       c.level_1_description, c.level_2_description, c.level_3_description,
                       c.level_4_description, c.level_5_description
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

        $oldRow = $this->getById($id);
        $ok = $stmt->execute();
        if (!$ok || !is_array($oldRow)) {
            return $ok;
        }
        $newRow = $this->getById($id);
        if (!is_array($newRow)) {
            return $ok;
        }
        $changed = false;
        foreach (['current_level', 'target_level', 'assessed_level', 'comments'] as $f) {
            if (($oldRow[$f] ?? null) != ($newRow[$f] ?? null)) {
                $changed = true;
                break;
            }
        }
        if ($changed) {
            $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'adms_performance_competencies',
                $id,
                $usuarioId,
                'UPDATE',
                $oldRow,
                $newRow
            );
        }

        return $ok;
    }
}

