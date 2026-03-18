<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RoomRequestGroupsRepository;
use App\adms\Models\Repository\RoomRequestTypesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Criar tipo de solicitação adicional para Reserva de Salas
 */
class RoomsCreateRequestType
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        }

        $groupsRepo = new RoomRequestGroupsRepository();
        $this->data['groups'] = $groupsRepo->getAll(true);

        $pageElements = [
            'title_head' => 'Criar Tipo de Solicitação (Salas)',
            'menu' => 'RoomsListRequestTypes',
            'buttonPermission' => [
                'RoomsListRequestTypes',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rooms/create_request_type', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('form_create_room_request_type', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-create-request-type');
            exit;
        }

        $data = [
            'code' => strtolower(trim($_POST['code'] ?? '')),
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'requires_responsible' => isset($_POST['requires_responsible']) && $_POST['requires_responsible'] === '1',
            'default_responsible_group_id' => !empty($_POST['default_responsible_group_id']) ? (int)$_POST['default_responsible_group_id'] : null,
            'requires_quantity' => isset($_POST['requires_quantity']) && $_POST['requires_quantity'] === '1',
            'is_active' => isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true,
        ];

        if (empty($data['code']) || empty($data['name'])) {
            $_SESSION['error'] = 'Código e Nome são obrigatórios.';
            return;
        }

        if (!preg_match('/^[a-z0-9_]+$/', $data['code'])) {
            $_SESSION['error'] = 'Código deve conter apenas letras minúsculas, números e underscore.';
            return;
        }

        $repo = new RoomRequestTypesRepository();
        if ($repo->getByCode($data['code'])) {
            $_SESSION['error'] = 'Já existe um tipo com este código.';
            return;
        }

        $repo->create($data);
        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Tipo de solicitação criado com sucesso!</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-request-types');
        exit;
    }
}

