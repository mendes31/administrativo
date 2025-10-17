<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use PDO;

class InvCategoriesRepository extends DbConnection
{
    public function getAll(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $params = [];
        $wheres = [];
        if (!empty($filters['name'])) { $wheres[] = 'name LIKE :name'; $params[':name'] = '%' . $filters['name'] . '%'; }
        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';
        $sql = 'SELECT id, name FROM inv_categories ' . $whereSql . ' ORDER BY name ASC LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
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
        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';
        $stmt = $this->getConnection()->prepare('SELECT COUNT(*) FROM inv_categories ' . $whereSql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getOne(int $id): array|bool
    {
        $stmt = $this->getConnection()->prepare('SELECT id, name FROM inv_categories WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int|bool
    {
        $stmt = $this->getConnection()->prepare('INSERT INTO inv_categories (name, created_at) VALUES (:name, NOW())');
        $stmt->bindValue(':name', $data['name']);
        if ($stmt->execute()) { return (int)$this->getConnection()->lastInsertId(); }
        return false;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->getConnection()->prepare('UPDATE inv_categories SET name = :name, updated_at = NOW() WHERE id = :id');
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->getConnection()->prepare('DELETE FROM inv_categories WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    public function getAllForSelect(): array
    {
        $sql = 'SELECT id, name FROM inv_categories ORDER BY name';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}



