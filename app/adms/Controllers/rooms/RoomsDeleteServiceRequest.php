<?php

namespace App\adms\Controllers\rooms;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\RoomServiceRequestAccessHelper;
use App\adms\Models\Repository\RoomServiceRequestsRepository;

/**
 * Excluir solicitação avulsa - Reserva de Salas
 */
class RoomsDeleteServiceRequest
{
    public function index(int|string $id): void
    {
        $id = (int)$id;
        if (!$id) {
            $_SESSION['error'] = 'Solicitação não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-service-requests');
            return;
        }

        if (!CSRFHelper::validateCSRFToken('form_delete_room_service_request', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-service-requests');
            exit;
        }

        $repo = new RoomServiceRequestsRepository();
        $row = $repo->getById($id);
        if (!$row) {
            $_SESSION['error'] = 'Solicitação não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-service-requests');
            exit;
        }
        if (!RoomServiceRequestAccessHelper::currentUserMayDeleteServiceRequest($row)) {
            $_SESSION['error'] = 'Não tem permissão para excluir esta solicitação.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-service-requests');
            exit;
        }

        $repo->delete($id);

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Solicitação excluída com sucesso!</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-service-requests');
        exit;
    }
}

