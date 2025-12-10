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
        $requestTypesRepo = new RequestTypesRepository();

        // Buscar salas ativas
        $this->data['rooms'] = $roomsRepo->getAll(['status' => 'active'], 1, 1000);

        // Buscar usuários ativos para participantes
        $this->data['users'] = $usersRepo->getAllUsers(1, 1000, ['bloqueado' => false]);

        // Buscar tipos de solicitação ativos
        $this->data['requestTypes'] = $requestTypesRepo->getAllActive();

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
        $additionalRequests = $_POST['additional_requests'] ?? [];

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

        // Verificar conflito de horário
        $bookingsRepo = new RoomBookingsRepository();
        if ($bookingsRepo->hasConflict($roomId, $startDatetime, $endDatetime)) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Atenção: A sala já está reservada neste horário. Deseja entrar na lista de espera?</div>';
            // TODO: Implementar redirecionamento para lista de espera
            header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
            exit;
        }

        // Verificar regras da sala (antecedência mínima/máxima, duração máxima)
        $now = time();
        $hoursUntilStart = ($startTimestamp - $now) / 3600;
        $daysUntilStart = ($startTimestamp - $now) / 86400;
        $durationHours = ($endTimestamp - $startTimestamp) / 3600;

        if (!empty($room['min_advance_booking_hours']) && $hoursUntilStart < $room['min_advance_booking_hours']) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: A reserva deve ser feita com pelo menos ' . $room['min_advance_booking_hours'] . ' horas de antecedência!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
            exit;
        }

        if (!empty($room['max_advance_booking_days']) && $daysUntilStart > $room['max_advance_booking_days']) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: A reserva não pode ser feita com mais de ' . $room['max_advance_booking_days'] . ' dias de antecedência!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
            exit;
        }

        if (!empty($room['booking_duration_limit_hours']) && $durationHours > $room['booking_duration_limit_hours']) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: A duração máxima permitida é de ' . $room['booking_duration_limit_hours'] . ' horas!</div>';
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

        // Criar reserva
        $bookingData = [
            'room_id' => $roomId,
            'user_id' => $_SESSION['user_id'] ?? 0,
            'title' => $title,
            'description' => $description,
            'start_datetime' => date('Y-m-d H:i:s', $startTimestamp),
            'end_datetime' => date('Y-m-d H:i:s', $endTimestamp),
            'status' => $status,
            'requires_approval' => $requiresApproval,
            'has_additional_requests' => !empty($additionalRequests),
        ];

        try {
            $bookingId = $bookingsRepo->create($bookingData);

            // Adicionar participantes (se houver)
            if (!empty($participants) && is_array($participants)) {
                $this->addParticipants($bookingId, $participants);
            }

            // Adicionar solicitações adicionais (se houver)
            if (!empty($additionalRequests) && is_array($additionalRequests)) {
                $this->addAdditionalRequests($bookingId, $additionalRequests);
            }

            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Reserva criada com sucesso!</div>';
            
            // Se veio do modal rápido, redirecionar de volta para o calendário da sala
            if ($fromQuickBooking) {
                header('Location: ' . $_ENV['URL_ADM'] . 'book-room?room_id=' . $roomId);
            } else {
                header('Location: ' . $_ENV['URL_ADM'] . 'view-booking/' . $bookingId);
            }
            exit;
        } catch (\Exception $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao criar reserva: ' . htmlspecialchars($e->getMessage()) . '</div>';
            header('Location: ' . $_ENV['URL_ADM'] . $redirectTo);
            exit;
        }
    }

    /**
     * Adicionar participantes à reserva
     */
    private function addParticipants(int $bookingId, array $participantIds): void
    {
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

    /**
     * Adicionar solicitações adicionais à reserva
     */
    private function addAdditionalRequests(int $bookingId, array $requests): void
    {
        $requestsRepo = new BookingAdditionalRequestsRepository();
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

    /**
     * Obter conexão com o banco
     */
    private function getConnection(): \PDO
    {
        $dbConnection = new \App\adms\Models\Services\DbConnection();
        return $dbConnection->getConnection();
    }
}

