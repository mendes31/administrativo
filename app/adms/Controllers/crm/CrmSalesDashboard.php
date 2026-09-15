<?php

declare(strict_types=1);

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

/**
 * Dashboard de Vendas SAP no CRM (shell da UI).
 */
class CrmSalesDashboard
{
    private array|string|null $data = null;

    public function index(): void
    {
        $pageElements = [
            'title_head' => 'Dashboard de Vendas CRM',
            'menu' => 'crm-sales-dashboard',
            'buttonPermission' => [
                'CrmSalesDashboard',
                'CrmSalesDashboardData',
                'CrmSalesDashboardSync',
                'CrmListSalesUsages',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = $pageLayoutService->configurePageElements($pageElements);
        $base = $_ENV['URL_ADM'] ?? '';
        $this->data['api_url'] = $base . 'crm-sales-dashboard-data';
        $this->data['sync_url'] = $base . 'crm-sales-dashboard-sync';
        $this->data['usages_url'] = $base . 'crm-list-sales-usages';
        $perms = $this->data['buttonPermission'] ?? [];
        $this->data['can_sync'] = is_array($perms) && in_array('CrmSalesDashboardSync', $perms, true);
        $this->data['can_usages'] = is_array($perms) && in_array('CrmListSalesUsages', $perms, true);

        $loadView = new LoadViewService('adms/Views/crm/sales_dashboard', $this->data);
        $loadView->loadView();
    }
}
