<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\RoomServiceRequestNotificationHelper;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\RoomBookingsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\RoomRequestTypesRepository;
use App\adms\Models\Repository\RoomServiceRequestsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Criar solicitação de serviço (lanches, equipamentos, etc.) — opcionalmente vinculada a uma reserva.
 */
class RoomsCreateServiceRequest
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        }

        $typesRepo = new RoomRequestTypesRepository();
        $this->data['requestTypes'] = $typesRepo->getAll(true);

        $bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
        $this->data['linkedBooking'] = null;
        $this->data['prefill'] = null;
        if ($bookingId > 0) {
            $linked = $this->loadBookingForCurrentUser($bookingId);
            if ($linked) {
                $this->data['linkedBooking'] = $linked;
                $this->data['prefill'] = $this->buildPrefillFromBooking($linked);
            } else {
                $_SESSION['error'] = 'Reserva não encontrada ou você não tem permissão para vincular solicitações a ela.';
            }
        }

        $pageElements = [
            'title_head' => 'Criar Solicitação (Salas)',
            'menu' => 'RoomsListServiceRequests',
            'buttonPermission' => [
                'RoomsListServiceRequests',
            ],
        ];

        $this->data['can_change_service_request_requester'] = UserAccessHelper::hasFullSystemAccess();
        $this->data['service_request_requester_users'] = $this->data['can_change_service_request_requester']
            ? (new UsersRepository())->getUsersForRoomParticipantPicker()
            : [];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rooms/create_service_request', $this->data);
        $loadView->loadView();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadBookingForCurrentUser(int $bookingId): ?array
    {
        $repo = new RoomBookingsRepository();
        $booking = $repo->getById($bookingId);
        if (!$booking) {
            return null;
        }
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if (!UserAccessHelper::hasFullSystemAccess() && (int)($booking['user_id'] ?? 0) !== $userId) {
            return null;
        }

        return $booking;
    }

    /**
     * @param array<string, mixed> $booking
     *
     * @return array{service_date: string, start_time: string, end_time: string, location: string}
     */
    private function buildPrefillFromBooking(array $booking): array
    {
        $start = $booking['start_datetime'] ?? '';
        $end = $booking['end_datetime'] ?? '';
        $serviceDate = '';
        $startTime = '';
        $endTime = '';
        if ($start !== '') {
            $ts = strtotime((string)$start);
            if ($ts !== false) {
                $serviceDate = date('Y-m-d', $ts);
                $startTime = date('H:i', $ts);
            }
        }
        if ($end !== '') {
            $te = strtotime((string)$end);
            if ($te !== false) {
                $endTime = date('H:i', $te);
            }
        }
        $room = trim((string)($booking['room_name'] ?? ''));
        $loc = trim((string)($booking['location'] ?? ''));
        $location = $room !== '' ? $room : $loc;
        if ($room !== '' && $loc !== '' && strcasecmp($room, $loc) !== 0) {
            $location = $room . ($loc !== '' ? ' — ' . $loc : '');
        }

        return [
            'service_date' => $serviceDate !== '' ? $serviceDate : date('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'location' => $location !== '' ? $location : 'Sala',
        ];
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('form_create_room_service_request', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-create-service-request');
            exit;
        }

        $postedBookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
        $linkedBooking = $postedBookingId > 0 ? $this->loadBookingForCurrentUser($postedBookingId) : null;
        if ($postedBookingId > 0 && !$linkedBooking) {
            $_SESSION['error'] = 'Reserva inválida ou sem permissão para vincular.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-create-service-request');
            exit;
        }

        $requestTypeId = !empty($_POST['request_type_id']) ? (int)$_POST['request_type_id'] : 0;
        $description = trim($_POST['request_description'] ?? '');
        $quantity = isset($_POST['quantity']) && $_POST['quantity'] !== '' ? (int)$_POST['quantity'] : null;
        $serviceDate = trim($_POST['service_date'] ?? '');
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime = trim($_POST['end_time'] ?? '');
        $location = trim($_POST['location'] ?? '');

        if (!$requestTypeId) {
            $_SESSION['error'] = 'Selecione o tipo de solicitação.';

            return;
        }

        if ($serviceDate === '' || $startTime === '' || $location === '') {
            $_SESSION['error'] = 'Data, horário de início e local são obrigatórios.';

            return;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $serviceDate)) {
            $_SESSION['error'] = 'Data inválida.';

            return;
        }
        if (!preg_match('/^\d{2}:\d{2}$/', $startTime)) {
            $_SESSION['error'] = 'Horário de início inválido.';

            return;
        }
        if ($endTime !== '' && !preg_match('/^\d{2}:\d{2}$/', $endTime)) {
            $_SESSION['error'] = 'Horário de término inválido.';

            return;
        }

        $typesRepo = new RoomRequestTypesRepository();
        $type = $typesRepo->getById($requestTypeId);
        if (!$type || empty($type['is_active'])) {
            $_SESSION['error'] = 'Tipo de solicitação inválido.';

            return;
        }

        if (!empty($type['requires_quantity']) && ($quantity === null || $quantity <= 0)) {
            $_SESSION['error'] = 'Este tipo requer a quantidade.';

            return;
        }

        $responsibleGroupId = !empty($type['default_responsible_group_id']) ? (int)$type['default_responsible_group_id'] : null;
        if (!empty($type['requires_responsible']) && empty($responsibleGroupId)) {
            $_SESSION['error'] = 'Este tipo requer uma equipe responsável, mas nenhuma equipe foi definida no tipo.';

            return;
        }

        $sessionUid = (int) ($_SESSION['user_id'] ?? 0);
        $requesterUserId = $sessionUid;
        if (UserAccessHelper::hasFullSystemAccess()) {
            $postedRequester = (int) ($_POST['requester_user_id'] ?? 0);
            if ($postedRequester > 0) {
                $uRow = (new UsersRepository())->getUser($postedRequester);
                if ($uRow !== false) {
                    $requesterUserId = $postedRequester;
                }
            }
        }
        if ($requesterUserId <= 0) {
            $_SESSION['error'] = 'Não foi possível identificar o solicitante. Inicie sessão novamente.';

            return;
        }

        $repo = new RoomServiceRequestsRepository();
        $payload = [
            'requester_user_id' => $requesterUserId,
            'request_type_id' => $requestTypeId,
            'request_description' => $description !== '' ? $description : null,
            'quantity' => $quantity,
            'status' => 'pending',
            'service_date' => $serviceDate,
            'start_time' => $startTime . ':00',
            'end_time' => $endTime !== '' ? $endTime . ':00' : null,
            'location' => $location,
            'priority' => 'normal',
            'responsible_group_id' => $responsibleGroupId,
        ];
        if ($linkedBooking) {
            $payload['booking_id'] = (int)$linkedBooking['id'];
        }

        $id = $repo->create($payload);

        try {
            (new RoomServiceRequestNotificationHelper())->notifyGroupOnNewRequest($id);
        } catch (\Throwable) {
            // Não impedir a criação se a notificação falhar
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Solicitação criada com sucesso!</div>';
        if ($linkedBooking) {
            header('Location: ' . $_ENV['URL_ADM'] . 'view-booking/' . (int)$linkedBooking['id']);
        } else {
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-view-service-request/' . $id);
        }
        exit;
    }
}
