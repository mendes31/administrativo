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
                'CrmSalesInvoices',
                'CrmSalesCarteira',
                'CrmSalesVendedores',
                'CrmSalesProdutos',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = $pageLayoutService->configurePageElements($pageElements);
        $base = $_ENV['URL_ADM'] ?? '';
        $this->data['api_url'] = $base . 'crm-sales-dashboard-data';
        $this->data['sync_url'] = $base . 'crm-sales-dashboard-sync';
        $this->data['invoices_url'] = $base . 'crm-sales-invoices';
        $this->data['usages_url'] = $base . 'crm-list-sales-usages';
        $perms = $this->data['buttonPermission'] ?? [];
        $this->data['can_sync'] = is_array($perms) && in_array('CrmSalesDashboardSync', $perms, true);
        $this->data['can_usages'] = is_array($perms) && in_array('CrmListSalesUsages', $perms, true);
        $this->data['can_dashboard'] = true;
        $this->data['can_carteira'] = is_array($perms) && in_array('CrmSalesCarteira', $perms, true);
        $this->data['can_vendedores'] = is_array($perms) && in_array('CrmSalesVendedores', $perms, true);
        $this->data['can_produtos'] = is_array($perms) && in_array('CrmSalesProdutos', $perms, true);
        $this->data['sales_nav_active'] = 'dashboard';
        $this->data['query_string'] = '';
        $this->data['dashboard_url'] = $base . 'crm-sales-dashboard';
        $this->data['carteira_url'] = $base . 'crm-sales-carteira';
        $this->data['vendedores_url'] = $base . 'crm-sales-vendedores';
        $this->data['produtos_url'] = $base . 'crm-sales-produtos';

        $loadView = new LoadViewService('adms/Views/crm/sales_dashboard', $this->data);
        $loadView->loadView();
    }
}
