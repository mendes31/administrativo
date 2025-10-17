<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\inventory\InvStocksRepository;
use App\adms\Views\Services\LoadViewService;

class ListInventoryStocks
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'name' => $_GET['name'] ?? '',
            'code' => $_GET['code'] ?? '',
            'active' => $_GET['active'] ?? ''
        ];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        $repo = new InvStocksRepository();
        $this->data['rows'] = $repo->getAll($page, $perPage, $filters);
        $total = $repo->countAll($filters);

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total, $perPage, $page, 'list-inventory-stocks', array_filter([
                'name' => $filters['name'],
                'code' => $filters['code'],
                'active' => $filters['active'],
                'per_page' => $perPage
            ])
        );
        $this->data['paginator'] = $pagination['html'];

        $pageElements = [
            'title_head' => 'Estoques',
            'menu' => 'estoque',
            'buttonPermission' => ['CreateInventoryStock', 'UpdateInventoryStock', 'DeleteInventoryStock'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/stocks/list', $this->data);
        $loadView->loadView();
    }
}








