<?php

declare(strict_types=1);

namespace App\adms\Controllers\cashFlow;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

class FinCashFlowDashboard
{
    private array|string|null $data = null;

    public function index(): void
    {
        $pageElements = [
            'title_head' => 'Fluxo de Caixa SAP',
            'menu' => 'fin-cash-flow-dashboard',
            'buttonPermission' => [
                'FinCashFlowDashboard',
                'FinCashFlowDashboardData',
                'FinCashFlowDashboardSync',
                'ListFinCashAccounts',
                'ListFinCashInvestments',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = $pageLayoutService->configurePageElements($pageElements);
        $base = $_ENV['URL_ADM'] ?? '';
        $this->data['api_url'] = $base . 'fin-cash-flow-dashboard-data';
        $this->data['sync_url'] = $base . 'fin-cash-flow-dashboard-sync';
        $this->data['accounts_url'] = $base . 'list-fin-cash-accounts';
        $this->data['investments_url'] = $base . 'list-fin-cash-investments';
        $perms = $this->data['buttonPermission'] ?? [];
        $this->data['can_sync'] = is_array($perms) && in_array('FinCashFlowDashboardSync', $perms, true);
        $this->data['can_accounts'] = is_array($perms) && in_array('ListFinCashAccounts', $perms, true);
        $this->data['can_investments'] = is_array($perms) && in_array('ListFinCashInvestments', $perms, true);

        $loadView = new LoadViewService('adms/Views/cashFlow/dashboard', $this->data);
        $loadView->loadView();
    }
}
