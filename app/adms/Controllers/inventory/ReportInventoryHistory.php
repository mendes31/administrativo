<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\inventory\InvMovementsRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvStocksRepository;
use App\adms\Views\Services\LoadViewService;

class ReportInventoryHistory
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'type' => $_GET['type'] ?? '',
            'from' => $_GET['from'] ?? '',
            'to' => $_GET['to'] ?? '',
            'inv_item_id' => $_GET['inv_item_id'] ?? '',
            'inv_stock_id' => $_GET['inv_stock_id'] ?? '',
            'movement_id' => $_GET['movement_id'] ?? '',
        ];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        $repo = new InvMovementsRepository();
        $this->data['rows'] = $repo->reportMovements($page, $perPage, $filters);
        $total = $repo->countReportMovements($filters);

        $itemsRepo = new InvItemsRepository();
        $stocksRepo = new InvStocksRepository();
        $this->data['items'] = $itemsRepo->getAllForSelectWithAdminType();
        $this->data['stocks'] = $stocksRepo->getAllForSelect();

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total, $perPage, $page, 'report-inventory-history', array_filter($filters + ['per_page' => $perPage])
        );
        $this->data['paginator'] = $pagination['html'];

        $pageElements = [
            'title_head' => 'Relatório de Histórico de Movimentações',
            'menu' => 'estoque',
            'buttonPermission' => [],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/reports/history', $this->data);
        $loadView->loadView();
    }
}








