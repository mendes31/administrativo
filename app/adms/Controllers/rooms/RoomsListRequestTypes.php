<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\RoomRequestTypesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Listar tipos de solicitações adicionais para Reserva de Salas
 */
class RoomsListRequestTypes
{
    private array|string|null $data = null;

    public function index(): void
    {
        $repo = new RoomRequestTypesRepository();
        $this->data['requestTypes'] = $repo->getAll();

        $pageElements = [
            'title_head' => 'Tipos de Solicitação (Salas)',
            'menu' => 'RoomsListRequestTypes',
            'buttonPermission' => [
                'RoomsCreateRequestType',
                'RoomsUpdateRequestType',
                'RoomsDeleteRequestType',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rooms/list_request_types', $this->data);
        $loadView->loadView();
    }
}

