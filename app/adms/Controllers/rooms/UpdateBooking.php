<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\BookingAdditionalRequestsRepository;
use App\adms\Helpers\RoomWaitlistService;
use App\adms\Models\Repository\BookingWaitlistRepository;
use App\adms\Models\Repository\MeetingRoomsRepository;
use App\adms\Models\Repository\RoomRequestTypesRepository;
use App\adms\Models\Repository\RoomBookingsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar reserva de sala
 */
class UpdateBooking
{
    private array|string|null $data = null;

    public function index(string|int|null $id = null): void
    {
        $resolvedId = $id ? (int)$id : 0;

        try {
            $this->data = [];

            if ($resolvedId === 0) {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">ID da reserva não informado!</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-bookings');
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->update($resolvedId);
            } else {
                $this->showForm($resolvedId);
            }
        } catch (\Throwable $e) {
            \App\adms\Helpers\GenerateLog::generateLog('error', 'UpdateBooking falhou: ' . $e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Não foi possível processar o editor de reserva. Se o problema continuar, contacte o administrador (detalhe registado no log).</div>';
            $adm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
            header('Location: ' . ($resolvedId > 0 ? $adm . 'view-booking/' . $resolvedId : $adm . 'dashboard'));
            exit;
        }
    }

    private function showForm(int $id): void
    {
        $bookingsRepo = new RoomBookingsRepository();
        $booking = $bookingsRepo->getById($id);

        if (!$booking) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Reserva não encontrada!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-bookings');
            exit;
        }

        // Verificar permissão e se pode editar
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        $userId = $_SESSION['user_id'] ?? 0;

        if (!$isSuperAdmin && (int)$booking['user_id'] !== $userId) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Você não tem permissão para editar esta reserva!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-booking/' . $id);
            exit;
        }

