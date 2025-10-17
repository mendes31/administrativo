<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\inventory\InvUnitsRepository;
use App\adms\Views\Services\LoadViewService;

class ListInventoryUnits
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'code' => $_GET['code'] ?? '',
            'name' => $_GET['name'] ?? ''
        ];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        $repo = new InvUnitsRepository();
        $this->data['rows'] = $repo->getAll($page, $perPage, $filters);
        $total = $repo->countAll($filters);

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'list-inventory-units',
            array_filter([
                'code' => $filters['code'],
                'name' => $filters['name'],
                'per_page' => $perPage
            ])
        );
        $this->data['paginator'] = $pagination['html'];

        $pageElements = [
            'title_head' => 'Unidades de Medida',
            'menu' => 'estoque',
            'buttonPermission' => ['CreateInventoryUnit', 'UpdateInventoryUnit', 'ViewInventoryUnit', 'DeleteInventoryUnit'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/units/list', $this->data);
        $loadView->loadView();
    }
}








