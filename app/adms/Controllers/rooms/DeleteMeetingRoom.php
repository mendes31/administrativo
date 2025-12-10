<?php

namespace App\adms\Controllers\rooms;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\MeetingRoomsRepository;
use App\adms\Models\Repository\RoomBookingsRepository;

/**
 * Controller para deletar sala de reunião
 */
class DeleteMeetingRoom
{
    public function index(string|int|null $id = null): void
    {
        $id = $id ? (int)$id : 0;

        if ($id === 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">ID da sala não informado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-meeting-rooms');
            exit;
        }

        // Verificar se há reservas futuras para esta sala
        $bookingsRepo = new RoomBookingsRepository();
        $futureBookings = $bookingsRepo->getFutureBookingsByRoom($id);

        if (!empty($futureBookings)) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Não é possível excluir a sala pois existem reservas futuras associadas a ela!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-meeting-room/' . $id);
            exit;
        }

        $repository = new MeetingRoomsRepository();
        $room = $repository->getById($id);

        if (!$room) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Sala não encontrada!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-meeting-rooms');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRFHelper::validateCSRFToken('form_delete_meeting_room', $_POST['csrf_token'] ?? '')) {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Token de segurança inválido!</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'view-meeting-room/' . $id);
                exit;
            }

            try {
                // Deletar imagem se existir
                if (!empty($room['image'])) {
                    $imagePath = 'public/adms/uploads/' . $room['image'];
                    if (file_exists($imagePath)) {
                        @unlink($imagePath);
                    }
                }

                $repository->delete($id);

                $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Sala excluída com sucesso!</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-meeting-rooms');
                exit;
            } catch (\Exception $e) {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao excluir sala: ' . htmlspecialchars($e->getMessage()) . '</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'view-meeting-room/' . $id);
                exit;
            }
        } else {
            // Redirecionar para view com confirmação
            $_SESSION['delete_room_id'] = $id;
            $_SESSION['delete_room_token'] = CSRFHelper::generateCSRFToken('form_delete_meeting_room');
            header('Location: ' . $_ENV['URL_ADM'] . 'view-meeting-room/' . $id);
            exit;
        }
    }
}

