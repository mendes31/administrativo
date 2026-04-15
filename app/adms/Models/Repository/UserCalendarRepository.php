<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Calendário pessoal: compromissos próprios + leitura de reservas/eventos para o utilizador.
 */
class UserCalendarRepository extends DbConnection
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listPersonalInRange(int $userId, string $rangeStart, string $rangeEnd): array
    {
        if ($userId <= 0) {
            return [];
        }
        $sql = 'SELECT id, title, description, start_datetime, end_datetime, \'personal\' AS source, NULL AS sub_source
                FROM adms_user_calendar_entries
                WHERE user_id = :uid
                  AND start_datetime < :end
                  AND end_datetime > :start
                ORDER BY start_datetime ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':start', $rangeStart);
        $stmt->bindValue(':end', $rangeEnd);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Agenda unificada (ordenada por início): pessoal, salas (organizador ou convidado), eventos corporativos (RSVP).
     *
     * @return list<array<string, mixed>>
     */
    public function listUnifiedAgenda(int $userId, string $rangeStart, string $rangeEnd): array
    {
        if ($userId <= 0) {
            return [];
        }
        $conn = $this->getConnection();
        $out = [];

        $st = $conn->prepare(
            'SELECT id, title, description, start_datetime, end_datetime, \'personal\' AS source, NULL AS extra
             FROM adms_user_calendar_entries
             WHERE user_id = :uid AND start_datetime < :end AND end_datetime > :start'
        );
        $st->execute([':uid' => $userId, ':start' => $rangeStart, ':end' => $rangeEnd]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r) {
            $r['booking_organizer_id'] = $userId;
            $out[] = $r;
        }

        $st2 = $conn->prepare(
            "SELECT rb.id, rb.title, rb.description, rb.start_datetime, rb.end_datetime,
                    rb.user_id AS booking_organizer_id,
                    'room_booking' AS source,
                    CONCAT('organizador|', mr.name) AS extra
             FROM adms_room_bookings rb
             INNER JOIN adms_meeting_rooms mr ON mr.id = rb.room_id
             WHERE rb.user_id = :uid
               AND rb.status IN ('pending', 'confirmed', 'in_progress')
               AND rb.start_datetime < :end AND rb.end_datetime > :start"
        );
        $st2->execute([':uid' => $userId, ':start' => $rangeStart, ':end' => $rangeEnd]);
        foreach ($st2->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r) {
            $out[] = $r;
        }

        $st3 = $conn->prepare(
            "SELECT rb.id, rb.title, rb.description, rb.start_datetime, rb.end_datetime,
                    rb.user_id AS booking_organizer_id,
                    'room_invite' AS source,
                    CONCAT('convidado|', mr.name, '|', bp.status) AS extra,
                    bp.rsvp_token AS rsvp_token
             FROM adms_booking_participants bp
             INNER JOIN adms_room_bookings rb ON rb.id = bp.booking_id
             INNER JOIN adms_meeting_rooms mr ON mr.id = rb.room_id
             WHERE bp.user_id = :uid
               AND rb.user_id <> :uid2
               AND rb.status IN ('pending', 'confirmed', 'in_progress')
               AND rb.start_datetime < :end AND rb.end_datetime > :start"
        );
        $st3->execute([':uid' => $userId, ':uid2' => $userId, ':start' => $rangeStart, ':end' => $rangeEnd]);
        foreach ($st3->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r) {
            $out[] = $r;
        }

        $st4 = $conn->prepare(
            "SELECT e.id, e.title, e.description, e.starts_at AS start_datetime, e.ends_at AS end_datetime,
                    NULL AS booking_organizer_id,
                    'company_event' AS source,
                    CONCAT('rsvp|', r.status) AS extra
             FROM adms_company_events e
             INNER JOIN adms_company_event_rsvps r ON r.event_id = e.id AND r.user_id = :uid
             WHERE COALESCE(e.ativo, 1) = 1
               AND e.starts_at < :end AND e.ends_at > :start"
        );
        $st4->execute([':uid' => $userId, ':start' => $rangeStart, ':end' => $rangeEnd]);
        foreach ($st4->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r) {
            $out[] = $r;
        }

        usort($out, static function (array $a, array $b): int {
            return strcmp((string) ($a['start_datetime'] ?? ''), (string) ($b['start_datetime'] ?? ''));
        });

        return $out;
    }

    /**
     * Próximos itens da agenda unificada do utilizador (hoje até N dias), para resumos (ex.: dashboard).
     *
     * @return list<array<string, mixed>>
     */
    public function listUnifiedAgendaPreview(int $userId, int $daysAhead = 14, int $maxItems = 6): array
    {
        if ($userId <= 0) {
            return [];
        }
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59', strtotime('+' . max(0, $daysAhead) . ' days'));
        $all = $this->listUnifiedAgenda($userId, $start, $end);

        return array_slice($all, 0, max(1, $maxItems));
    }

    public function countUnifiedAgendaInRange(int $userId, int $daysAhead = 14): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59', strtotime('+' . max(0, $daysAhead) . ' days'));

        return count($this->listUnifiedAgenda($userId, $start, $end));
    }

    public function countUnifiedAgendaInMonth(int $userId, int $year, int $month): int
    {
        if ($userId <= 0 || $month < 1 || $month > 12) {
            return 0;
        }
        $from = sprintf('%04d-%02d-01', $year, $month);
        $tEnd = strtotime($from . ' +1 month -1 day');
        if ($tEnd === false) {
            return 0;
        }
        $to = date('Y-m-d', $tEnd);

        return count($this->listUnifiedAgenda($userId, $from . ' 00:00:00', $to . ' 23:59:59'));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPersonalById(int $userId, int $entryId): ?array
    {
        if ($userId <= 0 || $entryId <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT id, user_id, title, description, start_datetime, end_datetime
             FROM adms_user_calendar_entries WHERE id = :id AND user_id = :uid LIMIT 1'
        );
        $stmt->bindValue(':id', $entryId, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function updatePersonal(int $userId, int $entryId, string $title, ?string $description, string $startSql, string $endSql): bool
    {
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_user_calendar_entries
             SET title = :title, description = :desc, start_datetime = :s, end_datetime = :e, updated_at = NOW()
             WHERE id = :id AND user_id = :uid'
        );
        $stmt->bindValue(':title', mb_substr($title, 0, 255));
        $stmt->bindValue(':desc', $description !== null && $description !== '' ? $description : null, $description !== null && $description !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':s', $startSql);
        $stmt->bindValue(':e', $endSql);
        $stmt->bindValue(':id', $entryId, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);

        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listTomorrowAndConflicts(int $userId): array
    {
        $start = date('Y-m-d 00:00:00', strtotime('+1 day'));
        $end = date('Y-m-d 23:59:59', strtotime('+1 day'));
        $items = $this->listUnifiedAgenda($userId, $start, $end);

        return $items;
    }

    public function createPersonal(int $userId, string $title, ?string $description, string $startSql, string $endSql): int
    {
        $sql = 'INSERT INTO adms_user_calendar_entries (user_id, title, description, start_datetime, end_datetime, created_at, updated_at)
                VALUES (:uid, :title, :desc, :s, :e, NOW(), NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':title', mb_substr($title, 0, 255));
        $stmt->bindValue(':desc', $description !== null && $description !== '' ? $description : null, $description !== null && $description !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':s', $startSql);
        $stmt->bindValue(':e', $endSql);
        $stmt->execute();

        return (int) $this->getConnection()->lastInsertId();
    }

    public function deletePersonal(int $userId, int $entryId): bool
    {
        $stmt = $this->getConnection()->prepare(
            'DELETE FROM adms_user_calendar_entries WHERE id = :id AND user_id = :uid'
        );
        $stmt->bindValue(':id', $entryId, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);

        return $stmt->execute() && $stmt->rowCount() > 0;
    }
}
