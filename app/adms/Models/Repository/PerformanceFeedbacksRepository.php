<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Repository para gerenciar feedbacks de desempenho
 */
class PerformanceFeedbacksRepository extends DbConnection
{
    /**
     * Criar novo feedback
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_performance_feedbacks 
                (employee_id, given_by, feedback_type, feedback_text, is_anonymous, 
                 is_public, related_review_id, related_goal_id)
                VALUES 
                (:employee_id, :given_by, :feedback_type, :feedback_text, :is_anonymous,
                 :is_public, :related_review_id, :related_goal_id)";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':employee_id', $data['employee_id'], PDO::PARAM_INT);
        $stmt->bindValue(':given_by', $data['given_by'], PDO::PARAM_INT);
        $stmt->bindValue(':feedback_type', $data['feedback_type'] ?? 'general');
        $stmt->bindValue(':feedback_text', $data['feedback_text']);
        $stmt->bindValue(':is_anonymous', isset($data['is_anonymous']) && $data['is_anonymous'] ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':is_public', isset($data['is_public']) && $data['is_public'] ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':related_review_id', $data['related_review_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':related_goal_id', $data['related_goal_id'] ?? null, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * Buscar por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT pf.*, 
                       e.name as employee_name, e.email as employee_email,
                       g.name as given_by_name, g.email as given_by_email
                FROM adms_performance_feedbacks pf
                INNER JOIN adms_users e ON pf.employee_id = e.id
                INNER JOIN adms_users g ON pf.given_by = g.id
                WHERE pf.id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Listar feedbacks com filtros
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['employee_id'])) {
            $where[] = 'pf.employee_id = :employee_id';
            $params[':employee_id'] = $filters['employee_id'];
        }
        
        if (!empty($filters['given_by'])) {
            $where[] = 'pf.given_by = :given_by';
            $params[':given_by'] = $filters['given_by'];
        }
        
        if (!empty($filters['feedback_type'])) {
            $where[] = 'pf.feedback_type = :feedback_type';
            $params[':feedback_type'] = $filters['feedback_type'];
        }
        
        if (!empty($filters['related_review_id'])) {
            $where[] = 'pf.related_review_id = :related_review_id';
            $params[':related_review_id'] = $filters['related_review_id'];
        }
        
        if (!empty($filters['related_goal_id'])) {
            $where[] = 'pf.related_goal_id = :related_goal_id';
            $params[':related_goal_id'] = $filters['related_goal_id'];
        }
        
        // Permissões
        $isSuperAdmin = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$isSuperAdmin) {
            // Usuário vê apenas feedbacks que ele deu ou recebeu
            $where[] = '(pf.employee_id = :user_id OR pf.given_by = :user_id)';
            $params[':user_id'] = $userId;
        }
        
        $sql = "SELECT pf.*, 
                       e.name as employee_name,
                       g.name as given_by_name
                FROM adms_performance_feedbacks pf
                INNER JOIN adms_users e ON pf.employee_id = e.id
                INNER JOIN adms_users g ON pf.given_by = g.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY pf.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualizar feedback
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        
        $allowedFields = ['feedback_type', 'feedback_text', 'is_anonymous', 'is_public', 
                         'related_review_id', 'related_goal_id'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                if ($field === 'is_anonymous' || $field === 'is_public') {
                    $values[":{$field}"] = $data[$field] ? 1 : 0;
                } else {
                    $values[":{$field}"] = $data[$field];
                }
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[':id'] = $id;
        $fields[] = "updated_at = NOW()";
        
        $sql = "UPDATE adms_performance_feedbacks SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($values as $key => $value) {
            $type = PDO::PARAM_STR;
            if ($key === ':id' || $key === ':related_review_id' || $key === ':related_goal_id') {
                $type = PDO::PARAM_INT;
            } elseif ($key === ':is_anonymous' || $key === ':is_public') {
                $type = PDO::PARAM_INT;
            }
            $stmt->bindValue($key, $value, $type);
        }
        
        return $stmt->execute();
    }

    /**
     * Deletar feedback
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM adms_performance_feedbacks WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Contar total de feedbacks
     */
    public function count(array $filters = []): int
    {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['employee_id'])) {
            $where[] = 'pf.employee_id = :employee_id';
            $params[':employee_id'] = $filters['employee_id'];
        }
        
        if (!empty($filters['given_by'])) {
            $where[] = 'pf.given_by = :given_by';
            $params[':given_by'] = $filters['given_by'];
        }
        
        if (!empty($filters['feedback_type'])) {
            $where[] = 'pf.feedback_type = :feedback_type';
            $params[':feedback_type'] = $filters['feedback_type'];
        }
        
        // Permissões
        $isSuperAdmin = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$isSuperAdmin) {
            $where[] = '(pf.employee_id = :user_id OR pf.given_by = :user_id)';
            $params[':user_id'] = $userId;
        }
        
        $sql = "SELECT COUNT(*) as total
                FROM adms_performance_feedbacks pf
                WHERE " . implode(' AND ', $where);
        
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return (int)$stmt->fetchColumn();
    }
}

