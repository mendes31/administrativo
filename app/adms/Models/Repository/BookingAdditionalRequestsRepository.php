<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Repository para gerenciar solicitações adicionais nas reservas
 */
class BookingAdditionalRequestsRepository extends DbConnection
{
    /**
     * Criar nova solicitação
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_booking_additional_requests 
                (booking_id, request_type, request_description, quantity, responsible_user_id, status)
                VALUES 
                (:booking_id, :request_type, :request_description, :quantity, :responsible_user_id, :status)";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':booking_id', $data['booking_id'], PDO::PARAM_INT);
        $stmt->bindValue(':request_type', $data['request_type']);
        $stmt->bindValue(':request_description', $data['request_description']);
        $stmt->bindValue(':quantity', $data['quantity'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':responsible_user_id', $data['responsible_user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':status', $data['status'] ?? 'pending');
        
        $stmt->execute();
        
        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * Buscar por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT bar.*, 
                       rb.title as booking_title, rb.start_datetime, rb.end_datetime,
                       mr.name as room_name,
                       u.name as responsible_name, u.email as responsible_email,
                       a.name as attended_by_name
                FROM adms_booking_additional_requests bar
                INNER JOIN adms_room_bookings rb ON bar.booking_id = rb.id
                INNER JOIN adms_meeting_rooms mr ON rb.room_id = mr.id
                INNER JOIN adms_users u ON bar.responsible_user_id = u.id
                LEFT JOIN adms_users a ON bar.attended_by = a.id
                WHERE bar.id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Buscar por booking_id
     */
    public function getByBookingId(int $bookingId): array
    {
        $sql = "SELECT bar.*, 
                       u.name as responsible_name, u.email as responsible_email
                FROM adms_booking_additional_requests bar
                INNER JOIN adms_users u ON bar.responsible_user_id = u.id
                WHERE bar.booking_id = :booking_id
                ORDER BY bar.created_at ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar por responsible_user_id
     */
    public function getByResponsibleUserId(int $userId, array $filters = []): array
    {
        $where = ["bar.responsible_user_id = :user_id"];
        $params = [':user_id' => $userId];
        
        if (!empty($filters['status'])) {
            $where[] = "bar.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        $sql = "SELECT bar.*, 
                       rb.title as booking_title, rb.start_datetime, rb.end_datetime,
                       mr.name as room_name,
                       u.name as requester_name
                FROM adms_booking_additional_requests bar
                INNER JOIN adms_room_bookings rb ON bar.booking_id = rb.id
                INNER JOIN adms_meeting_rooms mr ON rb.room_id = mr.id
                INNER JOIN adms_users u ON rb.user_id = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY rb.start_datetime ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualizar solicitação
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'request_type', 'request_description', 'quantity', 'responsible_user_id',
            'status', 'attended_at', 'attended_by', 'notes', 'notification_sent',
            'notification_sent_at'
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
        
        $sql = "UPDATE adms_booking_additional_requests 
                SET " . implode(', ', $updates) . "
                WHERE id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }

    /**
     * Deletar solicitação
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM adms_booking_additional_requests WHERE id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Deletar todas as solicitações de uma reserva
     */
    public function deleteByBookingId(int $bookingId): bool
    {
        $sql = "DELETE FROM adms_booking_additional_requests WHERE booking_id = :booking_id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
}

