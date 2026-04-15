<?php

declare(strict_types=1);

namespace App\adms\Controllers\rooms;

use App\adms\Helpers\BookingParticipantNotificationHelper;
use App\adms\Helpers\UserAgendaConflictHelper;
use App\adms\Models\Repository\BookingParticipantsRepository;

/**
 * Resposta pública ao convite de participação (sem login). URL: meeting-booking-rsvp/{token} ou .../{token}/accept|decline
 */
final class MeetingBookingRsvp
{
    public function index(?string $path = null): void
    {
        $path = $path !== null ? trim($path, '/') : '';

        if ($path === '') {
            $this->outputPage('meeting_booking_rsvp_public', [
                'mode' => 'result',
                'heading' => 'Convite inválido',
                'message' => 'Este link está incompleto ou expirado.',
                'success' => false,
            ]);
            return;
        }

        $parts = explode('/', $path, 2);
        $token = $parts[0] ?? '';
        $action = isset($parts[1]) ? strtolower(trim($parts[1])) : null;

        $repo = new BookingParticipantsRepository();
        $row = $repo->findByRsvpToken($token);
        if (!$row) {
            $this->outputPage('meeting_booking_rsvp_public', [
                'mode' => 'result',
                'heading' => 'Convite inválido',
                'message' => 'Não encontrámos este convite. Se já respondeu, o organizador vê o estado na reserva.',
                'success' => false,
            ]);
            return;
        }

        $bookingStatus = (string) ($row['booking_status'] ?? '');
        if (in_array($bookingStatus, ['cancelled', 'completed'], true)) {
            $this->outputPage('meeting_booking_rsvp_public', [
                'mode' => 'result',
                'heading' => 'Reserva encerrada',
                'message' => 'Esta reserva já foi cancelada ou concluída. Não é possível alterar a resposta.',
                'success' => false,
            ]);
            return;
        }

        if ($action === null || $action === '') {
            $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
            $t = rawurlencode($token);
            $this->outputPage('meeting_booking_rsvp_public', [
                'mode' => 'choice',
                'heading' => 'Convite para reunião',
                'title' => (string) ($row['booking_title'] ?? 'Reunião'),
                'room' => (string) ($row['room_name'] ?? ''),
                'start' => (string) ($row['start_datetime'] ?? ''),
                'end' => (string) ($row['end_datetime'] ?? ''),
                'accept_url' => $base . '/meeting-booking-rsvp/' . $t . '/accept',
                'decline_url' => $base . '/meeting-booking-rsvp/' . $t . '/decline',
                'my_calendar_url' => $base . '/my-calendar',
            ]);
            return;
        }

        if (!in_array($action, ['accept', 'decline'], true)) {
            $this->outputPage('meeting_booking_rsvp_public', [
                'mode' => 'result',
                'heading' => 'Pedido inválido',
                'message' => 'Ação não reconhecida.',
                'success' => false,
            ]);
            return;
        }

        if ($action === 'accept') {
            $participantUid = (int) ($row['user_id'] ?? 0);
            $startB = (string) ($row['start_datetime'] ?? '');
            $endB = (string) ($row['end_datetime'] ?? '');
            $bookingId = (int) ($row['booking_id'] ?? 0);
            $ack = isset($_GET['ack_conflict']) && (string) $_GET['ack_conflict'] === '1';
            if ($participantUid > 0 && !$ack && $startB !== '' && $endB !== '') {
                $overlaps = UserAgendaConflictHelper::findOverlaps($participantUid, $startB, $endB, $bookingId > 0 ? $bookingId : null);
                if ($overlaps !== []) {
                    $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
                    $t = rawurlencode($token);
                    $this->outputPage('meeting_booking_rsvp_public', [
                        'mode' => 'conflict',
                        'heading' => 'Conflito de agenda',
                        'title' => (string) ($row['booking_title'] ?? 'Reunião'),
                        'room' => (string) ($row['room_name'] ?? ''),
                        'start' => $startB,
                        'end' => $endB,
                        'overlaps' => $overlaps,
                        'accept_force_url' => $base . '/meeting-booking-rsvp/' . $t . '/accept?ack_conflict=1',
                        'decline_url' => $base . '/meeting-booking-rsvp/' . $t . '/decline',
                        'my_calendar_url' => $base . '/my-calendar',
                    ]);

                    return;
                }
            }
        }

        $newStatus = $action === 'decline' ? 'declined' : 'confirmed';
        $ok = $repo->updateParticipantStatusByToken($token, $newStatus);
        if (!$ok) {
            $this->outputPage('meeting_booking_rsvp_public', [
                'mode' => 'result',
                'heading' => 'Erro',
                'message' => 'Não foi possível registar a resposta. Tente novamente.',
                'success' => false,
            ]);
            return;
        }

        $participantId = (int) ($row['user_id'] ?? 0);
        $bookingId = (int) ($row['booking_id'] ?? 0);
        $guestLabel = trim((string) ($row['guest_name'] ?? ''));
        if ($guestLabel === '') {
            $guestLabel = trim((string) ($row['guest_email'] ?? ''));
        }
        try {
            (new BookingParticipantNotificationHelper())->notifyOrganizerOfRsvp($bookingId, $participantId, $newStatus, $guestLabel);
        } catch (\Throwable) {
        }

        $msg = $newStatus === 'declined'
            ? 'Registámos a sua recusa. O organizador foi informado.'
            : 'Obrigado! A sua presença foi confirmada. O organizador foi informado.';
        $this->outputPage('meeting_booking_rsvp_public', [
            'mode' => 'result',
            'heading' => 'Resposta registada',
            'message' => $msg,
            'success' => true,
        ]);
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function outputPage(string $template, array $vars): void
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        extract($vars, EXTR_OVERWRITE);
        $file = __DIR__ . '/../../Views/rooms/' . $template . '.php';
        if (!is_file($file)) {
            echo '<!DOCTYPE html><html><body><p>Erro ao carregar a página.</p></body></html>';
            exit;
        }
        include $file;
        exit;
    }
}
