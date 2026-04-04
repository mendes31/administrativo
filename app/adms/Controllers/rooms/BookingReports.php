<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\MeetingRoomsRepository;
use App\adms\Models\Repository\RoomBookingsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Relatórios de reservas de salas (slug: booking-reports → BookingReports).
 */
class BookingReports
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data = [];

        $firstDay = (new \DateTime('first day of this month'))->format('Y-m-d');
        $lastDay = (new \DateTime('last day of this month'))->format('Y-m-d');

        $filters = [
            'start_date' => $_GET['start_date'] ?? $firstDay,
            'end_date' => $_GET['end_date'] ?? $lastDay,
        ];

        if (!empty($_GET['room_id'])) {
            $filters['room_id'] = (int)$_GET['room_id'];
        }
        if (!empty($_GET['status'])) {
            $filters['status'] = (string)$_GET['status'];
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }
        $limit = 25;

        $repo = new RoomBookingsRepository();
        $bookings = $repo->getAll($filters, $page, $limit);
        $total = $repo->count($filters);

        $summaryFilters = [
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
        ];
        if (!empty($filters['room_id'])) {
            $summaryFilters['room_id'] = $filters['room_id'];
        }

        $statuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];
        $summaryByStatus = [];
        foreach ($statuses as $st) {
            $summaryByStatus[$st] = $repo->count(array_merge($summaryFilters, ['status' => $st]));
        }

        $this->data['bookings'] = $bookings;
        $this->data['filters'] = $filters;
        $this->data['summary_by_status'] = $summaryByStatus;

        $paginationFilters = array_filter([
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
            'room_id' => $filters['room_id'] ?? null,
            'status' => $filters['status'] ?? null,
        ], static fn ($v) => $v !== null && $v !== '');

        $pagination = PaginationService::generatePagination(
            $total,
            $limit,
            $page,
            'booking-reports',
            $paginationFilters
        );
        $this->data['pagination'] = $pagination['html'] ?? '';

        $roomsRepo = new MeetingRoomsRepository();
        $this->data['rooms'] = $roomsRepo->getAll(['status' => 'active'], 1, 1000);

        $pageElements = [
            'title_head' => 'Relatórios de Reservas',
            'menu' => 'booking-reports',
            'buttonPermission' => [
                'ListBookings',
                'RoomCalendar',
                'ViewBooking',
                'AdminBookingDashboard',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rooms/booking_reports', $this->data);
        $loadView->loadView();
    }
}
