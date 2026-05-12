<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
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
        if ($stmt->execute()) {
            $newId = (int) $this->getConnection()->lastInsertId();
            if ($newId > 0) {
                $row = $this->getRowById($newId);
                if (is_array($row)) {
                    $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                    LogAlteracaoService::registrarAlteracao(
                        'inv_stocks',
                        $newId,
                        $usuarioId,
                        'INSERT',
                        [],
                        $row
                    );
                }
            }

            return $newId;
        }

        return false;
    }

    public function update(int $id, array $data): bool
    {
        $oldRow = $this->getRowById($id);
        $sql = 'UPDATE inv_stocks SET name = :name, code = :code, adms_branch_id = :branch, active = :active, updated_at = NOW() WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':code', $data['code']);
        $stmt->bindValue(':branch', !empty($data['adms_branch_id']) ? (int)$data['adms_branch_id'] : null, !empty($data['adms_branch_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':active', isset($data['active']) ? (int)$data['active'] : 1, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && is_array($oldRow)) {
            $newRow = $this->getRowById($id);
            if (is_array($newRow)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'inv_stocks',
                    $id,
                    $usuarioId,
                    'UPDATE',
                    $oldRow,
                    $newRow
                );
            }
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        $oldRow = $this->getRowById($id);
        $stmt = $this->getConnection()->prepare('DELETE FROM inv_stocks WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && is_array($oldRow)) {
            $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'inv_stocks',
                $id,
                $usuarioId,
                'DELETE',
                $oldRow,
                []
            );
        }

        return $ok;
    }
    public function getAllForSelect(): array
    {
        $sql = 'SELECT id, name FROM inv_stocks WHERE active = 1 ORDER BY name ASC';
        $stmt = $this->getConnection()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRowById(int $id): ?array
    {
        $stmt = $this->getConnection()->prepare('SELECT * FROM inv_stocks WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }
}


