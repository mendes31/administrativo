<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

/**
 * Repository de ciclos de desempenho.
 */
class PerformanceCyclesRepository extends DbConnection
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_performance_cycles
                (name, year, period_start, period_end, status, description, created_by)
                VALUES
                (:name, :year, :period_start, :period_end, :status, :description, :created_by)';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':year', (int) $data['year'], PDO::PARAM_INT);
        $stmt->bindValue(':period_start', $data['period_start']);
        $stmt->bindValue(':period_end', $data['period_end']);
        $stmt->bindValue(':status', $data['status'] ?? 'draft');
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':created_by', (int) $data['created_by'], PDO::PARAM_INT);
        $stmt->execute();

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->getById($newId);
            if (is_array($row)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_performance_cycles',
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
        $sql = 'SELECT c.*, u.name AS created_by_name
                FROM adms_performance_cycles c
                LEFT JOIN adms_users u ON u.id = c.created_by
                WHERE c.id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        [$where, $params] = $this->buildFilters($filters);

        $sql = 'SELECT c.*, u.name AS created_by_name
                FROM adms_performance_cycles c
                LEFT JOIN adms_users u ON u.id = c.created_by
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY c.year DESC, c.period_start DESC, c.id DESC
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
        $sql = 'SELECT COUNT(*) FROM adms_performance_cycles c WHERE ' . implode(' AND ', $where);
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Ciclos disponíveis para vínculo em metas (draft|open).
     *
     * @return list<array<string, mixed>>
     */
    public function listLinkable(): array
    {
        $sql = "SELECT id, name, year, status, period_start, period_end
                FROM adms_performance_cycles
                WHERE status IN ('draft', 'open')
                ORDER BY year DESC, period_start DESC, id DESC";
        $stmt = $this->getConnection()->query($sql);

        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function countGoals(int $cycleId): int
    {
        $sql = 'SELECT COUNT(*) FROM adms_performance_goals WHERE performance_cycle_id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $cycleId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function countReviews(int $cycleId): int
    {
        $sql = 'SELECT COUNT(*) FROM adms_performance_reviews WHERE performance_cycle_id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $cycleId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['name', 'year', 'period_start', 'period_end', 'status', 'description'];
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
        $sql = 'UPDATE adms_performance_cycles SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);

        $oldRow = $this->getById($id);
        foreach ($values as $key => $value) {
            if ($key === ':id' || $key === ':year') {
                $stmt->bindValue($key, (int) $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }

        $ok = $stmt->execute();
        if ($ok && is_array($oldRow)) {
            $newRow = $this->getById($id);
            if (is_array($newRow)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_performance_cycles',
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

        if (!empty($filters['year'])) {
            $where[] = 'c.year = :year';
            $params[':year'] = (int) $filters['year'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'c.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $where[] = 'c.name LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        return [$where, $params];
    }
}
