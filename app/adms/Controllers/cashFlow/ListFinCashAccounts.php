<?php

declare(strict_types=1);

namespace App\adms\Controllers\cashFlow;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\cashFlow\FinCashAccountRepository;
use App\adms\Views\Services\LoadViewService;

class ListFinCashAccounts
{
    private array|string|null $data = null;

    public function index(): void
    {
        $repo = new FinCashAccountRepository();
        $this->data['accounts'] = $repo->getAll();

        $pageElements = [
            'title_head' => 'Contas Financeiras SAP',
            'menu' => 'list-fin-cash-accounts',
            'buttonPermission' => [
                'FinCashFlowDashboard',
                'UpdateFinCashAccount',
                'FinCashFlowDashboardSync',
            ],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        $this->data['sync_url'] = ($_ENV['URL_ADM'] ?? '') . 'fin-cash-flow-dashboard-sync';

        $loadView = new LoadViewService('adms/Views/cashFlow/accounts', $this->data);
        $loadView->loadView();
    }
}
