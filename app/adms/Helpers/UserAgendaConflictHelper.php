<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\UserCalendarRepository;
use PDO;

/**
 * Sobreposições na agenda do utilizador (reservas confirmadas + compromissos pessoais).
 */
final class UserAgendaConflictHelper
{
    /**
     * @return list<array{label: string, start: string, end: string}>
     */
    public static function findOverlaps(int $userId, string $startSql, string $endSql, ?int $excludeBookingId = null): array
    {
        if ($userId <= 0) {
            return [];
        }
        $repo = new UserCalendarRepository();
        $conn = $repo->getConnection();
        $out = [];

        $sql = "SELECT rb.id, rb.title, rb.start_datetime, rb.end_datetime, mr.name AS room_name
                FROM adms_booking_participants bp
                INNER JOIN adms_room_bookings rb ON rb.id = bp.booking_id
                INNER JOIN adms_meeting_rooms mr ON mr.id = rb.room_id
                WHERE bp.user_id = :uid
                  AND bp.status = 'confirmed'
                  AND rb.status IN ('pending', 'confirmed', 'in_progress')
                  AND rb.start_datetime < :end AND rb.end_datetime > :start";
        if ($excludeBookingId !== null && $excludeBookingId > 0) {
            $sql .= ' AND rb.id <> :ex';
        }
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':start', $startSql);
        $stmt->bindValue(':end', $endSql);
        if ($excludeBookingId !== null && $excludeBookingId > 0) {
            $stmt->bindValue(':ex', $excludeBookingId, PDO::PARAM_INT);
        }
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r) {
            $out[] = [
                'label' => 'Reunião (confirmada): ' . ($r['title'] ?? '') . ' — ' . ($r['room_name'] ?? ''),
                'start' => (string) ($r['start_datetime'] ?? ''),
                'end' => (string) ($r['end_datetime'] ?? ''),
            ];
        }

        $sql2 = 'SELECT id, title, start_datetime, end_datetime
                 FROM adms_user_calendar_entries
                 WHERE user_id = :uid
                   AND start_datetime < :end AND end_datetime > :start';
        $st2 = $conn->prepare($sql2);
        $st2->bindValue(':uid', $userId, PDO::PARAM_INT);
        $st2->bindValue(':start', $startSql);
        $st2->bindValue(':end', $endSql);
        $st2->execute();
        foreach ($st2->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r) {
            $out[] = [
                'label' => 'Compromisso pessoal: ' . ($r['title'] ?? ''),
                'start' => (string) ($r['start_datetime'] ?? ''),
                'end' => (string) ($r['end_datetime'] ?? ''),
            ];
        }

        return $out;
    }
}
