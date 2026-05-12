<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\inventory\InvPositionsRepository;
use App\adms\Models\Repository\inventory\InvStocksRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class ListInventoryPositions
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'inv_stock_id' => $_GET['inv_stock_id'] ?? '',
            'code' => $_GET['code'] ?? '',
            'description' => $_GET['description'] ?? ''
        ];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        $repo = new InvPositionsRepository();
        $this->data['rows'] = $repo->getAll($page, $perPage, $filters);
        $total = $repo->countAll($filters);

        $returnList = $_ENV['URL_ADM'] . 'list-inventory-positions';
        foreach ($this->data['rows'] as &$row) {
            $pid = (int) ($row['id'] ?? 0);
            $row['log_resumo'] = $pid > 0 ? LogResumoService::getResumo('inv_positions', $pid, $returnList) : [];
        }
        unset($row);

        $stocksRepo = new InvStocksRepository();
        $this->data['stocks'] = $stocksRepo->getAllForSelect();

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total, $perPage, $page, 'list-inventory-positions', array_filter([
                'inv_stock_id' => $filters['inv_stock_id'],
                'code' => $filters['code'],
                'description' => $filters['description'],
                'per_page' => $perPage
            ])
        );
        $this->data['paginator'] = $pagination['html'];

        $pageElements = [
            'title_head' => 'Posições Internas',
            'menu' => 'estoque',
            'buttonPermission' => ['CreateInventoryPosition', 'UpdateInventoryPosition', 'DeleteInventoryPosition'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/positions/list', $this->data);
        $loadView->loadView();
    }
}








