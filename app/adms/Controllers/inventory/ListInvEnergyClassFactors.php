<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Services\InvEnergyClassFactorService;
use App\adms\Views\Services\LoadViewService;

class ListInvEnergyClassFactors
{
    private array $data = [];

    public function index(): void
    {
        $this->data['rows'] = InvEnergyClassFactorService::listForAdmin();

        $pageElements = [
            'title_head' => 'Classes HVAC (critério 8)',
            'menu' => 'estoque',
            'buttonPermission' => [
                'ListInvEnergyClassFactors',
                'SaveInvEnergyClassFactors',
                'ListInvCostPeriods',
                'ViewInvCostPeriod',
            ],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/costs/energy_class_factors_list', $this->data);
        $loadView->loadView();
    }
}
