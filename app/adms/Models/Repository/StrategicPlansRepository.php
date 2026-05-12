<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class StrategicPlansRepository extends DbConnection
{
    public function getAll(): array
    {
        $stmt = $this->getConnection()->query('SELECT * FROM adms_strategic_plans ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT 
                    sp.*,
                    d.name as dep_name,
                    u.name as user_name
                FROM adms_strategic_plans sp
                LEFT JOIN adms_departments d ON sp.department_id = d.id
                LEFT JOIN adms_users u ON sp.responsible_id = u.id
                WHERE sp.id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_strategic_plans (department_id, responsible_id, title, what, why, where_field, who_field, start_date, end_date, how, how_much, completed, status, comment, direction_comment, created_at, updated_at) VALUES (:department_id, :responsible_id, :title, :what, :why, :where_field, :who_field, :start_date, :end_date, :how, :how_much, :completed, :status, :comment, :direction_comment, NOW(), NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            'department_id' => $data['department_id'],
            'responsible_id' => $data['responsible_id'],
            'title' => $data['title'],
            'what' => $data['what'],
            'why' => $data['why'],
            'where_field' => $data['where_field'],
            'who_field' => $data['who_field'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'how' => $data['how'],
            'how_much' => $data['how_much'],
            'completed' => $data['completed'] ?? 0,
            'status' => $data['status'],
            'comment' => $data['comment'],
            'direction_comment' => $data['direction_comment'],
        ]);
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->getById($newId);
            if (is_array($row)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_strategic_plans',
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

    public function update(int $id, array $data): bool
    {
        $oldRow = $this->getById($id);
        $sql = 'UPDATE adms_strategic_plans SET department_id = :department_id, responsible_id = :responsible_id, title = :title, what = :what, why = :why, where_field = :where_field, who_field = :who_field, start_date = :start_date, end_date = :end_date, how = :how, how_much = :how_much, completed = :completed, status = :status, comment = :comment, direction_comment = :direction_comment, updated_at = NOW() WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $ok = $stmt->execute([
            'department_id' => $data['department_id'],
            'responsible_id' => $data['responsible_id'],
            'title' => $data['title'],
            'what' => $data['what'],
            'why' => $data['why'],
            'where_field' => $data['where_field'],
            'who_field' => $data['who_field'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'how' => $data['how'],
            'how_much' => $data['how_much'],
            'completed' => $data['completed'] ?? 0,
            'status' => $data['status'],
            'comment' => $data['comment'],
            'direction_comment' => $data['direction_comment'],
            'id' => $id,
        ]);
        if ($ok && is_array($oldRow)) {
            $newRow = $this->getById($id);
            if (is_array($newRow)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_strategic_plans',
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

    public function delete(int $id): bool
    {
        $oldRow = $this->getById($id);
        $stmt = $this->getConnection()->prepare('DELETE FROM adms_strategic_plans WHERE id = :id');
        $ok = $stmt->execute(['id' => $id]);
        if ($ok && is_array($oldRow)) {
            $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'adms_strategic_plans',
                $id,
                $usuarioId,
                'DELETE',
                $oldRow,
                []
            );
        }

        return $ok;
    }

    /**
     * Buscar planos estratégicos com última observação
     */
    public function getAllStrategicPlansWithLastObservation(array $criteria, int $page, int $limit, ?int $userDepartmentId = null, string $orderBy = 'start_date'): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $whereClauses = [];
        $params = [];

        // Filtro por departamento do usuário logado (se não for super admin)
        if ($userDepartmentId !== null) {
            $whereClauses[] = 'sp.department_id = :user_department_id';
            $params['user_department_id'] = $userDepartmentId;
        }

        // Construir cláusulas WHERE
        if (!empty($criteria['titulo'])) {
            $whereClauses[] = 'sp.title LIKE :titulo';
            $params['titulo'] = '%' . $criteria['titulo'] . '%';
        }
        if (!empty($criteria['departamento'])) {
            $whereClauses[] = 'd.name LIKE :departamento';
            $params['departamento'] = '%' . $criteria['departamento'] . '%';
        }
        if (!empty($criteria['responsavel'])) {
            $whereClauses[] = 'u.name LIKE :responsavel';
            $params['responsavel'] = '%' . $criteria['responsavel'] . '%';
        }
        if (!empty($criteria['status'])) {
            $whereClauses[] = 'sp.status = :status';
            $params['status'] = $criteria['status'];
        }

        $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        $sql = "SELECT 
                    sp.*,
                    d.name as dep_name,
                    u.name as user_name,
                    u.email as user_email,
                    last_obs.observation as last_observation,
                    last_obs.created_at as last_observation_date,
                    last_obs.user_name as last_observation_user,
                    last_obs.department_name as last_observation_department
                FROM adms_strategic_plans sp
                LEFT JOIN adms_departments d ON sp.department_id = d.id
                LEFT JOIN adms_users u ON sp.responsible_id = u.id
                LEFT JOIN (
                    SELECT 
                        o1.strategic_plan_id,
                        o1.observation,
                        o1.created_at,
                        u2.name as user_name,
                        d2.name as department_name
                    FROM adms_strategic_plan_observations o1
                    LEFT JOIN adms_users u2 ON o1.user_id = u2.id
                    LEFT JOIN adms_departments d2 ON u2.user_department_id = d2.id
                    WHERE o1.created_at = (
                        SELECT MAX(o2.created_at)
                        FROM adms_strategic_plan_observations o2
                        WHERE o2.strategic_plan_id = o1.strategic_plan_id
                    )
                ) last_obs ON sp.id = last_obs.strategic_plan_id
                {$whereSql}
                ORDER BY sp.start_date ASC, sp.end_date ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, \PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca paginada e filtrada de planos estratégicos
     * @param array $criteria
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getAllStrategicPlans(array $criteria, int $page, int $limit): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $whereClauses = [];
        $params = [];
        if (!empty($criteria['titulo'])) {
            $whereClauses[] = 'title LIKE :titulo';
            $params[':titulo'] = '%' . $criteria['titulo'] . '%';
        }
        if (!empty($criteria['departamento'])) {
            $whereClauses[] = 'department_id IN (SELECT id FROM adms_departments WHERE name LIKE :departamento)';
            $params[':departamento'] = '%' . $criteria['departamento'] . '%';
        }
        if (!empty($criteria['responsavel'])) {
            $whereClauses[] = 'responsible_id IN (SELECT id FROM adms_users WHERE name LIKE :responsavel)';
            $params[':responsavel'] = '%' . $criteria['responsavel'] . '%';
        }
        if (!empty($criteria['status'])) {
            $whereClauses[] = 'status = :status';
            $params[':status'] = $criteria['status'];
        }
        $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
        $sql = 'SELECT 
                    sp.*,
                    d.name as dep_name,
                    u.name as user_name
                FROM adms_strategic_plans sp
                LEFT JOIN adms_departments d ON sp.department_id = d.id
                LEFT JOIN adms_users u ON sp.responsible_id = u.id
                ' . $whereSql . ' ORDER BY sp.created_at DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna o total de registros filtrados
     * @param array $criteria
     * @return int
     */
    public function getAmountStrategicPlans(array $criteria, ?int $userDepartmentId = null): int
    {
        $whereClauses = [];
        $params = [];

        // Filtro por departamento do usuário logado (se não for super admin)
        if ($userDepartmentId !== null) {
            $whereClauses[] = 'department_id = :user_department_id';
            $params[':user_department_id'] = $userDepartmentId;
        }

        if (!empty($criteria['titulo'])) {
            $whereClauses[] = 'title LIKE :titulo';
            $params[':titulo'] = '%' . $criteria['titulo'] . '%';
        }
        if (!empty($criteria['departamento'])) {
            $whereClauses[] = 'department_id IN (SELECT id FROM adms_departments WHERE name LIKE :departamento)';
            $params[':departamento'] = '%' . $criteria['departamento'] . '%';
        }
        if (!empty($criteria['responsavel'])) {
            $whereClauses[] = 'responsible_id IN (SELECT id FROM adms_users WHERE name LIKE :responsavel)';
            $params[':responsavel'] = '%' . $criteria['responsavel'] . '%';
        }
        if (!empty($criteria['status'])) {
            $whereClauses[] = 'status = :status';
            $params[':status'] = $criteria['status'];
        }
        $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
        $sql = 'SELECT COUNT(id) as amount_records FROM adms_strategic_plans ' . $whereSql;
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }
} 