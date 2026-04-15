<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\BookingParticipantsRepository;
use App\adms\Models\Repository\NotificationsRepository;
use App\adms\Models\Repository\RoomBookingsRepository;
use App\adms\Models\Repository\UsersRepository;

/**
 * Convites e RSVP de participantes em reservas de sala (e-mail + in-app).
 */
final class BookingParticipantNotificationHelper
{
    public function __construct(
        private ?RoomBookingsRepository $bookingsRepo = null,
        private ?UsersRepository $usersRepo = null,
        private ?NotificationsRepository $notificationsRepo = null,
        private ?BookingParticipantsRepository $participantsRepo = null,
    ) {
        $this->bookingsRepo = $bookingsRepo ?? new RoomBookingsRepository();
        $this->usersRepo = $usersRepo ?? new UsersRepository();
        $this->notificationsRepo = $notificationsRepo ?? new NotificationsRepository();
        $this->participantsRepo = $participantsRepo ?? new BookingParticipantsRepository();
    }

    /**
     * @return array{0: ?string, 1: ?string} [e-mail, nome] do organizador para Reply-To (evita From arbitrário no SMTP)
     */
    private function organizerReplyToFromBooking(array $booking): array
    {
        $email = trim((string) ($booking['user_email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [null, null];
        }
        $name = trim((string) ($booking['user_name'] ?? ''));
        if ($name === '') {
            $name = $email;
        }

        return [$email, $name];
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string, 3: ?string} [replyToEmail, replyToName, fromDisplayName, fromAddressOverride]
     */
    private function roomBookingMailFromContext(array $booking): array
    {
        [$replyToEmail, $replyToName] = $this->organizerReplyToFromBooking($booking);
        $orgName = trim((string) ($booking['user_name'] ?? ''));
        if ($orgName === '' && $replyToName !== null && $replyToName !== '') {
            $orgName = $replyToName;
        }
        if ($orgName === '' && $replyToEmail !== null && $replyToEmail !== '') {
            $orgName = $replyToEmail;
        }
        $fromDisplay = $orgName !== '' ? $orgName . ' | Salas' : null;
        $fromAddrOverride = $replyToEmail;

        return [$replyToEmail, $replyToName, $fromDisplay, $fromAddrOverride];
    }

    private function formatRoomBookingSlotPortuguese(string $startSql, string $endSql): string
    {
        $ts = strtotime($startSql);
        $te = strtotime($endSql);
        if ($ts === false) {
            return $startSql;
        }
        $a = date('d/m/Y H:i', $ts);
        if ($te !== false) {
            return $a . ' → ' . date('d/m/Y H:i', $te);
        }

        return $a;
    }

    /**
     * Envia convite (e-mail com links RSVP + notificação in-app) aos utilizadores indicados nesta reserva.
     *
     * @param int[] $participantUserIds
     */
    public function sendInvites(int $bookingId, array $participantUserIds): void
    {
        $participantUserIds = array_values(array_unique(array_filter(array_map('intval', $participantUserIds))));
        if ($participantUserIds === []) {
            return;
        }

        $booking = $this->bookingsRepo->getById($bookingId);
        if (!$booking) {
            return;
        }

        $organizerId = (int) ($booking['user_id'] ?? 0);
        $title = (string) ($booking['title'] ?? 'Reunião');
        $room = (string) ($booking['room_name'] ?? 'Sala');
        $start = (string) ($booking['start_datetime'] ?? '');
        $end = (string) ($booking['end_datetime'] ?? '');
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        $viewBookingLink = $base . '/view-booking/' . $bookingId;

        $placeholders = implode(',', array_fill(0, count($participantUserIds), '?'));
        $sql = "SELECT bp.user_id, bp.rsvp_token, u.name, u.email
                FROM adms_booking_participants bp
                INNER JOIN adms_users u ON bp.user_id = u.id
                WHERE bp.booking_id = ? AND bp.user_id IN ({$placeholders})";
        $conn = $this->participantsRepo->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $bookingId, \PDO::PARAM_INT);
        $i = 2;
        foreach ($participantUserIds as $uid) {
            $stmt->bindValue($i++, $uid, \PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        [$replyToEmail, $replyToName, $fromDisplay, $fromAddrOverride] = $this->roomBookingMailFromContext($booking);

        foreach ($rows as $row) {
            $uid = (int) ($row['user_id'] ?? 0);
            $token = trim((string) ($row['rsvp_token'] ?? ''));
            $email = trim((string) ($row['email'] ?? ''));
            $name = (string) ($row['name'] ?? $email);
            if ($uid <= 0 || $token === '') {
                continue;
            }

            $openLink = $base . '/meeting-booking-rsvp/' . rawurlencode($token);
            $acceptLink = $openLink . '/accept';
            $declineLink = $openLink . '/decline';

            $summary = "Sala: {$room}. Início: {$start}. Fim: {$end}.";

            $this->notificationsRepo->create([
                'user_id' => $uid,
                'type' => 'room_booking_invite',
                'title' => 'Convite: ' . $title,
                'message' => $summary . ' Responda pelo link do e-mail ou aqui: ' . $openLink,
                'link_url' => $openLink,
                'entity_type' => 'room_booking',
                'entity_id' => $bookingId,
            ]);

            if ($email === '') {
                continue;
            }

            $subject = '[Salas] Convite: ' . $title;
            $body = '<p>Olá, ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<p>Foi convidado(a) para a reunião <strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
                . '<p><strong>Sala:</strong> ' . htmlspecialchars($room, ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Início:</strong> ' . htmlspecialchars($start, ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Fim:</strong> ' . htmlspecialchars($end, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p>Indique se comparece:</p>'
                . '<p><a href="' . htmlspecialchars($acceptLink, ENT_QUOTES, 'UTF-8') . '">Aceitar</a>'
                . ' &nbsp;|&nbsp; <a href="' . htmlspecialchars($declineLink, ENT_QUOTES, 'UTF-8') . '">Recusar</a></p>'
                . '<p><small>Ou abra esta página para escolher: <a href="' . htmlspecialchars($openLink, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($openLink, ENT_QUOTES, 'UTF-8') . '</a></small></p>'
                . '<p><small>Organizador pode acompanhar respostas em: ' . htmlspecialchars($viewBookingLink, ENT_QUOTES, 'UTF-8') . '</small></p>';

            $alt = "Convite: {$title}\nSala: {$room}\nInício: {$start}\nFim: {$end}\n\nAceitar: {$acceptLink}\nRecusar: {$declineLink}\n";

            try {
                SendEmailService::sendEmail($email, $name, $subject, $body, $alt, $replyToEmail, $replyToName, $fromDisplay, $fromAddrOverride);
            } catch (\Throwable) {
            }
        }

        // Opcional: avisar o organizador que convites foram enviados
        if ($organizerId > 0) {
            $this->notificationsRepo->create([
                'user_id' => $organizerId,
                'type' => 'room_booking_invites_sent',
                'title' => 'Convites enviados',
                'message' => 'Foram enviados convites por e-mail/in-app aos participantes da reunião: ' . $title . '.',
                'link_url' => $viewBookingLink,
                'entity_type' => 'room_booking',
                'entity_id' => $bookingId,
            ]);
        }
    }

    /**
     * Envia convite por e-mail a participantes externos (sem user_id).
     */
    public function sendGuestInvites(int $bookingId): void
    {
        $booking = $this->bookingsRepo->getById($bookingId);
        if (!$booking) {
            return;
        }

        $organizerId = (int) ($booking['user_id'] ?? 0);
        $title = (string) ($booking['title'] ?? 'Reunião');
        $room = (string) ($booking['room_name'] ?? 'Sala');
        $start = (string) ($booking['start_datetime'] ?? '');
        $end = (string) ($booking['end_datetime'] ?? '');
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        $viewBookingLink = $base . '/view-booking/' . $bookingId;

        $sql = 'SELECT guest_email, guest_name, rsvp_token FROM adms_booking_participants
                WHERE booking_id = :bid AND user_id IS NULL AND guest_email IS NOT NULL AND guest_email <> \'\'';
        $stmt = $this->participantsRepo->getConnection()->prepare($sql);
        $stmt->bindValue(':bid', $bookingId, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        [$replyToEmail, $replyToName, $fromDisplay, $fromAddrOverride] = $this->roomBookingMailFromContext($booking);

        foreach ($rows as $row) {
            $email = trim((string) ($row['guest_email'] ?? ''));
            $token = trim((string) ($row['rsvp_token'] ?? ''));
            if ($email === '' || $token === '') {
                continue;
            }
            $name = trim((string) ($row['guest_name'] ?? '')) ?: $email;
            $openLink = $base . '/meeting-booking-rsvp/' . rawurlencode($token);
            $acceptLink = $openLink . '/accept';
            $declineLink = $openLink . '/decline';
            $summary = "Sala: {$room}. Início: {$start}. Fim: {$end}.";

            $subject = '[Salas] Convite: ' . $title;
            $body = '<p>Olá, ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<p>Foi convidado(a) para a reunião <strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
                . '<p><strong>Sala:</strong> ' . htmlspecialchars($room, ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Início:</strong> ' . htmlspecialchars($start, ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Fim:</strong> ' . htmlspecialchars($end, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p>Indique se comparece:</p>'
                . '<p><a href="' . htmlspecialchars($acceptLink, ENT_QUOTES, 'UTF-8') . '">Aceitar</a>'
                . ' &nbsp;|&nbsp; <a href="' . htmlspecialchars($declineLink, ENT_QUOTES, 'UTF-8') . '">Recusar</a></p>'
                . '<p><small>Ou abra: <a href="' . htmlspecialchars($openLink, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($openLink, ENT_QUOTES, 'UTF-8') . '</a></small></p>'
                . '<p><small>Organizador: ' . htmlspecialchars($viewBookingLink, ENT_QUOTES, 'UTF-8') . '</small></p>';
            $alt = "Convite: {$title}\nAceitar: {$acceptLink}\nRecusar: {$declineLink}\n";

            try {
                SendEmailService::sendEmail($email, $name, $subject, $body, $alt, $replyToEmail, $replyToName, $fromDisplay, $fromAddrOverride);
            } catch (\Throwable) {
            }
        }

        if ($organizerId > 0 && $rows !== []) {
            $this->notificationsRepo->create([
                'user_id' => $organizerId,
                'type' => 'room_booking_invites_sent',
                'title' => 'Convites enviados',
                'message' => 'Foram enviados convites por e-mail a participantes externos da reunião: ' . $title . '.',
                'link_url' => $viewBookingLink,
                'entity_type' => 'room_booking',
                'entity_id' => $bookingId,
            ]);
        }
    }

    /**
     * Uma mensagem por participante interno com todas as datas da série (links RSVP por ocorrência).
     *
     * @param int[] $bookingIds IDs das reservas da mesma série (>= 2)
     * @param int[] $participantUserIds
     */
    public function sendInvitesForRecurringSeries(array $bookingIds, array $participantUserIds): void
    {
        $bookingIds = array_values(array_unique(array_filter(array_map('intval', $bookingIds), static fn (int $v): bool => $v > 0)));
        $participantUserIds = array_values(array_unique(array_filter(array_map('intval', $participantUserIds), static fn (int $v): bool => $v > 0)));
        if ($bookingIds === [] || $participantUserIds === []) {
            return;
        }
        if (count($bookingIds) === 1) {
            $this->sendInvites($bookingIds[0], $participantUserIds);

            return;
        }

        $conn = $this->participantsRepo->getConnection();
        $ph = implode(',', array_fill(0, count($bookingIds), '?'));
        $ordSql = "SELECT id FROM adms_room_bookings WHERE id IN ($ph) ORDER BY start_datetime ASC, id ASC";
        $stmt = $conn->prepare($ordSql);
        foreach ($bookingIds as $i => $id) {
            $stmt->bindValue($i + 1, $id, \PDO::PARAM_INT);
        }
        $stmt->execute();
        $orderedIds = array_map(static fn ($v) => (int) $v, $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: []);
        if ($orderedIds === []) {
            return;
        }

        $firstId = $orderedIds[0];
        $booking = $this->bookingsRepo->getById($firstId);
        if (!$booking) {
            return;
        }

        $organizerId = (int) ($booking['user_id'] ?? 0);
        $title = (string) ($booking['title'] ?? 'Reunião');
        $room = (string) ($booking['room_name'] ?? 'Sala');
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        $viewBookingLink = $base . '/view-booking/' . $firstId;
        [$replyToEmail, $replyToName, $fromDisplay, $fromAddrOverride] = $this->roomBookingMailFromContext($booking);

        $phOrd = implode(',', array_fill(0, count($orderedIds), '?'));
        $stmt2 = $conn->prepare(
            "SELECT id, start_datetime, end_datetime FROM adms_room_bookings WHERE id IN ($phOrd) ORDER BY start_datetime ASC, id ASC"
        );
        foreach ($orderedIds as $i => $id) {
            $stmt2->bindValue($i + 1, $id, \PDO::PARAM_INT);
        }
        $stmt2->execute();
        $slots = $stmt2->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $slotById = [];
        foreach ($slots as $s) {
            $slotById[(int) ($s['id'] ?? 0)] = $s;
        }
        $n = count($orderedIds);

        foreach ($participantUserIds as $uid) {
            if ($uid <= 0) {
                continue;
            }
            $ph2 = implode(',', array_fill(0, count($orderedIds), '?'));
            $sql = "SELECT bp.booking_id, bp.rsvp_token, u.name, u.email
                    FROM adms_booking_participants bp
                    INNER JOIN adms_users u ON bp.user_id = u.id
                    WHERE bp.user_id = ? AND bp.booking_id IN ($ph2)";
            $st = $conn->prepare($sql);
            $st->bindValue(1, $uid, \PDO::PARAM_INT);
            $j = 2;
            foreach ($orderedIds as $bid) {
                $st->bindValue($j++, $bid, \PDO::PARAM_INT);
            }
            $st->execute();
            $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            if ($rows === []) {
                continue;
            }
            $row0 = $rows[0];
            $email = trim((string) ($row0['email'] ?? ''));
            $name = (string) ($row0['name'] ?? $email);
            $byBid = [];
            foreach ($rows as $r) {
                $byBid[(int) ($r['booking_id'] ?? 0)] = $r;
            }

            $tableHtml = '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;border-color:#dee2e6">'
                . '<thead><tr><th align="left">Data / horário</th><th align="left">Responder</th></tr></thead><tbody>';
            $altLines = [];
            foreach ($orderedIds as $bid) {
                if (!isset($byBid[$bid], $slotById[$bid])) {
                    continue;
                }
                $r = $byBid[$bid];
                $token = trim((string) ($r['rsvp_token'] ?? ''));
                if ($token === '') {
                    continue;
                }
                $stt = (string) ($slotById[$bid]['start_datetime'] ?? '');
                $en = (string) ($slotById[$bid]['end_datetime'] ?? '');
                $slotLabel = $this->formatRoomBookingSlotPortuguese($stt, $en);
                $openLink = $base . '/meeting-booking-rsvp/' . rawurlencode($token);
                $acceptLink = $openLink . '/accept';
                $declineLink = $openLink . '/decline';
                $tableHtml .= '<tr><td>' . htmlspecialchars($slotLabel, ENT_QUOTES, 'UTF-8') . '</td><td>'
                    . '<a href="' . htmlspecialchars($acceptLink, ENT_QUOTES, 'UTF-8') . '">Aceitar</a> | '
                    . '<a href="' . htmlspecialchars($declineLink, ENT_QUOTES, 'UTF-8') . '">Recusar</a><br>'
                    . '<small><a href="' . htmlspecialchars($openLink, ENT_QUOTES, 'UTF-8') . '">Abrir convite</a></small></td></tr>';
                $altLines[] = "{$slotLabel} — Aceitar: {$acceptLink} — Recusar: {$declineLink}";
            }
            $tableHtml .= '</tbody></table>';
            if ($altLines === []) {
                continue;
            }

            $summaryMsg = "Série semanal de {$n} sessões na sala {$room}. Responda em cada data (cada sessão tem confirmação própria).";
            $this->notificationsRepo->create([
                'user_id' => $uid,
                'type' => 'room_booking_invite',
                'title' => 'Convite (série): ' . $title,
                'message' => $summaryMsg . ' Ver: ' . $viewBookingLink,
                'link_url' => $viewBookingLink,
                'entity_type' => 'room_booking',
                'entity_id' => $firstId,
            ]);

            if ($email === '') {
                continue;
            }

            $subject = '[Salas] Convite (série): ' . $title;
            $body = '<p>Olá, ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<p>Foi convidado(a) para a reunião recorrente <strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong> '
                . '(<strong>' . $n . ' sessões</strong> semanais, mesma sala e horário de cada vez).</p>'
                . '<p><strong>Sala:</strong> ' . htmlspecialchars($room, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p>Indique <strong>em cada data</strong> se comparece (cada ocorrência tem resposta própria no sistema):</p>'
                . $tableHtml
                . '<p><small>Resumo da reserva: <a href="' . htmlspecialchars($viewBookingLink, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($viewBookingLink, ENT_QUOTES, 'UTF-8') . '</a></small></p>';

            $alt = "Convite (série): {$title}\nSala: {$room}\n{$n} sessões\n\n" . implode("\n", $altLines) . "\n";

            try {
                SendEmailService::sendEmail($email, $name, $subject, $body, $alt, $replyToEmail, $replyToName, $fromDisplay, $fromAddrOverride);
            } catch (\Throwable) {
            }
        }

        if ($organizerId > 0) {
            $this->notificationsRepo->create([
                'user_id' => $organizerId,
                'type' => 'room_booking_invites_sent',
                'title' => 'Convites enviados (série)',
                'message' => 'Foram enviados convites por e-mail (um por participante) para a série de ' . $n . ' sessões: ' . $title . '.',
                'link_url' => $viewBookingLink,
                'entity_type' => 'room_booking',
                'entity_id' => $firstId,
            ]);
        }
    }

    /**
     * Um e-mail por endereço externo com todas as datas da série.
     *
     * @param int[] $bookingIds
     */
    public function sendGuestInvitesForRecurringSeries(array $bookingIds): void
    {
        $bookingIds = array_values(array_unique(array_filter(array_map('intval', $bookingIds), static fn (int $v): bool => $v > 0)));
        if ($bookingIds === []) {
            return;
        }
        if (count($bookingIds) === 1) {
            $this->sendGuestInvites($bookingIds[0]);

            return;
        }

        $conn = $this->participantsRepo->getConnection();
        $ph = implode(',', array_fill(0, count($bookingIds), '?'));
        $ordSql = "SELECT id FROM adms_room_bookings WHERE id IN ($ph) ORDER BY start_datetime ASC, id ASC";
        $stmt = $conn->prepare($ordSql);
        foreach ($bookingIds as $i => $id) {
            $stmt->bindValue($i + 1, $id, \PDO::PARAM_INT);
        }
        $stmt->execute();
        $orderedIds = array_map(static fn ($v) => (int) $v, $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: []);
        if ($orderedIds === []) {
            return;
        }

        $firstId = $orderedIds[0];
        $booking = $this->bookingsRepo->getById($firstId);
        if (!$booking) {
            return;
        }

        $organizerId = (int) ($booking['user_id'] ?? 0);
        $title = (string) ($booking['title'] ?? 'Reunião');
        $room = (string) ($booking['room_name'] ?? 'Sala');
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        $viewBookingLink = $base . '/view-booking/' . $firstId;
        [$replyToEmail, $replyToName, $fromDisplay, $fromAddrOverride] = $this->roomBookingMailFromContext($booking);

        $phOrd = implode(',', array_fill(0, count($orderedIds), '?'));
        $sql = "SELECT bp.booking_id, bp.guest_email, bp.guest_name, bp.rsvp_token,
                       rb.start_datetime, rb.end_datetime
                FROM adms_booking_participants bp
                INNER JOIN adms_room_bookings rb ON rb.id = bp.booking_id
                WHERE bp.booking_id IN ($phOrd) AND bp.user_id IS NULL
                  AND bp.guest_email IS NOT NULL AND bp.guest_email <> ''";
        $st = $conn->prepare($sql);
        foreach ($orderedIds as $i => $id) {
            $st->bindValue($i + 1, $id, \PDO::PARAM_INT);
        }
        $st->execute();
        $allRows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $byGuest = [];
        foreach ($allRows as $row) {
            $em = mb_strtolower(trim((string) ($row['guest_email'] ?? '')));
            if ($em === '' || !filter_var($em, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $byGuest[$em][] = $row;
        }

        $n = count($orderedIds);

        foreach ($byGuest as $guestEmail => $guestRows) {
            $name = trim((string) ($guestRows[0]['guest_name'] ?? '')) ?: $guestEmail;
            $tableHtml = '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;border-color:#dee2e6">'
                . '<thead><tr><th align="left">Data / horário</th><th align="left">Responder</th></tr></thead><tbody>';
            $altLines = [];
            foreach ($orderedIds as $bid) {
                $match = null;
                foreach ($guestRows as $gr) {
                    if ((int) ($gr['booking_id'] ?? 0) === $bid) {
                        $match = $gr;
                        break;
                    }
                }
                if ($match === null) {
                    continue;
                }
                $token = trim((string) ($match['rsvp_token'] ?? ''));
                if ($token === '') {
                    continue;
                }
                $stt = (string) ($match['start_datetime'] ?? '');
                $en = (string) ($match['end_datetime'] ?? '');
                $slotLabel = $this->formatRoomBookingSlotPortuguese($stt, $en);
                $openLink = $base . '/meeting-booking-rsvp/' . rawurlencode($token);
                $acceptLink = $openLink . '/accept';
                $declineLink = $openLink . '/decline';
                $tableHtml .= '<tr><td>' . htmlspecialchars($slotLabel, ENT_QUOTES, 'UTF-8') . '</td><td>'
                    . '<a href="' . htmlspecialchars($acceptLink, ENT_QUOTES, 'UTF-8') . '">Aceitar</a> | '
                    . '<a href="' . htmlspecialchars($declineLink, ENT_QUOTES, 'UTF-8') . '">Recusar</a><br>'
                    . '<small><a href="' . htmlspecialchars($openLink, ENT_QUOTES, 'UTF-8') . '">Abrir convite</a></small></td></tr>';
                $altLines[] = "{$slotLabel} — Aceitar: {$acceptLink} — Recusar: {$declineLink}";
            }
            $tableHtml .= '</tbody></table>';
            if ($altLines === []) {
                continue;
            }

            $subject = '[Salas] Convite (série): ' . $title;
            $body = '<p>Olá, ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<p>Foi convidado(a) para a reunião recorrente <strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong> '
                . '(<strong>' . $n . ' sessões</strong> semanais).</p>'
                . '<p><strong>Sala:</strong> ' . htmlspecialchars($room, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p>Indique <strong>em cada data</strong> se comparece:</p>'
                . $tableHtml
                . '<p><small>Organizador: <a href="' . htmlspecialchars($viewBookingLink, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($viewBookingLink, ENT_QUOTES, 'UTF-8') . '</a></small></p>';
            $alt = "Convite (série): {$title}\nSala: {$room}\n{$n} sessões\n\n" . implode("\n", $altLines) . "\n";

            try {
                SendEmailService::sendEmail($guestEmail, $name, $subject, $body, $alt, $replyToEmail, $replyToName, $fromDisplay, $fromAddrOverride);
            } catch (\Throwable) {
            }
        }

        if ($organizerId > 0 && $byGuest !== []) {
            $this->notificationsRepo->create([
                'user_id' => $organizerId,
                'type' => 'room_booking_invites_sent',
                'title' => 'Convites enviados (série)',
                'message' => 'Foram enviados convites por e-mail (um por participante externo) para a série de ' . $n . ' sessões: ' . $title . '.',
                'link_url' => $viewBookingLink,
                'entity_type' => 'room_booking',
                'entity_id' => $firstId,
            ]);
        }
    }

    public function notifyOrganizerOfRsvp(int $bookingId, int $participantUserId, string $status, string $externalGuestLabel = ''): void
    {
        $booking = $this->bookingsRepo->getById($bookingId);
        if (!$booking) {
            return;
        }
        $organizerId = (int) ($booking['user_id'] ?? 0);
        if ($organizerId <= 0 || ($participantUserId > 0 && $organizerId === $participantUserId)) {
            return;
        }

        if ($participantUserId > 0) {
            $user = $this->usersRepo->getUser($participantUserId);
            $pname = is_array($user) ? (string) ($user['name'] ?? 'Participante') : 'Participante';
        } else {
            $pname = trim($externalGuestLabel) !== '' ? trim($externalGuestLabel) : 'Participante externo';
        }
        $label = $status === 'declined' ? 'recusou' : 'aceitou';
        $title = (string) ($booking['title'] ?? 'Reunião');
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        $link = $base . '/view-booking/' . $bookingId;

        $this->notificationsRepo->create([
            'user_id' => $organizerId,
            'type' => 'room_booking_rsvp',
            'title' => 'Resposta ao convite — ' . $title,
            'message' => $pname . ' ' . $label . ' o convite para a reunião.',
            'link_url' => $link,
            'entity_type' => 'room_booking',
            'entity_id' => $bookingId,
        ]);

        $org = $this->usersRepo->getUser($organizerId);
        if (!is_array($org)) {
            return;
        }
        $orgEmail = trim((string) ($org['email'] ?? ''));
        if ($orgEmail === '') {
            return;
        }
        $orgName = (string) ($org['name'] ?? $orgEmail);
        $subject = '[Salas] Resposta ao convite: ' . $title;
        $body = '<p>' . htmlspecialchars($pname, ENT_QUOTES, 'UTF-8') . ' <strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong> o convite para <strong>'
            . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
            . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">Ver reserva e estado dos participantes</a></p>';
        $alt = "{$pname} {$label} o convite para {$title}. Ver: {$link}\n";

        $replyToEmail = null;
        $replyToName = null;
        if ($participantUserId > 0) {
            $pu = $this->usersRepo->getUser($participantUserId);
            if (is_array($pu)) {
                $pe = trim((string) ($pu['email'] ?? ''));
                if ($pe !== '' && filter_var($pe, FILTER_VALIDATE_EMAIL)) {
                    $replyToEmail = $pe;
                    $replyToName = (string) ($pu['name'] ?? $pe);
                }
            }
        }

        try {
            SendEmailService::sendEmail($orgEmail, $orgName, $subject, $body, $alt, $replyToEmail, $replyToName);
        } catch (\Throwable) {
        }
    }

    /**
     * Notifica participantes internos e externos (exceto o organizador) após cancelamento da reserva.
     */
    public function notifyParticipantsOfCancellation(int $bookingId, int $organizerUserId, string $reason = ''): void
    {
        $booking = $this->bookingsRepo->getById($bookingId);
        if (!$booking) {
            return;
        }

        $title = (string) ($booking['title'] ?? 'Reunião');
        $room = (string) ($booking['room_name'] ?? 'Sala');
        $start = (string) ($booking['start_datetime'] ?? '');
        $end = (string) ($booking['end_datetime'] ?? '');
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        $isSeries = trim((string) ($booking['recurrence_series_id'] ?? '')) !== '';
        $slotPt = $this->formatRoomBookingSlotPortuguese($start, $end);

        $sql = 'SELECT bp.user_id, bp.guest_email, bp.guest_name, u.name, u.email
                FROM adms_booking_participants bp
                LEFT JOIN adms_users u ON bp.user_id = u.id
                WHERE bp.booking_id = :bid';
        $stmt = $this->participantsRepo->getConnection()->prepare($sql);
        $stmt->bindValue(':bid', $bookingId, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $reasonHtml = $reason !== '' ? '<p><strong>Motivo:</strong> ' . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . '</p>' : '';
        $summary = "Sala: {$room}. Sessão: {$slotPt}.";
        $seriesHtml = $isSeries
            ? '<p><strong>Nota:</strong> Este aviso refere-se <strong>apenas a esta data</strong> de uma reunião <strong>recorrente</strong>. As restantes sessões da série mantêm-se agendadas.</p>'
            : '';
        $seriesInApp = $isSeries ? ' Apenas esta sessão da série foi cancelada; as outras datas mantêm-se.' : '';
        [$replyToEmail, $replyToName, $fromDisplay, $fromAddrOverride] = $this->roomBookingMailFromContext($booking);
        $subjectPrefix = $isSeries ? '[Salas] Sessão cancelada (série): ' : '[Salas] Reserva cancelada: ';
        $inAppTitle = ($isSeries ? 'Sessão cancelada (série): ' : 'Reserva cancelada: ') . $title;

        foreach ($rows as $row) {
            $uid = (int) ($row['user_id'] ?? 0);
            $guestEmail = trim((string) ($row['guest_email'] ?? ''));
            if ($uid > 0 && $uid === $organizerUserId) {
                continue;
            }

            if ($uid > 0) {
                $email = trim((string) ($row['email'] ?? ''));
                $name = (string) ($row['name'] ?? $email ?: 'Participante');
                $this->notificationsRepo->create([
                    'user_id' => $uid,
                    'type' => 'room_booking_cancelled',
                    'title' => $inAppTitle,
                    'message' => $summary . ' Esta sessão foi cancelada.' . $seriesInApp,
                    'link_url' => $base . '/list-bookings',
                    'entity_type' => 'room_booking',
                    'entity_id' => $bookingId,
                ]);
                if ($email === '') {
                    continue;
                }
                $subject = $subjectPrefix . $title;
                $body = '<p>Olá, ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>'
                    . ($isSeries
                        ? '<p>A <strong>sessão</strong> da reunião <strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong> indicada abaixo foi <strong>cancelada</strong>.</p>'
                        : '<p>A reunião <strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong> foi <strong>cancelada</strong>.</p>')
                    . $seriesHtml
                    . '<p><strong>Sala:</strong> ' . htmlspecialchars($room, ENT_QUOTES, 'UTF-8') . '<br>'
                    . '<strong>Data e horário desta sessão:</strong> ' . htmlspecialchars($slotPt, ENT_QUOTES, 'UTF-8') . '</p>'
                    . $reasonHtml;
                $alt = ($isSeries ? "Sessão cancelada (série): {$title}\n" : "Reserva cancelada: {$title}\n") . "{$summary}\n";
                try {
                    SendEmailService::sendEmail($email, $name, $subject, $body, $alt, $replyToEmail, $replyToName, $fromDisplay, $fromAddrOverride);
                } catch (\Throwable) {
                }
            } elseif ($guestEmail !== '' && filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
                $gname = trim((string) ($row['guest_name'] ?? '')) ?: $guestEmail;
                $subject = $subjectPrefix . $title;
                $body = '<p>Olá, ' . htmlspecialchars($gname, ENT_QUOTES, 'UTF-8') . ',</p>'
                    . ($isSeries
                        ? '<p>A <strong>sessão</strong> da reunião <strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong> indicada abaixo foi <strong>cancelada</strong>.</p>'
                        : '<p>A reunião <strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong> foi <strong>cancelada</strong>.</p>')
                    . $seriesHtml
                    . '<p><strong>Sala:</strong> ' . htmlspecialchars($room, ENT_QUOTES, 'UTF-8') . '<br>'
                    . '<strong>Data e horário desta sessão:</strong> ' . htmlspecialchars($slotPt, ENT_QUOTES, 'UTF-8') . '</p>'
                    . $reasonHtml;
                $alt = ($isSeries ? "Sessão cancelada (série): {$title}\n" : "Reserva cancelada: {$title}\n") . "{$summary}\n";
                try {
                    SendEmailService::sendEmail($guestEmail, $gname, $subject, $body, $alt, $replyToEmail, $replyToName, $fromDisplay, $fromAddrOverride);
                } catch (\Throwable) {
                }
            }
        }
    }

    /**
     * Notifica participantes quando a data/hora (ou sala) da reserva é alterada.
     * Gera novos tokens RSVP e pede confirmação de novo para esta sessão (links antigos deixam de valer).
     */
    public function notifyParticipantsOfReschedule(
        int $bookingId,
        int $organizerUserId,
        string $oldStart,
        string $oldEnd,
        string $newStart,
        string $newEnd,
        string $oldRoomName,
        string $newRoomName,
    ): void {
        $booking = $this->bookingsRepo->getById($bookingId);
        if (!$booking) {
            return;
        }

        $this->participantsRepo->resetAllInviteeRsvpForBooking($bookingId);

        $title = (string) ($booking['title'] ?? 'Reunião');
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        $viewLink = $base . '/view-booking/' . $bookingId;
        $isSeries = trim((string) ($booking['recurrence_series_id'] ?? '')) !== '';
        $seriesHtml = $isSeries
            ? '<p><strong>Nota:</strong> Este aviso refere-se <strong>só a esta sessão</strong> de uma reunião recorrente. As outras datas da série não são alteradas por esta mensagem.</p>'
            : '';

        $oldSlotPt = $this->formatRoomBookingSlotPortuguese($oldStart, $oldEnd);
        $newSlotPt = $this->formatRoomBookingSlotPortuguese($newStart, $newEnd);

        $sql = 'SELECT bp.user_id, bp.guest_email, bp.guest_name, bp.rsvp_token, u.name, u.email
                FROM adms_booking_participants bp
                LEFT JOIN adms_users u ON bp.user_id = u.id
                WHERE bp.booking_id = :bid AND COALESCE(bp.is_organizer, 0) = 0';
        $stmt = $this->participantsRepo->getConnection()->prepare($sql);
        $stmt->bindValue(':bid', $bookingId, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        [$replyToEmail, $replyToName, $fromDisplay, $fromAddrOverride] = $this->roomBookingMailFromContext($booking);
        $subjectPrefix = $isSeries
            ? '[Salas] Sessão reagendada (série) — confirme novamente: '
            : '[Salas] Reunião reagendada — confirme novamente: ';
        $inAppTitle = ($isSeries ? 'Sessão reagendada (série): ' : 'Reunião reagendada: ') . $title;
        $inAppMsg = 'Horário ou sala desta sessão foram alterados. Antes: ' . $oldSlotPt . ' (' . $oldRoomName . '). '
            . 'Agora: ' . $newSlotPt . ' (' . $newRoomName . ').'
            . ($isSeries ? ' Apenas esta sessão da série.' : '')
            . ' É necessário confirmar de novo a presença (e-mail com links).';

        foreach ($rows as $row) {
            $uid = (int) ($row['user_id'] ?? 0);
            $guestEmail = trim((string) ($row['guest_email'] ?? ''));
            if ($uid > 0 && $uid === $organizerUserId) {
                continue;
            }

            $token = trim((string) ($row['rsvp_token'] ?? ''));

            if ($uid > 0) {
                $email = trim((string) ($row['email'] ?? ''));
                $name = (string) ($row['name'] ?? $email ?: 'Participante');
                $this->notificationsRepo->create([
                    'user_id' => $uid,
                    'type' => 'room_booking_rescheduled',
                    'title' => $inAppTitle,
                    'message' => $inAppMsg,
                    'link_url' => $viewLink,
                    'entity_type' => 'room_booking',
                    'entity_id' => $bookingId,
                ]);
                if ($email === '' || $token === '') {
                    continue;
                }
                $openLink = $base . '/meeting-booking-rsvp/' . rawurlencode($token);
                $acceptLink = $openLink . '/accept';
                $declineLink = $openLink . '/decline';
                $subject = $subjectPrefix . $title;
                $leadInternal = $isSeries
                    ? '<p>A <strong>sessão</strong> da reunião <strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong> foi <strong>reagendada</strong>.</p>'
                    : '<p>A reunião <strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong> foi <strong>reagendada</strong>.</p>';
                $body = '<p>Olá, ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>'
                    . $leadInternal
                    . $seriesHtml
                    . '<p><strong>Antes:</strong> ' . htmlspecialchars($oldRoomName, ENT_QUOTES, 'UTF-8')
                    . ' — ' . htmlspecialchars($oldSlotPt, ENT_QUOTES, 'UTF-8') . '<br>'
                    . '<strong>Agora:</strong> ' . htmlspecialchars($newRoomName, ENT_QUOTES, 'UTF-8')
                    . ' — ' . htmlspecialchars($newSlotPt, ENT_QUOTES, 'UTF-8') . '</p>'
                    . '<p>Por favor <strong>confirme novamente</strong> se comparece'
                    . ($isSeries ? ' a <strong>esta sessão</strong>' : '')
                    . ' (a resposta anterior deixa de aplicar-se após o reagendamento):</p>'
                    . '<p><a href="' . htmlspecialchars($acceptLink, ENT_QUOTES, 'UTF-8') . '">Aceitar</a>'
                    . ' &nbsp;|&nbsp; <a href="' . htmlspecialchars($declineLink, ENT_QUOTES, 'UTF-8') . '">Recusar</a></p>'
                    . '<p><small>Ou abra: <a href="' . htmlspecialchars($openLink, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($openLink, ENT_QUOTES, 'UTF-8') . '</a></small></p>'
                    . '<p><small><a href="' . htmlspecialchars($viewLink, ENT_QUOTES, 'UTF-8') . '">Ver reserva</a></small></p>';
                $alt = "Sessão reagendada: {$title}\nAntes: {$oldRoomName} — {$oldSlotPt}\nAgora: {$newRoomName} — {$newSlotPt}\n\nConfirme de novo — Aceitar: {$acceptLink}\nRecusar: {$declineLink}\n";
                try {
                    SendEmailService::sendEmail($email, $name, $subject, $body, $alt, $replyToEmail, $replyToName, $fromDisplay, $fromAddrOverride);
                } catch (\Throwable) {
                }
            } elseif ($guestEmail !== '' && filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
                $gname = trim((string) ($row['guest_name'] ?? '')) ?: $guestEmail;
                if ($token === '') {
                    continue;
                }
                $openLink = $base . '/meeting-booking-rsvp/' . rawurlencode($token);
                $acceptLink = $openLink . '/accept';
                $declineLink = $openLink . '/decline';
                $subject = $subjectPrefix . $title;
                $leadGuest = $isSeries
                    ? '<p>A <strong>sessão</strong> da reunião <strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong> foi <strong>reagendada</strong>.</p>'
                    : '<p>A reunião <strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong> foi <strong>reagendada</strong>.</p>';
                $body = '<p>Olá, ' . htmlspecialchars($gname, ENT_QUOTES, 'UTF-8') . ',</p>'
                    . $leadGuest
                    . $seriesHtml
                    . '<p><strong>Antes:</strong> ' . htmlspecialchars($oldRoomName, ENT_QUOTES, 'UTF-8')
                    . ' — ' . htmlspecialchars($oldSlotPt, ENT_QUOTES, 'UTF-8') . '<br>'
                    . '<strong>Agora:</strong> ' . htmlspecialchars($newRoomName, ENT_QUOTES, 'UTF-8')
                    . ' — ' . htmlspecialchars($newSlotPt, ENT_QUOTES, 'UTF-8') . '</p>'
                    . '<p>Por favor <strong>confirme novamente</strong> a presença'
                    . ($isSeries ? ' nesta sessão' : '')
                    . ':</p>'
                    . '<p><a href="' . htmlspecialchars($acceptLink, ENT_QUOTES, 'UTF-8') . '">Aceitar</a>'
                    . ' &nbsp;|&nbsp; <a href="' . htmlspecialchars($declineLink, ENT_QUOTES, 'UTF-8') . '">Recusar</a></p>'
                    . '<p><small>Ou abra: <a href="' . htmlspecialchars($openLink, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($openLink, ENT_QUOTES, 'UTF-8') . '</a></small></p>'
                    . '<p><small><a href="' . htmlspecialchars($viewLink, ENT_QUOTES, 'UTF-8') . '">Ver reserva</a></small></p>';
                $alt = "Sessão reagendada: {$title}\nConfirme de novo — Aceitar: {$acceptLink}\nRecusar: {$declineLink}\n";
                try {
                    SendEmailService::sendEmail($guestEmail, $gname, $subject, $body, $alt, $replyToEmail, $replyToName, $fromDisplay, $fromAddrOverride);
                } catch (\Throwable) {
                }
            }
        }
    }
}
