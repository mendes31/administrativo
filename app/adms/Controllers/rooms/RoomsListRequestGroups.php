<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\RoomRequestGroupsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Listar grupos/equipes responsáveis por solicitações (Reserva de Salas)
 */
class RoomsListRequestGroups
{
    private array|string|null $data = null;

    public function index(): void
    {
        $repo = new RoomRequestGroupsRepository();
        $this->data['groups'] = $repo->getAll(false);

        $pageElements = [
            'title_head' => 'Equipes/Grupos Responsáveis (Salas)',
            'menu' => 'RoomsListRequestGroups',
            'buttonPermission' => [
                'RoomsCreateRequestGroup',
                'RoomsUpdateRequestGroup',
                'RoomsDeleteRequestGroup',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rooms/list_request_groups', $this->data);
        $loadView->loadView();
    }
}

