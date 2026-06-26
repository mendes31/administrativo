<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;
use App\adms\Views\Services\LoadViewService;

class ListInvCostPeriods
{
    private array|string|null $data = null;

    public function index(): void
    {
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 20;

        $repo = new InvCostPeriodsRepository();
        $this->data['rows'] = $repo->getAll($page, $perPage);
        $total = $repo->countAll();

        $pagination = PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'list-inventory-cost-periods',
            []
        );
        $this->data['pagination'] = $pagination;

        $pageElements = [
            'title_head' => 'Períodos de Custeio',
            'menu' => 'estoque',
            'buttonPermission' => ['CreateInvCostPeriod', 'ViewInvCostPeriod', 'UpdateInvCostPeriod', 'DeleteInvCostPeriod', 'ListInvCostProductionBatches', 'SimulateInventoryCost'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/costs/periods_list', $this->data);
        $loadView->loadView();
    }
}
