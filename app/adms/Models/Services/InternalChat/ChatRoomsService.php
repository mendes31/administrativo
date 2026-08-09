<?php

declare(strict_types=1);

namespace App\adms\Models\Services\InternalChat;

use App\adms\Helpers\BookingParticipantNotificationHelper;
use App\adms\Helpers\ImageHelper;
use App\adms\Helpers\RoomWaitlistService;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\MeetingRoomsRepository;
use App\adms\Models\Repository\RoomBookingsRepository;

/**
 * Tools tipadas de salas/reservas para o Tiarajuzinho (sem SQL livre).
 */
class ChatRoomsService
{
    private MeetingRoomsRepository $rooms;
    private RoomBookingsRepository $bookings;
    private ButtonPermissionUserRepository $perms;

    public function __construct(
        ?MeetingRoomsRepository $rooms = null,
        ?RoomBookingsRepository $bookings = null,
        ?ButtonPermissionUserRepository $perms = null
    ) {
        $this->rooms = $rooms ?? new MeetingRoomsRepository();
        $this->bookings = $bookings ?? new RoomBookingsRepository();
        $this->perms = $perms ?? new ButtonPermissionUserRepository();
    }

    public function userCan(string $controller): bool
    {
        $list = $this->perms->buttonPermission([$controller]);

        return is_array($list) && in_array($controller, $list, true);
    }

    public function userCanListRooms(): bool
    {
        return $this->userCan('ListMeetingRooms')
            || $this->userCan('BookRoom')
            || $this->userCan('RoomCalendar');
    }

    public function userCanViewAgenda(): bool
    {
        return $this->userCan('RoomCalendar')
            || $this->userCan('BookRoom')
            || $this->userCan('ListMeetingRooms')
            || $this->userCan('ListBookings');
    }

    public function userCanReserve(): bool
    {
        return $this->userCan('CreateBooking');
    }

    public function userCanCancel(): bool
    {
        return $this->userCan('CancelBooking');
    }

