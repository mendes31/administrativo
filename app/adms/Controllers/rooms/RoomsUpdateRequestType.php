<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RoomRequestGroupsRepository;
use App\adms\Models\Repository\RoomRequestTypesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Editar tipo de solicitação adicional para Reserva de Salas
 */
class RoomsUpdateRequestType
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $id = (int)$id;
        if (!$id) {
            $_SESSION['error'] = 'Tipo de solicitação não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-request-types');
            return;
        }

        $repo = new RoomRequestTypesRepository();
        $type = $repo->getById($id);
        if (!$type) {
            $_SESSION['error'] = 'Tipo de solicitação não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-request-types');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($id, $type);
        }

        $groupsRepo = new RoomRequestGroupsRepository();
        $this->data['groups'] = $groupsRepo->getAll(true);
        $this->data['requestType'] = $type;

        $pageElements = [
            'title_head' => 'Editar Tipo de Solicitação (Salas)',
            'menu' => 'RoomsListRequestTypes',
            'buttonPermission' => [
                'RoomsListRequestTypes',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rooms/update_request_type', $this->data);
        $loadView->loadView();
    }

    private function update(int $id, array $original): void
    {
        if (!CSRFHelper::validateCSRFToken('form_update_room_request_type', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            return;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'requires_responsible' => isset($_POST['requires_responsible']) && $_POST['requires_responsible'] === '1',
            'default_responsible_group_id' => !empty($_POST['default_responsible_group_id']) ? (int)$_POST['default_responsible_group_id'] : null,
            'requires_quantity' => isset($_POST['requires_quantity']) && $_POST['requires_quantity'] === '1',
            'is_active' => isset($_POST['is_active']) ? (bool)$_POST['is_active'] : (bool)$original['is_active'],
        ];

        if (empty($data['name'])) {
            $_SESSION['error'] = 'Nome é obrigatório.';
            return;
        }

        $repo = new RoomRequestTypesRepository();
        $repo->update($id, $data);
        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Tipo de solicitação atualizado com sucesso!</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-request-types');
        exit;
    }
}

