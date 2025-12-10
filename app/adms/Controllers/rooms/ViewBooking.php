<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\BookingAdditionalRequestsRepository;
use App\adms\Models\Repository\RoomBookingsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar reserva de sala
 */
class ViewBooking
{
    private array|string|null $data = null;

    public function index(string|int|null $id = null): void
    {
        $this->data = [];

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

        // Verificar permissão (usuário comum só vê suas próprias reservas, exceto super admin)
        $isSuperAdmin = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
        $userId = $_SESSION['user_id'] ?? 0;

        if (!$isSuperAdmin && (int)$booking['user_id'] !== $userId) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Você não tem permissão para visualizar esta reserva!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-bookings');
            exit;
        }

        // Buscar participantes
        $this->data['booking'] = $booking;
        $this->data['participants'] = $bookingsRepo->getParticipantsByBookingId($id);

        // Buscar solicitações adicionais
        $requestsRepo = new BookingAdditionalRequestsRepository();
        $this->data['additionalRequests'] = $requestsRepo->getByBookingId($id);

        $pageElements = [
            'title_head' => 'Visualizar Reserva',
            'menu' => 'view-booking',
            'buttonPermission' => [
                'ListBookings',
                'UpdateBooking',
                'CancelBooking',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/rooms/view_booking', $this->data);
        $loadView->loadView();
    }
}

