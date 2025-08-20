<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdDataMappingRepository;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Models\Repository\LgpdInventoryRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdDataMapping
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'source_system' => $_GET['source_system'] ?? '',
            'destination_system' => $_GET['destination_system'] ?? '',
            'ropa_id' => $_GET['ropa_id'] ?? '',
            'inventory_id' => $_GET['inventory_id'] ?? ''
        ];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        $repo = new LgpdDataMappingRepository();
        $ropaRepo = new LgpdRopaRepository();
        $inventoryRepo = new LgpdInventoryRepository();
        
        $this->data['ropas'] = $ropaRepo->getAll([], 1, 1000); // Para select
        $this->data['inventarios'] = $inventoryRepo->getAll([], 1, 1000); // Para select
        $this->data['dataMappings'] = $repo->getAll($filters, $page, $perPage);
        $total = $repo->getAmountDataMapping($filters);

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'lgpd-data-mapping',
            array_filter([
                'source_system' => $filters['source_system'],
                'destination_system' => $filters['destination_system'],
                'ropa_id' => $filters['ropa_id'],
                'inventory_id' => $filters['inventory_id'],
                'per_page' => $perPage
            ])
        );
        $this->data['pagination'] = $pagination;
        
        $pageElements = [
            'title_head' => 'Data Mapping LGPD',
            'menu' => 'lgpd-data-mapping',
            'buttonPermission' => ['ListLgpdDataMapping', 'LgpdDataMappingCreate', 'LgpdDataMappingEdit', 'LgpdDataMappingView', 'LgpdDataMappingDelete'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/data-mapping/list", $this->data);
        $loadView->loadView();
    }
}