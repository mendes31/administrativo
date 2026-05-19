<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\BookingAdditionalRequestsRepository;
use App\adms\Models\Repository\RoomRequestGroupsRepository;
use App\adms\Models\Repository\RoomServiceRequestsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Gerenciador de solicitações de serviço (com ou sem reserva vinculada).
 */
class RoomsListServiceRequests
{
    private array|string|null $data = null;

    public function index(string|int|null $page = null): void
    {
        $this->data = [];

        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = max(1, (int) $_GET['page']);
        } elseif ($page !== null && $page !== '') {
            $page = max(1, (int) $page);
        } else {
            $page = 1;
        }
        $limit = 20;

        $filters = [];
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        if (!empty($_GET['responsible_group_id'])) {
            $filters['responsible_group_id'] = (int)$_GET['responsible_group_id'];
        }
        if (isset($_GET['has_booking']) && $_GET['has_booking'] !== '') {
            $filters['has_booking'] = $_GET['has_booking'];
        }

        if (!UserAccessHelper::hasFullSystemAccess()) {
            $uid = (int) ($_SESSION['user_id'] ?? 0);
            if ($uid > 0) {
                $filters['requester_or_booking_organizer_user_id'] = $uid;
            }
        }

        $repo = new RoomServiceRequestsRepository();
        $this->data['requests'] = $repo->getAll($filters, $page, $limit);
        $total = $repo->count($filters);

        $pagination = PaginationService::generatePagination(
            $total,
            $limit,
            $page,
            'rooms-list-service-requests',
            $filters
        );
        $this->data['pagination'] = $pagination['html'] ?? '';

        $groupsRepo = new RoomRequestGroupsRepository();
        $this->data['groups'] = $groupsRepo->getAll(true);
        $this->data['filters'] = $filters;
        $this->data['service_requests_list_only_own'] = !UserAccessHelper::hasFullSystemAccess();

        $barRepo = new BookingAdditionalRequestsRepository();
        $sessionUid = (int) ($_SESSION['user_id'] ?? 0);
        $this->data['booking_additional_requests'] = UserAccessHelper::hasFullSystemAccess()
            ? $barRepo->listRecentForAdmin(150)
            : $barRepo->listInvolvingUser($sessionUid, 100);

        $pageElements = [
            'title_head' => 'Solicitações (Salas)',
            'menu' => 'RoomsListServiceRequests',
            'buttonPermission' => [
                'RoomsCreateServiceRequest',
                'RoomsViewServiceRequest',
                'RoomsUpdateServiceRequest',
                'RoomsDeleteServiceRequest',
                'ViewBooking',
                'UpdateBooking',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rooms/list_service_requests', $this->data);
        $loadView->loadView();
    }
}

