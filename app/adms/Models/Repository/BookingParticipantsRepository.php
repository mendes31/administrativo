<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class BookingParticipantsRepository extends DbConnection
{
    public function getById(int $id): ?array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_booking_participants WHERE id = :id LIMIT 1'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function deleteByBookingId(int $bookingId): void
    {
        $list = $this->getConnection()->prepare(
            'SELECT * FROM adms_booking_participants WHERE booking_id = :bid'
        );
        $list->bindValue(':bid', $bookingId, PDO::PARAM_INT);
        $list->execute();
        $rows = $list->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
        foreach ($rows as $row) {
            $pid = (int) ($row['id'] ?? 0);
            if ($pid > 0) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_booking_participants',
                    $pid,
                    $usuarioId,
                    'DELETE',
                    $row,
                    []
                );
            }
        }

        $stmt = $this->getConnection()->prepare('DELETE FROM adms_booking_participants WHERE booking_id = :bid');
        $stmt->bindValue(':bid', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /** Remove só participantes internos (preserva convidados externos na edição). */
    public function deleteInternalParticipantsByBookingId(int $bookingId): void
    {
        $list = $this->getConnection()->prepare(
            'SELECT * FROM adms_booking_participants WHERE booking_id = :bid AND user_id IS NOT NULL'
        );
        $list->bindValue(':bid', $bookingId, PDO::PARAM_INT);
        $list->execute();
        $rows = $list->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
        foreach ($rows as $row) {
            $pid = (int) ($row['id'] ?? 0);
            if ($pid > 0) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_booking_participants',
                    $pid,
                    $usuarioId,
                    'DELETE',
                    $row,
                    []
                );
            }
        }

        $stmt = $this->getConnection()->prepare(
            'DELETE FROM adms_booking_participants WHERE booking_id = :bid AND user_id IS NOT NULL'
        );
        $stmt->bindValue(':bid', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * @return int[]
     */
    public function getUserIdsForBooking(int $bookingId): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT user_id FROM adms_booking_participants WHERE booking_id = :bid AND user_id IS NOT NULL ORDER BY user_id'
        );
        $stmt->bindValue(':bid', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_map(static fn ($id) => (int) $id, $rows ?: []);
    }

    /**
     * Insere participante com token RSVP (convite por e-mail).
     */
    public function insertParticipant(int $bookingId, int $userId, bool $isOrganizer = false, string $status = 'pending'): string
    {
        $token = $this->generateUniqueToken();
        $sql = 'INSERT INTO adms_booking_participants
                (booking_id, user_id, is_organizer, status, notified, rsvp_token, rsvp_responded_at, created_at)
                VALUES (:booking_id, :user_id, :is_organizer, :status, 0, :rsvp_token, NULL, NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':is_organizer', $isOrganizer ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':rsvp_token', $token);
        $stmt->execute();

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $newData = $this->getById($newId);
            if (is_array($newData)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_booking_participants',
                    $newId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $newData
                );
            }
        }

        return $token;
    }

    /**
     * Participante externo (sem conta no sistema) — convite só por e-mail com RSVP.
     */
    public function insertGuestParticipant(int $bookingId, string $email, string $displayName = ''): string
    {
        $email = mb_strtolower(trim($email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '';
        }
        $displayName = trim($displayName) !== '' ? trim($displayName) : $email;
        $token = $this->generateUniqueToken();
        $sql = 'INSERT INTO adms_booking_participants
                (booking_id, user_id, guest_email, guest_name, is_organizer, status, notified, rsvp_token, rsvp_responded_at, created_at)
                VALUES (:booking_id, NULL, :guest_email, :guest_name, 0, :status, 0, :rsvp_token, NULL, NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);
        $stmt->bindValue(':guest_email', mb_substr($email, 0, 255));
        $stmt->bindValue(':guest_name', mb_substr($displayName, 0, 191));
        $stmt->bindValue(':status', 'pending');
        $stmt->bindValue(':rsvp_token', $token);
        $stmt->execute();

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $newData = $this->getById($newId);
            if (is_array($newData)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_booking_participants',
                    $newId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $newData
                );
            }
        }

        return $token;
    }

    /**
     * Participante + dados mínimos da reserva e sala.
     *
     * @return array<string, mixed>|null
     */
    public function findByRsvpToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        $sql = 'SELECT bp.*,
                       rb.title AS booking_title,
                       rb.start_datetime, rb.end_datetime, rb.status AS booking_status, rb.user_id AS organizer_user_id,
                       mr.name AS room_name,
                       u.name AS participant_user_name
                FROM adms_booking_participants bp
                INNER JOIN adms_room_bookings rb ON bp.booking_id = rb.id
                INNER JOIN adms_meeting_rooms mr ON rb.room_id = mr.id
                LEFT JOIN adms_users u ON bp.user_id = u.id
                WHERE bp.rsvp_token = :t
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':t', $token);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function updateParticipantStatusByToken(string $token, string $status): bool
    {
        $allowed = ['pending', 'confirmed', 'declined'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        $token = trim($token);
        if ($token === '') {
            return false;
        }
        $stmtFind = $this->getConnection()->prepare(
            'SELECT id FROM adms_booking_participants WHERE rsvp_token = :t LIMIT 1'
        );
        $stmtFind->bindValue(':t', $token);
        $stmtFind->execute();
        $foundId = (int) ($stmtFind->fetchColumn() ?: 0);
        if ($foundId <= 0) {
            return false;
        }
        $oldData = $this->getById($foundId);
        $sql = 'UPDATE adms_booking_participants
                SET status = :status, rsvp_responded_at = NOW()
                WHERE rsvp_token = :t';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':t', $token);

        $ok = $stmt->execute() && $stmt->rowCount() > 0;
        if ($ok && is_array($oldData)) {
            $newData = $this->getById($foundId);
            if (is_array($newData)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_booking_participants',
                    $foundId,
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
     * Após reagendamento de horário/sala: volta convites a "pendente", gera novos tokens RSVP
     * (links antigos deixam de valer) para pedir nova confirmação. Não altera o organizador (is_organizer = 1).
     */
    public function resetAllInviteeRsvpForBooking(int $bookingId): void
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT id FROM adms_booking_participants WHERE booking_id = :bid AND COALESCE(is_organizer, 0) = 0'
        );
        $stmt->bindValue(':bid', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        $ids = array_map(static fn ($v) => (int) $v, $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        foreach ($ids as $pid) {
            if ($pid <= 0) {
                continue;
            }
            $oldData = $this->getById($pid);
            $token = $this->generateUniqueToken();
            $up = $this->getConnection()->prepare(
                'UPDATE adms_booking_participants
                    SET status = \'pending\',
                        notified = 0,
                        rsvp_responded_at = NULL,
                        rsvp_token = :t
                  WHERE id = :id AND booking_id = :bid'
            );
            $up->bindValue(':t', $token);
            $up->bindValue(':id', $pid, PDO::PARAM_INT);
            $up->bindValue(':bid', $bookingId, PDO::PARAM_INT);
            $up->execute();
            if (is_array($oldData) && $up->rowCount() > 0) {
                $newData = $this->getById($pid);
                if (is_array($newData)) {
                    $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                    LogAlteracaoService::registrarAlteracao(
                        'adms_booking_participants',
                        $pid,
                        $usuarioId,
                        'UPDATE',
                        $oldData,
                        $newData
                    );
                }
            }
        }
    }

    private function generateUniqueToken(): string
    {
        for ($i = 0; $i < 8; $i++) {
            $token = bin2hex(random_bytes(32));
            $stmt = $this->getConnection()->prepare('SELECT id FROM adms_booking_participants WHERE rsvp_token = :t LIMIT 1');
            $stmt->bindValue(':t', $token);
            $stmt->execute();
            if (!$stmt->fetch()) {
                return $token;
            }
        }

        return bin2hex(random_bytes(32));
    }
}
