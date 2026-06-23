<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\SstGheRepository;
use App\adms\Views\Services\LoadViewService;

class SstListGhe
{
    private array $data = [];
    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'adms_department_id' => $_GET['adms_department_id'] ?? '',
        ];
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 20, 50, 100], true)) {
            $this->limitResult = (int) $_GET['per_page'];
        }
        $repo = new SstGheRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limitResult, $filters);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'sst-list-ghe',
            array_merge(['per_page' => $this->limitResult], $filters)
        );
        $this->data['per_page'] = $this->limitResult;
        $this->data['filters'] = $filters;
        $this->data['departments'] = (new DepartmentsRepository())->getAllDepartmentsSelect();
        $pageElements = [
            'title_head' => 'GHE — Ambientes de Trabalho SST',
            'menu' => 'sst-list-ghe',
            'buttonPermission' => ['SstViewGhe', 'SstCreateGhe', 'SstUpdateGhe', 'SstDeleteGhe'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/ghe/list', $this->data))->loadView();
    }
}