    /**
     * @return array{ok:bool, resposta:string, tool:string, data?:mixed}
     */
    public function listRooms(?string $search = null): array
    {
        if (!$this->userCanListRooms()) {
            return $this->deny('ListMeetingRooms / BookRoom', 'rooms.list');
        }

        $filters = ['status' => 'active'];
        if ($search !== null && trim($search) !== '') {
            $filters['search'] = trim($search);
        }
        $rows = $this->rooms->getAll($filters, 1, 50);
        if ($rows === []) {
            return [
                'ok' => true,
                'resposta' => 'Nenhuma sala ativa encontrada' . ($search ? ' para «' . $search . '»' : '') . '.',
                'tool' => 'rooms.list',
                'data' => ['rooms' => []],
            ];
        }

        $summary = [];
        $firstId = 0;
        $n = 1;
        $options = [];
        foreach ($rows as $room) {
            $id = (int) ($room['id'] ?? 0);
            $name = (string) ($room['name'] ?? 'Sala');
            $cap = (int) ($room['capacity'] ?? 0);
            $loc = trim((string) ($room['location'] ?? ''));
            $building = trim((string) ($room['building'] ?? ''));
            $imagePath = trim((string) ($room['image'] ?? ''));
            $imageUrl = $imagePath !== ''
                ? (rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/serve-file?path=' . ImageHelper::encodePathForServeFile($imagePath))
                : null;
            $extra = $this->formatRoomPlaceExtras($cap, $building, $loc);
            $summary[] = [
                'id' => $id,
                'name' => $name,
                'capacity' => $cap,
                'location' => $loc,
                'building' => $building,
                'image' => $imagePath !== '' ? $imagePath : null,
                'image_url' => $imageUrl,
            ];
            $options[] = [
                'value' => (string) $n,
                'label' => $n . ' — ' . $name,
                'sub' => implode(' · ', $extra),
                'image_url' => $imageUrl,
            ];
            if ($firstId === 0 && $id > 0) {
                $firstId = $id;
            }
            $n++;
        }
        if ($firstId > 0) {
            $this->rememberRoom($firstId);
        }

        $count = count($options);

        return [
            'ok' => true,
            'resposta' => $count === 1
                ? 'Há 1 sala ativa. Toque nela (ou digite 1) para escolher data e horários.'
                : "Há {$count} salas ativas. Toque numa delas (ou digite o número) para escolher data e horários.",
            'tool' => 'rooms.list',
            'data' => [
                'rooms' => $summary,
                'ui' => [
                    'type' => 'rooms_wizard',
                    'step' => 'pick_room',
                    'options' => $options,
                    'hint' => 'agendar',
                ],
            ],
        ];
    }

    /**
     * @return array{ok:bool, resposta:string, tool:string, data?:mixed}
     */
    public function agenda(?string $roomQuery, string $dayYmd): array
    {
        if (!$this->userCanViewAgenda()) {
            return $this->deny('RoomCalendar / BookRoom', 'rooms.agenda');
        }

        $room = $this->resolveRoom($roomQuery);
        if ($room === null) {
            return [
                'ok' => false,
                'resposta' => 'Não encontrei a sala'
                    . ($roomQuery ? ' «' . $roomQuery . '»' : '')
                    . '. Digite «salas» para listar ou informe o nome/número.',
                'tool' => 'rooms.agenda',
            ];
        }

        $roomId = (int) $room['id'];
        $this->rememberRoom($roomId);
        $bookings = $this->bookings->getBookingsByRoomAndPeriod($roomId, $dayYmd, $dayYmd);
        $dayBr = date('d/m/Y', strtotime($dayYmd) ?: time());
        $name = (string) ($room['name'] ?? 'Sala');

        if ($bookings === []) {
            return [
                'ok' => true,
                'resposta' => sprintf(
                    'Agenda de «%s» em %s: sem reservas nesse dia.',
                    $name,
                    $dayBr
                ),
                'tool' => 'rooms.agenda',
                'data' => [
                    'room_id' => $roomId,
                    'room_name' => $name,
                    'date' => $dayYmd,
                    'bookings' => [],
                    'book_url' => $this->bookUrl($roomId),
                ],
            ];
        }

        $lines = [sprintf('Agenda de «%s» em %s (%d):', $name, $dayBr, count($bookings)), ''];
        $export = [];
        foreach ($bookings as $b) {
            $start = date('H:i', strtotime((string) $b['start_datetime']));
            $end = date('H:i', strtotime((string) $b['end_datetime']));
            $title = trim((string) ($b['title'] ?? 'Reserva')) ?: 'Reserva';
            $who = trim((string) ($b['user_name'] ?? ''));
            $status = $this->bookingStatusLabel((string) ($b['status'] ?? ''));
            $lines[] = sprintf('• %s–%s — %s', $start, $end, $title);
            if ($who !== '') {
                $lines[] = '  ' . $who . ' · ' . $status;
            } else {
                $lines[] = '  ' . $status;
            }
            $export[] = [
                'id' => (int) $b['id'],
                'title' => $title,
                'start' => $b['start_datetime'],
                'end' => $b['end_datetime'],
                'user_name' => $who,
                'status' => (string) ($b['status'] ?? ''),
            ];
        }
        $lines[] = '';
        $lines[] = 'Abrir na tela: ' . $this->bookUrl($roomId);

        return [
            'ok' => true,
            'resposta' => implode("\n", $lines),
            'tool' => 'rooms.agenda',
            'data' => [
                'room_id' => $roomId,
                'room_name' => $name,
                'date' => $dayYmd,
                'bookings' => $export,
                'book_url' => $this->bookUrl($roomId),
            ],
        ];
    }

    /**
     * @return array{ok:bool, resposta:string, tool:string, data?:mixed}
     */
    public function myBookings(int $userId, int $limit = 15): array
    {
        if (
            !$this->userCan('ListBookings')
            && !$this->userCanReserve()
            && !$this->userCanViewAgenda()
        ) {
            return $this->deny('ListBookings', 'rooms.my');
        }

        $rows = $this->bookings->getAll([
            'user_id' => $userId,
            'status' => 'confirmed',
        ], 1, $limit);

        // Inclui pending também
        $pending = $this->bookings->getAll([
            'user_id' => $userId,
            'status' => 'pending',
        ], 1, $limit);
        $merged = array_merge($pending, $rows);
        usort($merged, static function (array $a, array $b): int {
            return strcmp((string) ($a['start_datetime'] ?? ''), (string) ($b['start_datetime'] ?? ''));
        });
        $now = date('Y-m-d H:i:s');
        $future = array_values(array_filter($merged, static function (array $b) use ($now): bool {
            return (string) ($b['end_datetime'] ?? '') >= $now
                && in_array((string) ($b['status'] ?? ''), ['pending', 'confirmed', 'in_progress'], true);
        }));
        $future = array_slice($future, 0, $limit);

        if ($future === []) {
            return [
                'ok' => true,
                'resposta' => 'Você não tem reservas futuras.',
                'tool' => 'rooms.my',
                'data' => ['bookings' => []],
            ];
        }

        $lines = ['Suas próximas reservas (' . count($future) . '):', ''];
        $export = [];
        $firstId = 0;
        foreach ($future as $b) {
            $id = (int) ($b['id'] ?? 0);
            if ($firstId === 0 && $id > 0) {
                $firstId = $id;
            }
            $roomName = (string) ($b['room_name'] ?? 'Sala');
            $title = trim((string) ($b['title'] ?? '')) ?: 'Reunião';
            $dayBr = date('d/m/Y', strtotime((string) $b['start_datetime']) ?: time());
            $startHm = date('H:i', strtotime((string) $b['start_datetime']) ?: time());
            $endHm = date('H:i', strtotime((string) $b['end_datetime']) ?: time());
            $status = $this->bookingStatusLabel((string) ($b['status'] ?? ''));
            $lines[] = sprintf('• #%d · %s', $id, $roomName);
            $lines[] = sprintf('  %s · %s–%s · %s · %s', $dayBr, $startHm, $endHm, $title, $status);
            $export[] = [
                'id' => $id,
                'sala' => $roomName,
                'inicio' => $b['start_datetime'],
                'fim' => $b['end_datetime'],
                'titulo' => $title,
                'status' => (string) ($b['status'] ?? ''),
            ];
        }
        $lines[] = '';
        $lines[] = $firstId > 0
            ? 'Para cancelar, diga «cancelar reserva #' . $firstId . '».'
            : 'Para cancelar, diga «cancelar reserva #ID».';

        return [
            'ok' => true,
            'resposta' => implode("\n", $lines),
            'tool' => 'rooms.my',
            'data' => ['bookings' => $export],
        ];
    }

    /**
     * @return array{ok:bool, resposta:string, tool:string, data?:mixed}
     */
    public function reserve(
        int $userId,
        ?string $roomQuery,
        string $startSql,
        string $endSql,
        string $title,
        ?string $description = null
    ): array {
        if (!$this->userCanReserve()) {
            return $this->deny('CreateBooking', 'rooms.reserve');
        }
        if ($userId < 1) {
            return [
                'ok' => false,
                'resposta' => 'Usuário não identificado para reservar.',
                'tool' => 'rooms.reserve',
            ];
        }

        $title = trim($title);
        if ($title === '') {
            $title = 'Reunião';
        }

        $startTs = strtotime($startSql);
        $endTs = strtotime($endSql);
        if ($startTs === false || $endTs === false) {
            return [
                'ok' => false,
                'resposta' => 'Data/hora inválida. Use, por exemplo: «reservar sala X amanhã 14:00 às 15:00 reunião Planejamento».',
                'tool' => 'rooms.reserve',
            ];
        }
        if ($endTs <= $startTs) {
            return [
                'ok' => false,
                'resposta' => 'O horário de fim deve ser depois do início.',
                'tool' => 'rooms.reserve',
            ];
        }

        $room = $this->resolveRoom($roomQuery);
        if ($room === null || ($room['status'] ?? '') !== 'active') {
            return [
                'ok' => false,
                'resposta' => 'Sala não encontrada ou inativa. Digite «salas» para ver as opções.',
                'tool' => 'rooms.reserve',
            ];
        }

        $roomId = (int) $room['id'];
        $ruleErr = $this->validateRoomRules($room, $startTs, $endTs);
        if ($ruleErr !== null) {
            return [
                'ok' => false,
                'resposta' => $ruleErr,
                'tool' => 'rooms.reserve',
            ];
        }

        $startSql = date('Y-m-d H:i:s', $startTs);
        $endSql = date('Y-m-d H:i:s', $endTs);
        $pdo = $this->bookings->getConnection();
        $gotLock = RoomWaitlistService::acquireRoomBookingLock($pdo, $roomId);
        if (!$gotLock) {
            return [
                'ok' => false,
                'resposta' => 'Não foi possível validar o horário agora. Tente novamente em instantes.',
                'tool' => 'rooms.reserve',
            ];
        }

        try {
            if ($this->bookings->hasConflict($roomId, $startSql, $endSql)) {
                return [
                    'ok' => false,
                    'resposta' => sprintf(
                        'Conflito: «%s» já tem reserva nesse intervalo. Veja a agenda: «agenda da sala %s %s».',
                        (string) $room['name'],
                        (string) $room['name'],
                        date('d/m/Y', $startTs)
                    ),
                    'tool' => 'rooms.reserve',
                ];
            }

            $requiresApproval = !empty($room['requires_approval']);
            $status = $requiresApproval ? 'pending' : 'confirmed';
            $bookingId = $this->bookings->create([
                'room_id' => $roomId,
                'user_id' => $userId,
                'title' => $title,
                'description' => $description,
                'start_datetime' => $startSql,
                'end_datetime' => $endSql,
                'status' => $status,
                'requires_approval' => $requiresApproval,
                'has_additional_requests' => false,
            ]);
            $this->rememberRoom($roomId);

            $msg = $requiresApproval
                ? sprintf(
                    'Reserva #%d criada como pendente de aprovação em «%s» (%s – %s) — «%s».',
                    $bookingId,
                    (string) $room['name'],
                    date('d/m/Y H:i', $startTs),
                    date('H:i', $endTs),
                    $title
                )
                : sprintf(
                    'Reserva #%d confirmada em «%s» (%s – %s) — «%s».',
                    $bookingId,
                    (string) $room['name'],
                    date('d/m/Y H:i', $startTs),
                    date('H:i', $endTs),
                    $title
                );

            return [
                'ok' => true,
                'resposta' => $msg . "\nAbrir: " . rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/view-booking/' . $bookingId,
                'tool' => 'rooms.reserve',
                'data' => [
                    'booking_id' => $bookingId,
                    'room_id' => $roomId,
                    'room_name' => $room['name'],
                    'start_datetime' => $startSql,
                    'end_datetime' => $endSql,
                    'status' => $status,
                    'title' => $title,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'resposta' => 'Erro ao criar reserva: ' . $e->getMessage(),
                'tool' => 'rooms.reserve',
            ];
        } finally {
            RoomWaitlistService::releaseRoomBookingLock($pdo, $roomId);
        }
    }

    /**
     * @return array{ok:bool, resposta:string, tool:string, data?:mixed}
     */
    public function cancel(int $userId, int $bookingId, string $reason = ''): array
    {
        if (!$this->userCanCancel()) {
            return $this->deny('CancelBooking', 'rooms.cancel');
        }
        if ($bookingId < 1) {
            return [
                'ok' => false,
                'resposta' => 'Informe o ID da reserva. Ex.: «cancelar reserva #12» ou «minhas reservas».',
                'tool' => 'rooms.cancel',
            ];
        }

        $booking = $this->bookings->getById($bookingId);
        if ($booking === null) {
            return [
                'ok' => false,
                'resposta' => 'Reserva #' . $bookingId . ' não encontrada.',
                'tool' => 'rooms.cancel',
            ];
        }

        $isOwner = (int) ($booking['user_id'] ?? 0) === $userId;
        $isAdmin = UserAccessHelper::hasFullSystemAccess();
        if (!$isOwner && !$isAdmin) {
            return [
                'ok' => false,
                'resposta' => 'Você só pode cancelar as suas próprias reservas.',
                'tool' => 'rooms.cancel',
            ];
        }

        $status = (string) ($booking['status'] ?? '');
        if (in_array($status, ['cancelled', 'completed'], true)) {
            return [
                'ok' => false,
                'resposta' => 'Esta reserva já está ' . ($status === 'cancelled' ? 'cancelada' : 'concluída') . '.',
                'tool' => 'rooms.cancel',
            ];
        }

        try {
            $this->bookings->update($bookingId, [
                'status' => 'cancelled',
                'cancelled_by' => $userId,
                'cancelled_at' => date('Y-m-d H:i:s'),
                'cancellation_reason' => trim($reason) !== '' ? trim($reason) : 'Cancelado via Tiarajuzinho',
            ]);

            try {
                (new BookingParticipantNotificationHelper())->notifyParticipantsOfCancellation(
                    $bookingId,
                    (int) ($booking['user_id'] ?? 0),
                    $reason
                );
            } catch (\Throwable) {
            }
            try {
                (new RoomWaitlistService())->notifyAllWaitingOnCancellation($booking);
            } catch (\Throwable) {
            }

            return [
                'ok' => true,
                'resposta' => sprintf(
                    'Reserva #%d («%s» em %s) cancelada.',
                    $bookingId,
                    (string) ($booking['room_name'] ?? 'Sala'),
                    date('d/m/Y H:i', strtotime((string) $booking['start_datetime']) ?: time())
                ),
                'tool' => 'rooms.cancel',
                'data' => ['booking_id' => $bookingId, 'status' => 'cancelled'],
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'resposta' => 'Erro ao cancelar: ' . $e->getMessage(),
                'tool' => 'rooms.cancel',
            ];
        }
    }

    /**
     * Todos os blocos de 30 min (08:00–18:00), com livres e ocupados (nome/contato).
     *
     * @return list<array{
     *   start:string,
     *   end:string,
     *   available:bool,
     *   status:string,
     *   reserved_by:?string,
     *   reserved_department:?string,
     *   booking_id:?int
     * }>
     */
    public function listDayHalfHourSlots(int $roomId, string $dayYmd): array
    {
        if ($roomId < 1 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dayYmd)) {
            return [];
        }

        $bookings = $this->bookings->getBookingsByRoomAndPeriod($roomId, $dayYmd, $dayYmd);
        $starts = [];
        for ($hour = 8; $hour < 18; $hour++) {
            $starts[] = sprintf('%02d:00', $hour);
            $starts[] = sprintf('%02d:30', $hour);
        }

        $now = time();
        $isToday = $dayYmd === date('Y-m-d');
        $slots = [];
        foreach ($starts as $startHm) {
            [$h, $m] = array_map('intval', explode(':', $startHm));
            $startTs = strtotime(sprintf('%s %02d:%02d:00', $dayYmd, $h, $m));
            if ($startTs === false) {
                continue;
            }
            $endTs = $startTs + 30 * 60;
            $slot = [
                'start' => date('H:i', $startTs),
                'end' => date('H:i', $endTs),
                'available' => true,
                'status' => 'free',
                'reserved_by' => null,
                'reserved_department' => null,
                'booking_id' => null,
            ];

            if ($isToday && $endTs <= $now) {
                $slot['available'] = false;
                $slot['status'] = 'past';
                $slots[] = $slot;
                continue;
            }

            foreach ($bookings as $b) {
                $bs = strtotime((string) ($b['start_datetime'] ?? ''));
                $be = strtotime((string) ($b['end_datetime'] ?? ''));
                if ($bs === false || $be === false) {
                    continue;
                }
                if ($startTs < $be && $endTs > $bs) {
                    $slot['available'] = false;
                    $slot['status'] = 'busy';
                    $slot['reserved_by'] = trim((string) ($b['user_name'] ?? '')) ?: 'Reservado';
                    $dept = trim((string) ($b['user_department'] ?? ''));
                    $slot['reserved_department'] = $dept !== '' ? $dept : null;
                    $slot['booking_id'] = (int) ($b['id'] ?? 0) ?: null;
                    break;
                }
            }
            $slots[] = $slot;
        }

        return $slots;
    }

    /**
     * Blocos livres de 30 min (08:00–18:00), alinhado à tela book-room.
     *
     * @return list<array{start:string,end:string}>
     */
    public function listFreeHalfHourSlots(int $roomId, string $dayYmd): array
    {
        $free = [];
        foreach ($this->listDayHalfHourSlots($roomId, $dayYmd) as $slot) {
            if (!empty($slot['available'])) {
                $free[] = [
                    'start' => (string) $slot['start'],
                    'end' => (string) $slot['end'],
                ];
            }
        }

        return $free;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolveRoom(?string $query): ?array
    {
        $query = trim((string) $query);
        if ($query === '') {
            $last = $this->getLastRoomId();
            if ($last > 0) {
                $room = $this->rooms->getById($last);
                if ($room && ($room['status'] ?? '') === 'active') {
                    return $room;
                }
            }

            return null;
        }

        if (preg_match('/^#?(\d+)$/', $query, $m)) {
            $room = $this->rooms->getById((int) $m[1]);
            if ($room && ($room['status'] ?? '') === 'active') {
                return $room;
            }
        }

        $rows = $this->rooms->getAll(['status' => 'active', 'search' => $query], 1, 30);
        if ($rows === []) {
            $rows = $this->rooms->getAll(['status' => 'active'], 1, 100);
        }

        $q = mb_strtolower($query);
        $best = null;
        $bestScore = 0;
        foreach ($rows as $room) {
            $name = mb_strtolower((string) ($room['name'] ?? ''));
            $score = 0;
            if ($name === $q) {
                $score = 100;
            } elseif ($name !== '' && str_contains($name, $q)) {
                $score = 80;
            } elseif ($name !== '' && str_contains($q, $name)) {
                $score = 70;
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $room;
            }
        }

        return $bestScore >= 70 ? $best : null;
    }

    private function validateRoomRules(array $room, int $startTimestamp, int $endTimestamp): ?string
    {
        $now = time();
        $hoursUntilStart = ($startTimestamp - $now) / 3600;
        $daysUntilStart = ($startTimestamp - $now) / 86400;
        $durationHours = ($endTimestamp - $startTimestamp) / 3600;

        if ($startTimestamp < $now - 60) {
            return 'Não é possível reservar no passado.';
        }
        if (!empty($room['min_advance_booking_hours']) && $hoursUntilStart < (float) $room['min_advance_booking_hours']) {
            return 'A reserva exige pelo menos ' . $room['min_advance_booking_hours'] . ' hora(s) de antecedência.';
        }
        if (!empty($room['max_advance_booking_days']) && $daysUntilStart > (float) $room['max_advance_booking_days']) {
            return 'A reserva não pode ser feita com mais de ' . $room['max_advance_booking_days'] . ' dia(s) de antecedência.';
        }
        if (!empty($room['booking_duration_limit_hours']) && $durationHours > (float) $room['booking_duration_limit_hours']) {
            return 'A duração máxima permitida é de ' . $room['booking_duration_limit_hours'] . ' hora(s).';
        }

        return null;
    }

    /**
     * @return array{ok:bool, resposta:string, tool:string}
     */
    /**
     * Capacidade + local (evita repetir prédio quando location já contém o building).
     *
     * @return list<string>
     */
    public function formatRoomPlaceExtras(int $capacity, string $building, string $location): array
    {
        $extra = [];
        if ($capacity > 0) {
            $extra[] = $capacity . ' lugares';
        }
        $place = $this->mergePlaceLabels($building, $location);
        if ($place !== '') {
            $extra[] = $place;
        }

        return $extra;
    }

    public function mergePlaceLabels(string $building, string $location): string
    {
        $building = trim($building);
        $location = trim($location);
        if ($building === '' && $location === '') {
            return '';
        }
        if ($building === '') {
            return $location;
        }
        if ($location === '') {
            return $building;
        }
        if (mb_stripos($location, $building) !== false) {
            return $location;
        }
        if (mb_stripos($building, $location) !== false) {
            return $building;
        }

        return $building . ' · ' . $location;
    }

    public function bookingStatusLabel(string $status): string
    {
        return match (strtolower(trim($status))) {
            'pending' => 'pendente',
            'confirmed' => 'confirmada',
            'in_progress' => 'em andamento',
            'cancelled', 'canceled' => 'cancelada',
            'completed' => 'concluída',
            default => $status !== '' ? $status : '—',
        };
    }

    private function deny(string $needed, string $tool): array
    {
        return [
            'ok' => false,
            'resposta' => 'Sem permissão para esta ação de salas. Solicite ao admin a página «' . $needed . '» no seu nível de acesso (além de McpChat).',
            'tool' => $tool,
        ];
    }

    private function bookUrl(int $roomId): string
    {
        return rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/book-room?room_id=' . $roomId;
    }

    private function rememberRoom(int $roomId): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && $roomId > 0) {
            $_SESSION['internal_chat_last_room_id'] = $roomId;
        }
    }

    private function getLastRoomId(): int
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return (int) ($_SESSION['internal_chat_last_room_id'] ?? 0);
        }

        return 0;
    }
}
