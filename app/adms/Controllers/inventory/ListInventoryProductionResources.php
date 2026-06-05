<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\inventory\InvProductionResourcesRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class ListInventoryProductionResources
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'erp_code' => $_GET['erp_code'] ?? '',
            'name' => $_GET['name'] ?? '',
            'resource_type' => $_GET['resource_type'] ?? '',
            'active' => $_GET['active'] ?? '',
        ];
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;

        $repo = new InvProductionResourcesRepository();
        $this->data['rows'] = $repo->getAll($page, $perPage, $filters);
        $total = $repo->countAll($filters);

        $returnList = $_ENV['URL_ADM'] . 'list-inventory-production-resources';
        foreach ($this->data['rows'] as &$row) {
            $rid = (int) ($row['id'] ?? 0);
            $row['log_resumo'] = $rid > 0 ? LogResumoService::getResumo('inv_production_resources', $rid, $returnList) : [];
        }
        unset($row);

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'list-inventory-production-resources',
            array_filter(array_merge($filters, ['per_page' => $perPage]))
        );
        $this->data['paginator'] = $pagination['html'];

        $pageElements = [
            'title_head' => 'Recursos de Produção',
            'menu' => 'estoque',
            'buttonPermission' => ['CreateInventoryProductionResource', 'UpdateInventoryProductionResource', 'DeleteInventoryProductionResource'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/production_resources/list', $this->data);
        $loadView->loadView();
    }
}
