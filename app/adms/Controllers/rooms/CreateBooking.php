<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\BookingParticipantNotificationHelper;
use App\adms\Helpers\RoomBookingRecurrencePlanner;
use App\adms\Models\Repository\BookingAdditionalRequestsRepository;
use App\adms\Models\Repository\BookingParticipantsRepository;
use App\adms\Models\Repository\MeetingRoomsRepository;
use App\adms\Helpers\RoomWaitlistService;
use App\adms\Models\Repository\BookingWaitlistRepository;
use App\adms\Models\Repository\RoomBookingsRepository;
use App\adms\Models\Repository\RoomBookingSlotHoldRepository;
use App\adms\Models\Repository\RoomRequestTypesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criar reserva de sala
 */
class CreateBooking
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        } else {
            $this->showForm();
        }
    }

    private function showForm(): void
    {
        $roomsRepo = new MeetingRoomsRepository();
        $usersRepo = new UsersRepository();
        $requestTypesRepo = new RoomRequestTypesRepository();

        // Buscar salas ativas
        $this->data['rooms'] = $roomsRepo->getAll(['status' => 'active'], 1, 1000);

        $this->data['users'] = $usersRepo->getUsersForRoomParticipantPicker();

        // Buscar tipos de solicitação ativos
        $this->data['requestTypes'] = $requestTypesRepo->getAll(true);

        // Pré-selecionar sala se room_id foi passado via GET
        $this->data['selected_room_id'] = !empty($_GET['room_id']) ? (int)$_GET['room_id'] : null;

        $pageElements = [
            'title_head' => 'Criar Reserva de Sala',
            'menu' => 'create-booking',
            'buttonPermission' => [
                'ListBookings',
                'RoomCalendar',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/rooms/create_booking', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        // Verificar se veio do modal rápido (book-room)
        $fromQuickBooking = !empty($_POST['from_quick_booking']);
        $redirectTo = $fromQuickBooking ? 'book-room?room_id=' . (int)($_POST['room_id'] ?? 0) : 'create-booking';
        
        if (!CSRFHelper::validateCSRFToken('form_quick_booking', $_POST['csrf_token'] ?? '') && 
            !CSRFHelper::validateCSRFToken('form_create_booking', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Token de segurança inválido!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
            exit;
        }

        $roomId = (int)($_POST['room_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $startDatetime = trim($_POST['start_datetime'] ?? '');
        $endDatetime = trim($_POST['end_datetime'] ?? '');
        $participants = $_POST['participants'] ?? [];
        $participantGuestEmailsRaw = (string) ($_POST['participant_guest_emails'] ?? '');
        $additionalRequests = $_POST['additional_requests'] ?? [];
        $slotHoldToken = trim((string) ($_POST['slot_hold_token'] ?? ''));

        // Validações básicas
        if ($roomId === 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Selecione uma sala!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
            exit;
        }

        if (empty($title)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Título da reunião é obrigatório!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
            exit;
        }

        if (empty($startDatetime) || empty($endDatetime)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Data e hora de início e fim são obrigatórias!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
            exit;
        }

        // Validar formato de data
        $startTimestamp = strtotime($startDatetime);
        $endTimestamp = strtotime($endDatetime);

        if ($startTimestamp === false || $endTimestamp === false) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Formato de data/hora inválido!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
            exit;
        }

        if ($endTimestamp <= $startTimestamp) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Data/hora de fim deve ser posterior à data/hora de início!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
            exit;
        }

        // Verificar se a sala existe e está ativa
        $roomsRepo = new MeetingRoomsRepository();
        $room = $roomsRepo->getById($roomId);

        if (!$room || $room['status'] !== 'active') {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Sala não encontrada ou inativa!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
            exit;
        }

        $roomRulesErr = $this->validateRoomRulesForSlot($room, $startTimestamp, $endTimestamp);
        if ($roomRulesErr !== null) {
            $_SESSION['msg'] = $roomRulesErr;
            header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
            exit;
        }

        $bookingsRepo = new RoomBookingsRepository();
        $pdo = $bookingsRepo->getConnection();
        $waitlistRepo = new BookingWaitlistRepository();
        $waitlistService = new RoomWaitlistService($waitlistRepo);
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        $startSql = date('Y-m-d H:i:s', $startTimestamp);
        $endSql = date('Y-m-d H:i:s', $endTimestamp);

        $recurrenceEnabled = !empty($_POST['recurrence_enabled']);
        $recurrenceUntil = trim((string) ($_POST['recurrence_until'] ?? ''));
        $recurrenceSeriesId = null;
        $occurrences = [[$startSql, $endSql]];

        if ($recurrenceEnabled) {
            if ($recurrenceUntil === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $recurrenceUntil)) {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Recorrência semanal: indique a data final (AAAA-MM-DD).</div>';
                header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
                exit;
            }
            if ($recurrenceUntil < date('Y-m-d', $startTimestamp)) {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">A data final da recorrência não pode ser anterior ao dia da primeira ocorrência.</div>';
                header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
                exit;
            }
            $recurrenceSeriesId = bin2hex(random_bytes(18));
            $occurrences = RoomBookingRecurrencePlanner::buildWeeklySeries($startSql, $endSql, $recurrenceUntil);
            if ($occurrences === []) {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Não foi possível gerar ocorrências para a recorrência. Verifique as datas.</div>';
                header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
                exit;
            }
            foreach ($occurrences as [$s, $e]) {
                $ts = strtotime($s);
                $te = strtotime($e);
                if ($ts === false || $te === false) {
                    $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao processar datas da série.</div>';
                    header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
                    exit;
                }
                $errOcc = $this->validateRoomRulesForSlot($room, $ts, $te);
                if ($errOcc !== null) {
                    $_SESSION['msg'] = $errOcc;
                    header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
                    exit;
                }
            }
        }

        if ($fromQuickBooking) {
            $holdRepo = new RoomBookingSlotHoldRepository();
            if (!$holdRepo->validateHoldForUser($roomId, $startSql, $endSql, $currentUserId, $slotHoldToken)) {
                $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">O bloqueio deste horário expirou ou é inválido. Abra novamente o horário para reservar.</div>';
                header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
                exit;
            }
        }

        $gotLock = RoomWaitlistService::acquireRoomBookingLock($pdo, $roomId);
        if (!$gotLock) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Não foi possível validar o horário neste momento. Tente novamente em instantes.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
            exit;
        }

        // Determinar status inicial
        $status = 'confirmed';
        $requiresApproval = false;

        if ($room['requires_approval']) {
            $status = 'pending';
            $requiresApproval = true;
        }

        $bookingTemplate = [
            'room_id' => $roomId,
            'user_id' => $_SESSION['user_id'] ?? 0,
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'requires_approval' => $requiresApproval,
            'has_additional_requests' => !empty($additionalRequests),
        ];

        $bookingId = 0;
        $createError = '';
        $conflictRedirect = false;
        $guestEmails = $this->parseGuestEmailList($participantGuestEmailsRaw);
        $notifyHelper = new BookingParticipantNotificationHelper();

        try {
            foreach ($occurrences as [$cs, $ce]) {
                if ($bookingsRepo->hasConflict($roomId, $cs, $ce)) {
                    if ($waitlistRepo->hasNotifiedOverlap($roomId, $currentUserId, $cs, $ce)) {
                        $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Conflito na série ou na primeira data: horário já reservado por outro utilizador.</div>';
                    } else {
                        $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Conflito na série: a sala já está reservada numa das datas.</div>';
                    }
                    $conflictRedirect = true;
                    break;
                }
            }

            if (!$conflictRedirect) {
                $createdIds = [];
                $pdo->beginTransaction();
                try {
                    foreach ($occurrences as [$cs, $ce]) {
                        $row = $bookingTemplate;
                        $row['start_datetime'] = $cs;
                        $row['end_datetime'] = $ce;
                        if ($recurrenceSeriesId !== null) {
                            $row['recurrence_series_id'] = $recurrenceSeriesId;
                        }
                        $bid = $bookingsRepo->create($row);
                        $createdIds[] = $bid;
                        if (!empty($participants) && is_array($participants)) {
                            $this->addParticipants($bid, $participants);
                        }
                        if ($guestEmails !== []) {
                            $this->addGuestParticipants($bid, $guestEmails);
                        }
                    }
                    $pdo->commit();
                } catch (\Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $e;
                }

                $seriesBatch = count($createdIds) > 1;
                if ($seriesBatch) {
                    if (!empty($participants) && is_array($participants)) {
                        $invitedIds = array_values(array_unique(array_filter(
                            array_map(static fn ($v) => (int) $v, $participants),
                            static fn (int $id): bool => $id > 0
                        )));
                        if ($invitedIds !== []) {
                            try {
                                $notifyHelper->sendInvitesForRecurringSeries($createdIds, $invitedIds);
                            } catch (\Throwable) {
                            }
                        }
                    }
                    if ($guestEmails !== []) {
                        try {
                            $notifyHelper->sendGuestInvitesForRecurringSeries($createdIds);
                        } catch (\Throwable) {
                        }
                    }
                } else {
                    foreach ($createdIds as $bid) {
                        if (!empty($participants) && is_array($participants)) {
                            $invitedIds = array_values(array_unique(array_filter(
                                array_map(static fn ($v) => (int) $v, $participants),
                                static fn (int $id): bool => $id > 0
                            )));
                            if ($invitedIds !== []) {
                                try {
                                    $notifyHelper->sendInvites($bid, $invitedIds);
                                } catch (\Throwable) {
                                }
                            }
                        }
                        if ($guestEmails !== []) {
                            try {
                                $notifyHelper->sendGuestInvites($bid);
                            } catch (\Throwable) {
                            }
                        }
                    }
                }

                $bookingId = $createdIds[0] ?? 0;

                if ($fromQuickBooking && $slotHoldToken !== '') {
                    try {
                        (new RoomBookingSlotHoldRepository())->releaseByToken($slotHoldToken, $currentUserId);
                    } catch (\Throwable) {
                    }
                }

                if (!empty($additionalRequests) && is_array($additionalRequests)) {
                    $this->addAdditionalRequests($bookingId, $additionalRequests);
                }

                $roomLabel = (string)($room['name'] ?? 'Sala');
                foreach ($createdIds as $i => $bid) {
                    [$cs, $ce] = $occurrences[$i];
                    $waitlistService->finalizeAfterBookingCreated(
                        $roomId,
                        $cs,
                        $ce,
                        $currentUserId,
                        $bid,
                        $roomLabel
                    );
                }

                $n = count($createdIds);
                $suffix = $n > 1 ? ' Foram criadas ' . $n . ' ocorrências (repetição semanal até à data indicada).' : '';
                $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Reserva criada com sucesso!' . $suffix . '</div>';

                if ($fromQuickBooking) {
                    header('Location: ' . $_ENV['URL_ADM'] . 'book-room?room_id=' . $roomId);
                } else {
                    header('Location: ' . $_ENV['URL_ADM'] . 'view-booking/' . $bookingId);
                }
                exit;
            }
        } catch (\Throwable $e) {
            $createError = $e->getMessage();
        } finally {
            RoomWaitlistService::releaseRoomBookingLock($pdo, $roomId);
        }

        if ($fromQuickBooking && $slotHoldToken !== '') {
            try {
                (new RoomBookingSlotHoldRepository())->releaseByToken($slotHoldToken, $currentUserId);
            } catch (\Throwable) {
            }
        }

        if ($conflictRedirect) {
            header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
            exit;
        }

        $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao criar reserva: ' . htmlspecialchars($createError) . '</div>';
        header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
        exit;
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

    /**
     * Adicionar participantes à reserva (com token RSVP para convite).
     *
     * @return int[] IDs de utilizadores convidados
     */
    private function addParticipants(int $bookingId, array $participantIds): array
    {
        $repo = new BookingParticipantsRepository();
        $invited = [];
        foreach ($participantIds as $userId) {
            $userId = (int) $userId;
            if ($userId > 0) {
                $repo->insertParticipant($bookingId, $userId, false, 'pending');
                $invited[] = $userId;
            }
        }

        return $invited;
    }

    /**
     * @return list<string> e-mails válidos e únicos
     */
    private function parseGuestEmailList(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $parts = preg_split('/[\n,;]+/', $raw) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $e = mb_strtolower(trim((string) $p));
            if ($e === '' || !filter_var($e, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $out[$e] = $e;
        }

        return array_slice(array_values($out), 0, 30);
    }

    /**
     * @param list<string> $emails
     */
    private function addGuestParticipants(int $bookingId, array $emails): void
    {
        $repo = new BookingParticipantsRepository();
        foreach ($emails as $email) {
            $repo->insertGuestParticipant($bookingId, $email, '');
        }
    }

    /**
     * Adicionar solicitações adicionais à reserva
     */
    private function addAdditionalRequests(int $bookingId, array $requests): void
    {
        $requestsRepo = new BookingAdditionalRequestsRepository();
        $requestTypesRepo = new RoomRequestTypesRepository();
        $organizerId = (int) ($_SESSION['user_id'] ?? 0);

        foreach ($requests as $request) {
            if (empty($request['type'])) {
                continue;
            }

            $requestType = $requestTypesRepo->getByCode((string) $request['type']);
            if (!$requestType) {
                continue;
            }

            $responsibleUserId = !empty($request['responsible_user_id']) ? (int) $request['responsible_user_id'] : 0;
            if ($responsibleUserId <= 0 && !empty($requestType['requires_responsible'])) {
                $responsibleUserId = (int) ($requestType['default_responsible_user_id'] ?? 0);
            }
            if ($responsibleUserId <= 0) {
                $responsibleUserId = $organizerId;
            }
            if ($responsibleUserId <= 0) {
                continue;
            }

            $requestData = [
                'booking_id' => $bookingId,
                'request_type' => $request['type'],
                'request_description' => $request['description'] ?? '',
                'quantity' => !empty($request['quantity']) ? (int) $request['quantity'] : null,
                'responsible_user_id' => $responsibleUserId,
                'status' => 'pending',
            ];

            $requestsRepo->create($requestData);
        }
    }
}

