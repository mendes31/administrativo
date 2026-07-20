<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class CriticalPositionsRepository extends DbConnection
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_critical_positions
                (position_id, risk_level, status, notes, created_by)
                VALUES
                (:position_id, :risk_level, :status, :notes, :created_by)';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':position_id', (int) $data['position_id'], PDO::PARAM_INT);
        $stmt->bindValue(':risk_level', $data['risk_level'] ?? 'medium');
        $stmt->bindValue(':status', $data['status'] ?? 'active');
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $stmt->bindValue(':created_by', (int) $data['created_by'], PDO::PARAM_INT);
        $stmt->execute();

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->getById($newId);
            if (is_array($row)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_critical_positions',
                    $newId,
                    (int) ($_SESSION['user_id'] ?? $data['created_by'] ?? 1),
                    'INSERT',
                    [],
                    $row
                );
            }
        }

        return $newId;
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT cp.*,
                       p.name AS position_name,
                       cb.name AS created_by_name,
                       (SELECT COUNT(*) FROM adms_succession_successors s
                        WHERE s.critical_position_id = cp.id) AS successors_count
                FROM adms_critical_positions cp
                INNER JOIN adms_positions p ON p.id = cp.position_id
                LEFT JOIN adms_users cb ON cb.id = cp.created_by
                WHERE cp.id = :id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getByPositionId(int $positionId): ?array
    {
        $sql = 'SELECT * FROM adms_critical_positions WHERE position_id = :position_id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':position_id', $positionId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        [$where, $params] = $this->buildFilters($filters);

        $sql = 'SELECT cp.*, p.name AS position_name,
                       (SELECT COUNT(*) FROM adms_succession_successors s
                        WHERE s.critical_position_id = cp.id) AS successors_count
                FROM adms_critical_positions cp
                INNER JOIN adms_positions p ON p.id = cp.position_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY FIELD(cp.risk_level, \'high\', \'medium\', \'low\'), p.name ASC
                LIMIT :limit OFFSET :offset';

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count(array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($filters);
        $sql = 'SELECT COUNT(*) FROM adms_critical_positions cp
                INNER JOIN adms_positions p ON p.id = cp.position_id
                WHERE ' . implode(' AND ', $where);
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $data): bool
    {
        $before = $this->getById($id);
        if (!$before) {
            return false;
        }

        $sql = 'UPDATE adms_critical_positions SET
                    risk_level = :risk_level,
                    status = :status,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':risk_level', $data['risk_level'] ?? 'medium');
        $stmt->bindValue(':status', $data['status'] ?? 'active');
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $ok = $stmt->execute();

        if ($ok) {
            $after = $this->getById($id);
            if (is_array($after)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_critical_positions',
                    $id,
                    (int) ($_SESSION['user_id'] ?? 1),
                    'UPDATE',
                    $before,
                    $after
                );
            }
        }

        return $ok;
    }

    /** @return array{0: list<string>, 1: array<string, mixed>} */
    private function buildFilters(array $filters): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 'cp.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['risk_level'])) {
            $where[] = 'cp.risk_level = :risk_level';
            $params[':risk_level'] = $filters['risk_level'];
        }
        if (!empty($filters['search'])) {
            $where[] = 'p.name LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        return [$where, $params];
    }
}
