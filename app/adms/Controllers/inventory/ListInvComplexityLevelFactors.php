<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Services\InvComplexityLevelFactorService;
use App\adms\Views\Services\LoadViewService;

class ListInvComplexityLevelFactors
{
    private array $data = [];

    public function index(): void
    {
        $this->data['rows'] = InvComplexityLevelFactorService::listForAdmin();

        $pageElements = [
            'title_head' => 'Complexidade (crit. 4/6)',
            'menu' => 'estoque',
            'buttonPermission' => [
                'ListInvComplexityLevelFactors',
                'SaveInvComplexityLevelFactors',
                'ListInvCostPeriods',
                'ViewInvCostPeriod',
            ],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/costs/complexity_level_factors_list', $this->data);
        $loadView->loadView();
    }
}
