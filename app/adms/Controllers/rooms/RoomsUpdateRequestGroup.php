<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RoomRequestGroupsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Editar grupo/equipe responsável (Reserva de Salas)
 */
class RoomsUpdateRequestGroup
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $id = (int)$id;
        if (!$id) {
            $_SESSION['error'] = 'Grupo não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-request-groups');
            return;
        }

        $repo = new RoomRequestGroupsRepository();
        $group = $repo->getById($id);
        if (!$group) {
            $_SESSION['error'] = 'Grupo não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-request-groups');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($id);
        }

        $usersRepo = new UsersRepository();
        $this->data['users'] = $usersRepo->getAllUsers(1, 2000, ['status' => 'Ativo']);
        $this->data['group'] = $group;
        $this->data['groupMemberIds'] = array_map('intval', $repo->getMemberUserIds($id));

        $pageElements = [
            'title_head' => 'Editar Equipe/Grupo (Salas)',
            'menu' => 'RoomsListRequestGroups',
            'buttonPermission' => [
                'RoomsListRequestGroups',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rooms/update_request_group', $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('form_update_room_request_group', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            return;
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
        $existing = $repo->getByName($data['name']);
        if ($existing && (int)$existing['id'] !== $id) {
            $_SESSION['error'] = 'Já existe um grupo com este nome.';
            return;
        }

        $repo->update($id, $data);
        $repo->replaceMembers($id, $members);

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Equipe/Grupo atualizado com sucesso!</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-request-groups');
        exit;
    }
}

