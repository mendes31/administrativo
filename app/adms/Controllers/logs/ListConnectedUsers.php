<?php

namespace App\adms\Controllers\logs;

use App\adms\Models\Repository\AdmsSessionsRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

class ListConnectedUsers
{
    private array|string|null $data = null;

    public function index(): void
    {
        $repo = new AdmsSessionsRepository();
        $this->data['sessions'] = $repo->listActiveSessionsWithUsers();
        $this->data['total'] = count($this->data['sessions']);
        $this->data['current_user_id'] = (int)($_SESSION['user_id'] ?? 0);

        $pageElements = [
            'title_head' => 'Usuários conectados',
            'menu' => 'list-connected-users',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/logs/listConnectedUsers', $this->data);
        $loadView->loadView();
    }
}
