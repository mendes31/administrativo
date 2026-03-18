<?php

namespace App\adms\Controllers\rooms;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RoomRequestTypesRepository;

/**
 * Excluir tipo de solicitação adicional de Reserva de Salas
 */
class RoomsDeleteRequestType
{
    public function index(int|string $id): void
    {
        $id = (int)$id;
        if (!$id) {
            $_SESSION['error'] = 'Tipo de solicitação não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-request-types');
            return;
        }

        if (!CSRFHelper::validateCSRFToken('form_delete_room_request_type', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
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

        if (!$repo->delete($id)) {
            $_SESSION['error'] = 'Não é possível excluir este tipo, pois já está em uso em reservas. Desative-o ao invés de excluir.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-request-types');
            return;
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Tipo de solicitação excluído com sucesso!</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-request-types');
    }
}

