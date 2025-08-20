<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
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
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_strategic_plans WHERE id = :id');
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
        return (int)$this->getConnection()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE adms_strategic_plans SET department_id = :department_id, responsible_id = :responsible_id, title = :title, what = :what, why = :why, where_field = :where_field, who_field = :who_field, start_date = :start_date, end_date = :end_date, how = :how, how_much = :how_much, completed = :completed, status = :status, comment = :comment, direction_comment = :direction_comment, updated_at = NOW() WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        return $stmt->execute([
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
    }

    public function delete(int $id): bool
    {
        $stmt = $this->getConnection()->prepare('DELETE FROM adms_strategic_plans WHERE id = :id');
        return $stmt->execute(['id' => $id]);
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
        $sql = 'SELECT * FROM adms_strategic_plans ' . $whereSql . ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
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
    public function getAmountStrategicPlans(array $criteria): int
    {
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
        $sql = 'SELECT COUNT(id) as amount_records FROM adms_strategic_plans ' . $whereSql;
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }
} 