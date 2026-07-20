<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

/**
 * Repository de sessões de calibração.
 */
class PerformanceCalibrationsRepository extends DbConnection
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_performance_calibrations
                (performance_cycle_id, status, session_notes, created_by)
                VALUES
                (:performance_cycle_id, :status, :session_notes, :created_by)';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':performance_cycle_id', (int) $data['performance_cycle_id'], PDO::PARAM_INT);
        $stmt->bindValue(':status', $data['status'] ?? 'draft');
        $stmt->bindValue(':session_notes', $data['session_notes'] ?? null);
        $stmt->bindValue(':created_by', (int) $data['created_by'], PDO::PARAM_INT);
        $stmt->execute();

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->getById($newId);
            if (is_array($row)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_performance_calibrations',
                    $newId,
                    (int) ($_SESSION['user_id'] ?? $data['created_by']),
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
        $sql = 'SELECT cal.*,
                       cy.name AS cycle_name, cy.year AS cycle_year, cy.status AS cycle_status,
                       cy.period_start, cy.period_end,
                       u.name AS created_by_name,
                       lb.name AS locked_by_name
                FROM adms_performance_calibrations cal
                INNER JOIN adms_performance_cycles cy ON cy.id = cal.performance_cycle_id
                LEFT JOIN adms_users u ON u.id = cal.created_by
                LEFT JOIN adms_users lb ON lb.id = cal.locked_by
                WHERE cal.id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function getByCycleId(int $cycleId): ?array
    {
        $sql = 'SELECT id FROM adms_performance_calibrations WHERE performance_cycle_id = :cid LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':cid', $cycleId, PDO::PARAM_INT);
        $stmt->execute();
        $id = (int) $stmt->fetchColumn();

        return $id > 0 ? $this->getById($id) : null;
    }

    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        [$where, $params] = $this->buildFilters($filters);

        $sql = 'SELECT cal.*, cy.name AS cycle_name, cy.year AS cycle_year, u.name AS created_by_name
                FROM adms_performance_calibrations cal
                INNER JOIN adms_performance_cycles cy ON cy.id = cal.performance_cycle_id
                LEFT JOIN adms_users u ON u.id = cal.created_by
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY cal.id DESC
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
        $sql = 'SELECT COUNT(*) FROM adms_performance_calibrations cal
                INNER JOIN adms_performance_cycles cy ON cy.id = cal.performance_cycle_id
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
        $allowed = ['status', 'session_notes', 'locked_at', 'locked_by'];
        $fields = [];
        $values = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $values[":{$field}"] = $data[$field];
            }
        }

        if ($fields === []) {
            return false;
        }

        $values[':id'] = $id;
        $fields[] = 'updated_at = NOW()';
        $sql = 'UPDATE adms_performance_calibrations SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $oldRow = $this->getById($id);

        foreach ($values as $key => $value) {
            if (($key === ':locked_by' || $key === ':id') && $value !== null) {
                $stmt->bindValue($key, (int) $value, PDO::PARAM_INT);
            } elseif ($key === ':locked_by' || $key === ':locked_at') {
                $stmt->bindValue($key, $value, $value === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            } else {
                $stmt->bindValue($key, $value);
            }
        }

        $ok = $stmt->execute();
        if ($ok && is_array($oldRow)) {
            $newRow = $this->getById($id);
            if (is_array($newRow)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_performance_calibrations',
                    $id,
                    (int) ($_SESSION['user_id'] ?? 1),
                    'UPDATE',
                    $oldRow,
                    $newRow
                );
            }
        }

        return $ok;
    }

    /**
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    private function buildFilters(array $filters): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'cal.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['performance_cycle_id'])) {
            $where[] = 'cal.performance_cycle_id = :performance_cycle_id';
            $params[':performance_cycle_id'] = (int) $filters['performance_cycle_id'];
        }
        if (!empty($filters['search'])) {
            $where[] = 'cy.name LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        return [$where, $params];
    }
}
