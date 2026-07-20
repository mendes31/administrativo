<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class CareerPromotionsRepository extends DbConnection
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_career_promotions
                (user_id, from_position_id, to_position_id, career_track_id, career_level_id,
                 effective_date, status, notes, created_by)
                VALUES
                (:user_id, :from_position_id, :to_position_id, :career_track_id, :career_level_id,
                 :effective_date, :status, :notes, :created_by)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', (int) $data['user_id'], PDO::PARAM_INT);
        $this->bindNullInt($stmt, ':from_position_id', $data['from_position_id'] ?? null);
        $stmt->bindValue(':to_position_id', (int) $data['to_position_id'], PDO::PARAM_INT);
        $this->bindNullInt($stmt, ':career_track_id', $data['career_track_id'] ?? null);
        $this->bindNullInt($stmt, ':career_level_id', $data['career_level_id'] ?? null);
        $stmt->bindValue(':effective_date', $data['effective_date']);
        $stmt->bindValue(':status', $data['status'] ?? 'draft');
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $stmt->bindValue(':created_by', (int) $data['created_by'], PDO::PARAM_INT);
        $stmt->execute();
        $id = (int) $this->getConnection()->lastInsertId();
        if ($id > 0) {
            $row = $this->getById($id);
            if (is_array($row)) {
                LogAlteracaoService::registrarAlteracao('adms_career_promotions', $id, (int) ($_SESSION['user_id'] ?? $data['created_by']), 'INSERT', [], $row);
            }
        }
        return $id;
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT pr.*,
                       u.name AS user_name,
                       fp.name AS from_position_name,
                       tp.name AS to_position_name,
                       t.name AS track_name,
                       l.name AS level_name,
                       cb.name AS created_by_name,
                       ab.name AS approved_by_name
                FROM adms_career_promotions pr
                INNER JOIN adms_users u ON u.id = pr.user_id
                LEFT JOIN adms_positions fp ON fp.id = pr.from_position_id
                INNER JOIN adms_positions tp ON tp.id = pr.to_position_id
                LEFT JOIN adms_career_tracks t ON t.id = pr.career_track_id
                LEFT JOIN adms_career_levels l ON l.id = pr.career_level_id
                LEFT JOIN adms_users cb ON cb.id = pr.created_by
                LEFT JOIN adms_users ab ON ab.id = pr.approved_by
                WHERE pr.id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        [$where, $params] = $this->filters($filters);
        $sql = 'SELECT pr.*, u.name AS user_name, tp.name AS to_position_name
                FROM adms_career_promotions pr
                INNER JOIN adms_users u ON u.id = pr.user_id
                INNER JOIN adms_positions tp ON tp.id = pr.to_position_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY pr.effective_date DESC, pr.id DESC
                LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count(array $filters = []): int
    {
        [$where, $params] = $this->filters($filters);
        $sql = 'SELECT COUNT(*) FROM adms_career_promotions pr WHERE ' . implode(' AND ', $where);
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
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
        $sql = 'UPDATE adms_career_promotions SET
                    from_position_id = :from_position_id,
                    to_position_id = :to_position_id,
                    career_track_id = :career_track_id,
                    career_level_id = :career_level_id,
                    effective_date = :effective_date,
                    status = :status,
                    notes = :notes,
                    approved_by = :approved_by,
                    approved_at = :approved_at,
                    applied_at = :applied_at,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindNullInt($stmt, ':from_position_id', $data['from_position_id'] ?? null);
        $stmt->bindValue(':to_position_id', (int) $data['to_position_id'], PDO::PARAM_INT);
        $this->bindNullInt($stmt, ':career_track_id', $data['career_track_id'] ?? null);
        $this->bindNullInt($stmt, ':career_level_id', $data['career_level_id'] ?? null);
        $stmt->bindValue(':effective_date', $data['effective_date']);
        $stmt->bindValue(':status', $data['status'] ?? 'draft');
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $this->bindNullInt($stmt, ':approved_by', $data['approved_by'] ?? null);
        $stmt->bindValue(':approved_at', $data['approved_at'] ?? null);
        $stmt->bindValue(':applied_at', $data['applied_at'] ?? null);
        $ok = $stmt->execute();
        if ($ok) {
            $after = $this->getById($id);
            if (is_array($after)) {
                LogAlteracaoService::registrarAlteracao('adms_career_promotions', $id, (int) ($_SESSION['user_id'] ?? 1), 'UPDATE', $before, $after);
            }
        }
        return $ok;
    }

    public function applyUserPosition(int $userId, int $positionId): bool
    {
        $sql = 'UPDATE adms_users SET user_position_id = :position_id WHERE id = :user_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':position_id', $positionId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /** @return array{0: list<string>, 1: array<string, mixed>} */
    private function filters(array $filters): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['user_id'])) {
            $where[] = 'pr.user_id = :user_id';
            $params[':user_id'] = (int) $filters['user_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'pr.status = :status';
            $params[':status'] = $filters['status'];
        }
        return [$where, $params];
    }

    private function bindNullInt(\PDOStatement $stmt, string $param, mixed $value): void
    {
        if ($value === null || $value === '' || (int) $value <= 0) {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue($param, (int) $value, PDO::PARAM_INT);
        }
    }
}
