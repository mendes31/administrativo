<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\LogResumoService;
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

        $returnList = $_ENV['URL_ADM'] . 'list-inventory-items';
        foreach ($this->data['items'] as &$item) {
            $iid = (int) ($item['id'] ?? 0);
            $item['log_resumo'] = $iid > 0 ? LogResumoService::getResumoInventoryItemContext($iid, $returnList) : [];
        }
        unset($item);

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









