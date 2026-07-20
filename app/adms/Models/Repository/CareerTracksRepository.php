<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class CareerTracksRepository extends DbConnection
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_career_tracks (name, description, status, created_by)
                VALUES (:name, :description, :status, :created_by)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'active');
        $stmt->bindValue(':created_by', (int) $data['created_by'], PDO::PARAM_INT);
        $stmt->execute();
        $id = (int) $this->getConnection()->lastInsertId();
        if ($id > 0) {
            $row = $this->getById($id);
            if (is_array($row)) {
                LogAlteracaoService::registrarAlteracao('adms_career_tracks', $id, (int) ($_SESSION['user_id'] ?? $data['created_by']), 'INSERT', [], $row);
            }
        }
        return $id;
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT t.*, u.name AS created_by_name,
                       (SELECT COUNT(*) FROM adms_career_levels l WHERE l.career_track_id = t.id) AS levels_count
                FROM adms_career_tracks t
                LEFT JOIN adms_users u ON u.id = t.created_by
                WHERE t.id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 't.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $where[] = 't.name LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        $sql = 'SELECT t.*, (SELECT COUNT(*) FROM adms_career_levels l WHERE l.career_track_id = t.id) AS levels_count
                FROM adms_career_tracks t
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY t.name ASC LIMIT :limit OFFSET :offset';
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
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $where[] = 'name LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        $sql = 'SELECT COUNT(*) FROM adms_career_tracks WHERE ' . implode(' AND ', $where);
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function listActive(): array
    {
        $sql = 'SELECT id, name FROM adms_career_tracks WHERE status = \'active\' ORDER BY name';
        return $this->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(int $id, array $data): bool
    {
        $before = $this->getById($id);
        if (!$before) {
            return false;
        }
        $sql = 'UPDATE adms_career_tracks SET name = :name, description = :description, status = :status, updated_at = NOW() WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'active');
        $ok = $stmt->execute();
        if ($ok) {
            $after = $this->getById($id);
            if (is_array($after)) {
                LogAlteracaoService::registrarAlteracao('adms_career_tracks', $id, (int) ($_SESSION['user_id'] ?? 1), 'UPDATE', $before, $after);
            }
        }
        return $ok;
    }
}
