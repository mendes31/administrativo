<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RoomRequestTypesRepository;
use App\adms\Models\Repository\RoomServiceRequestsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Criar solicitação avulsa (sem reserva) - Reserva de Salas
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

        $pageElements = [
            'title_head' => 'Criar Solicitação (Salas)',
            'menu' => 'RoomsListServiceRequests',
            'buttonPermission' => [
                'RoomsListServiceRequests',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rooms/create_service_request', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('form_create_room_service_request', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
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

        // Validação básica de formato (YYYY-MM-DD e HH:MM)
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

        $repo = new RoomServiceRequestsRepository();
        $id = $repo->create([
            'requester_user_id' => (int)($_SESSION['user_id'] ?? 0),
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
        ]);

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Solicitação criada com sucesso!</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'rooms-view-service-request/' . $id);
        exit;
    }
}

