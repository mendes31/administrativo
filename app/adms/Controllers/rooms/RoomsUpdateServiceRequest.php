<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RoomRequestGroupsRepository;
use App\adms\Models\Repository\RoomRequestTypesRepository;
use App\adms\Models\Repository\RoomServiceRequestsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Editar solicitação avulsa - Reserva de Salas
 */
class RoomsUpdateServiceRequest
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $id = (int)$id;
        if (!$id) {
            $_SESSION['error'] = 'Solicitação não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-service-requests');
            return;
        }

        $repo = new RoomServiceRequestsRepository();
        $request = $repo->getById($id);
        if (!$request) {
            $_SESSION['error'] = 'Solicitação não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-service-requests');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($request);
        }

        $typesRepo = new RoomRequestTypesRepository();
        $this->data['requestTypes'] = $typesRepo->getAll(true);

        $groupsRepo = new RoomRequestGroupsRepository();
        $this->data['groups'] = $groupsRepo->getAll(true);

        $this->data['request'] = $request;

        $pageElements = [
            'title_head' => 'Editar Solicitação (Salas)',
            'menu' => 'RoomsListServiceRequests',
            'buttonPermission' => [
                'RoomsListServiceRequests',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rooms/update_service_request', $this->data);
        $loadView->loadView();
    }

    private function update(array $original): void
    {
        if (!CSRFHelper::validateCSRFToken('form_update_room_service_request', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            return;
        }

        $requestTypeId = !empty($_POST['request_type_id']) ? (int)$_POST['request_type_id'] : (int)$original['request_type_id'];
        $description = trim($_POST['request_description'] ?? '');
        $quantity = isset($_POST['quantity']) && $_POST['quantity'] !== '' ? (int)$_POST['quantity'] : null;
        $status = trim($_POST['status'] ?? $original['status'] ?? 'pending');
        $responsibleGroupId = isset($_POST['responsible_group_id']) && $_POST['responsible_group_id'] !== '' ? (int)$_POST['responsible_group_id'] : null;
        $serviceDate = trim($_POST['service_date'] ?? ($original['service_date'] ?? ''));
        $startTime = trim($_POST['start_time'] ?? ($original['start_time'] ?? ''));
        $endTime = trim($_POST['end_time'] ?? ($original['end_time'] ?? ''));

        $allowedStatus = ['pending', 'in_progress', 'done', 'cancelled'];
        if (!in_array($status, $allowedStatus, true)) {
            $_SESSION['error'] = 'Status inválido.';
            return;
        }

        if ($serviceDate === '' || $startTime === '' || empty($original['service_date']) && $serviceDate === '') {
            $_SESSION['error'] = 'Data e horário de início são obrigatórios.';
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

        if (!empty($type['requires_responsible']) && empty($responsibleGroupId) && empty($type['default_responsible_group_id'])) {
            $_SESSION['error'] = 'Este tipo requer uma equipe responsável. Defina no tipo ou selecione uma equipe.';
            return;
        }

        if (empty($responsibleGroupId) && !empty($type['default_responsible_group_id'])) {
            $responsibleGroupId = (int)$type['default_responsible_group_id'];
        }

        $repo = new RoomServiceRequestsRepository();
        $repo->update((int)$original['id'], [
            'request_type_id' => $requestTypeId,
            'request_description' => $description !== '' ? $description : null,
            'quantity' => $quantity,
            'status' => $status,
            'service_date' => $serviceDate,
            'start_time' => $startTime . ':00',
            'end_time' => $endTime !== '' ? $endTime . ':00' : null,
            'location' => trim($_POST['location'] ?? ($original['location'] ?? '')),
            'responsible_group_id' => $responsibleGroupId,
        ]);

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Solicitação atualizada com sucesso!</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'rooms-view-service-request/' . (int)$original['id']);
        exit;
    }
}

