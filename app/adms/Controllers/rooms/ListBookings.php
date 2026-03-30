<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\MeetingRoomsRepository;
use App\adms\Models\Repository\RoomBookingsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar reservas de salas
 */
class ListBookings
{
    private array|string|null $data = null;

    public function index(string|int|null $page = null): void
    {
        $this->data = [];

        $page = $page ? (int)$page : 1;
        $limit = 20;

        $filters = [];
        
        if (!empty($_GET['room_id'])) {
            $filters['room_id'] = (int)$_GET['room_id'];
        }
        
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        
        if (!empty($_GET['start_date'])) {
            $filters['start_date'] = $_GET['start_date'];
        }
        
        if (!empty($_GET['end_date'])) {
            $filters['end_date'] = $_GET['end_date'];
        }

        // Super admin vê todas as reservas, usuário comum vê apenas as suas
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        if (!$isSuperAdmin && empty($filters['user_id'])) {
            $filters['user_id'] = $_SESSION['user_id'] ?? 0;
        }

        $repository = new RoomBookingsRepository();
        $bookings = $repository->getAll($filters, $page, $limit);
        $total = $repository->count($filters);

        $this->data['bookings'] = $bookings;
        $this->data['filters'] = $filters;
        
        $pagination = PaginationService::generatePagination(
            $total,
            $limit,
            $page,
            'list-bookings',
            $filters
        );
        $this->data['pagination'] = $pagination['html'] ?? '';

        // Buscar salas para filtro
        $roomsRepo = new MeetingRoomsRepository();
        $this->data['rooms'] = $roomsRepo->getAll(['status' => 'active'], 1, 1000);

        $pageElements = [
            'title_head' => 'Listar Reservas',
            'menu' => 'list-bookings',
            'buttonPermission' => [
                'CreateBooking',
                'ViewBooking',
                'UpdateBooking',
                'CancelBooking',
                'RoomCalendar',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/rooms/list_bookings', $this->data);
        $loadView->loadView();
    }
}

