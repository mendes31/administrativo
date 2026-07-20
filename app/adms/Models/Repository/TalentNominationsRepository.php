<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

/**
 * Nomeações de talent pool / HiPo por ciclo.
 */
class TalentNominationsRepository extends DbConnection
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_talent_nominations
                (user_id, performance_cycle_id, nine_box, status, notes, nominated_by)
                VALUES
                (:user_id, :performance_cycle_id, :nine_box, :status, :notes, :nominated_by)';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', (int) $data['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':performance_cycle_id', (int) $data['performance_cycle_id'], PDO::PARAM_INT);
        $this->bindNullableInt($stmt, ':nine_box', $data['nine_box'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'active');
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $stmt->bindValue(':nominated_by', (int) $data['nominated_by'], PDO::PARAM_INT);
        $stmt->execute();

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->getById($newId);
            if (is_array($row)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_talent_nominations',
                    $newId,
                    (int) ($_SESSION['user_id'] ?? $data['nominated_by'] ?? 1),
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
        $sql = 'SELECT t.*,
                       u.name AS user_name, u.email AS user_email,
                       c.name AS cycle_name, c.status AS cycle_status,
                       n.name AS nominated_by_name
                FROM adms_talent_nominations t
                INNER JOIN adms_users u ON u.id = t.user_id
                INNER JOIN adms_performance_cycles c ON c.id = t.performance_cycle_id
                LEFT JOIN adms_users n ON n.id = t.nominated_by
                WHERE t.id = :id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getByUserAndCycle(int $userId, int $cycleId): ?array
    {
        $sql = 'SELECT * FROM adms_talent_nominations
                WHERE user_id = :user_id AND performance_cycle_id = :cycle_id
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':cycle_id', $cycleId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * @return array<int, array<string, mixed>> keyed by user_id for active nominations in cycle
     */
    public function getActiveMapByCycle(int $cycleId): array
    {
        $sql = 'SELECT * FROM adms_talent_nominations
                WHERE performance_cycle_id = :cycle_id AND status = \'active\'';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':cycle_id', $cycleId, PDO::PARAM_INT);
        $stmt->execute();

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(int) $row['user_id']] = $row;
        }

        return $map;
    }

    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        [$where, $params] = $this->buildFilters($filters);

        $sql = 'SELECT t.*, u.name AS user_name, c.name AS cycle_name, n.name AS nominated_by_name
                FROM adms_talent_nominations t
                INNER JOIN adms_users u ON u.id = t.user_id
                INNER JOIN adms_performance_cycles c ON c.id = t.performance_cycle_id
                LEFT JOIN adms_users n ON n.id = t.nominated_by
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY t.created_at DESC
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
        $sql = 'SELECT COUNT(*) FROM adms_talent_nominations t WHERE ' . implode(' AND ', $where);
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

        $sql = 'UPDATE adms_talent_nominations SET
                    nine_box = :nine_box,
                    status = :status,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindNullableInt($stmt, ':nine_box', $data['nine_box'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'active');
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $ok = $stmt->execute();

        if ($ok) {
            $after = $this->getById($id);
            if (is_array($after)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_talent_nominations',
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

    /**
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    private function buildFilters(array $filters): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 't.user_id = :user_id';
            $params[':user_id'] = (int) $filters['user_id'];
        }
        if (!empty($filters['performance_cycle_id'])) {
            $where[] = 't.performance_cycle_id = :performance_cycle_id';
            $params[':performance_cycle_id'] = (int) $filters['performance_cycle_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 't.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['nine_box'])) {
            $where[] = 't.nine_box = :nine_box';
            $params[':nine_box'] = (int) $filters['nine_box'];
        }

        return [$where, $params];
    }

    private function bindNullableInt(\PDOStatement $stmt, string $param, mixed $value): void
    {
        if ($value === null || $value === '' || (int) $value <= 0) {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue($param, (int) $value, PDO::PARAM_INT);
        }
    }
}
