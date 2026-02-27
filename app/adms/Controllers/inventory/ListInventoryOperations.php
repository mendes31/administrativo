<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\inventory\InvOperationsRepository;
use App\adms\Views\Services\LoadViewService;

class ListInventoryOperations
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'code' => $_GET['code'] ?? '',
            'name' => $_GET['name'] ?? '',
            'active' => $_GET['active'] ?? '',
        ];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        $repo = new InvOperationsRepository();
        $this->data['rows'] = $repo->getAll($page, $perPage, $filters);
        $total = $repo->countAll($filters);

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'list-inventory-operations',
            array_filter([
                'code' => $filters['code'],
                'name' => $filters['name'],
                'active' => $filters['active'],
                'per_page' => $perPage,
            ])
        );
        $this->data['paginator'] = $pagination['html'];

        $pageElements = [
            'title_head' => 'Operações de Produção',
            'menu' => 'estoque',
            'buttonPermission' => ['CreateInventoryOperation', 'UpdateInventoryOperation', 'DeleteInventoryOperation'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/operations/list', $this->data);
        $loadView->loadView();
    }
}

