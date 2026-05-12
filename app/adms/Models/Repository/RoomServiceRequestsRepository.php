<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

/**
 * Solicitações de serviço do módulo de salas — com ou sem reserva vinculada.
 *
 * Tabela: adms_room_service_requests (booking_id NULL = avulsa)
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

        if (!empty($filters['requester_or_booking_organizer_user_id'])) {
            $ro = (int) $filters['requester_or_booking_organizer_user_id'];
            if ($ro > 0) {
                $where[] = '(sr.requester_user_id = :robo_u1 OR EXISTS (SELECT 1 FROM adms_room_bookings b WHERE b.id = sr.booking_id AND b.user_id = :robo_u2))';
                $params[':robo_u1'] = $ro;
                $params[':robo_u2'] = $ro;
            }
        }

        if (isset($filters['has_booking'])) {
            if ($filters['has_booking'] === true || $filters['has_booking'] === '1' || $filters['has_booking'] === 1) {
                $where[] = 'sr.booking_id IS NOT NULL';
            } elseif ($filters['has_booking'] === false || $filters['has_booking'] === '0' || $filters['has_booking'] === 0) {
                $where[] = 'sr.booking_id IS NULL';
            }
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

        if (!empty($filters['requester_or_booking_organizer_user_id'])) {
            $ro = (int) $filters['requester_or_booking_organizer_user_id'];
            if ($ro > 0) {
                $where[] = '(sr.requester_user_id = :robo_u1 OR EXISTS (SELECT 1 FROM adms_room_bookings b WHERE b.id = sr.booking_id AND b.user_id = :robo_u2))';
                $params[':robo_u1'] = $ro;
                $params[':robo_u2'] = $ro;
            }
        }

        if (isset($filters['has_booking'])) {
            if ($filters['has_booking'] === true || $filters['has_booking'] === '1' || $filters['has_booking'] === 1) {
                $where[] = 'sr.booking_id IS NOT NULL';
            } elseif ($filters['has_booking'] === false || $filters['has_booking'] === '0' || $filters['has_booking'] === 0) {
                $where[] = 'sr.booking_id IS NULL';
            }
        }

        $sql = "SELECT sr.*,
                       rt.code AS request_type_code,
                       rt.name AS request_type_name,
                       rg.name AS responsible_group_name,
                       u.name AS requester_name,
                       cu.name AS claimed_by_name,
                       rb.title AS booking_title,
                       mr.name AS booking_room_name
                FROM adms_room_service_requests sr
                INNER JOIN adms_room_request_types rt ON sr.request_type_id = rt.id
                INNER JOIN adms_users u ON sr.requester_user_id = u.id
                LEFT JOIN adms_room_request_groups rg ON sr.responsible_group_id = rg.id
                LEFT JOIN adms_users cu ON sr.claimed_by_user_id = cu.id
                LEFT JOIN adms_room_bookings rb ON sr.booking_id = rb.id
                LEFT JOIN adms_meeting_rooms mr ON rb.room_id = mr.id";

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
                       cu.name AS claimed_by_name,
                       rb.title AS booking_title,
                       rb.user_id AS booking_organizer_user_id,
                       mr.name AS booking_room_name
                FROM adms_room_service_requests sr
                INNER JOIN adms_room_request_types rt ON sr.request_type_id = rt.id
                INNER JOIN adms_users u ON sr.requester_user_id = u.id
                LEFT JOIN adms_room_request_groups rg ON sr.responsible_group_id = rg.id
                LEFT JOIN adms_users cu ON sr.claimed_by_user_id = cu.id
                LEFT JOIN adms_room_bookings rb ON sr.booking_id = rb.id
                LEFT JOIN adms_meeting_rooms mr ON rb.room_id = mr.id
                WHERE sr.id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_room_service_requests
                (requester_user_id, booking_id, request_type_id, request_description, quantity, status,
                 service_date, start_time, end_time, location, priority,
                 responsible_group_id, created_at, updated_at)
                VALUES
                (:requester_user_id, :booking_id, :request_type_id, :request_description, :quantity, :status,
                 :service_date, :start_time, :end_time, :location, :priority,
                 :responsible_group_id, NOW(), NOW())";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':requester_user_id', (int)$data['requester_user_id'], PDO::PARAM_INT);
        $bookingId = isset($data['booking_id']) && $data['booking_id'] !== '' && $data['booking_id'] !== null
            ? (int)$data['booking_id'] : null;
        if ($bookingId !== null && $bookingId > 0) {
            $stmt->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':booking_id', null, PDO::PARAM_NULL);
        }
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

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $newData = $this->getById($newId);
            if (is_array($newData)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_room_service_requests',
                    $newId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $newData
                );
            }
        }

        return $newId;
    }

    /**
     * Solicitações de serviço vinculadas a uma reserva.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getByBookingId(int $bookingId): array
    {
        $sql = "SELECT sr.*,
                       rt.code AS request_type_code,
                       rt.name AS request_type_name,
                       rg.name AS responsible_group_name,
                       u.name AS requester_name,
                       cu.name AS claimed_by_name,
                       rb.title AS booking_title,
                       mr.name AS booking_room_name
                FROM adms_room_service_requests sr
                INNER JOIN adms_room_request_types rt ON sr.request_type_id = rt.id
                INNER JOIN adms_users u ON sr.requester_user_id = u.id
                LEFT JOIN adms_room_request_groups rg ON sr.responsible_group_id = rg.id
                LEFT JOIN adms_users cu ON sr.claimed_by_user_id = cu.id
                INNER JOIN adms_room_bookings rb ON sr.booking_id = rb.id
                INNER JOIN adms_meeting_rooms mr ON rb.room_id = mr.id
                WHERE sr.booking_id = :booking_id
                ORDER BY sr.created_at ASC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function update(int $id, array $data): bool
    {
        $allowed = [
            'booking_id',
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

        $oldData = $this->getById($id);

        $sets[] = 'updated_at = NOW()';

        $sql = "UPDATE adms_room_service_requests
                SET " . implode(', ', $sets) . "
                WHERE id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        $ok = $stmt->execute();
        if ($ok && $stmt->rowCount() > 0 && is_array($oldData)) {
            $newData = $this->getById($id);
            if (is_array($newData)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_room_service_requests',
                    $id,
                    $usuarioId,
                    'UPDATE',
                    $oldData,
                    $newData
                );
            }
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $sql = "DELETE FROM adms_room_service_requests WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deleted = $stmt->rowCount() > 0;
        if ($deleted && is_array($oldData)) {
            $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'adms_room_service_requests',
                $id,
                $usuarioId,
                'DELETE',
                $oldData,
                []
            );
        }

        return $deleted;
    }
}

