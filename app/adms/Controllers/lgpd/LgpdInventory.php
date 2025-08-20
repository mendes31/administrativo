<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdInventoryRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdInventory
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'area' => $_GET['area'] ?? '',
            'data_type' => $_GET['data_type'] ?? '',
            'data_category' => $_GET['data_category'] ?? '',
            'risk_level' => $_GET['risk_level'] ?? '',
            'department_id' => $_GET['department_id'] ?? ''
        ];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        $repo = new LgpdInventoryRepository();
        $departmentsRepo = new DepartmentsRepository();
        $this->data['departamentos'] = $departmentsRepo->getAllDepartmentsSelect();
        $this->data['inventories'] = $repo->getAll($filters, $page, $perPage);
        $total = $repo->getAmountInventory($filters);

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'lgpd-inventory',
            array_filter([
                'area' => $filters['area'],
                'data_category' => $filters['data_category'],
                'risk_level' => $filters['risk_level'],
                'department_id' => $filters['department_id'],
                'per_page' => $perPage
            ])
        );
        $this->data['pagination'] = $pagination;
        
        $pageElements = [
            'title_head' => 'Inventário de Dados Pessoais',
            'menu' => 'lgpd-inventory',
            'buttonPermission' => ['ListLgpdInventory', 'LgpdInventoryCreate', 'LgpdInventoryEdit', 'LgpdInventoryView', 'LgpdInventoryDelete'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/inventory/list", $this->data);
        $loadView->loadView();
    }
}