<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RoomRequestGroupsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Criar grupo/equipe responsável (Reserva de Salas)
 */
class RoomsCreateRequestGroup
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        }

        $usersRepo = new UsersRepository();
        $this->data['users'] = $usersRepo->getAllUsers(1, 2000, ['status' => 'Ativo']);

        $pageElements = [
            'title_head' => 'Criar Equipe/Grupo (Salas)',
            'menu' => 'RoomsListRequestGroups',
            'buttonPermission' => [
                'RoomsListRequestGroups',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rooms/create_request_group', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('form_create_room_request_group', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-create-request-group');
            exit;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true,
        ];
        $members = $_POST['members'] ?? [];
        if (!is_array($members)) {
            $members = [];
        }

        if (empty($data['name'])) {
            $_SESSION['error'] = 'Nome é obrigatório.';
            return;
        }

        $repo = new RoomRequestGroupsRepository();
        if ($repo->getByName($data['name'])) {
            $_SESSION['error'] = 'Já existe um grupo com este nome.';
            return;
        }

        $id = $repo->create($data);
        $repo->replaceMembers($id, $members);

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Equipe/Grupo criado com sucesso!</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-request-groups');
        exit;
    }
}

