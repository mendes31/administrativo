<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

/**
 * Repository para gerenciar avaliações de desempenho
 */
class PerformanceReviewsRepository extends DbConnection
{
    /**
     * Criar nova avaliação de desempenho
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_performance_reviews 
                (employee_id, reviewer_id, review_type, review_period_start, review_period_end, 
                 review_date, status, overall_score, strengths, improvements, comments, 
                 employee_comments, evaluation_id, created_by)
                VALUES 
                (:employee_id, :reviewer_id, :review_type, :review_period_start, :review_period_end,
                 :review_date, :status, :overall_score, :strengths, :improvements, :comments,
                 :employee_comments, :evaluation_id, :created_by)";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':employee_id', $data['employee_id'], PDO::PARAM_INT);
        $stmt->bindValue(':reviewer_id', $data['reviewer_id'], PDO::PARAM_INT);
        $stmt->bindValue(':review_type', $data['review_type']);
        $stmt->bindValue(':review_period_start', $data['review_period_start']);
        $stmt->bindValue(':review_period_end', $data['review_period_end']);
        $stmt->bindValue(':review_date', $data['review_date']);
        $stmt->bindValue(':status', $data['status'] ?? 'draft');
        $stmt->bindValue(':overall_score', $data['overall_score'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':strengths', $data['strengths'] ?? null);
        $stmt->bindValue(':improvements', $data['improvements'] ?? null);
        $stmt->bindValue(':comments', $data['comments'] ?? null);
        $stmt->bindValue(':employee_comments', $data['employee_comments'] ?? null);
        $stmt->bindValue(':evaluation_id', $data['evaluation_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':created_by', $data['created_by'], PDO::PARAM_INT);
        
        $stmt->execute();

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->getById($newId);
            if (is_array($row)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_performance_reviews',
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
     * Buscar por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT pr.*, 
                       e.name as employee_name, e.email as employee_email,
                       r.name as reviewer_name, r.email as reviewer_email,
                       c.name as creator_name
                FROM adms_performance_reviews pr
                INNER JOIN adms_users e ON pr.employee_id = e.id
                INNER JOIN adms_users r ON pr.reviewer_id = r.id
                INNER JOIN adms_users c ON pr.created_by = c.id
                WHERE pr.id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Listar avaliações com filtros
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['employee_id'])) {
            $where[] = 'pr.employee_id = :employee_id';
            $params[':employee_id'] = $filters['employee_id'];
        }
        
        if (!empty($filters['reviewer_id'])) {
            $where[] = 'pr.reviewer_id = :reviewer_id';
            $params[':reviewer_id'] = $filters['reviewer_id'];
        }
        
        if (!empty($filters['review_type'])) {
            $where[] = 'pr.review_type = :review_type';
            $params[':review_type'] = $filters['review_type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = 'pr.status = :status';
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(e.name LIKE :search OR pr.comments LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        // Super admin vê tudo, gestor vê sua equipe, colaborador vê apenas as suas
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$isSuperAdmin) {
            // Gestor vê avaliações da equipe + suas próprias
            // Colaborador vê apenas suas próprias
            $where[] = '(pr.employee_id = :user_id OR pr.reviewer_id = :user_id OR pr.created_by = :user_id)';
            $params[':user_id'] = $userId;
        }
        
        $sql = "SELECT pr.*, 
                       e.name as employee_name, e.email as employee_email,
                       r.name as reviewer_name
                FROM adms_performance_reviews pr
                INNER JOIN adms_users e ON pr.employee_id = e.id
                INNER JOIN adms_users r ON pr.reviewer_id = r.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY pr.review_date DESC, pr.created_at DESC
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
     * Contar total de avaliações
     */
    public function count(array $filters = []): int
    {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['employee_id'])) {
            $where[] = 'pr.employee_id = :employee_id';
            $params[':employee_id'] = $filters['employee_id'];
        }
        
        if (!empty($filters['reviewer_id'])) {
            $where[] = 'pr.reviewer_id = :reviewer_id';
            $params[':reviewer_id'] = $filters['reviewer_id'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = 'pr.status = :status';
            $params[':status'] = $filters['status'];
        }
        
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$isSuperAdmin) {
            $where[] = '(pr.employee_id = :user_id OR pr.reviewer_id = :user_id OR pr.created_by = :user_id)';
            $params[':user_id'] = $userId;
        }
        
        $sql = "SELECT COUNT(*) as total
                FROM adms_performance_reviews pr
                WHERE " . implode(' AND ', $where);
        
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Atualizar avaliação
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        
        $allowedFields = ['review_type', 'review_period_start', 'review_period_end', 'review_date',
                         'status', 'overall_score', 'strengths', 'improvements', 'comments',
                         'employee_comments', 'evaluation_id', 'completed_at'];
        
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
        
        $sql = "UPDATE adms_performance_reviews SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);

        $oldRow = $this->getById($id);

        foreach ($values as $key => $value) {
            $type = PDO::PARAM_STR;
            if (in_array($key, [':id', ':overall_score'])) {
                $type = PDO::PARAM_STR; // PDO::PARAM_INT para INT, mas overall_score é DECIMAL
            }
            $stmt->bindValue($key, $value, $type);
        }
        
        $ok = $stmt->execute();
        if ($ok && is_array($oldRow)) {
            $newRow = $this->getById($id);
            if (is_array($newRow)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_performance_reviews',
                    $id,
                    $usuarioId,
                    'UPDATE',
                    $oldRow,
                    $newRow
                );
            }
        }

        return $ok;
    }

    /**
     * Deletar avaliação (soft delete - mudar status)
     */
    public function delete(int $id): bool
    {
        $oldRow = $this->getById($id);
        $sql = "UPDATE adms_performance_reviews SET status = 'cancelled', updated_at = NOW() WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && is_array($oldRow)) {
            $newRow = $this->getById($id);
            if (is_array($newRow)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_performance_reviews',
                    $id,
                    $usuarioId,
                    'UPDATE',
                    $oldRow,
                    $newRow
                );
            }
        }

        return $ok;
    }

    /**
     * Buscar dados para Matriz 9BOX
     * Retorna colaboradores com suas avaliações mais recentes e calcula Potencial e Desempenho
     */
    public function getNineBoxData(array $filters = []): array
    {
        $where = ['pr.status = :status'];
        $params = [':status' => 'completed'];
        
        // Filtros
        if (!empty($filters['department_id'])) {
            $where[] = 'e.user_department_id = :department_id';
            $params[':department_id'] = $filters['department_id'];
        }
        
        if (!empty($filters['position_id'])) {
            $where[] = 'e.user_position_id = :position_id';
            $params[':position_id'] = $filters['position_id'];
        }
        
        if (!empty($filters['period_start'])) {
            $where[] = 'pr.review_period_start >= :period_start';
            $params[':period_start'] = $filters['period_start'];
        }
        
        if (!empty($filters['period_end'])) {
            $where[] = 'pr.review_period_end <= :period_end';
            $params[':period_end'] = $filters['period_end'];
        }

        // Buscar avaliação mais recente de cada colaborador
        $sql = "SELECT 
                    pr.employee_id,
                    e.name as employee_name,
                    e.email as employee_email,
                    d.name as department_name,
                    p.name as position_name,
                    pr.overall_score as performance_score,
                    COALESCE(pr.potential_score, 
                        CASE 
                            WHEN pr.overall_score >= 8 THEN 8.5
                            WHEN pr.overall_score >= 6 THEN 6.5
                            ELSE 4.5
                        END
                    ) as potential_score,
                    pr.review_date,
                    pr.review_type,
                    pr.id as review_id
                FROM adms_performance_reviews pr
                INNER JOIN adms_users e ON pr.employee_id = e.id
                INNER JOIN adms_departments d ON e.user_department_id = d.id
                INNER JOIN adms_positions p ON e.user_position_id = p.id
                INNER JOIN (
                    SELECT employee_id, MAX(review_date) as max_date
                    FROM adms_performance_reviews
                    WHERE status = 'completed'
                    GROUP BY employee_id
                ) latest ON pr.employee_id = latest.employee_id AND pr.review_date = latest.max_date
                WHERE " . implode(' AND ', $where) . "
                AND e.status = 'Ativo'
                AND e.data_desligamento IS NULL
                ORDER BY e.name";
        
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular box para cada colaborador
        $boxes = [
            1 => [], 2 => [], 3 => [],
            4 => [], 5 => [], 6 => [],
            7 => [], 8 => [], 9 => []
        ];
        
        foreach ($employees as &$employee) {
            $performance = (float)($employee['performance_score'] ?? 0);
            $potential = (float)($employee['potential_score'] ?? 0);
            
            // Classificar Desempenho (0-10)
            if ($performance < 6) {
                $performanceLevel = 'low'; // Baixo
            } elseif ($performance < 8) {
                $performanceLevel = 'medium'; // Médio
            } else {
                $performanceLevel = 'high'; // Alto
            }
            
            // Classificar Potencial (0-10)
            if ($potential < 6) {
                $potentialLevel = 'low'; // Baixo
            } elseif ($potential < 8) {
                $potentialLevel = 'medium'; // Médio
            } else {
                $potentialLevel = 'high'; // Alto
            }
            
            // Determinar box (1-9)
            // Box: Potencial (Y) x Desempenho (X)
            // Linha 1 (Alto Potencial): Boxes 7, 8, 9
            // Linha 2 (Médio Potencial): Boxes 4, 5, 6
            // Linha 3 (Baixo Potencial): Boxes 1, 2, 3
            $box = 0;
            if ($potentialLevel === 'high') {
                if ($performanceLevel === 'low') $box = 7;
                elseif ($performanceLevel === 'medium') $box = 8;
                else $box = 9;
            } elseif ($potentialLevel === 'medium') {
                if ($performanceLevel === 'low') $box = 4;
                elseif ($performanceLevel === 'medium') $box = 5;
                else $box = 6;
            } else {
                if ($performanceLevel === 'low') $box = 1;
                elseif ($performanceLevel === 'medium') $box = 2;
                else $box = 3;
            }
            
            $employee['box'] = $box;
            $employee['performance_level'] = $performanceLevel;
            $employee['potential_level'] = $potentialLevel;
            
            $boxes[$box][] = $employee;
        }
        
        return [
            'employees' => $employees,
            'boxes' => $boxes,
            'total' => count($employees)
        ];
    }
}

