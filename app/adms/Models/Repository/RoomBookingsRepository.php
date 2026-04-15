<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Repository para gerenciar reservas de salas
 */
class RoomBookingsRepository extends DbConnection
{
    /**
     * Criar nova reserva
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_room_bookings 
                (room_id, user_id, title, description, start_datetime, end_datetime, status,
                 requires_approval, has_additional_requests, recurrence_series_id)
                VALUES 
                (:room_id, :user_id, :title, :description, :start_datetime, :end_datetime, :status,
                 :requires_approval, :has_additional_requests, :recurrence_series_id)";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':room_id', $data['room_id'], PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':start_datetime', $data['start_datetime']);
        $stmt->bindValue(':end_datetime', $data['end_datetime']);
        $stmt->bindValue(':status', $data['status'] ?? 'pending');
        $stmt->bindValue(':requires_approval', $data['requires_approval'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':has_additional_requests', $data['has_additional_requests'] ?? false, PDO::PARAM_BOOL);
        $rid = isset($data['recurrence_series_id']) ? trim((string) $data['recurrence_series_id']) : '';
        $stmt->bindValue(':recurrence_series_id', $rid !== '' ? $rid : null, $rid !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        
        $stmt->execute();
        
        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * Buscar por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT rb.*, 
                       mr.name as room_name, mr.location, mr.capacity,
                       u.name as user_name, u.email as user_email,
                       a.name as approver_name,
                       c.name as canceller_name
                FROM adms_room_bookings rb
                INNER JOIN adms_meeting_rooms mr ON rb.room_id = mr.id
                INNER JOIN adms_users u ON rb.user_id = u.id
                LEFT JOIN adms_users a ON rb.approved_by = a.id
                LEFT JOIN adms_users c ON rb.cancelled_by = c.id
                WHERE rb.id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Listar reservas com filtros
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['room_id'])) {
            $where[] = "rb.room_id = :room_id";
            $params[':room_id'] = $filters['room_id'];
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = "rb.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "rb.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['start_date'])) {
            $where[] = "DATE(rb.start_datetime) >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $where[] = "DATE(rb.end_datetime) <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        
        // Super admin vê tudo, usuário comum vê apenas suas reservas
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$isSuperAdmin && empty($filters['user_id'])) {
            $where[] = "rb.user_id = :current_user_id";
            $params[':current_user_id'] = $userId;
        }
        
        $sql = "SELECT rb.*, 
                       mr.name as room_name, mr.location,
                       u.name as user_name
                FROM adms_room_bookings rb
                INNER JOIN adms_meeting_rooms mr ON rb.room_id = mr.id
                INNER JOIN adms_users u ON rb.user_id = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY rb.start_datetime DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Contar total de reservas
     */
    public function count(array $filters = []): int
    {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['room_id'])) {
            $where[] = "room_id = :room_id";
            $params[':room_id'] = $filters['room_id'];
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = "user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['start_date'])) {
            $where[] = "DATE(start_datetime) >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $where[] = "DATE(end_datetime) <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$isSuperAdmin && empty($filters['user_id'])) {
            $where[] = "user_id = :current_user_id";
            $params[':current_user_id'] = $userId;
        }
        
        $sql = "SELECT COUNT(*) as total FROM adms_room_bookings WHERE " . implode(' AND ', $where);
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)($result['total'] ?? 0);
    }

    /**
     * Verificar conflito de horário
     */
    public function hasConflict(int $roomId, string $startDatetime, string $endDatetime, ?int $excludeBookingId = null): bool
    {
        $where = [
            "room_id = :room_id",
            "status IN ('pending', 'confirmed', 'in_progress')",
            "((start_datetime < :end_datetime AND end_datetime > :start_datetime))"
        ];
        
        $params = [
            ':room_id' => $roomId,
            ':start_datetime' => $startDatetime,
            ':end_datetime' => $endDatetime,
        ];
        
        if ($excludeBookingId !== null) {
            $where[] = "id != :exclude_id";
            $params[':exclude_id'] = $excludeBookingId;
        }
        
        $sql = "SELECT COUNT(*) as total 
                FROM adms_room_bookings 
                WHERE " . implode(' AND ', $where);
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)($result['total'] ?? 0) > 0;
    }

    /**
     * Atualizar reserva
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'room_id',
            'title', 'description', 'start_datetime', 'end_datetime', 'status',
            'requires_approval', 'approved_by', 'approved_at', 'cancelled_by',
            'cancelled_at', 'cancellation_reason', 'reminder_sent', 'reminder_sent_at',
            'has_additional_requests', 'recurrence_series_id',
        ];
        
        $updates = [];
        $params = [':id' => $id];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $updates[] = "updated_at = NOW()";
        
        $sql = "UPDATE adms_room_bookings 
                SET " . implode(', ', $updates) . "
                WHERE id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }

    /**
     * Deletar reserva
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM adms_room_bookings WHERE id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Buscar reservas futuras de uma sala
     */
    public function getFutureBookingsByRoom(int $roomId): array
    {
        $sql = "SELECT rb.*, 
                       mr.name as room_name,
                       u.name as user_name
                FROM adms_room_bookings rb
                INNER JOIN adms_meeting_rooms mr ON rb.room_id = mr.id
                INNER JOIN adms_users u ON rb.user_id = u.id
                WHERE rb.room_id = :room_id
                AND rb.start_datetime > NOW()
                AND rb.status IN ('pending', 'confirmed', 'in_progress')
                ORDER BY rb.start_datetime ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':room_id', $roomId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar participantes de uma reserva
     */
    public function getParticipantsByBookingId(int $bookingId): array
    {
        $sql = "SELECT bp.*,
                       COALESCE(u.name, bp.guest_name, '') AS user_name,
                       COALESCE(u.email, bp.guest_email, '') AS user_email
                FROM adms_booking_participants bp
                LEFT JOIN adms_users u ON bp.user_id = u.id
                WHERE bp.booking_id = :booking_id
                ORDER BY bp.is_organizer DESC, user_name ASC, bp.guest_email ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar reservas de uma sala em um período específico
     */
    public function getBookingsByRoomAndPeriod(int $roomId, string $startDate, string $endDate): array
    {
        $sql = "SELECT rb.*, 
                       u.name as user_name, u.email as user_email
                FROM adms_room_bookings rb
                INNER JOIN adms_users u ON rb.user_id = u.id
                WHERE rb.room_id = :room_id
                AND rb.status IN ('pending', 'confirmed', 'in_progress')
                AND (
                    (DATE(rb.start_datetime) BETWEEN :start_date AND :end_date)
                    OR (DATE(rb.end_datetime) BETWEEN :start_date AND :end_date)
                    OR (DATE(rb.start_datetime) <= :start_date AND DATE(rb.end_datetime) >= :end_date)
                )
                ORDER BY rb.start_datetime ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':room_id', $roomId, PDO::PARAM_INT);
        $stmt->bindValue(':start_date', $startDate);
        $stmt->bindValue(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ocorrências ativas da mesma série de recorrência.
     *
     * @return list<array<string, mixed>>
     */
    public function listActiveInRecurrenceSeries(string $seriesId): array
    {
        $seriesId = trim($seriesId);
        if ($seriesId === '') {
            return [];
        }
        $sql = 'SELECT rb.*, mr.name AS room_name
                FROM adms_room_bookings rb
                INNER JOIN adms_meeting_rooms mr ON rb.room_id = mr.id
                WHERE rb.recurrence_series_id = :sid
                  AND rb.status IN (\'pending\', \'confirmed\', \'in_progress\')
                ORDER BY rb.start_datetime ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':sid', $seriesId, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Última ocorrência ativa da série (por data de início).
     *
     * @return array<string, mixed>|null
     */
    public function getLastActiveInRecurrenceSeries(string $seriesId): ?array
    {
        $rows = $this->listActiveInRecurrenceSeries($seriesId);

        return $rows !== [] ? $rows[array_key_last($rows)] : null;
    }
}

