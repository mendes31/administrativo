<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\BookingAdditionalRequestsRepository;
use App\adms\Models\Repository\RoomBookingsRepository;
use App\adms\Models\Repository\RoomServiceRequestsRepository;
use App\adms\Models\Services\LogResumoService;
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
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        $userId = $_SESSION['user_id'] ?? 0;

        if (!$isSuperAdmin && (int)$booking['user_id'] !== $userId) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Você não tem permissão para visualizar esta reserva!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-bookings');
            exit;
        }

        // Buscar participantes
        $this->data['booking'] = $booking;
        $this->data['participants'] = $bookingsRepo->getParticipantsByBookingId($id);

        $seriesId = trim((string) ($booking['recurrence_series_id'] ?? ''));
        $this->data['recurrence_series_count'] = 0;
        if ($seriesId !== '') {
            $this->data['recurrence_series_count'] = count($bookingsRepo->listActiveInRecurrenceSeries($seriesId));
        }

        // Solicitações adicionais (modelo legado em adms_booking_additional_requests)
        $requestsRepo = new BookingAdditionalRequestsRepository();
        $this->data['additionalRequests'] = $requestsRepo->getByBookingId($id);

        // Solicitações de serviço (tipos / equipes — adms_room_service_requests, opcionalmente vinculadas)
        $serviceReqRepo = new RoomServiceRequestsRepository();
        $this->data['serviceRequests'] = $serviceReqRepo->getByBookingId($id);

        $returnUrl = $_ENV['URL_ADM'] . 'view-booking/' . $id;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_room_bookings', $id, $returnUrl);

        $pageElements = [
            'title_head' => 'Visualizar Reserva',
            'menu' => 'view-booking',
            'buttonPermission' => [
                'ListBookings',
                'UpdateBooking',
                'CancelBooking',
                'RoomsCreateServiceRequest',
                'RoomsViewServiceRequest',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/rooms/view_booking', $this->data);
        $loadView->loadView();
    }
}

