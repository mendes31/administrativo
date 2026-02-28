<?php

namespace App\adms\Models\Repository\projects;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

class ProjStagesRepository extends DbConnection
{
    public function getAll(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $limit);

        $where = [];
        $params = [];

        if (!empty($filters['name'])) {
            $where[] = 's.name LIKE :name';
            $params[':name'] = '%' . $filters['name'] . '%';
        }
        if ($filters['is_cost_stage'] !== '' && $filters['is_cost_stage'] !== null) {
            $where[] = 's.is_cost_stage = :is_cost_stage';
            $params[':is_cost_stage'] = (int)$filters['is_cost_stage'];
        }
        if ($filters['active'] !== '' && $filters['active'] !== null) {
            $where[] = 's.active = :active';
            $params[':active'] = (int)$filters['active'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT s.id, s.name, s.description, s.sequence_default, s.is_cost_stage, s.active, s.created_at, s.updated_at
                FROM proj_stages s
                {$whereSql}
                ORDER BY s.sequence_default ASC, s.name ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'data' => $data,
            'total' => $this->countAll($filters),
        ];
    }

    public function countAll(array $filters = []): int
    {
        $where = [];
        $params = [];

        if (!empty($filters['name'])) {
            $where[] = 'name LIKE :name';
            $params[':name'] = '%' . $filters['name'] . '%';
        }
        if ($filters['is_cost_stage'] !== '' && $filters['is_cost_stage'] !== null) {
            $where[] = 'is_cost_stage = :is_cost_stage';
            $params[':is_cost_stage'] = (int)$filters['is_cost_stage'];
        }
        if ($filters['active'] !== '' && $filters['active'] !== null) {
            $where[] = 'active = :active';
            $params[':active'] = (int)$filters['active'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT COUNT(*) AS total FROM proj_stages {$whereSql}";

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
        $sql = "SELECT id, name, description, sequence_default, is_cost_stage, active
                FROM proj_stages
                WHERE id = :id
                LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }

    public function create(array $data): int|bool
    {
        try {
            $sql = "INSERT INTO proj_stages (name, description, sequence_default, is_cost_stage, active, created_at)
                    VALUES (:name, :description, :sequence_default, :is_cost_stage, :active, :created_at)";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':sequence_default', (int)($data['sequence_default'] ?? 1), PDO::PARAM_INT);
            $stmt->bindValue(':is_cost_stage', isset($data['is_cost_stage']) ? (int)$data['is_cost_stage'] : 0, PDO::PARAM_INT);
            $stmt->bindValue(':active', isset($data['active']) ? (int)$data['active'] : 1, PDO::PARAM_INT);
            $stmt->bindValue(':created_at', date('Y-m-d H:i:s'));
            $stmt->execute();

            return (int)$this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Falha ao criar etapa de projeto', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            $sql = "UPDATE proj_stages
                       SET name = :name,
                           description = :description,
                           sequence_default = :sequence_default,
                           is_cost_stage = :is_cost_stage,
                           active = :active,
                           updated_at = :updated_at
                     WHERE id = :id";

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':sequence_default', (int)($data['sequence_default'] ?? 1), PDO::PARAM_INT);
            $stmt->bindValue(':is_cost_stage', isset($data['is_cost_stage']) ? (int)$data['is_cost_stage'] : 0, PDO::PARAM_INT);
            $stmt->bindValue(':active', isset($data['active']) ? (int)$data['active'] : 0, PDO::PARAM_INT);
            $stmt->bindValue(':updated_at', date('Y-m-d H:i:s'));
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Falha ao atualizar etapa de projeto', ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM proj_stages WHERE id = :id LIMIT 1";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Falha ao apagar etapa de projeto', ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function getAllForSelect(): array
    {
        $sql = "SELECT id, name, is_cost_stage
                FROM proj_stages
                WHERE active = 1
                ORDER BY sequence_default ASC, name ASC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Retorna mapa id => name para os IDs informados.
     *
     * @param int[] $ids
     * @return array<int, string>
     */
    public function getNamesByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $ids = array_map('intval', array_filter($ids));
        if (empty($ids)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id, name FROM proj_stages WHERE id IN ($placeholders)";
        $stmt = $this->getConnection()->prepare($sql);
        foreach (array_values($ids) as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['id']] = $row['name'] ?? '';
        }
        return $map;
    }
}

