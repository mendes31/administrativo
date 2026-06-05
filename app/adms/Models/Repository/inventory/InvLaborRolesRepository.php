<?php

namespace App\adms\Models\Repository\inventory;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

class InvLaborRolesRepository extends DbConnection
{
    public function getAll(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $params = [];
        $wheres = [];
        if (!empty($filters['name'])) {
            $wheres[] = 'lr.name LIKE :name';
            $params[':name'] = '%' . $filters['name'] . '%';
        }
        if (isset($filters['active']) && $filters['active'] !== '') {
            $wheres[] = 'lr.active = :active';
            $params[':active'] = (int) $filters['active'];
        }
        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';

        $sql = 'SELECT lr.id, lr.code, lr.name, lr.default_cost_per_min, lr.adms_position_id, lr.active,
                       p.name AS position_name
                FROM inv_labor_roles lr
                LEFT JOIN adms_positions p ON p.id = lr.adms_position_id
                ' . $whereSql . '
                ORDER BY lr.name ASC
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
        if (!empty($filters['name'])) {
            $wheres[] = 'name LIKE :name';
            $params[':name'] = '%' . $filters['name'] . '%';
        }
        if (isset($filters['active']) && $filters['active'] !== '') {
            $wheres[] = 'active = :active';
            $params[':active'] = (int) $filters['active'];
        }
        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';
        $stmt = $this->getConnection()->prepare('SELECT COUNT(*) FROM inv_labor_roles ' . $whereSql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function getOne(int $id): array|bool
    {
        $stmt = $this->getConnection()->prepare('SELECT * FROM inv_labor_roles WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }

    public function getAllForSelect(): array
    {
        $stmt = $this->getConnection()->query(
            'SELECT id, code, name, default_cost_per_min FROM inv_labor_roles WHERE active = 1 ORDER BY name ASC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int|bool
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'INSERT INTO inv_labor_roles (code, name, default_cost_per_min, adms_position_id, active, created_at)
                 VALUES (:code, :name, :default_cost_per_min, :adms_position_id, :active, :created_at)'
            );
            $stmt->bindValue(':code', trim((string) ($data['code'] ?? '')) ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':name', trim((string) $data['name']));
            $stmt->bindValue(':default_cost_per_min', (float) ($data['default_cost_per_min'] ?? 0));
            $stmt->bindValue(':adms_position_id', !empty($data['adms_position_id']) ? (int) $data['adms_position_id'] : null, !empty($data['adms_position_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':active', isset($data['active']) ? (int) $data['active'] : 1, PDO::PARAM_INT);
            $stmt->bindValue(':created_at', date('Y-m-d H:i:s'));
            $stmt->execute();
            $newId = (int) $this->getConnection()->lastInsertId();
            if ($newId > 0) {
                $row = $this->getOne($newId);
                if (is_array($row)) {
                    LogAlteracaoService::registrarAlteracao('inv_labor_roles', $newId, (int) ($_SESSION['user_id'] ?? 1), 'INSERT', [], $row);
                }
            }

            return $newId;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Falha ao criar papel de MO', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            $oldRow = $this->getOne($id);
            $stmt = $this->getConnection()->prepare(
                'UPDATE inv_labor_roles SET code = :code, name = :name, default_cost_per_min = :default_cost_per_min,
                 adms_position_id = :adms_position_id, active = :active, updated_at = :updated_at WHERE id = :id'
            );
            $stmt->bindValue(':code', trim((string) ($data['code'] ?? '')) ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':name', trim((string) $data['name']));
            $stmt->bindValue(':default_cost_per_min', (float) ($data['default_cost_per_min'] ?? 0));
            $stmt->bindValue(':adms_position_id', !empty($data['adms_position_id']) ? (int) $data['adms_position_id'] : null, !empty($data['adms_position_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':active', isset($data['active']) ? (int) $data['active'] : 1, PDO::PARAM_INT);
            $stmt->bindValue(':updated_at', date('Y-m-d H:i:s'));
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $ok = $stmt->execute();
            if ($ok && is_array($oldRow)) {
                $newRow = $this->getOne($id);
                if (is_array($newRow)) {
                    LogAlteracaoService::registrarAlteracao('inv_labor_roles', $id, (int) ($_SESSION['user_id'] ?? 1), 'UPDATE', $oldRow, $newRow);
                }
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Falha ao atualizar papel de MO', ['id' => $id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $oldRow = $this->getOne($id);
            $stmt = $this->getConnection()->prepare('DELETE FROM inv_labor_roles WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $ok = $stmt->execute();
            if ($ok && is_array($oldRow)) {
                LogAlteracaoService::registrarAlteracao('inv_labor_roles', $id, (int) ($_SESSION['user_id'] ?? 1), 'DELETE', $oldRow, []);
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Falha ao excluir papel de MO', ['id' => $id, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
