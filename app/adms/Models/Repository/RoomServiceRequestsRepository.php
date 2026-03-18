<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Solicitações avulsas (sem reserva) - Reserva de Salas
 *
 * Tabela: adms_room_service_requests
 */
class RoomServiceRequestsRepository extends DbConnection
{
    public function count(array $filters = []): int
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'sr.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['responsible_group_id'])) {
            $where[] = 'sr.responsible_group_id = :responsible_group_id';
            $params[':responsible_group_id'] = (int)$filters['responsible_group_id'];
        }

        if (!empty($filters['requester_user_id'])) {
            $where[] = 'sr.requester_user_id = :requester_user_id';
            $params[':requester_user_id'] = (int)$filters['requester_user_id'];
        }

        $sql = "SELECT COUNT(*) AS total
                FROM adms_room_service_requests sr";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);

        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'sr.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['responsible_group_id'])) {
            $where[] = 'sr.responsible_group_id = :responsible_group_id';
            $params[':responsible_group_id'] = (int)$filters['responsible_group_id'];
        }

        if (!empty($filters['requester_user_id'])) {
            $where[] = 'sr.requester_user_id = :requester_user_id';
            $params[':requester_user_id'] = (int)$filters['requester_user_id'];
        }

        $sql = "SELECT sr.*,
                       rt.code AS request_type_code,
                       rt.name AS request_type_name,
                       rg.name AS responsible_group_name,
                       u.name AS requester_name,
                       cu.name AS claimed_by_name
                FROM adms_room_service_requests sr
                INNER JOIN adms_room_request_types rt ON sr.request_type_id = rt.id
                INNER JOIN adms_users u ON sr.requester_user_id = u.id
                LEFT JOIN adms_room_request_groups rg ON sr.responsible_group_id = rg.id
                LEFT JOIN adms_users cu ON sr.claimed_by_user_id = cu.id";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY sr.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT sr.*,
                       rt.code AS request_type_code,
                       rt.name AS request_type_name,
                       rg.name AS responsible_group_name,
                       u.name AS requester_name,
                       u.email AS requester_email,
                       cu.name AS claimed_by_name
                FROM adms_room_service_requests sr
                INNER JOIN adms_room_request_types rt ON sr.request_type_id = rt.id
                INNER JOIN adms_users u ON sr.requester_user_id = u.id
                LEFT JOIN adms_room_request_groups rg ON sr.responsible_group_id = rg.id
                LEFT JOIN adms_users cu ON sr.claimed_by_user_id = cu.id
                WHERE sr.id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_room_service_requests
                (requester_user_id, request_type_id, request_description, quantity, status,
                 service_date, start_time, end_time, location, priority,
                 responsible_group_id, created_at, updated_at)
                VALUES
                (:requester_user_id, :request_type_id, :request_description, :quantity, :status,
                 :service_date, :start_time, :end_time, :location, :priority,
                 :responsible_group_id, NOW(), NOW())";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':requester_user_id', (int)$data['requester_user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':request_type_id', (int)$data['request_type_id'], PDO::PARAM_INT);
        $stmt->bindValue(':request_description', $data['request_description'] ?? null);
        $stmt->bindValue(':quantity', $data['quantity'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':status', $data['status'] ?? 'pending');
        $stmt->bindValue(':service_date', $data['service_date']);
        $stmt->bindValue(':start_time', $data['start_time']);
        $stmt->bindValue(':end_time', $data['end_time'] ?? null);
        $stmt->bindValue(':location', $data['location']);
        $stmt->bindValue(':priority', $data['priority'] ?? 'normal');
        $stmt->bindValue(':responsible_group_id', $data['responsible_group_id'] ?? null, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$this->getConnection()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = [
            'request_type_id',
            'request_description',
            'quantity',
            'status',
            'service_date',
            'start_time',
            'end_time',
            'location',
            'priority',
            'responsible_group_id',
            'claimed_by_user_id',
            'claimed_at',
        ];

        $sets = [];
        $params = [':id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $placeholder = ':' . $field;
                $sets[] = "$field = $placeholder";
                $params[$placeholder] = $data[$field];
            }
        }

        if (!$sets) {
            return false;
        }

        $sets[] = 'updated_at = NOW()';

        $sql = "UPDATE adms_room_service_requests
                SET " . implode(', ', $sets) . "
                WHERE id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM adms_room_service_requests WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}

