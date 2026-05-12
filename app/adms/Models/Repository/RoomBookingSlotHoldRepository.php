<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

/**
 * Reserva temporária de intervalo na tela "Reservar sala" (evita dois utilizadores
 * a preencher o mesmo horário em simultâneo antes de confirmar).
 */
final class RoomBookingSlotHoldRepository extends DbConnection
{
    private const TTL_SECONDS = 120;

    /**
     * @return array<string, mixed>|null
     */
    public function getById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_room_booking_slot_holds WHERE id = :id LIMIT 1'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function purgeExpired(): void
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_room_booking_slot_holds WHERE expires_at < NOW()'
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
        foreach ($rows as $row) {
            $hid = (int) ($row['id'] ?? 0);
            if ($hid > 0) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_room_booking_slot_holds',
                    $hid,
                    $usuarioId,
                    'DELETE',
                    $row,
                    []
                );
            }
        }
        $this->getConnection()->exec('DELETE FROM adms_room_booking_slot_holds WHERE expires_at < NOW()');
    }

    /**
     * @return array{ok: true, token: string, expires_at: string}|array{ok: false, blocked_by: string}
     */
    public function acquire(int $roomId, string $startDatetime, string $endDatetime, int $userId, string $userDisplayName): array
    {
        $this->purgeExpired();
        $userId = max(0, $userId);
        if ($roomId <= 0 || $userId <= 0) {
            return ['ok' => false, 'blocked_by' => ''];
        }

        $blocker = $this->findBlockingHolder($roomId, $startDatetime, $endDatetime, $userId);
        if ($blocker !== null) {
            return ['ok' => false, 'blocked_by' => $blocker];
        }

        $existing = $this->findOwnHold($roomId, $startDatetime, $endDatetime, $userId);
        $expires = date('Y-m-d H:i:s', time() + self::TTL_SECONDS);
        if ($existing) {
            $token = (string) ($existing['hold_token'] ?? '');
            if ($token !== '') {
                $holdId = (int) ($existing['id'] ?? 0);
                $oldData = $holdId > 0 ? $this->getById($holdId) : null;
                $stmt = $this->getConnection()->prepare(
                    'UPDATE adms_room_booking_slot_holds SET expires_at = :e, user_display_name = :n WHERE hold_token = :t AND user_id = :u'
                );
                $stmt->bindValue(':e', $expires);
                $stmt->bindValue(':n', mb_substr($userDisplayName, 0, 255));
                $stmt->bindValue(':t', $token);
                $stmt->bindValue(':u', $userId, PDO::PARAM_INT);
                $stmt->execute();
                if ($holdId > 0 && is_array($oldData) && $stmt->rowCount() > 0) {
                    $newData = $this->getById($holdId);
                    if (is_array($newData)) {
                        $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                        LogAlteracaoService::registrarAlteracao(
                            'adms_room_booking_slot_holds',
                            $holdId,
                            $usuarioId,
                            'UPDATE',
                            $oldData,
                            $newData
                        );
                    }
                }

                return ['ok' => true, 'token' => $token, 'expires_at' => $expires];
            }
        }

        $token = $this->generateToken();
        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_room_booking_slot_holds
            (room_id, start_datetime, end_datetime, user_id, user_display_name, hold_token, expires_at, created_at)
            VALUES (:r, :s, :e, :u, :n, :t, :x, NOW())'
        );
        $stmt->bindValue(':r', $roomId, PDO::PARAM_INT);
        $stmt->bindValue(':s', $startDatetime);
        $stmt->bindValue(':e', $endDatetime);
        $stmt->bindValue(':u', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':n', mb_substr($userDisplayName, 0, 255));
        $stmt->bindValue(':t', $token);
        $stmt->bindValue(':x', $expires);
        $stmt->execute();

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $newData = $this->getById($newId);
            if (is_array($newData)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_room_booking_slot_holds',
                    $newId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $newData
                );
            }
        }

        return ['ok' => true, 'token' => $token, 'expires_at' => $expires];
    }

    public function renew(string $token, int $userId): bool
    {
        $token = trim($token);
        if ($token === '' || $userId <= 0) {
            return false;
        }
        $stmtFind = $this->getConnection()->prepare(
            'SELECT * FROM adms_room_booking_slot_holds
             WHERE hold_token = :t AND user_id = :u AND expires_at >= NOW()
             LIMIT 1'
        );
        $stmtFind->bindValue(':t', $token);
        $stmtFind->bindValue(':u', $userId, PDO::PARAM_INT);
        $stmtFind->execute();
        $oldData = $stmtFind->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($oldData === null) {
            return false;
        }
        $holdId = (int) ($oldData['id'] ?? 0);
        $expires = date('Y-m-d H:i:s', time() + self::TTL_SECONDS);
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_room_booking_slot_holds SET expires_at = :x
             WHERE hold_token = :t AND user_id = :u AND expires_at >= NOW()'
        );
        $stmt->bindValue(':x', $expires);
        $stmt->bindValue(':t', $token);
        $stmt->bindValue(':u', $userId, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0 && $holdId > 0) {
            $newData = $this->getById($holdId);
            if (is_array($newData)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_room_booking_slot_holds',
                    $holdId,
                    $usuarioId,
                    'UPDATE',
                    $oldData,
                    $newData
                );
            }
        }

        return $stmt->rowCount() > 0;
    }

    public function releaseByToken(string $token, int $userId): void
    {
        $token = trim($token);
        if ($token === '' || $userId <= 0) {
            return;
        }
        $list = $this->getConnection()->prepare(
            'SELECT * FROM adms_room_booking_slot_holds WHERE hold_token = :t AND user_id = :u'
        );
        $list->bindValue(':t', $token);
        $list->bindValue(':u', $userId, PDO::PARAM_INT);
        $list->execute();
        $rows = $list->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
        foreach ($rows as $row) {
            $hid = (int) ($row['id'] ?? 0);
            if ($hid > 0) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_room_booking_slot_holds',
                    $hid,
                    $usuarioId,
                    'DELETE',
                    $row,
                    []
                );
            }
        }
        $stmt = $this->getConnection()->prepare(
            'DELETE FROM adms_room_booking_slot_holds WHERE hold_token = :t AND user_id = :u'
        );
        $stmt->bindValue(':t', $token);
        $stmt->bindValue(':u', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Utilizado ao submeter a reserva rápida: o token tem de corresponder ao intervalo e ao utilizador.
     */
    public function validateHoldForUser(
        int $roomId,
        string $startDatetime,
        string $endDatetime,
        int $userId,
        string $token
    ): bool {
        $token = trim($token);
        if ($token === '' || $userId <= 0 || $roomId <= 0) {
            return false;
        }
        $sql = 'SELECT id FROM adms_room_booking_slot_holds
                WHERE hold_token = :t AND user_id = :u AND room_id = :r
                  AND start_datetime = :s AND end_datetime = :e
                  AND expires_at >= NOW()
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':t', $token);
        $stmt->bindValue(':u', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':r', $roomId, PDO::PARAM_INT);
        $stmt->bindValue(':s', $startDatetime);
        $stmt->bindValue(':e', $endDatetime);
        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lista holds ativos que intersectam um dia (para pintar slots no modal do dia).
     *
     * @return list<array{start_datetime: string, end_datetime: string, user_display_name: string, user_id: int}>
     */
    public function listActiveForRoomAndDate(int $roomId, string $dateYmd): array
    {
        $this->purgeExpired();
        if ($roomId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateYmd)) {
            return [];
        }
        $dayStart = $dateYmd . ' 00:00:00';
        $dayEnd = $dateYmd . ' 23:59:59';
        $sql = 'SELECT start_datetime, end_datetime, user_display_name, user_id
                FROM adms_room_booking_slot_holds
                WHERE room_id = :r AND expires_at >= NOW()
                  AND start_datetime < :day_end AND end_datetime > :day_start
                ORDER BY start_datetime ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':r', $roomId, PDO::PARAM_INT);
        $stmt->bindValue(':day_start', $dayStart);
        $stmt->bindValue(':day_end', $dayEnd);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    private function findBlockingHolder(int $roomId, string $start, string $end, int $excludeUserId): ?string
    {
        $sql = 'SELECT user_display_name FROM adms_room_booking_slot_holds
                WHERE room_id = :r AND user_id <> :u AND expires_at >= NOW()
                  AND start_datetime < :e AND end_datetime > :s
                ORDER BY id ASC LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':r', $roomId, PDO::PARAM_INT);
        $stmt->bindValue(':u', $excludeUserId, PDO::PARAM_INT);
        $stmt->bindValue(':s', $start);
        $stmt->bindValue(':e', $end);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $name = trim((string) ($row['user_display_name'] ?? ''));

        return $name !== '' ? $name : 'Outro utilizador';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findOwnHold(int $roomId, string $start, string $end, int $userId): ?array
    {
        $sql = 'SELECT * FROM adms_room_booking_slot_holds
                WHERE room_id = :r AND user_id = :u AND expires_at >= NOW()
                  AND start_datetime = :s AND end_datetime = :e
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':r', $roomId, PDO::PARAM_INT);
        $stmt->bindValue(':u', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':s', $start);
        $stmt->bindValue(':e', $end);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
