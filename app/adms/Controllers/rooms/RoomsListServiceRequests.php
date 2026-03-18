<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\RoomRequestGroupsRepository;
use App\adms\Models\Repository\RoomServiceRequestsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Listar solicitações avulsas (sem reserva) - Reserva de Salas
 */
class RoomsListServiceRequests
{
    private array|string|null $data = null;

    public function index(string|int|null $page = null): void
    {
        $this->data = [];

        $page = $page ? (int)$page : 1;
        $limit = 20;

        $filters = [];
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        if (!empty($_GET['responsible_group_id'])) {
            $filters['responsible_group_id'] = (int)$_GET['responsible_group_id'];
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

        $pageElements = [
            'title_head' => 'Solicitações (Salas)',
            'menu' => 'RoomsListServiceRequests',
            'buttonPermission' => [
                'RoomsCreateServiceRequest',
                'RoomsViewServiceRequest',
                'RoomsUpdateServiceRequest',
                'RoomsDeleteServiceRequest',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rooms/list_service_requests', $this->data);
        $loadView->loadView();
    }
}

