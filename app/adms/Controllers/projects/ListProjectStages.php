<?php

namespace App\adms\Controllers\projects;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\projects\ProjStagesRepository;
use App\adms\Views\Services\LoadViewService;

class ListProjectStages
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
            'is_cost_stage' => $_GET['is_cost_stage'] ?? '',
            'active' => $_GET['active'] ?? '',
        ];

        $repo = new ProjStagesRepository();
        $result = $repo->getAll((int)$page, $this->limitResult, $filters);
        $this->data['stages'] = $result['data'] ?? [];
        $total = (int)($result['total'] ?? 0);

        $pagination = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int)$page,
            'list-project-stages',
            array_merge($filters, ['per_page' => $this->limitResult])
        );
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;

        $pageElements = [
            'title_head' => 'Etapas de Projeto',
            // Usa a própria rota como identificador para destacar apenas o submenu "Etapas"
            'menu' => 'list-project-stages',
            'buttonPermission' => ['ListProjectStages', 'CreateProjectStage', 'UpdateProjectStage', 'DeleteProjectStage'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/projects/stages/list', $this->data);
        $loadView->loadView();
    }
}

