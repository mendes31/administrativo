<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\MeetingRoomsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar salas de reunião
 */
class ListMeetingRooms
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
        
        if (!empty($_GET['building'])) {
            $filters['building'] = $_GET['building'];
        }
        
        if (!empty($_GET['floor'])) {
            $filters['floor'] = $_GET['floor'];
        }
        
        if (!empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
        
        if (!empty($_GET['min_capacity'])) {
            $filters['min_capacity'] = (int)$_GET['min_capacity'];
        }

        $repository = new MeetingRoomsRepository();
        $rooms = $repository->getAll($filters, $page, $limit);
        $total = $repository->count($filters);

        $this->data['rooms'] = $rooms;
        $this->data['filters'] = $filters;
        
        $pagination = PaginationService::generatePagination(
            $total,
            $limit,
            $page,
            'list-meeting-rooms',
            $filters
        );
        $this->data['pagination'] = $pagination['html'] ?? '';

        // Buscar prédios e andares únicos para filtros
        $allRooms = $repository->getAll([], 1, 1000);
        $buildings = array_unique(array_filter(array_column($allRooms, 'building')));
        $floors = array_unique(array_filter(array_column($allRooms, 'floor')));
        
        $this->data['buildings'] = $buildings;
        $this->data['floors'] = $floors;

        $pageElements = [
            'title_head' => 'Listar Salas de Reunião',
            'menu' => 'list-meeting-rooms',
            'buttonPermission' => [
                'CreateMeetingRoom',
                'ViewMeetingRoom',
                'UpdateMeetingRoom',
                'DeleteMeetingRoom',
                'BookRoom',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/rooms/list', $this->data);
        $loadView->loadView();
    }
}

