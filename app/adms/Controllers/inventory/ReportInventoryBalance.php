<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\inventory\InvBalancesRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvStocksRepository;
use App\adms\Models\Repository\inventory\InvPositionsRepository;
use App\adms\Views\Services\LoadViewService;

class ReportInventoryBalance
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'inv_item_id' => $_GET['inv_item_id'] ?? '',
            'inv_stock_id' => $_GET['inv_stock_id'] ?? '',
            'inv_position_id' => $_GET['inv_position_id'] ?? '',
            'batch_code' => $_GET['batch_code'] ?? '',
            'expiration_date' => $_GET['expiration_date'] ?? ''
        ];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        $repo = new InvBalancesRepository();
        $this->data['rows'] = $repo->reportBalances($page, $perPage, $filters);
        $total = $repo->countReportBalances($filters);

        $itemsRepo = new InvItemsRepository();
        $stocksRepo = new InvStocksRepository();
        $positionsRepo = new InvPositionsRepository();
        $this->data['items'] = $itemsRepo->getAllForSelectWithAdminType();
        $this->data['stocks'] = $stocksRepo->getAllForSelect();
        $this->data['positions'] = !empty($filters['inv_stock_id']) ? $positionsRepo->getAllForSelectByStock((int)$filters['inv_stock_id']) : [];

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total, $perPage, $page, 'report-inventory-balance', array_filter($filters + ['per_page' => $perPage])
        );
        $this->data['paginator'] = $pagination['html'];

        $pageElements = [
            'title_head' => 'Relatório de Saldos de Estoque',
            'menu' => 'estoque',
            'buttonPermission' => [],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/reports/balance', $this->data);
        $loadView->loadView();
    }
}








