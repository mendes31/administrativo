<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
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

        $repository = new MeetingRoomsRepository();
        $this->data['rooms'] = $repository->getAll([], 1, 100);
        $this->data['csrf_import_room_bookings'] = CSRFHelper::generateCSRFToken('import_room_bookings');

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
