<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\BookingWaitlistRepository;
use App\adms\Models\Repository\MeetingRoomsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Lista inscrições na fila de espera (slug: booking-waitlist).
 */
class BookingWaitlist
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data = [];

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }
        $limit = 20;

        $filters = [];

        if (!empty($_GET['room_id'])) {
            $filters['room_id'] = (int)$_GET['room_id'];
        }
        if (!empty($_GET['status'])) {
            $filters['status'] = (string)$_GET['status'];
        }
        if (!empty($_GET['start_date'])) {
            $filters['start_date'] = (string)$_GET['start_date'];
        }
        if (!empty($_GET['end_date'])) {
            $filters['end_date'] = (string)$_GET['end_date'];
        }

        $isFullAccess = UserAccessHelper::hasFullSystemAccess();
        if (!$isFullAccess) {
            $filters['user_id'] = (int)($_SESSION['user_id'] ?? 0);
        }

        $this->data['is_full_access'] = $isFullAccess;

        $repo = new BookingWaitlistRepository();
        $rows = $repo->getAll($filters, $page, $limit);
        $total = $repo->count($filters);

        $this->data['waitlist'] = $rows;
        $this->data['filters'] = $filters;

        $pagination = PaginationService::generatePagination(
            $total,
            $limit,
            $page,
            'booking-waitlist',
            array_filter([
                'room_id' => $filters['room_id'] ?? null,
                'status' => $filters['status'] ?? null,
                'start_date' => $filters['start_date'] ?? null,
                'end_date' => $filters['end_date'] ?? null,
            ], static fn ($v) => $v !== null && $v !== '')
        );
        $this->data['pagination'] = $pagination['html'] ?? '';

        $roomsRepo = new MeetingRoomsRepository();
        $this->data['rooms'] = $roomsRepo->getAll(['status' => 'active'], 1, 1000);

        $pageElements = [
            'title_head' => 'Lista de Espera — Salas',
            'menu' => 'booking-waitlist',
            'buttonPermission' => [
                'BookRoom',
                'ListBookings',
                'RoomCalendar',
                'ListMeetingRooms',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rooms/booking_waitlist', $this->data);
        $loadView->loadView();
    }
}
