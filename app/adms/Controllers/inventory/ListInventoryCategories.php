<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\inventory\InvCategoriesRepository;
use App\adms\Views\Services\LoadViewService;

class ListInventoryCategories
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [ 'name' => $_GET['name'] ?? '' ];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        $repo = new InvCategoriesRepository();
        $this->data['rows'] = $repo->getAll($page, $perPage, $filters);
        $total = $repo->countAll($filters);

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total, $perPage, $page, 'list-inventory-categories', array_filter(['name' => $filters['name'], 'per_page' => $perPage])
        );
        $this->data['paginator'] = $pagination['html'];

        $pageElements = [
            'title_head' => 'Categorias de Itens',
            'menu' => 'estoque',
            'buttonPermission' => ['CreateInventoryCategory', 'UpdateInventoryCategory', 'DeleteInventoryCategory'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/categories/list', $this->data);
        $loadView->loadView();
    }
}








