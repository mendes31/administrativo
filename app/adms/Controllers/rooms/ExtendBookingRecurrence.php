<?php

declare(strict_types=1);

namespace App\adms\Controllers\rooms;

use App\adms\Helpers\BookingParticipantNotificationHelper;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\RoomBookingRecurrencePlanner;
use App\adms\Helpers\RoomWaitlistService;
use App\adms\Models\Repository\BookingParticipantsRepository;
use App\adms\Models\Repository\MeetingRoomsRepository;
use App\adms\Models\Repository\RoomBookingsRepository;

/**
 * Prolonga uma série semanal existente até uma nova data final (só cria ocorrências sem conflito).
 */
final class ExtendBookingRecurrence
{
    public function index(string|int|null $id = null): void
    {
        $bookingId = $id ? (int) $id : 0;
        $adm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $bookingId <= 0) {
            header('Location: ' . $adm . 'list-bookings');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_extend_booking_recurrence', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Token de segurança inválido!</div>';
            header('Location: ' . $adm . 'view-booking/' . $bookingId);
            exit;
        }

        $newUntil = trim((string) ($_POST['recurrence_extend_until'] ?? ''));
        if ($newUntil === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $newUntil)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Indique a nova data final (AAAA-MM-DD).</div>';
            header('Location: ' . $adm . 'view-booking/' . $bookingId);
            exit;
        }

        $bookingsRepo = new RoomBookingsRepository();
        $booking = $bookingsRepo->getById($bookingId);
        if (!$booking) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Reserva não encontrada.</div>';
            header('Location: ' . $adm . 'list-bookings');
            exit;
        }

        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if (!$isSuperAdmin && (int) ($booking['user_id'] ?? 0) !== $userId) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Sem permissão para prolongar esta série.</div>';
            header('Location: ' . $adm . 'view-booking/' . $bookingId);
            exit;
        }

        if (in_array($booking['status'] ?? '', ['cancelled', 'completed'], true)) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Não é possível prolongar uma reserva cancelada ou concluída.</div>';
            header('Location: ' . $adm . 'view-booking/' . $bookingId);
            exit;
        }

        $seriesId = trim((string) ($booking['recurrence_series_id'] ?? ''));
        if ($seriesId === '') {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Esta reserva não faz parte de uma série recorrente.</div>';
            header('Location: ' . $adm . 'view-booking/' . $bookingId);
            exit;
        }

        $last = $bookingsRepo->getLastActiveInRecurrenceSeries($seriesId);
        if (!$last) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Não foi possível localizar a série no calendário.</div>';
            header('Location: ' . $adm . 'view-booking/' . $bookingId);
            exit;
        }

        $occurrences = RoomBookingRecurrencePlanner::buildWeeklyContinuation(
            (string) $last['start_datetime'],
            (string) $last['end_datetime'],
            $newUntil
        );
        if ($occurrences === []) {
            $_SESSION['msg'] = '<div class="alert alert-info" role="alert">Nenhuma ocorrência nova até essa data (a data deve ser posterior à última sessão da série).</div>';
            header('Location: ' . $adm . 'view-booking/' . $bookingId);
            exit;
        }

        $roomId = (int) ($booking['room_id'] ?? 0);
        $roomsRepo = new MeetingRoomsRepository();
        $room = $roomsRepo->getById($roomId);
        if (!$room || ($room['status'] ?? '') !== 'active') {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Sala inválida ou inativa.</div>';
            header('Location: ' . $adm . 'view-booking/' . $bookingId);
            exit;
        }

        foreach ($occurrences as [$s, $e]) {
            $ts = strtotime($s);
            $te = strtotime($e);
            if ($ts === false || $te === false) {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao processar datas.</div>';
                header('Location: ' . $adm . 'view-booking/' . $bookingId);
                exit;
            }
            $err = $this->validateRoomRulesForSlot($room, $ts, $te);
            if ($err !== null) {
                $_SESSION['msg'] = $err;
                header('Location: ' . $adm . 'view-booking/' . $bookingId);
                exit;
            }
        }

        $participantRows = $bookingsRepo->getParticipantsByBookingId($bookingId);
        $internalIds = [];
        $guestEmails = [];
        foreach ($participantRows as $r) {
            $uid = (int) ($r['user_id'] ?? 0);
            if ($uid > 0) {
                $internalIds[] = $uid;
            }
            $ge = mb_strtolower(trim((string) ($r['guest_email'] ?? '')));
            if ($ge !== '' && filter_var($ge, FILTER_VALIDATE_EMAIL)) {
                $guestEmails[$ge] = $ge;
            }
        }
        $internalIds = array_values(array_unique($internalIds));
        $guestEmails = array_values(array_slice(array_values($guestEmails), 0, 30));

        $pdo = $bookingsRepo->getConnection();
        $waitlistService = new RoomWaitlistService();
        $notifyHelper = new BookingParticipantNotificationHelper();
        $partsRepo = new BookingParticipantsRepository();

        $bookingTemplate = [
            'room_id' => $roomId,
            'user_id' => (int) ($booking['user_id'] ?? 0),
            'title' => (string) ($booking['title'] ?? ''),
            'description' => (string) ($booking['description'] ?? ''),
            'status' => (string) ($booking['status'] ?? 'confirmed'),
            'requires_approval' => (bool) ($booking['requires_approval'] ?? false),
            'has_additional_requests' => false,
            'recurrence_series_id' => $seriesId,
        ];

        $gotLock = RoomWaitlistService::acquireRoomBookingLock($pdo, $roomId);
        if (!$gotLock) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Não foi possível validar o horário neste momento. Tente novamente.</div>';
            header('Location: ' . $adm . 'view-booking/' . $bookingId);
            exit;
        }

        $createdIds = [];
        try {
            foreach ($occurrences as [$cs, $ce]) {
                if ($bookingsRepo->hasConflict($roomId, $cs, $ce)) {
                    $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Não foi possível prolongar: conflito de horário numa das novas datas. Escolha uma data final mais curta ou outro intervalo.</div>';
                    header('Location: ' . $adm . 'view-booking/' . $bookingId);
                    exit;
                }
            }

            $pdo->beginTransaction();
            try {
                foreach ($occurrences as [$cs, $ce]) {
                    $row = $bookingTemplate;
                    $row['start_datetime'] = $cs;
                    $row['end_datetime'] = $ce;
                    $newId = $bookingsRepo->create($row);
                    $createdIds[] = $newId;
                    foreach ($internalIds as $uid) {
                        if ($uid > 0) {
                            $partsRepo->insertParticipant($newId, $uid, false, 'pending');
                        }
                    }
                    foreach ($guestEmails as $em) {
                        $partsRepo->insertGuestParticipant($newId, $em, '');
                    }
                }
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }

            if (count($createdIds) > 1) {
                if ($internalIds !== []) {
                    try {
                        $notifyHelper->sendInvitesForRecurringSeries($createdIds, $internalIds);
                    } catch (\Throwable) {
                    }
                }
                if ($guestEmails !== []) {
                    try {
                        $notifyHelper->sendGuestInvitesForRecurringSeries($createdIds);
                    } catch (\Throwable) {
                    }
                }
            } else {
                foreach ($createdIds as $newBid) {
                    if ($internalIds !== []) {
                        try {
                            $notifyHelper->sendInvites($newBid, $internalIds);
                        } catch (\Throwable) {
                        }
                    }
                    if ($guestEmails !== []) {
                        try {
                            $notifyHelper->sendGuestInvites($newBid);
                        } catch (\Throwable) {
                        }
                    }
                }
            }

            $roomLabel = (string) ($room['name'] ?? 'Sala');
            $winner = (int) ($booking['user_id'] ?? 0);
            foreach ($createdIds as $i => $newBid) {
                [$cs, $ce] = $occurrences[$i];
                $waitlistService->finalizeAfterBookingCreated($roomId, $cs, $ce, $winner, $newBid, $roomLabel);
            }

            $n = count($createdIds);
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Série prolongada: foram adicionadas ' . $n . ' nova(s) ocorrência(s).</div>';
            header('Location: ' . $adm . 'view-booking/' . $bookingId);
            exit;
        } catch (\Throwable) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao gravar as novas ocorrências. Tente novamente.</div>';
            header('Location: ' . $adm . 'view-booking/' . $bookingId);
            exit;
        } finally {
            RoomWaitlistService::releaseRoomBookingLock($pdo, $roomId);
        }
    }

    /**
     * @return string|null HTML de alerta de erro ou null se válido
     */
    private function validateRoomRulesForSlot(array $room, int $startTimestamp, int $endTimestamp): ?string
    {
        $now = time();
        $hoursUntilStart = ($startTimestamp - $now) / 3600;
        $daysUntilStart = ($startTimestamp - $now) / 86400;
        $durationHours = ($endTimestamp - $startTimestamp) / 3600;

        if (!empty($room['min_advance_booking_hours']) && $hoursUntilStart < $room['min_advance_booking_hours']) {
            return '<div class="alert alert-danger" role="alert">Erro: A reserva deve ser feita com pelo menos ' . $room['min_advance_booking_hours'] . ' horas de antecedência!</div>';
        }

        if (!empty($room['max_advance_booking_days']) && $daysUntilStart > $room['max_advance_booking_days']) {
            return '<div class="alert alert-danger" role="alert">Erro: A reserva não pode ser feita com mais de ' . $room['max_advance_booking_days'] . ' dias de antecedência!</div>';
        }

        if (!empty($room['booking_duration_limit_hours']) && $durationHours > $room['booking_duration_limit_hours']) {
            return '<div class="alert alert-danger" role="alert">Erro: A duração máxima permitida é de ' . $room['booking_duration_limit_hours'] . ' horas!</div>';
        }

        return null;
    }
}
