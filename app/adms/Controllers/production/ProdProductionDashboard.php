<?php

declare(strict_types=1);

namespace App\adms\Controllers\production;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

/**
 * Dashboard de Produção SAP/BEAS (shell da UI).
 */
class ProdProductionDashboard
{
    private array|string|null $data = null;

    public function index(): void
    {
        $pageElements = [
            'title_head' => 'Dashboard de Produção',
            'menu' => 'prod-production-dashboard',
            'buttonPermission' => [
                'ProdProductionDashboard',
                'ProdProductionDashboardData',
                'ProdProductionDashboardSync',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = $pageLayoutService->configurePageElements($pageElements);
        $base = $_ENV['URL_ADM'] ?? '';
        $this->data['api_url'] = $base . 'prod-production-dashboard-data';
        $this->data['sync_url'] = $base . 'prod-production-dashboard-sync';
        $perms = $this->data['buttonPermission'] ?? [];
        $this->data['can_sync'] = is_array($perms) && in_array('ProdProductionDashboardSync', $perms, true);

        $loadView = new LoadViewService('adms/Views/production/dashboard', $this->data);
        $loadView->loadView();
    }
}
