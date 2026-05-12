<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

class InvOperationsRepository extends DbConnection
{
    public function getAll(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $params = [];
        $wheres = [];

        if (!empty($filters['code'])) {
            $wheres[] = 'code LIKE :code';
            $params[':code'] = '%' . $filters['code'] . '%';
        }
        if (!empty($filters['name'])) {
            $wheres[] = 'name LIKE :name';
            $params[':name'] = '%' . $filters['name'] . '%';
        }
        if (isset($filters['active']) && $filters['active'] !== '') {
            $wheres[] = 'active = :active';
            $params[':active'] = (int)$filters['active'];
        }
        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';

        $sql = 'SELECT id, code, name, default_cost_per_hour, active 
                FROM inv_operations ' . $whereSql . '
                ORDER BY name ASC
                LIMIT :limit OFFSET :offset';

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countAll(array $filters = []): int
    {
        $params = [];
        $wheres = [];
        if (!empty($filters['code'])) {
            $wheres[] = 'code LIKE :code';
            $params[':code'] = '%' . $filters['code'] . '%';
        }
        if (!empty($filters['name'])) {
            $wheres[] = 'name LIKE :name';
            $params[':name'] = '%' . $filters['name'] . '%';
        }
        if (isset($filters['active']) && $filters['active'] !== '') {
            $wheres[] = 'active = :active';
            $params[':active'] = (int)$filters['active'];
        }
        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';
        $sql = 'SELECT COUNT(*) AS total FROM inv_operations ' . $whereSql;
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function getOne(int $id): array|bool
    {
        $sql = 'SELECT * FROM inv_operations WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }

    public function getAllForSelect(): array
    {
        $sql = 'SELECT id, name FROM inv_operations WHERE active = 1 ORDER BY name ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function existsCode(?string $code, ?int $excludeId = null): bool
    {
        if ($code === null || $code === '') {
            return false;
        }
        $sql = 'SELECT id FROM inv_operations WHERE code = :code' .
               ($excludeId ? ' AND id <> :id' : '') .
               ' LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':code', $code);
        if ($excludeId) {
            $stmt->bindValue(':id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    }

    public function create(array $data): int|bool
    {
        try {
            $sql = 'INSERT INTO inv_operations (code, name, description, default_cost_per_hour, active, created_at)
                    VALUES (:code, :name, :description, :default_cost_per_hour, :active, :created_at)';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':code', $data['code'] ?: null, $data['code'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':default_cost_per_hour', $data['default_cost_per_hour'] ?? 0);
            $stmt->bindValue(':active', isset($data['active']) ? (int)$data['active'] : 1, PDO::PARAM_INT);
            $stmt->bindValue(':created_at', date('Y-m-d H:i:s'));
            $stmt->execute();
            $newId = (int) $this->getConnection()->lastInsertId();
            if ($newId > 0) {
                $row = $this->getOne($newId);
                if (is_array($row)) {
                    $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                    LogAlteracaoService::registrarAlteracao(
                        'inv_operations',
                        $newId,
                        $usuarioId,
                        'INSERT',
                        [],
                        $row
                    );
                }
            }

            return $newId;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Falha ao criar operação de estoque', [
                'error' => $e->getMessage(),
                'code' => $data['code'] ?? '',
                'name' => $data['name'] ?? '',
            ]);
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        $oldRow = $this->getOne($id);
        try {
            $sql = 'UPDATE inv_operations 
                    SET code = :code, name = :name, description = :description, 
                        default_cost_per_hour = :default_cost_per_hour, active = :active, updated_at = :updated_at
                    WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':code', $data['code'] ?: null, $data['code'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':default_cost_per_hour', $data['default_cost_per_hour'] ?? 0);
            $stmt->bindValue(':active', isset($data['active']) ? (int)$data['active'] : 1, PDO::PARAM_INT);
            $stmt->bindValue(':updated_at', date('Y-m-d H:i:s'));
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $ok = $stmt->execute();
            if ($ok && is_array($oldRow)) {
                $newRow = $this->getOne($id);
                if (is_array($newRow)) {
                    $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                    LogAlteracaoService::registrarAlteracao(
                        'inv_operations',
                        $id,
                        $usuarioId,
                        'UPDATE',
                        $oldRow,
                        $newRow
                    );
                }
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Falha ao atualizar operação de estoque', [
                'error' => $e->getMessage(),
                'id' => $id,
            ]);
            return false;
        }
    }

    public function delete(int $id): bool
    {
        $oldRow = $this->getOne($id);
        try {
            $stmt = $this->getConnection()->prepare('DELETE FROM inv_operations WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $ok = $stmt->execute();
            if ($ok && is_array($oldRow)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'inv_operations',
                    $id,
                    $usuarioId,
                    'DELETE',
                    $oldRow,
                    []
                );
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Falha ao excluir operação de estoque', [
                'error' => $e->getMessage(),
                'id' => $id,
            ]);
            return false;
        }
    }
}

