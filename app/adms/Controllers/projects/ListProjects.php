<?php

namespace App\adms\Controllers\projects;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\projects\ProjProjectsRepository;
use App\adms\Views\Services\LoadViewService;

class ListProjects
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50, 100], true)) {
            $this->limitResult = (int)$_GET['per_page'];
        }

        $filters = [
            'name' => $_GET['name'] ?? '',
            'type' => $_GET['type'] ?? '',
            'status' => $_GET['status'] ?? '',
            'active' => $_GET['active'] ?? '',
        ];

        $repo = new ProjProjectsRepository();
        $result = $repo->getAll((int)$page, $this->limitResult, $filters);
        $this->data['projects'] = $result['data'] ?? [];
        $total = (int)($result['total'] ?? 0);

        $pagination = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int)$page,
            'list-projects',
            array_merge($filters, ['per_page' => $this->limitResult])
        );
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['filters'] = $filters;

        $pageElements = [
            'title_head' => 'Projetos',
            'menu' => 'list-projects',
            'buttonPermission' => ['ListProjects', 'CreateProject', 'UpdateProject', 'DeleteProject'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/projects/list', $this->data);
        $loadView->loadView();
    }
}

