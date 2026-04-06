<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Lista de espera de reservas de salas (adms_booking_waitlist).
 */
class BookingWaitlistRepository extends DbConnection
{
    public function getNextPriority(int $roomId): int
    {
        $sql = 'SELECT COALESCE(MAX(priority), 0) + 1 AS next_p
                FROM adms_booking_waitlist
                WHERE room_id = :room_id AND status = :status';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':room_id', $roomId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'waiting', PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($row['next_p'] ?? 1);
    }

    /**
     * Verifica se o usuário já tem entrada "waiting" sobrepondo o intervalo.
     */
    public function hasOverlappingWaiting(int $roomId, int $userId, string $startDatetime, string $endDatetime): bool
    {
        $sql = 'SELECT COUNT(*) FROM adms_booking_waitlist
                WHERE room_id = :room_id
                  AND user_id = :user_id
                  AND status = :status
                  AND desired_start_datetime < :end_dt
                  AND desired_end_datetime > :start_dt';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':room_id', $roomId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'waiting', PDO::PARAM_STR);
        $stmt->bindValue(':start_dt', $startDatetime, PDO::PARAM_STR);
        $stmt->bindValue(':end_dt', $endDatetime, PDO::PARAM_STR);
        $stmt->execute();

        return (int)$stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_booking_waitlist
                (room_id, user_id, desired_start_datetime, desired_end_datetime, priority, status, created_at)
                VALUES
                (:room_id, :user_id, :desired_start_datetime, :desired_end_datetime, :priority, :status, NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':room_id', $data['room_id'], PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':desired_start_datetime', $data['desired_start_datetime'], PDO::PARAM_STR);
        $stmt->bindValue(':desired_end_datetime', $data['desired_end_datetime'], PDO::PARAM_STR);
        $stmt->bindValue(':priority', $data['priority'], PDO::PARAM_INT);
        $stmt->bindValue(':status', $data['status'] ?? 'waiting', PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * Lista entradas da fila com sala e usuário.
     *
     * @param array<string, mixed> $filters room_id, status, user_id, start_date, end_date (YYYY-MM-DD)
     * @return list<array<string, mixed>>
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['room_id'])) {
            $where[] = 'w.room_id = :room_id';
            $params[':room_id'] = (int)$filters['room_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'w.status = :status';
            $params[':status'] = (string)$filters['status'];
        }
        if (!empty($filters['user_id'])) {
            $where[] = 'w.user_id = :user_id';
            $params[':user_id'] = (int)$filters['user_id'];
        }
        if (!empty($filters['start_date'])) {
            $where[] = 'DATE(w.desired_start_datetime) >= :start_date';
            $params[':start_date'] = (string)$filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $where[] = 'DATE(w.desired_start_datetime) <= :end_date';
            $params[':end_date'] = (string)$filters['end_date'];
        }

        $sql = 'SELECT w.*, mr.name AS room_name, mr.location AS room_location,
                       u.name AS user_name, u.email AS user_email
                FROM adms_booking_waitlist w
                INNER JOIN adms_meeting_rooms mr ON w.room_id = mr.id
                INNER JOIN adms_users u ON w.user_id = u.id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY w.desired_start_datetime ASC, w.priority ASC, w.id ASC
                LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function count(array $filters = []): int
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['room_id'])) {
            $where[] = 'w.room_id = :room_id';
            $params[':room_id'] = (int)$filters['room_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'w.status = :status';
            $params[':status'] = (string)$filters['status'];
        }
        if (!empty($filters['user_id'])) {
            $where[] = 'w.user_id = :user_id';
            $params[':user_id'] = (int)$filters['user_id'];
        }
        if (!empty($filters['start_date'])) {
            $where[] = 'DATE(w.desired_start_datetime) >= :start_date';
            $params[':start_date'] = (string)$filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $where[] = 'DATE(w.desired_start_datetime) <= :end_date';
            $params[':end_date'] = (string)$filters['end_date'];
        }

        $sql = 'SELECT COUNT(*) FROM adms_booking_waitlist w
                INNER JOIN adms_meeting_rooms mr ON w.room_id = mr.id
                INNER JOIN adms_users u ON w.user_id = u.id
                WHERE ' . implode(' AND ', $where);

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Entradas em espera cujo intervalo desejado sobrepõe [start, end] na sala.
     *
     * @param list<string> $statuses
     * @return list<array<string, mixed>>
     */
    public function findOverlappingByStatuses(
        int $roomId,
        string $startDatetime,
        string $endDatetime,
        array $statuses
    ): array {
        if ($statuses === []) {
            return [];
        }
        $placeholders = [];
        $params = [
            ':room_id' => $roomId,
            ':start_dt' => $startDatetime,
            ':end_dt' => $endDatetime,
        ];
        foreach ($statuses as $i => $st) {
            $k = ':st' . $i;
            $placeholders[] = $k;
            $params[$k] = (string)$st;
        }
        $inList = implode(', ', $placeholders);
        $sql = "SELECT w.* FROM adms_booking_waitlist w
                WHERE w.room_id = :room_id
                  AND w.status IN ($inList)
                  AND w.desired_start_datetime < :end_dt
                  AND w.desired_end_datetime > :start_dt
                ORDER BY w.priority ASC, w.id ASC";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            if (is_int($v)) {
                $stmt->bindValue($k, $v, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Usuário tem entrada notified (avisado de vaga) sobrepondo o horário — perdeu a corrida se der conflito na reserva.
     */
    public function hasNotifiedOverlap(int $roomId, int $userId, string $startDatetime, string $endDatetime): bool
    {
        $sql = 'SELECT COUNT(*) FROM adms_booking_waitlist
                WHERE room_id = :room_id
                  AND user_id = :user_id
                  AND status = :status
                  AND desired_start_datetime < :end_dt
                  AND desired_end_datetime > :start_dt';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':room_id', $roomId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'notified', PDO::PARAM_STR);
        $stmt->bindValue(':start_dt', $startDatetime, PDO::PARAM_STR);
        $stmt->bindValue(':end_dt', $endDatetime, PDO::PARAM_STR);
        $stmt->execute();

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Marca várias entradas como notificadas (vaga liberada — opção B: todos avisados).
     *
     * @param list<int> $ids
     */
    public function markAsNotifiedByIds(array $ids): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id) => $id > 0));
        if ($ids === []) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE adms_booking_waitlist
                SET status = 'notified', notified_at = NOW(), updated_at = NOW()
                WHERE id IN ($placeholders) AND status = 'waiting'";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($ids as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
    }

    /**
     * Após criar reserva: quem tinha waiting/notified sobreposto — um vencedor (user_id) fica accepted; demais expired.
     */
    public function resolveAfterBookingWon(
        int $roomId,
        string $startDatetime,
        string $endDatetime,
        int $winnerUserId,
        int $bookingId
    ): array {
        $losers = $this->findLoserUserIds($roomId, $startDatetime, $endDatetime, $winnerUserId);
        $sql = 'UPDATE adms_booking_waitlist SET
                    status = IF(user_id = :winner, \'accepted\', \'expired\'),
                    booking_id = IF(user_id = :winner2, :booking_id, NULL),
                    updated_at = NOW()
                WHERE room_id = :room_id
                  AND status IN (\'waiting\', \'notified\')
                  AND desired_start_datetime < :end_dt
                  AND desired_end_datetime > :start_dt';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':winner', $winnerUserId, PDO::PARAM_INT);
        $stmt->bindValue(':winner2', $winnerUserId, PDO::PARAM_INT);
        $stmt->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);
        $stmt->bindValue(':room_id', $roomId, PDO::PARAM_INT);
        $stmt->bindValue(':start_dt', $startDatetime, PDO::PARAM_STR);
        $stmt->bindValue(':end_dt', $endDatetime, PDO::PARAM_STR);
        $stmt->execute();

        return $losers;
    }

    /**
     * @return list<int> user_ids que perderam a vaga (exclui o vencedor)
     */
    public function findLoserUserIds(
        int $roomId,
        string $startDatetime,
        string $endDatetime,
        int $winnerUserId
    ): array {
        $sql = 'SELECT DISTINCT user_id FROM adms_booking_waitlist
                WHERE room_id = :room_id
                  AND user_id != :winner
                  AND status IN (\'waiting\', \'notified\')
                  AND desired_start_datetime < :end_dt
                  AND desired_end_datetime > :start_dt';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':room_id', $roomId, PDO::PARAM_INT);
        $stmt->bindValue(':winner', $winnerUserId, PDO::PARAM_INT);
        $stmt->bindValue(':start_dt', $startDatetime, PDO::PARAM_STR);
        $stmt->bindValue(':end_dt', $endDatetime, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static fn (array $r) => (int)$r['user_id'], $rows);
    }
}
