<?php

namespace App\adms\Controllers\dashboards;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\DashboardsRepository;
use App\adms\Views\Services\LoadViewService;

class ViewDashboard
{
    private array $data = [];

    public function index(?string $id = null): void
    {
        if (!$id) {
            $_SESSION['error'] = 'Dashboard não especificado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }
        
        $dashboardId = (int)$id;
        $userId = $_SESSION['user_id'] ?? 0;
        
        $repo = new DashboardsRepository();
        
        // Verificar acesso
        if (!$repo->canAccess($dashboardId, $userId)) {
            $_SESSION['error'] = 'Você não tem permissão para acessar este dashboard!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }
        
        $this->data['dashboard'] = $repo->getById($dashboardId);
        
        if (!$this->data['dashboard']) {
            $_SESSION['error'] = 'Dashboard não encontrado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }
        
        // Incrementar contador
        $repo->incrementViews($dashboardId);
        
        $pageElements = [
            'title_head' => $this->data['dashboard']['name'],
            'menu' => 'ViewDashboard',
            'buttonPermission' => ['ListDashboards', 'CreateDashboard']
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/dashboards/view', $this->data);
        $loadView->loadView();
    }
}

