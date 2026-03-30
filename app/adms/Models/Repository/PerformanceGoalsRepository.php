<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Repository para gerenciar metas de desempenho
 */
class PerformanceGoalsRepository extends DbConnection
{
    /**
     * Criar nova meta
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_performance_goals 
                (performance_review_id, employee_id, goal_title, goal_description, goal_type,
                 target_value, current_value, unit, deadline, weight, status, progress_percentage)
                VALUES 
                (:performance_review_id, :employee_id, :goal_title, :goal_description, :goal_type,
                 :target_value, :current_value, :unit, :deadline, :weight, :status, :progress_percentage)";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':performance_review_id', $data['performance_review_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':employee_id', $data['employee_id'], PDO::PARAM_INT);
        $stmt->bindValue(':goal_title', $data['goal_title']);
        $stmt->bindValue(':goal_description', $data['goal_description'] ?? null);
        $stmt->bindValue(':goal_type', $data['goal_type'] ?? 'individual');
        $stmt->bindValue(':target_value', $data['target_value'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':current_value', $data['current_value'] ?? 0, PDO::PARAM_STR);
        $stmt->bindValue(':unit', $data['unit'] ?? null);
        $stmt->bindValue(':deadline', $data['deadline'] ?? null);
        $stmt->bindValue(':weight', $data['weight'] ?? 1.0, PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? 'pending');
        $stmt->bindValue(':progress_percentage', $data['progress_percentage'] ?? 0, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * Buscar por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT pg.*, 
                       e.name as employee_name, e.email as employee_email
                FROM adms_performance_goals pg
                INNER JOIN adms_users e ON pg.employee_id = e.id
                WHERE pg.id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Listar metas com filtros
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['employee_id'])) {
            $where[] = 'pg.employee_id = :employee_id';
            $params[':employee_id'] = $filters['employee_id'];
        }
        
        if (!empty($filters['performance_review_id'])) {
            $where[] = 'pg.performance_review_id = :performance_review_id';
            $params[':performance_review_id'] = $filters['performance_review_id'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = 'pg.status = :status';
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['goal_type'])) {
            $where[] = 'pg.goal_type = :goal_type';
            $params[':goal_type'] = $filters['goal_type'];
        }
        
        // Permissões
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$isSuperAdmin) {
            $where[] = 'pg.employee_id = :user_id';
            $params[':user_id'] = $userId;
        }
        
        $sql = "SELECT pg.*, e.name as employee_name
                FROM adms_performance_goals pg
                INNER JOIN adms_users e ON pg.employee_id = e.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY pg.deadline ASC, pg.created_at DESC
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
     * Atualizar meta
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        
        $allowedFields = ['goal_title', 'goal_description', 'goal_type', 'target_value', 
                         'current_value', 'unit', 'deadline', 'weight', 'status', 
                         'progress_percentage', 'achieved_at'];
        
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
        
        $sql = "UPDATE adms_performance_goals SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($values as $key => $value) {
            $type = PDO::PARAM_STR;
            if ($key === ':id' || $key === ':progress_percentage') {
                $type = PDO::PARAM_INT;
            }
            $stmt->bindValue($key, $value, $type);
        }
        
        return $stmt->execute();
    }

    /**
     * Deletar meta
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM adms_performance_goals WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Contar total de metas
     */
    public function count(array $filters = []): int
    {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['employee_id'])) {
            $where[] = 'pg.employee_id = :employee_id';
            $params[':employee_id'] = $filters['employee_id'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = 'pg.status = :status';
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['goal_type'])) {
            $where[] = 'pg.goal_type = :goal_type';
            $params[':goal_type'] = $filters['goal_type'];
        }
        
        // Permissões
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$isSuperAdmin) {
            $where[] = 'pg.employee_id = :user_id';
            $params[':user_id'] = $userId;
        }
        
        $sql = "SELECT COUNT(*) as total
                FROM adms_performance_goals pg
                WHERE " . implode(' AND ', $where);
        
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return (int)$stmt->fetchColumn();
    }
}

