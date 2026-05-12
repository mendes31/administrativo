<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
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
        if (($data['quantity'] ?? null) === null || $data['quantity'] === '') {
            $stmt->bindValue(':quantity', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':quantity', (int)$data['quantity'], PDO::PARAM_INT);
        }
        $stmt->bindValue(':responsible_user_id', $data['responsible_user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':status', $data['status'] ?? 'pending');
        
        $stmt->execute();

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $newData = $this->getById($newId);
            if (is_array($newData)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_booking_additional_requests',
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

        $oldData = $this->getById($id);

        $updates[] = "updated_at = NOW()";
        
        $sql = "UPDATE adms_booking_additional_requests 
                SET " . implode(', ', $updates) . "
                WHERE id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $ok = $stmt->execute();
        if ($ok && $stmt->rowCount() > 0 && is_array($oldData)) {
            $newData = $this->getById($id);
            if (is_array($newData)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_booking_additional_requests',
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

    /**
     * Deletar solicitação
     */
    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $sql = "DELETE FROM adms_booking_additional_requests WHERE id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->execute();
        $deleted = $stmt->rowCount() > 0;
        if ($deleted && is_array($oldData)) {
            $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'adms_booking_additional_requests',
                $id,
                $usuarioId,
                'DELETE',
                $oldData,
                []
            );
        }

        return $deleted;
    }

    /**
     * Pedidos adicionais em que o utilizador é organizador da reserva ou responsável pelo pedido.
     *
     * @return list<array<string, mixed>>
     */
    public function listInvolvingUser(int $userId, int $limit = 100): array
    {
        if ($userId <= 0) {
            return [];
        }
        $limit = max(1, min(500, $limit));
        $sql = "SELECT bar.*,
                       rb.title AS booking_title,
                       rb.start_datetime AS booking_start_datetime,
                       rb.end_datetime AS booking_end_datetime,
                       rb.user_id AS booking_organizer_user_id,
                       COALESCE(mr.name, '') AS room_name,
                       ru.name AS responsible_name,
                       COALESCE(rt.name, bar.request_type) AS request_type_display_name
                FROM adms_booking_additional_requests bar
                INNER JOIN adms_room_bookings rb ON bar.booking_id = rb.id
                LEFT JOIN adms_meeting_rooms mr ON rb.room_id = mr.id
                INNER JOIN adms_users ru ON bar.responsible_user_id = ru.id
                LEFT JOIN adms_room_request_types rt ON rt.code = bar.request_type
                WHERE (rb.user_id = :u1 OR bar.responsible_user_id = :u2)
                ORDER BY rb.start_datetime DESC, bar.id DESC
                LIMIT {$limit}";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':u1', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':u2', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Lista recente para administrador (visão global).
     *
     * @return list<array<string, mixed>>
     */
    public function listRecentForAdmin(int $limit = 150): array
    {
        $limit = max(1, min(500, $limit));
        $sql = "SELECT bar.*,
                       rb.title AS booking_title,
                       rb.start_datetime AS booking_start_datetime,
                       rb.end_datetime AS booking_end_datetime,
                       rb.user_id AS booking_organizer_user_id,
                       COALESCE(mr.name, '') AS room_name,
                       ru.name AS responsible_name,
                       org.name AS organizer_name,
                       COALESCE(rt.name, bar.request_type) AS request_type_display_name
                FROM adms_booking_additional_requests bar
                INNER JOIN adms_room_bookings rb ON bar.booking_id = rb.id
                LEFT JOIN adms_meeting_rooms mr ON rb.room_id = mr.id
                INNER JOIN adms_users ru ON bar.responsible_user_id = ru.id
                INNER JOIN adms_users org ON rb.user_id = org.id
                LEFT JOIN adms_room_request_types rt ON rt.code = bar.request_type
                ORDER BY bar.created_at DESC, bar.id DESC
                LIMIT {$limit}";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Deletar todas as solicitações de uma reserva
     */
    public function deleteByBookingId(int $bookingId): bool
    {
        $listStmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_booking_additional_requests WHERE booking_id = :booking_id'
        );
        $listStmt->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);
        $listStmt->execute();
        $rows = $listStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
        foreach ($rows as $row) {
            $rid = (int) ($row['id'] ?? 0);
            if ($rid > 0) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_booking_additional_requests',
                    $rid,
                    $usuarioId,
                    'DELETE',
                    $row,
                    []
                );
            }
        }

        $sql = "DELETE FROM adms_booking_additional_requests WHERE booking_id = :booking_id";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);

        return $stmt->execute();
    }
}

