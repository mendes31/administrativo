<?php

namespace App\adms\Controllers\rooms;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RoomRequestGroupsRepository;

/**
 * Excluir grupo/equipe responsável (Reserva de Salas)
 */
class RoomsDeleteRequestGroup
{
    public function index(int|string $id): void
    {
        $id = (int)$id;
        if (!$id) {
            $_SESSION['error'] = 'Grupo não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-request-groups');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Método inválido.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-request-groups');
            return;
        }

        if (!CSRFHelper::validateCSRFToken('form_delete_room_request_group', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-request-groups');
            return;
        }

        $repo = new RoomRequestGroupsRepository();
        $ok = $repo->delete($id);
        if (!$ok) {
            $_SESSION['error'] = 'Não foi possível excluir. Verifique se existe algum tipo de solicitação vinculado a este grupo.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-request-groups');
            return;
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Equipe/Grupo excluído com sucesso!</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-request-groups');
        exit;
    }
}

