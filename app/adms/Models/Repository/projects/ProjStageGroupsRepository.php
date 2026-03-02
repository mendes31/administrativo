<?php

namespace App\adms\Models\Repository\projects;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

class ProjStageGroupsRepository extends DbConnection
{
    public function getAll(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $limit);

        $where = [];
        $params = [];

        if (!empty($filters['name'])) {
            $where[] = 'g.name LIKE :name';
            $params[':name'] = '%' . $filters['name'] . '%';
        }
        if ($filters['active'] !== '' && $filters['active'] !== null) {
            $where[] = 'g.active = :active';
            $params[':active'] = (int)$filters['active'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT
                    g.id,
                    g.name,
                    g.description,
                    g.active,
                    g.created_at,
                    g.updated_at,
                    COUNT(i.id) AS stages_count
                FROM proj_stage_groups g
                LEFT JOIN proj_stage_group_items i
                    ON i.stage_group_id = g.id
                {$whereSql}
                GROUP BY g.id, g.name, g.description, g.active, g.created_at, g.updated_at
                ORDER BY g.name ASC
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
        if ($filters['active'] !== '' && $filters['active'] !== null) {
            $where[] = 'active = :active';
            $params[':active'] = (int)$filters['active'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT COUNT(*) AS total FROM proj_stage_groups {$whereSql}";

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
        $sql = "SELECT id, name, description, active
                FROM proj_stage_groups
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
            $sql = "INSERT INTO proj_stage_groups (name, description, active, created_at)
                    VALUES (:name, :description, :active, :created_at)";

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':active', isset($data['active']) ? (int)$data['active'] : 1, PDO::PARAM_INT);
            $stmt->bindValue(':created_at', date('Y-m-d H:i:s'));
            $stmt->execute();

            return (int)$this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Falha ao criar grupo de etapas de projeto', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            $sql = "UPDATE proj_stage_groups
                       SET name = :name,
                           description = :description,
                           active = :active,
                           updated_at = :updated_at
                     WHERE id = :id";

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':active', isset($data['active']) ? (int)$data['active'] : 0, PDO::PARAM_INT);
            $stmt->bindValue(':updated_at', date('Y-m-d H:i:s'));
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Falha ao atualizar grupo de etapas de projeto', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM proj_stage_groups WHERE id = :id LIMIT 1";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Falha ao apagar grupo de etapas de projeto', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Retorna grupos de etapas ativos para selects.
     *
     * @return array<int, array{id:int,name:string}>
     */
    public function getAllForSelect(): array
    {
        $sql = "SELECT id, name
                FROM proj_stage_groups
                WHERE active = 1
                ORDER BY name ASC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Retorna as etapas de um grupo.
     *
     * @param int $groupId
     * @return array
     */
    public function getItemsByGroup(int $groupId): array
    {
        if ($groupId <= 0) {
            return [];
        }

        $sql = "SELECT
                    i.id,
                    i.stage_group_id,
                    i.stage_id,
                    i.sequence,
                    s.name AS stage_name,
                    s.is_cost_stage
                FROM proj_stage_group_items i
                INNER JOIN proj_stages s
                    ON s.id = i.stage_id
                WHERE i.stage_group_id = :group_id
                ORDER BY i.sequence ASC, i.id ASC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Substitui os itens (etapas) de um grupo pela lista informada.
     *
     * Cada linha do array deve conter:
     *  - stage_id (obrigatório)
     *  - sequence (opcional, padrão 1..n)
     *
     * @param int   $groupId
     * @param array $lines
     * @return bool
     */
    public function replaceItemsForGroup(int $groupId, array $lines): bool
    {
        if ($groupId <= 0) {
            return false;
        }

        $conn = $this->getConnection();

        try {
            $conn->beginTransaction();

            $stmtDelete = $conn->prepare('DELETE FROM proj_stage_group_items WHERE stage_group_id = :group_id');
            $stmtDelete->bindValue(':group_id', $groupId, PDO::PARAM_INT);
            $stmtDelete->execute();

            if (!empty($lines)) {
                $sqlInsert = "INSERT INTO proj_stage_group_items (
                                stage_group_id,
                                stage_id,
                                sequence,
                                created_at
                              ) VALUES (
                                :group_id,
                                :stage_id,
                                :sequence,
                                :created_at
                              )";

                $stmtInsert = $conn->prepare($sqlInsert);

                $position = 0;
                foreach ($lines as $line) {
                    $stageId = (int)($line['stage_id'] ?? 0);
                    if ($stageId <= 0) {
                        continue;
                    }

                    $sequence = (int)($line['sequence'] ?? 0);
                    if ($sequence <= 0) {
                        $sequence = ++$position;
                    }

                    $stmtInsert->bindValue(':group_id', $groupId, PDO::PARAM_INT);
                    $stmtInsert->bindValue(':stage_id', $stageId, PDO::PARAM_INT);
                    $stmtInsert->bindValue(':sequence', $sequence, PDO::PARAM_INT);
                    $stmtInsert->bindValue(':created_at', date('Y-m-d H:i:s'));

                    $stmtInsert->execute();

                    if ($position === 0) {
                        $position = $sequence;
                    }
                }
            }

            $conn->commit();
            return true;
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            GenerateLog::generateLog('error', 'Falha ao salvar itens do grupo de etapas', [
                'group_id' => $groupId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

