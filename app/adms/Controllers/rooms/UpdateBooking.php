<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\BookingAdditionalRequestsRepository;
use App\adms\Models\Repository\MeetingRoomsRepository;
use App\adms\Models\Repository\RequestTypesRepository;
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
        $this->data = [];

        $id = $id ? (int)$id : 0;

        if ($id === 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">ID da reserva não informado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-bookings');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($id);
        } else {
            $this->showForm($id);
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
        $isSuperAdmin = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
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
        $requestTypesRepo = new RequestTypesRepository();

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
        $this->data['requestTypes'] = $requestTypesRepo->getAllActive();

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
        $isSuperAdmin = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
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

        // Verificar conflito de horário (excluindo a própria reserva)
        if ($bookingsRepo->hasConflict($roomId, $startDatetime, $endDatetime, $id)) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Atenção: A sala já está reservada neste horário!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-booking/' . $id);
            exit;
        }

        // Atualizar reserva
        $updateData = [
            'room_id' => $roomId,
            'title' => $title,
            'description' => $description,
            'start_datetime' => date('Y-m-d H:i:s', $startTimestamp),
            'end_datetime' => date('Y-m-d H:i:s', $endTimestamp),
            'has_additional_requests' => !empty($additionalRequests),
        ];

        try {
            $bookingsRepo->update($id, $updateData);

            // Atualizar participantes
            $this->updateParticipants($id, $participants);

            // Atualizar solicitações adicionais
            $this->updateAdditionalRequests($id, $additionalRequests);

            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Reserva atualizada com sucesso!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-booking/' . $id);
            exit;
        } catch (\Exception $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao atualizar reserva: ' . htmlspecialchars($e->getMessage()) . '</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-booking/' . $id);
            exit;
        }
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
            $requestTypesRepo = new RequestTypesRepository();

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

