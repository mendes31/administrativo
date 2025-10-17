<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use PDO;

class InvPositionsRepository extends DbConnection
{
    public function getAll(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $params = [];
        $wheres = [];
        if (!empty($filters['inv_stock_id'])) { $wheres[] = 'p.inv_stock_id = :stock'; $params[':stock'] = (int)$filters['inv_stock_id']; }
        if (!empty($filters['code'])) { $wheres[] = 'p.code LIKE :code'; $params[':code'] = '%' . $filters['code'] . '%'; }
        if (!empty($filters['description'])) { $wheres[] = 'p.description LIKE :description'; $params[':description'] = '%' . $filters['description'] . '%'; }
        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';
        $sql = 'SELECT p.id, p.inv_stock_id, p.code, p.description FROM inv_positions p ' . $whereSql . ' ORDER BY p.code ASC LIMIT :limit OFFSET :offset';
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
        if (!empty($filters['inv_stock_id'])) { $wheres[] = 'inv_stock_id = :stock'; $params[':stock'] = (int)$filters['inv_stock_id']; }
        if (!empty($filters['code'])) { $wheres[] = 'code LIKE :code'; $params[':code'] = '%' . $filters['code'] . '%'; }
        if (!empty($filters['description'])) { $wheres[] = 'description LIKE :description'; $params[':description'] = '%' . $filters['description'] . '%'; }
        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';
        $stmt = $this->getConnection()->prepare('SELECT COUNT(*) FROM inv_positions ' . $whereSql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR); }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getOne(int $id): array|bool
    {
        $stmt = $this->getConnection()->prepare('SELECT id, inv_stock_id, code, description FROM inv_positions WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int|bool
    {
        $stmt = $this->getConnection()->prepare('INSERT INTO inv_positions (inv_stock_id, code, description, created_at) VALUES (:stock, :code, :description, NOW())');
        $stmt->bindValue(':stock', (int)$data['inv_stock_id'], PDO::PARAM_INT);
        $stmt->bindValue(':code', $data['code']);
        $stmt->bindValue(':description', $data['description']);
        if ($stmt->execute()) { return (int)$this->getConnection()->lastInsertId(); }
        return false;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->getConnection()->prepare('UPDATE inv_positions SET inv_stock_id = :stock, code = :code, description = :description, updated_at = NOW() WHERE id = :id');
        $stmt->bindValue(':stock', (int)$data['inv_stock_id'], PDO::PARAM_INT);
        $stmt->bindValue(':code', $data['code']);
        $stmt->bindValue(':description', $data['description']);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->getConnection()->prepare('DELETE FROM inv_positions WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    public function getAllForSelectByStock(int $stockId): array
    {
        $sql = 'SELECT id, code, description FROM inv_positions WHERE inv_stock_id = :stock ORDER BY code ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':stock', $stockId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}


