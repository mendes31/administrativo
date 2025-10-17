<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Views\Services\LoadViewService;

class ListInventoryItems
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'code' => $_GET['code'] ?? '',
            'description' => $_GET['description'] ?? '',
            'active' => $_GET['active'] ?? ''
        ];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        $repo = new InvItemsRepository();
        $this->data['items'] = $repo->getAll($page, $perPage, $filters);
        $total = $repo->countAll($filters);

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'list-inventory-items',
            array_filter([
                'code' => $filters['code'],
                'description' => $filters['description'],
                'active' => $filters['active'],
                'per_page' => $perPage
            ])
        );
        $this->data['paginator'] = $pagination['html'];

        $pageElements = [
            'title_head' => 'Itens de Estoque',
            'menu' => 'estoque',
            'buttonPermission' => ['CreateInventoryItem', 'UpdateInventoryItem', 'ViewInventoryItem', 'DeleteInventoryItem'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/items/list', $this->data);
        $loadView->loadView();
    }
}









