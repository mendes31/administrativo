<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Repository para gerenciar salas de reunião
 */
class MeetingRoomsRepository extends DbConnection
{
    /**
     * Criar nova sala
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_meeting_rooms 
                (name, description, capacity, location, floor, building, image, status, 
                 requires_approval, min_advance_booking_hours, max_advance_booking_days, 
                 booking_duration_limit_hours, created_by)
                VALUES 
                (:name, :description, :capacity, :location, :floor, :building, :image, :status,
                 :requires_approval, :min_advance_booking_hours, :max_advance_booking_days,
                 :booking_duration_limit_hours, :created_by)";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':capacity', $data['capacity'] ?? 1, PDO::PARAM_INT);
        $stmt->bindValue(':location', $data['location'] ?? null);
        $stmt->bindValue(':floor', $data['floor'] ?? null);
        $stmt->bindValue(':building', $data['building'] ?? null);
        $stmt->bindValue(':image', $data['image'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'active');
        $stmt->bindValue(':requires_approval', $data['requires_approval'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':min_advance_booking_hours', $data['min_advance_booking_hours'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':max_advance_booking_days', $data['max_advance_booking_days'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':booking_duration_limit_hours', $data['booking_duration_limit_hours'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':created_by', $data['created_by'], PDO::PARAM_INT);
        
        $stmt->execute();
        
        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * Buscar por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT mr.*, 
                       u.name as creator_name
                FROM adms_meeting_rooms mr
                LEFT JOIN adms_users u ON mr.created_by = u.id
                WHERE mr.id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Listar salas com filtros
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = "mr.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['building'])) {
            $where[] = "mr.building = :building";
            $params[':building'] = $filters['building'];
        }
        
        if (!empty($filters['floor'])) {
            $where[] = "mr.floor = :floor";
            $params[':floor'] = $filters['floor'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(mr.name LIKE :search OR mr.description LIKE :search OR mr.location LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (isset($filters['min_capacity'])) {
            $where[] = "mr.capacity >= :min_capacity";
            $params[':min_capacity'] = (int)$filters['min_capacity'];
        }
        
        $sql = "SELECT mr.*, 
                       u.name as creator_name
                FROM adms_meeting_rooms mr
                LEFT JOIN adms_users u ON mr.created_by = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY mr.name ASC
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
     * Contar total de salas
     */
    public function count(array $filters = []): int
    {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = "status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['building'])) {
            $where[] = "building = :building";
            $params[':building'] = $filters['building'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(name LIKE :search OR description LIKE :search OR location LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $sql = "SELECT COUNT(*) as total FROM adms_meeting_rooms WHERE " . implode(' AND ', $where);
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)($result['total'] ?? 0);
    }

    /**
     * Atualizar sala
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'name', 'description', 'capacity', 'location', 'floor', 'building', 'image',
            'status', 'requires_approval', 'min_advance_booking_hours', 
            'max_advance_booking_days', 'booking_duration_limit_hours'
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
        
        $sql = "UPDATE adms_meeting_rooms 
                SET " . implode(', ', $updates) . "
                WHERE id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }

    /**
     * Deletar sala
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM adms_meeting_rooms WHERE id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Buscar salas disponíveis em um horário
     */
    public function getAvailableRooms(string $startDatetime, string $endDatetime, ?int $minCapacity = null): array
    {
        $where = [
            "mr.status = 'active'",
            "NOT EXISTS (
                SELECT 1 FROM adms_room_bookings rb
                WHERE rb.room_id = mr.id
                AND rb.status IN ('confirmed', 'in_progress')
                AND (
                    (rb.start_datetime < :end_datetime AND rb.end_datetime > :start_datetime)
                )
            )"
        ];
        
        $params = [
            ':start_datetime' => $startDatetime,
            ':end_datetime' => $endDatetime,
        ];
        
        if ($minCapacity !== null) {
            $where[] = "mr.capacity >= :min_capacity";
            $params[':min_capacity'] = $minCapacity;
        }
        
        $sql = "SELECT mr.* 
                FROM adms_meeting_rooms mr
                WHERE " . implode(' AND ', $where) . "
                ORDER BY mr.name ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

