<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\ImageHelper;
use App\adms\Models\Repository\MeetingRoomsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar sala de reunião
 */
class ViewMeetingRoom
{
    private array|string|null $data = null;

    public function index(string|int|null $id = null): void
    {
        $this->data = [];

        $id = $id ? (int)$id : 0;

        if ($id === 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">ID da sala não informado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-meeting-rooms');
            exit;
        }

        $repository = new MeetingRoomsRepository();
        $room = $repository->getById($id);

        if (!$room) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Sala não encontrada!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-meeting-rooms');
            exit;
        }

        $this->data['room'] = $room;

        $pageElements = [
            'title_head' => 'Visualizar Sala de Reunião',
            'menu' => 'view-meeting-room',
            'buttonPermission' => [
                'ListMeetingRooms',
                'UpdateMeetingRoom',
                'DeleteMeetingRoom',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/rooms/view', $this->data);
        $loadView->loadView();
    }
}

