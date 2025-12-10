<?php

namespace App\adms\Controllers\rooms;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RoomBookingsRepository;

/**
 * Controller para cancelar reserva de sala
 */
class CancelBooking
{
    public function index(string|int|null $id = null): void
    {
        $id = $id ? (int)$id : 0;

        if ($id === 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">ID da reserva não informado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-bookings');
            exit;
        }

        $bookingsRepo = new RoomBookingsRepository();
        $booking = $bookingsRepo->getById($id);

        if (!$booking) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Reserva não encontrada!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-bookings');
            exit;
        }

        // Verificar permissão
        $isSuperAdmin = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
        $userId = $_SESSION['user_id'] ?? 0;

        if (!$isSuperAdmin && (int)$booking['user_id'] !== $userId) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Você não tem permissão para cancelar esta reserva!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-booking/' . $id);
            exit;
        }

        // Não permitir cancelar reservas já canceladas ou concluídas
        if (in_array($booking['status'], ['cancelled', 'completed'])) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Esta reserva já está ' . ($booking['status'] === 'cancelled' ? 'cancelada' : 'concluída') . '!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-booking/' . $id);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRFHelper::validateCSRFToken('form_cancel_booking', $_POST['csrf_token'] ?? '')) {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Token de segurança inválido!</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'view-booking/' . $id);
                exit;
            }

            $cancellationReason = trim($_POST['cancellation_reason'] ?? '');

            try {
                $updateData = [
                    'status' => 'cancelled',
                    'cancelled_by' => $userId,
                    'cancelled_at' => date('Y-m-d H:i:s'),
                    'cancellation_reason' => $cancellationReason,
                ];

                $bookingsRepo->update($id, $updateData);

                // TODO: Notificar participantes sobre o cancelamento
                // TODO: Verificar lista de espera e notificar próximo da fila

                $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Reserva cancelada com sucesso!</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'view-booking/' . $id);
                exit;
            } catch (\Exception $e) {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao cancelar reserva: ' . htmlspecialchars($e->getMessage()) . '</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'view-booking/' . $id);
                exit;
            }
        } else {
            // Redirecionar para view com formulário de cancelamento
            $_SESSION['cancel_booking_id'] = $id;
            $_SESSION['cancel_booking_token'] = CSRFHelper::generateCSRFToken('form_cancel_booking');
            header('Location: ' . $_ENV['URL_ADM'] . 'view-booking/' . $id);
            exit;
        }
    }
}

