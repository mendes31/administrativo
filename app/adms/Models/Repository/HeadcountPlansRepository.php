<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class HeadcountPlansRepository extends DbConnection
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_headcount_plans
                (department_id, position_id, period_year, period_month, planned_count, status, notes, created_by)
                VALUES
                (:department_id, :position_id, :period_year, :period_month, :planned_count, :status, :notes, :created_by)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':department_id', (int) $data['department_id'], PDO::PARAM_INT);
        if ($data['position_id'] === null) {
            $stmt->bindValue(':position_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':position_id', (int) $data['position_id'], PDO::PARAM_INT);
        }
        $stmt->bindValue(':period_year', (int) $data['period_year'], PDO::PARAM_INT);
        $stmt->bindValue(':period_month', (int) $data['period_month'], PDO::PARAM_INT);
        $stmt->bindValue(':planned_count', (int) $data['planned_count'], PDO::PARAM_INT);
        $stmt->bindValue(':status', $data['status'] ?? 'draft');
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $stmt->bindValue(':created_by', (int) $data['created_by'], PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->getConnection()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE adms_headcount_plans SET
                    planned_count = :planned_count,
                    status = :status,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':planned_count', (int) $data['planned_count'], PDO::PARAM_INT);
        $stmt->bindValue(':status', $data['status']);
        $stmt->bindValue(':notes', $data['notes'] ?? null);

        return $stmt->execute();
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT p.*,
                       d.name AS department_name,
                       pos.name AS position_name,
                       u.name AS created_by_name
                FROM adms_headcount_plans p
                INNER JOIN adms_departments d ON d.id = p.department_id
                LEFT JOIN adms_positions pos ON pos.id = p.position_id
                LEFT JOIN adms_users u ON u.id = p.created_by
                WHERE p.id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findDuplicate(int $departmentId, ?int $positionId, int $year, int $month, ?int $excludeId = null): ?array
    {
        $sql = 'SELECT id FROM adms_headcount_plans
                WHERE department_id = :department_id
                  AND period_year = :period_year
                  AND period_month = :period_month
                  AND ' . ($positionId === null ? 'position_id IS NULL' : 'position_id = :position_id');
        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':department_id', $departmentId, PDO::PARAM_INT);
        $stmt->bindValue(':period_year', $year, PDO::PARAM_INT);
        $stmt->bindValue(':period_month', $month, PDO::PARAM_INT);
        if ($positionId !== null) {
            $stmt->bindValue(':position_id', $positionId, PDO::PARAM_INT);
        }
        if ($excludeId !== null) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        [$where, $params] = $this->buildFilters($filters);
        $sql = 'SELECT p.*,
                       d.name AS department_name,
                       pos.name AS position_name,
                       u.name AS created_by_name
                FROM adms_headcount_plans p
                INNER JOIN adms_departments d ON d.id = p.department_id
                LEFT JOIN adms_positions pos ON pos.id = p.position_id
                LEFT JOIN adms_users u ON u.id = p.created_by
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY p.period_year DESC, p.period_month DESC, d.name ASC
                LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function count(array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($filters);
        $sql = 'SELECT COUNT(*) FROM adms_headcount_plans p WHERE ' . implode(' AND ', $where);
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Contagem efetiva alinhada ao People Analytics (ativos sem desligamento).
     */
    public function countActual(int $departmentId, ?int $positionId): int
    {
        $sql = "SELECT COUNT(*) FROM adms_users
                WHERE user_department_id = :department_id
                  AND status = 'Ativo'
                  AND (data_desligamento IS NULL OR data_desligamento = '' OR data_desligamento = '0000-00-00')";
        if ($positionId !== null) {
            $sql .= ' AND user_position_id = :position_id';
        }
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':department_id', $departmentId, PDO::PARAM_INT);
        if ($positionId !== null) {
            $stmt->bindValue(':position_id', $positionId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    private function buildFilters(array $filters): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 'p.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['department_id'])) {
            $where[] = 'p.department_id = :department_id';
            $params[':department_id'] = (int) $filters['department_id'];
        }
        if (!empty($filters['period_year'])) {
            $where[] = 'p.period_year = :period_year';
            $params[':period_year'] = (int) $filters['period_year'];
        }
        if (!empty($filters['period_month'])) {
            $where[] = 'p.period_month = :period_month';
            $params[':period_month'] = (int) $filters['period_month'];
        }

        return [$where, $params];
    }
}
