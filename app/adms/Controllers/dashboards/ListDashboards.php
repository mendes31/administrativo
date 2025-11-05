<?php

namespace App\adms\Controllers\dashboards;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\DashboardsRepository;
use App\adms\Views\Services\LoadViewService;

class ListDashboards
{
    private array $data = [];

    public function index(): void
    {
        $userId = $_SESSION['user_id'] ?? 0;
        
        $repo = new DashboardsRepository();
        $this->data['dashboards'] = $repo->getUserDashboards($userId, true);
        
        $pageElements = [
            'title_head' => 'Meus Dashboards',
            'menu' => 'ListDashboards',
            'buttonPermission' => ['CreateDashboard', 'ViewDashboard', 'DeleteDashboard']
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/dashboards/list', $this->data);
        $loadView->loadView();
    }
}

