<?php

namespace App\adms\Models\Repository\projects;

use App\adms\Models\Services\DbConnection;
use App\adms\Helpers\GenerateLog;
use PDO;
use Exception;

class ProjProjectsRepository extends DbConnection
{
    public function getAll(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $limit);

        $where = [];
        $params = [];

        if (!empty($filters['name'])) {
            $where[] = 'p.name LIKE :name';
            $params[':name'] = '%' . $filters['name'] . '%';
        }
        if (!empty($filters['type'])) {
            $where[] = 'p.type = :type';
            $params[':type'] = $filters['type'];
        }
        if ($filters['status'] !== '' && $filters['status'] !== null) {
            $where[] = 'p.status = :status';
            $params[':status'] = $filters['status'];
        }
        if ($filters['active'] !== '' && $filters['active'] !== null) {
            $where[] = 'p.active = :active';
            $params[':active'] = (int)$filters['active'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT 
                    p.id,
                    p.name,
                    p.type,
                    p.status,
                    p.start_date,
                    p.expected_end_date,
                    p.end_date,
                    p.percent_complete,
                    p.open_activities,
                    p.pn_code,
                    p.pn_name,
                    p.active,
                    u.name AS owner_name
                FROM proj_projects p
                LEFT JOIN adms_users u ON u.id = p.owner_user_id
                {$whereSql}
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
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
        if (!empty($filters['type'])) {
            $where[] = 'type = :type';
            $params[':type'] = $filters['type'];
        }
        if ($filters['status'] !== '' && $filters['status'] !== null) {
            $where[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }
        if ($filters['active'] !== '' && $filters['active'] !== null) {
            $where[] = 'active = :active';
            $params[':active'] = (int)$filters['active'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT COUNT(*) AS total FROM proj_projects {$whereSql}";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function getOne(int $id): array|bool
    {
        $sql = "SELECT * FROM proj_projects WHERE id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }

    public function create(array $data): int|bool
    {
        try {
            $sql = "INSERT INTO proj_projects 
                        (type, name, status, start_date, expected_end_date, end_date, open_activities, percent_complete,
                         pn_id, pn_code, pn_name, contact_user_id, owner_user_id, description, active, created_at)
                    VALUES
                        (:type, :name, :status, :start_date, :expected_end_date, :end_date, :open_activities, :percent_complete,
                         :pn_id, :pn_code, :pn_name, :contact_user_id, :owner_user_id, :description, :active, :created_at)";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':type', $data['type'] ?? 'INTERNAL');
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':status', $data['status'] ?? 'INICIADO');
            $stmt->bindValue(':start_date', $data['start_date'] ?? null);
            $stmt->bindValue(':expected_end_date', $data['expected_end_date'] ?? null);
            $stmt->bindValue(':end_date', $data['end_date'] ?? null);
            $stmt->bindValue(':open_activities', (int)($data['open_activities'] ?? 0), PDO::PARAM_INT);
            $stmt->bindValue(':percent_complete', (float)($data['percent_complete'] ?? 0));
            $stmt->bindValue(':pn_id', $data['pn_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':pn_code', $data['pn_code'] ?? null);
            $stmt->bindValue(':pn_name', $data['pn_name'] ?? null);
            $stmt->bindValue(':contact_user_id', $data['contact_user_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':owner_user_id', $data['owner_user_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':active', isset($data['active']) ? (int)$data['active'] : 1, PDO::PARAM_INT);
            $stmt->bindValue(':created_at', date('Y-m-d H:i:s'));
            $stmt->execute();

            return (int)$this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Falha ao criar projeto', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            $sql = "UPDATE proj_projects
                       SET type = :type,
                           name = :name,
                           status = :status,
                           start_date = :start_date,
                           expected_end_date = :expected_end_date,
                           end_date = :end_date,
                           open_activities = :open_activities,
                           percent_complete = :percent_complete,
                           pn_id = :pn_id,
                           pn_code = :pn_code,
                           pn_name = :pn_name,
                           contact_user_id = :contact_user_id,
                           owner_user_id = :owner_user_id,
                           description = :description,
                           active = :active,
                           updated_at = :updated_at
                     WHERE id = :id";

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':type', $data['type'] ?? 'INTERNAL');
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':status', $data['status'] ?? 'INICIADO');
            $stmt->bindValue(':start_date', $data['start_date'] ?? null);
            $stmt->bindValue(':expected_end_date', $data['expected_end_date'] ?? null);
            $stmt->bindValue(':end_date', $data['end_date'] ?? null);
            $stmt->bindValue(':open_activities', (int)($data['open_activities'] ?? 0), PDO::PARAM_INT);
            $stmt->bindValue(':percent_complete', (float)($data['percent_complete'] ?? 0));
            $stmt->bindValue(':pn_id', $data['pn_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':pn_code', $data['pn_code'] ?? null);
            $stmt->bindValue(':pn_name', $data['pn_name'] ?? null);
            $stmt->bindValue(':contact_user_id', $data['contact_user_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':owner_user_id', $data['owner_user_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':active', isset($data['active']) ? (int)$data['active'] : 1, PDO::PARAM_INT);
            $stmt->bindValue(':updated_at', date('Y-m-d H:i:s'));
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Falha ao atualizar projeto', ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM proj_projects WHERE id = :id LIMIT 1";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Falha ao apagar projeto', ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }
}

