<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

/**
 * Planos de Desenvolvimento Individual (PDI).
 */
class PdiPlansRepository extends DbConnection
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_pdi_plans
                (user_id, manager_id, title, description, period_start, period_end, status,
                 current_level, target_level, career_goal, performance_cycle_id, created_by)
                VALUES
                (:user_id, :manager_id, :title, :description, :period_start, :period_end, :status,
                 :current_level, :target_level, :career_goal, :performance_cycle_id, :created_by)';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', (int) $data['user_id'], PDO::PARAM_INT);
        $this->bindNullableInt($stmt, ':manager_id', $data['manager_id'] ?? null);
        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':period_start', $data['period_start']);
        $stmt->bindValue(':period_end', $data['period_end']);
        $stmt->bindValue(':status', $data['status'] ?? 'draft');
        $stmt->bindValue(':current_level', $data['current_level'] ?? null);
        $stmt->bindValue(':target_level', $data['target_level'] ?? null);
        $stmt->bindValue(':career_goal', $data['career_goal'] ?? null);
        $this->bindNullableInt($stmt, ':performance_cycle_id', $data['performance_cycle_id'] ?? null);
        $stmt->bindValue(':created_by', (int) $data['created_by'], PDO::PARAM_INT);
        $stmt->execute();

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->getById($newId);
            if (is_array($row)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_pdi_plans',
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
        $sql = 'SELECT p.*,
                       u.name AS user_name, u.email AS user_email,
                       m.name AS manager_name,
                       c.name AS cycle_name, c.status AS cycle_status,
                       cb.name AS created_by_name
                FROM adms_pdi_plans p
                INNER JOIN adms_users u ON u.id = p.user_id
                LEFT JOIN adms_users m ON m.id = p.manager_id
                LEFT JOIN adms_performance_cycles c ON c.id = p.performance_cycle_id
                LEFT JOIN adms_users cb ON cb.id = p.created_by
                WHERE p.id = :id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        [$where, $params] = $this->buildFilters($filters);

        $sql = 'SELECT p.*, u.name AS user_name, c.name AS cycle_name
                FROM adms_pdi_plans p
                INNER JOIN adms_users u ON u.id = p.user_id
                LEFT JOIN adms_performance_cycles c ON c.id = p.performance_cycle_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY p.period_end ASC, p.created_at DESC
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

        $sql = 'SELECT COUNT(*)
                FROM adms_pdi_plans p
                INNER JOIN adms_users u ON u.id = p.user_id
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

        $sql = 'UPDATE adms_pdi_plans SET
                    user_id = :user_id,
                    manager_id = :manager_id,
                    title = :title,
                    description = :description,
                    period_start = :period_start,
                    period_end = :period_end,
                    status = :status,
                    current_level = :current_level,
                    target_level = :target_level,
                    career_goal = :career_goal,
                    performance_cycle_id = :performance_cycle_id,
                    updated_at = NOW()
                WHERE id = :id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', (int) $data['user_id'], PDO::PARAM_INT);
        $this->bindNullableInt($stmt, ':manager_id', $data['manager_id'] ?? null);
        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':period_start', $data['period_start']);
        $stmt->bindValue(':period_end', $data['period_end']);
        $stmt->bindValue(':status', $data['status'] ?? 'draft');
        $stmt->bindValue(':current_level', $data['current_level'] ?? null);
        $stmt->bindValue(':target_level', $data['target_level'] ?? null);
        $stmt->bindValue(':career_goal', $data['career_goal'] ?? null);
        $this->bindNullableInt($stmt, ':performance_cycle_id', $data['performance_cycle_id'] ?? null);
        $ok = $stmt->execute();

        if ($ok) {
            $after = $this->getById($id);
            if (is_array($after)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_pdi_plans',
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

    public function approve(int $id, int $approvedBy): bool
    {
        $before = $this->getById($id);
        if (!$before) {
            return false;
        }

        $sql = 'UPDATE adms_pdi_plans SET
                    status = \'active\',
                    approved_by = :approved_by,
                    approved_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':approved_by', $approvedBy, PDO::PARAM_INT);
        $ok = $stmt->execute();

        if ($ok) {
            $after = $this->getById($id);
            if (is_array($after)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_pdi_plans',
                    $id,
                    $approvedBy,
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
            $where[] = 'p.user_id = :user_id';
            $params[':user_id'] = (int) $filters['user_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'p.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['performance_cycle_id'])) {
            $where[] = 'p.performance_cycle_id = :performance_cycle_id';
            $params[':performance_cycle_id'] = (int) $filters['performance_cycle_id'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(p.title LIKE :search OR p.description LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        $sessionUserId = (int) ($_SESSION['user_id'] ?? 0);
        if (!$isSuperAdmin && $sessionUserId > 0) {
            $where[] = '(p.user_id = :session_user_id OR p.manager_id = :session_manager_id)';
            $params[':session_user_id'] = $sessionUserId;
            $params[':session_manager_id'] = $sessionUserId;
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
