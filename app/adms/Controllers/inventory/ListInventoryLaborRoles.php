<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\inventory\InvLaborRolesRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class ListInventoryLaborRoles
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'name' => $_GET['name'] ?? '',
            'active' => $_GET['active'] ?? '',
        ];
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;

        $repo = new InvLaborRolesRepository();
        $this->data['rows'] = $repo->getAll($page, $perPage, $filters);
        $total = $repo->countAll($filters);

        $returnList = $_ENV['URL_ADM'] . 'list-inventory-labor-roles';
        foreach ($this->data['rows'] as &$row) {
            $rid = (int) ($row['id'] ?? 0);
            $row['log_resumo'] = $rid > 0 ? LogResumoService::getResumo('inv_labor_roles', $rid, $returnList) : [];
        }
        unset($row);

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'list-inventory-labor-roles',
            array_filter(array_merge($filters, ['per_page' => $perPage]))
        );
        $this->data['paginator'] = $pagination['html'];

        $pageElements = [
            'title_head' => 'Papéis de Mão de Obra',
            'menu' => 'estoque',
            'buttonPermission' => ['CreateInventoryLaborRole', 'UpdateInventoryLaborRole', 'DeleteInventoryLaborRole'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/labor_roles/list', $this->data);
        $loadView->loadView();
    }
}
