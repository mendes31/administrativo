<?php

namespace App\adms\Controllers\sac;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SacCategoriesRepository;
use App\adms\Models\Repository\SacSlaRulesRepository;
use App\adms\Views\Services\LoadViewService;

class SacListSlaRules
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        $filters = [
            'name' => $_GET['search'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'is_active' => $_GET['is_active'] ?? '',
        ];

        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }

        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50, 100])) {
            $this->limitResult = (int)$_GET['per_page'];
        }

        $rulesRepo = new SacSlaRulesRepository();
        $totalRules = $rulesRepo->getTotalRules($filters);
        $this->data['rules'] = $rulesRepo->getAllRules((int)$page, (int)$this->limitResult, $filters);

        $pagination = PaginationService::generatePagination(
            (int)$totalRules,
            (int)$this->limitResult,
            (int)$page,
            'sac-list-sla-rules',
            array_merge(['per_page' => $this->limitResult], $filters)
        );

        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['filters'] = $filters;

        $categoriesRepo = new SacCategoriesRepository();
        $this->data['categories'] = $categoriesRepo->getActiveCategories();

        $pageElements = [
            'title_head' => 'Regras de SLA - SAC',
            'menu' => 'sac-list-sla-rules',
            'buttonPermission' => ['SacCreateSlaRule', 'SacUpdateSlaRule', 'SacDeleteSlaRule'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/sac/slaRules/list", $this->data);
        $loadView->loadView();
    }
}
