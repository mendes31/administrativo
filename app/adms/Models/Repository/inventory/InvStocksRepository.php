<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use PDO;

class InvStocksRepository extends DbConnection
{
    public function getAll(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $params = [];
        $wheres = [];
        if (!empty($filters['name'])) { $wheres[] = 's.name LIKE :name'; $params[':name'] = '%' . $filters['name'] . '%'; }
        if (!empty($filters['code'])) { $wheres[] = 's.code LIKE :code'; $params[':code'] = '%' . $filters['code'] . '%'; }
        if (isset($filters['active']) && $filters['active'] !== '') { $wheres[] = 's.active = :active'; $params[':active'] = (int)$filters['active']; }
        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';
        $sql = 'SELECT s.id, s.name, s.code, s.adms_branch_id, s.active FROM inv_stocks s ' . $whereSql . ' ORDER BY s.name ASC LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR); }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countAll(array $filters = []): int
    {
        $params = [];
        $wheres = [];
        if (!empty($filters['name'])) { $wheres[] = 'name LIKE :name'; $params[':name'] = '%' . $filters['name'] . '%'; }
        if (!empty($filters['code'])) { $wheres[] = 'code LIKE :code'; $params[':code'] = '%' . $filters['code'] . '%'; }
        if (isset($filters['active']) && $filters['active'] !== '') { $wheres[] = 'active = :active'; $params[':active'] = (int)$filters['active']; }
        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';
        $stmt = $this->getConnection()->prepare('SELECT COUNT(*) FROM inv_stocks ' . $whereSql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR); }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getOne(int $id): array|bool
    {
        $stmt = $this->getConnection()->prepare('SELECT id, name, code, adms_branch_id, active FROM inv_stocks WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int|bool
    {
        $sql = 'INSERT INTO inv_stocks (name, code, adms_branch_id, active, created_at) VALUES (:name, :code, :branch, :active, NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':code', $data['code']);
        $stmt->bindValue(':branch', !empty($data['adms_branch_id']) ? (int)$data['adms_branch_id'] : null, !empty($data['adms_branch_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':active', isset($data['active']) ? (int)$data['active'] : 1, PDO::PARAM_INT);
        if ($stmt->execute()) { return (int)$this->getConnection()->lastInsertId(); }
        return false;
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE inv_stocks SET name = :name, code = :code, adms_branch_id = :branch, active = :active, updated_at = NOW() WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':code', $data['code']);
        $stmt->bindValue(':branch', !empty($data['adms_branch_id']) ? (int)$data['adms_branch_id'] : null, !empty($data['adms_branch_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':active', isset($data['active']) ? (int)$data['active'] : 1, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->getConnection()->prepare('DELETE FROM inv_stocks WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    public function getAllForSelect(): array
    {
        $sql = 'SELECT id, name FROM inv_stocks WHERE active = 1 ORDER BY name ASC';
        $stmt = $this->getConnection()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}


