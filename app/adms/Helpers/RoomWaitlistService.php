<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\BookingWaitlistRepository;
use App\adms\Models\Repository\NotificationsRepository;
use PDO;

/**
 * Lista de espera (opção B): ao cancelar, notifica todos com sobreposição;
 * ao reservar, apenas uma reserva válida — demais recebem aviso de horário preenchido.
 */
final class RoomWaitlistService
{
    private const LOCK_PREFIX = 'adms_room_booking_';

    private BookingWaitlistRepository $waitlistRepo;

    private NotificationsRepository $notificationsRepo;

    public function __construct(
        ?BookingWaitlistRepository $waitlistRepo = null,
        ?NotificationsRepository $notificationsRepo = null
    ) {
        $this->waitlistRepo = $waitlistRepo ?? new BookingWaitlistRepository();
        $this->notificationsRepo = $notificationsRepo ?? new NotificationsRepository();
    }

    /**
     * Lock por sala para evitar duas reservas simultâneas no mesmo intervalo.
     *
     * @return bool true se obteve o lock
     */
    public static function acquireRoomBookingLock(\PDO $pdo, int $roomId, int $timeoutSeconds = 15): bool
    {
        $name = self::LOCK_PREFIX . $roomId;
        $stmt = $pdo->query('SELECT GET_LOCK(' . $pdo->quote($name) . ', ' . (int)$timeoutSeconds . ')');
        if ($stmt === false) {
            return false;
        }

        return (int)$stmt->fetchColumn() === 1;
    }

    public static function releaseRoomBookingLock(\PDO $pdo, int $roomId): void
    {
        $name = self::LOCK_PREFIX . $roomId;
        $pdo->query('SELECT RELEASE_LOCK(' . $pdo->quote($name) . ')');
    }

    /**
     * Chamado quando um intervalo deixa de estar ocupado: cancelamento ou reagendamento (horário/sala antigos).
     *
     * @param array<string, mixed> $booking Registro de adms_room_bookings (getById) ou payload com room_id, start_datetime, end_datetime, room_name
     */
    public function notifyAllWaitingOnCancellation(array $booking): void
    {
        $roomId = (int)($booking['room_id'] ?? 0);
        $start = (string)($booking['start_datetime'] ?? '');
        $end = (string)($booking['end_datetime'] ?? '');
        if ($roomId <= 0 || $start === '' || $end === '') {
            return;
        }

        $rows = $this->waitlistRepo->findOverlappingByStatuses($roomId, $start, $end, ['waiting']);
        if ($rows === []) {
            return;
        }

        $ids = array_map(static fn (array $r) => (int)$r['id'], $rows);
        $this->waitlistRepo->markAsNotifiedByIds($ids);

        $roomName = (string)($booking['room_name'] ?? 'Sala');
        $startBr = self::formatPtBr($start);
        $endBr = self::formatPtBr($end);
        $bookUrl = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/book-room?room_id=' . $roomId;

        foreach ($rows as $row) {
            $uid = (int)($row['user_id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            $this->notificationsRepo->create([
                'user_id' => $uid,
                'type' => 'room_waitlist_vacancy',
                'title' => 'Vaga na sala — ' . $roomName,
                'message' => 'Abriu horário em ' . $roomName . ' (' . $startBr . ' – ' . $endBr . '). '
                    . 'A vaga é única: quem confirmar a reserva primeiro fica com o horário. Acesse o calendário da sala.',
                'link_url' => $bookUrl,
                'entity_type' => 'meeting_room',
                'entity_id' => $roomId,
            ]);
            $this->maybeSendVacancyWhatsApp($uid, $roomName, $startBr, $endBr, $bookUrl);
        }
    }

    /**
     * Após criar reserva com sucesso: define accepted/expired na fila e avisa quem perdeu.
     *
     * @return list<int> user_ids notificados como perdedores
     */
    public function finalizeAfterBookingCreated(
        int $roomId,
        string $startDatetime,
        string $endDatetime,
        int $winnerUserId,
        int $bookingId,
        string $roomName
    ): array {
        try {
            $losers = $this->waitlistRepo->resolveAfterBookingWon(
                $roomId,
                $startDatetime,
                $endDatetime,
                $winnerUserId,
                $bookingId
            );
            if ($losers === []) {
                return [];
            }

            $startBr = self::formatPtBr($startDatetime);
            $endBr = self::formatPtBr($endDatetime);

            foreach ($losers as $uid) {
                if ($uid <= 0) {
                    continue;
                }
                $this->notificationsRepo->create([
                    'user_id' => $uid,
                    'type' => 'room_waitlist_lost',
                    'title' => 'Horário já reservado — ' . $roomName,
                    'message' => 'Outro utilizador reservou antes o horário em ' . $roomName . ' (' . $startBr . ' – ' . $endBr . '). '
                        . 'Este intervalo deixou de estar disponível para a sua entrada na lista de espera.',
                    'entity_type' => 'room_booking',
                    'entity_id' => $bookingId,
                ]);
                $this->maybeSendLostWhatsApp($uid, $roomName, $startBr, $endBr);
            }

            return $losers;
        } catch (\Throwable $e) {
            GenerateLog::generateLog('alert', 'Lista de espera: finalizeAfterBookingCreated falhou (reserva já gravada).', [
                'message' => $e->getMessage(),
                'room_id' => $roomId,
                'booking_id' => $bookingId,
            ]);

            return [];
        }
    }

    private function maybeSendVacancyWhatsApp(int $userId, string $roomName, string $startBr, string $endBr, string $bookUrl): void
    {
        $u = $this->fetchUserWhatsAppRow($userId);
        if ($u === null || empty($u['celular'])) {
            return;
        }
        if (empty($u['receber_notificacoes_whatsapp'])) {
            return;
        }
        $msg = "Olá! Abriu vaga na sala *{$roomName}* ({$startBr} – {$endBr}). "
            . "A vaga é única — quem reservar primeiro fica com o horário. "
            . "Acesse: {$bookUrl}";
        SendWhatsAppService::sendMessage(preg_replace('/\D/', '', (string)$u['celular']), $msg);
    }

    private function maybeSendLostWhatsApp(int $userId, string $roomName, string $startBr, string $endBr): void
    {
        $u = $this->fetchUserWhatsAppRow($userId);
        if ($u === null || empty($u['celular'])) {
            return;
        }
        if (empty($u['receber_notificacoes_whatsapp'])) {
            return;
        }
        $msg = "O horário em *{$roomName}* ({$startBr} – {$endBr}) já foi reservado por outro utilizador. "
            . 'Este intervalo não está mais disponível para a sua lista de espera.';
        SendWhatsAppService::sendMessage(preg_replace('/\D/', '', (string)$u['celular']), $msg);
    }

    /**
     * @return array{celular: string|null, receber_notificacoes_whatsapp: int}|null
     */
    private function fetchUserWhatsAppRow(int $userId): ?array
    {
        $pdo = $this->waitlistRepo->getConnection();
        $sql = 'SELECT celular, COALESCE(receber_notificacoes_whatsapp, 1) AS receber_notificacoes_whatsapp
                FROM adms_users WHERE id = :id LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private static function formatPtBr(string $mysqlDatetime): string
    {
        $ts = strtotime($mysqlDatetime);

        return $ts ? date('d/m/Y H:i', $ts) : $mysqlDatetime;
    }
}