        // Não permitir editar reservas canceladas ou concluídas
        if (in_array($booking['status'], ['cancelled', 'completed'])) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Não é possível editar reservas canceladas ou concluídas!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-booking/' . $id);
            exit;
        }

        $roomsRepo = new MeetingRoomsRepository();
        $usersRepo = new UsersRepository();
        $requestTypesRepo = new RoomRequestTypesRepository();

        // Buscar participantes atuais
        $this->data['participants'] = $bookingsRepo->getParticipantsByBookingId($id);
        $participantIds = array_column($this->data['participants'], 'user_id');

        // Buscar solicitações adicionais atuais
        $requestsRepo = new BookingAdditionalRequestsRepository();
        $this->data['additionalRequests'] = $requestsRepo->getByBookingId($id);

        $this->data['form'] = $booking;
        $this->data['form']['participant_ids'] = $participantIds;
        $this->data['rooms'] = $roomsRepo->getAll(['status' => 'active'], 1, 1000);
        $this->data['users'] = $usersRepo->getAllUsers(1, 1000, ['bloqueado' => false]);
        $this->data['requestTypes'] = $requestTypesRepo->getAll(true);

        $pageElements = [
            'title_head' => 'Editar Reserva',
            'menu' => 'update-booking',
            'buttonPermission' => [
                'ListBookings',
                'ViewBooking',
                'CancelBooking',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/rooms/update_booking', $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('form_update_booking', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Token de segurança inválido!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-booking/' . $id);
            exit;
        }

        $bookingsRepo = new RoomBookingsRepository();
        $booking = $bookingsRepo->getById($id);

        if (!$booking) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Reserva não encontrada!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-bookings');
            exit;
        }

        // Verificar permissão
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        $userId = $_SESSION['user_id'] ?? 0;

        if (!$isSuperAdmin && (int)$booking['user_id'] !== $userId) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Você não tem permissão para editar esta reserva!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-booking/' . $id);
            exit;
        }

        // Não permitir editar reservas canceladas ou concluídas
        if (in_array($booking['status'], ['cancelled', 'completed'])) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Não é possível editar reservas canceladas ou concluídas!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-booking/' . $id);
            exit;
        }

        $roomId = (int)($_POST['room_id'] ?? $booking['room_id']);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $startDatetime = trim($_POST['start_datetime'] ?? '');
        $endDatetime = trim($_POST['end_datetime'] ?? '');
        $participants = $_POST['participants'] ?? [];
        $additionalRequests = $_POST['additional_requests'] ?? [];

        // Validações básicas
        if (empty($title)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Título da reunião é obrigatório!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-booking/' . $id);
            exit;
        }

        if (empty($startDatetime) || empty($endDatetime)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Data e hora de início e fim são obrigatórias!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-booking/' . $id);
            exit;
        }

        // Validar formato de data
        $startTimestamp = strtotime($startDatetime);
        $endTimestamp = strtotime($endDatetime);

        if ($startTimestamp === false || $endTimestamp === false) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Formato de data/hora inválido!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-booking/' . $id);
            exit;
        }

        if ($endTimestamp <= $startTimestamp) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Data/hora de fim deve ser posterior à data/hora de início!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-booking/' . $id);
            exit;
        }

        $roomsRepo = new MeetingRoomsRepository();
        $room = $roomsRepo->getById($roomId);

        if (!$room || ($room['status'] ?? '') !== 'active') {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Sala não encontrada ou inativa!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-booking/' . $id);
            exit;
        }

        $now = time();
        $hoursUntilStart = ($startTimestamp - $now) / 3600;
        $daysUntilStart = ($startTimestamp - $now) / 86400;
        $durationHours = ($endTimestamp - $startTimestamp) / 3600;

        if (!empty($room['min_advance_booking_hours']) && $hoursUntilStart < $room['min_advance_booking_hours']) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: A reserva deve ser feita com pelo menos ' . $room['min_advance_booking_hours'] . ' horas de antecedência!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-booking/' . $id);
            exit;
        }

        if (!empty($room['max_advance_booking_days']) && $daysUntilStart > $room['max_advance_booking_days']) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: A reserva não pode ser feita com mais de ' . $room['max_advance_booking_days'] . ' dias de antecedência!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-booking/' . $id);
            exit;
        }

        if (!empty($room['booking_duration_limit_hours']) && $durationHours > $room['booking_duration_limit_hours']) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: A duração máxima permitida é de ' . $room['booking_duration_limit_hours'] . ' horas!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-booking/' . $id);
            exit;
        }

        $startSql = date('Y-m-d H:i:s', $startTimestamp);
        $endSql = date('Y-m-d H:i:s', $endTimestamp);
        $pdo = $bookingsRepo->getConnection();
        $waitlistRepo = new BookingWaitlistRepository();
        $waitlistService = new RoomWaitlistService($waitlistRepo);
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);

        $gotLock = RoomWaitlistService::acquireRoomBookingLock($pdo, $roomId);
        if (!$gotLock) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Não foi possível validar o horário neste momento. Tente novamente em instantes.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-booking/' . $id);
            exit;
        }

        $updateData = [
            'room_id' => $roomId,
            'title' => $title,
            'description' => $description,
            'start_datetime' => $startSql,
            'end_datetime' => $endSql,
            'has_additional_requests' => !empty($additionalRequests),
        ];

        $conflictRedirect = false;
        $updateError = '';

        try {
            if ($bookingsRepo->hasConflict($roomId, $startSql, $endSql, $id)) {
                if ($waitlistRepo->hasNotifiedOverlap($roomId, $currentUserId, $startSql, $endSql)) {
                    $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Este horário já foi reservado por outro utilizador. A vaga foi preenchida — escolha outro intervalo ou entre novamente na lista de espera.</div>';
                } else {
                    $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Atenção: a sala já está reservada neste horário. Pode entrar na lista de espera pelo calendário da sala.</div>';
                }
                $conflictRedirect = true;
            } else {
                $bookingsRepo->update($id, $updateData);

                // Reagendamento: o intervalo antigo fica livre — notificar lista de espera (opção B), como no cancelamento
                $prevRoomId = (int)($booking['room_id'] ?? 0);
                $slotChanged = $prevRoomId !== $roomId
                    || strtotime((string)($booking['start_datetime'] ?? '')) !== strtotime($startSql)
                    || strtotime((string)($booking['end_datetime'] ?? '')) !== strtotime($endSql);
                if ($slotChanged) {
                    try {
                        $waitlistService->notifyAllWaitingOnCancellation([
                            'room_id' => $prevRoomId,
                            'start_datetime' => (string)($booking['start_datetime'] ?? ''),
                            'end_datetime' => (string)($booking['end_datetime'] ?? ''),
                            'room_name' => (string)($booking['room_name'] ?? 'Sala'),
                        ]);
                    } catch (\Throwable) {
                        // não bloquear a edição da reserva
                    }
                }

                $postUpdateFailed = false;
                try {
                    $this->updateParticipants($id, $participants);
                    $this->updateAdditionalRequests($id, $additionalRequests);

                    $roomLabel = (string)($room['name'] ?? 'Sala');
                    $waitlistService->finalizeAfterBookingCreated(
                        $roomId,
                        $startSql,
                        $endSql,
                        $currentUserId,
                        $id,
                        $roomLabel
                    );
                } catch (\Throwable $e) {
                    $postUpdateFailed = true;
                    \App\adms\Helpers\GenerateLog::generateLog('error', 'UpdateBooking: UPDATE da reserva OK; falha nos passos seguintes: ' . $e->getMessage(), [
                        'booking_id' => $id,
                        'exception' => $e::class,
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                }

                if ($postUpdateFailed) {
                    $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">A reserva foi atualizada, mas participantes, solicitações ou lista de espera não ficaram totalmente sincronizados. Pode editar de novo ou contactar o administrador (detalhe no log).</div>';
                } else {
                    $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Reserva atualizada com sucesso!</div>';
                }
                header('Location: ' . $_ENV['URL_ADM'] . 'view-booking/' . $id);
                exit;
            }
        } catch (\Throwable $e) {
            $updateError = $e->getMessage();
        } finally {
            RoomWaitlistService::releaseRoomBookingLock($pdo, $roomId);
        }

        if ($conflictRedirect) {
            header('Location: ' . $_ENV['URL_ADM'] . 'update-booking/' . $id);
            exit;
        }

        $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao atualizar reserva: ' . htmlspecialchars($updateError) . '</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'update-booking/' . $id);
        exit;
    }

    /**
     * Atualizar participantes da reserva
     */
    private function updateParticipants(int $bookingId, array $participantIds): void
    {
        // Deletar participantes existentes
        $sql = "DELETE FROM adms_booking_participants WHERE booking_id = :booking_id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':booking_id', $bookingId, \PDO::PARAM_INT);
        $stmt->execute();

        // Adicionar novos participantes
        if (!empty($participantIds) && is_array($participantIds)) {
            $sql = "INSERT INTO adms_booking_participants (booking_id, user_id, is_organizer, status, notified)
                    VALUES (:booking_id, :user_id, 0, 'pending', 0)";
            
            $stmt = $this->getConnection()->prepare($sql);
            
            foreach ($participantIds as $userId) {
                $userId = (int)$userId;
                if ($userId > 0) {
                    $stmt->bindValue(':booking_id', $bookingId, \PDO::PARAM_INT);
                    $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
        }
    }

    /**
     * Atualizar solicitações adicionais
     */
    private function updateAdditionalRequests(int $bookingId, array $requests): void
    {
        $requestsRepo = new BookingAdditionalRequestsRepository();
        
        // Deletar solicitações existentes
        $requestsRepo->deleteByBookingId($bookingId);

        // Adicionar novas solicitações
        if (!empty($requests) && is_array($requests)) {
            $requestTypesRepo = new RoomRequestTypesRepository();

            foreach ($requests as $request) {
                if (empty($request['type']) || empty($request['responsible_user_id'])) {
                    continue;
                }

                $requestType = $requestTypesRepo->getByCode($request['type']);
                if (!$requestType) {
                    continue;
                }

                $requestData = [
                    'booking_id' => $bookingId,
                    'request_type' => $request['type'],
                    'request_description' => $request['description'] ?? '',
                    'quantity' => !empty($request['quantity']) ? (int)$request['quantity'] : null,
                    'responsible_user_id' => (int)$request['responsible_user_id'],
                    'status' => 'pending',
                ];

                $requestsRepo->create($requestData);
            }
        }
    }

    /**
     * Obter conexão com o banco
     */
    private function getConnection(): \PDO
    {
        $dbConnection = new \App\adms\Models\Services\DbConnection();
        return $dbConnection->getConnection();
    }
}

