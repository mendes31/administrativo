<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\TextEncodingHelper;
use App\adms\Models\Services\DbConnection;
use PDO;

class CompanyEventsRepository extends DbConnection
{
    /**
     * Registra leitura de um evento para o usuário (upsert).
     */
    public function upsertRead(int $eventId, int $userId): void
    {
        $sql = 'INSERT INTO adms_company_event_reads (event_id, user_id, read_at, created_at)
                VALUES (:e, :u, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    read_at = IF(read_at IS NULL, NOW(), read_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':e', $eventId, PDO::PARAM_INT);
        $stmt->bindValue(':u', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Condição SQL: evento conta como "não lido" para o usuário.
     * - Sem RSVP obrigatório: não lido até registro em adms_company_event_reads.
     * - Com requires_rsvp: não lido até resposta confirm/decline (cancel só após confirmar).
     */
    private function sqlConditionEventUnreadForUser(): string
    {
        return '(
            (
                (e.requires_rsvp IS NULL OR e.requires_rsvp = 0)
                AND NOT EXISTS (
                    SELECT 1
                    FROM adms_company_event_reads r
                    WHERE r.event_id = e.id
                      AND r.user_id = :u
                      AND r.read_at IS NOT NULL
                )
            )
            OR
            (
                e.requires_rsvp = 1
                AND NOT EXISTS (
                    SELECT 1
                    FROM adms_company_event_rsvps rsvp
                    WHERE rsvp.event_id = e.id
                      AND rsvp.user_id = :u
                      AND rsvp.status IN (\'confirmed\', \'declined\', \'cancelled\')
                )
            )
        )';
    }

    /**
     * Marca uma lista de eventos como lidos para o usuário.
     *
     * @param int[] $eventIds
     */
    public function markManyAsRead(array $eventIds, int $userId): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $eventIds), static fn ($v) => $v > 0)));
        if ($userId <= 0 || $ids === []) {
            return;
        }
        foreach ($ids as $eventId) {
            $this->upsertRead($eventId, $userId);
        }
    }

    /**
     * Conta eventos não lidos do usuário que intersectam o mês informado.
     */
    public function countUnreadIntersectingMonth(int $year, int $month, int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $month = max(1, min(12, $month));
        $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $end = date('Y-m-t 23:59:59', strtotime($start));

        $unread = $this->sqlConditionEventUnreadForUser();
        $sql = "SELECT COUNT(*) AS total
                FROM adms_company_events e
                WHERE e.ativo = 1
                  AND e.created_by <> :u
                  AND e.starts_at <= :end
                  AND e.ends_at >= :start
                  AND (e.publish_at IS NULL OR e.publish_at <= NOW())
                  AND (e.expire_at IS NULL OR e.expire_at > NOW())
                  AND {$unread}";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end, ':u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Conta eventos não lidos do usuário que intersectam o ano informado.
     */
    public function countUnreadIntersectingYear(int $year, int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $start = sprintf('%04d-01-01 00:00:00', $year);
        $end = sprintf('%04d-12-31 23:59:59', $year);

        $unread = $this->sqlConditionEventUnreadForUser();
        $sql = "SELECT COUNT(*) AS total
                FROM adms_company_events e
                WHERE e.ativo = 1
                  AND e.created_by <> :u
                  AND e.starts_at <= :end
                  AND e.ends_at >= :start
                  AND (e.publish_at IS NULL OR e.publish_at <= NOW())
                  AND (e.expire_at IS NULL OR e.expire_at > NOW())
                  AND {$unread}";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end, ':u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function createEvent(array $data): int
    {
        $sql = 'INSERT INTO adms_company_events (
                    title, description, location, starts_at, ends_at, publish_at, expire_at,
                    rsvp_deadline, cancellation_deadline, requires_rsvp, allows_guests, max_guests_per_user,
                    created_by, department_id, ativo, created_at, updated_at
                ) VALUES (
                    :title, :description, :location, :starts_at, :ends_at, :publish_at, :expire_at,
                    :rsvp_deadline, :cancellation_deadline, :requires_rsvp, :allows_guests, :max_guests,
                    :created_by, :department_id, :ativo, NOW(), NOW()
                )';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':location' => $data['location'] ?? null,
            ':starts_at' => $data['starts_at'],
            ':ends_at' => $data['ends_at'],
            ':publish_at' => $data['publish_at'] ?? null,
            ':expire_at' => $data['expire_at'] ?? null,
            ':rsvp_deadline' => $data['rsvp_deadline'] ?? null,
            ':cancellation_deadline' => $data['cancellation_deadline'] ?? null,
            ':requires_rsvp' => !empty($data['requires_rsvp']) ? 1 : 0,
            ':allows_guests' => !empty($data['allows_guests']) ? 1 : 0,
            ':max_guests' => (int)($data['max_guests_per_user'] ?? 0),
            ':created_by' => (int)$data['created_by'],
            ':department_id' => isset($data['department_id']) ? (int)$data['department_id'] : null,
            ':ativo' => !empty($data['ativo']) ? 1 : 0,
        ]);
        return (int)$this->getConnection()->lastInsertId();
    }

    public function updateEvent(int $id, array $data): bool
    {
        $sql = 'UPDATE adms_company_events SET
                    title = :title, description = :description, location = :location,
                    starts_at = :starts_at, ends_at = :ends_at, publish_at = :publish_at, expire_at = :expire_at,
                    rsvp_deadline = :rsvp_deadline, cancellation_deadline = :cancellation_deadline,
                    requires_rsvp = :requires_rsvp, allows_guests = :allows_guests, max_guests_per_user = :max_guests,
                    department_id = :department_id, ativo = :ativo, updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':location' => $data['location'] ?? null,
            ':starts_at' => $data['starts_at'],
            ':ends_at' => $data['ends_at'],
            ':publish_at' => $data['publish_at'] ?? null,
            ':expire_at' => $data['expire_at'] ?? null,
            ':rsvp_deadline' => $data['rsvp_deadline'] ?? null,
            ':cancellation_deadline' => $data['cancellation_deadline'] ?? null,
            ':requires_rsvp' => !empty($data['requires_rsvp']) ? 1 : 0,
            ':allows_guests' => !empty($data['allows_guests']) ? 1 : 0,
            ':max_guests' => (int)($data['max_guests_per_user'] ?? 0),
            ':department_id' => isset($data['department_id']) ? (int)$data['department_id'] : null,
            ':ativo' => !empty($data['ativo']) ? 1 : 0,
        ]);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_company_events WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    /**
     * Eventos visíveis e com período intersectando o mês informado.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEventsIntersectingMonth(int $year, int $month): array
    {
        $month = max(1, min(12, $month));
        $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $end = date('Y-m-t 23:59:59', strtotime($start));

        $sql = 'SELECT e.*, u.name AS creator_name
                FROM adms_company_events e
                INNER JOIN adms_users u ON u.id = e.created_by
                WHERE e.ativo = 1
                  AND e.starts_at <= :end
                  AND e.ends_at >= :start
                  AND (e.publish_at IS NULL OR e.publish_at <= NOW())
                  AND (e.expire_at IS NULL OR e.expire_at > NOW())
                ORDER BY e.starts_at ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end]);
        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    /**
     * Conta eventos ativos/publicados que intersectam o ano informado.
     */
    public function countEventsIntersectingYear(int $year): int
    {
        $start = sprintf('%04d-01-01 00:00:00', $year);
        $end = sprintf('%04d-12-31 23:59:59', $year);

        $sql = 'SELECT COUNT(*) AS total
                FROM adms_company_events e
                WHERE e.ativo = 1
                  AND e.starts_at <= :end
                  AND e.ends_at >= :start
                  AND (e.publish_at IS NULL OR e.publish_at <= NOW())
                  AND (e.expire_at IS NULL OR e.expire_at > NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAllForAdmin(int $page, int $perPage): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT e.*, u.name AS creator_name
                FROM adms_company_events e
                INNER JOIN adms_users u ON u.id = e.created_by
                ORDER BY e.starts_at DESC
                LIMIT :lim OFFSET :off';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    public function countAll(): int
    {
        $row = $this->getConnection()->query('SELECT COUNT(*) AS c FROM adms_company_events')->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    public function getOrCreateRsvp(int $eventId, int $userId): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_company_event_rsvps WHERE event_id = :e AND user_id = :u LIMIT 1'
        );
        $stmt->execute([':e' => $eventId, ':u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
        $ins = $this->getConnection()->prepare(
            'INSERT INTO adms_company_event_rsvps (event_id, user_id, status, created_at, updated_at)
             VALUES (:e, :u, "pending", NOW(), NOW())'
        );
        $ins->execute([':e' => $eventId, ':u' => $userId]);
        $id = (int)$this->getConnection()->lastInsertId();
        return $this->getRsvpById($id) ?? [];
    }

    public function getRsvpById(int $id): ?array
    {
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_company_event_rsvps WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    public function getRsvpForUser(int $eventId, int $userId): ?array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_company_event_rsvps WHERE event_id = :e AND user_id = :u LIMIT 1'
        );
        $stmt->execute([':e' => $eventId, ':u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    /**
     * @param array<int, array{full_name: string, relationship?: string, age?: int, notes?: string}> $guests
     */
    public function saveRsvpWithGuests(
        int $eventId,
        int $userId,
        string $status,
        array $guests,
        bool $allowsGuests,
        int $maxGuests
    ): bool {
        $event = $this->getById($eventId);
        if (!$event || empty($event['ativo'])) {
            return false;
        }
        $now = date('Y-m-d H:i:s');

        $rsvp = $this->getOrCreateRsvp($eventId, $userId);
        $rsvpId = (int)($rsvp['id'] ?? 0);
        if ($rsvpId <= 0) {
            return false;
        }

        $newStatus = $status === 'declined' ? 'declined' : 'confirmed';
        if ($newStatus === 'confirmed' && !empty($event['rsvp_deadline']) && $now > $event['rsvp_deadline']) {
            return false;
        }

        $upd = $this->getConnection()->prepare(
            'UPDATE adms_company_event_rsvps SET status = :st, responded_at = NOW(), updated_at = NOW(), cancelled_at = NULL
             WHERE id = :id'
        );
        $upd->execute([':st' => $newStatus, ':id' => $rsvpId]);

        $this->getConnection()->prepare('DELETE FROM adms_company_event_guests WHERE rsvp_id = :r')->execute([':r' => $rsvpId]);

        if ($allowsGuests && $newStatus === 'confirmed' && $maxGuests > 0) {
            $guests = array_slice($guests, 0, $maxGuests);
            $insG = $this->getConnection()->prepare(
                'INSERT INTO adms_company_event_guests (rsvp_id, full_name, relationship, age, notes, created_at)
                 VALUES (:r, :fn, :rel, :age, :notes, NOW())'
            );
            foreach ($guests as $g) {
                $name = trim((string)($g['full_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $insG->execute([
                    ':r' => $rsvpId,
                    ':fn' => mb_substr($name, 0, 200),
                    ':rel' => isset($g['relationship']) ? mb_substr((string)$g['relationship'], 0, 100) : null,
                    ':age' => isset($g['age']) ? (int)$g['age'] : null,
                    ':notes' => isset($g['notes']) ? mb_substr((string)$g['notes'], 0, 500) : null,
                ]);
            }
        }

        return true;
    }

    public function cancelRsvp(int $eventId, int $userId): bool
    {
        $event = $this->getById($eventId);
        if (!$event) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        if (!empty($event['cancellation_deadline']) && $now > $event['cancellation_deadline']) {
            return false;
        }
        $rsvp = $this->getRsvpForUser($eventId, $userId);
        if (!$rsvp || ($rsvp['status'] ?? '') !== 'confirmed') {
            return false;
        }
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_company_event_rsvps SET status = "cancelled", cancelled_at = NOW(), updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute([':id' => (int)$rsvp['id']]);
        $this->getConnection()->prepare('DELETE FROM adms_company_event_guests WHERE rsvp_id = :r')->execute([':r' => (int)$rsvp['id']]);
        return $stmt->rowCount() > 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function deleteEvent(int $id): bool
    {
        $rsvpIds = $this->getConnection()->prepare('SELECT id FROM adms_company_event_rsvps WHERE event_id = :e');
        $rsvpIds->execute([':e' => $id]);
        $ids = $rsvpIds->fetchAll(PDO::FETCH_COLUMN) ?: [];
        foreach ($ids as $rid) {
            $this->getConnection()->prepare('DELETE FROM adms_company_event_guests WHERE rsvp_id = :r')->execute([':r' => (int)$rid]);
        }
        $this->getConnection()->prepare('DELETE FROM adms_company_event_rsvps WHERE event_id = :e')->execute([':e' => $id]);
        $stmt = $this->getConnection()->prepare('DELETE FROM adms_company_events WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getReportRowsForEvent(int $eventId): array
    {
        $sql = 'SELECT r.id AS rsvp_id, r.user_id, r.status, r.responded_at, r.cancelled_at,
                       u.name AS user_name, u.email AS user_email,
                       d.name AS department_name
                FROM adms_company_event_rsvps r
                INNER JOIN adms_users u ON u.id = r.user_id
                LEFT JOIN adms_departments d ON d.id = u.user_department_id
                WHERE r.event_id = :e
                ORDER BY u.name ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':e' => $eventId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $guestStmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_company_event_guests WHERE rsvp_id = :r ORDER BY id ASC'
        );
        foreach ($rows as &$row) {
            $guestStmt->execute([':r' => (int)$row['rsvp_id']]);
            $row['guests'] = $guestStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        unset($row);

        return $this->normalizeRows($rows);
    }

    private function normalizeRows(array $rows): array
    {
        foreach ($rows as &$row) {
            if (is_array($row)) {
                $row = $this->normalizeRow($row);
            }
        }
        unset($row);
        return $rows;
    }

    private function normalizeRow(array $row): array
    {
        foreach (['title', 'description', 'location', 'creator_name', 'user_name', 'department_name'] as $field) {
            if (array_key_exists($field, $row) && is_string($row[$field])) {
                $row[$field] = TextEncodingHelper::decodeEntities($row[$field]);
            }
        }
        if (isset($row['guests']) && is_array($row['guests'])) {
            foreach ($row['guests'] as &$guest) {
                if (is_array($guest)) {
                    foreach (['full_name', 'relationship', 'notes'] as $field) {
                        if (array_key_exists($field, $guest) && is_string($guest[$field])) {
                            $guest[$field] = TextEncodingHelper::decodeEntities($guest[$field]);
                        }
                    }
                }
            }
            unset($guest);
        }
        return $row;
    }
}
