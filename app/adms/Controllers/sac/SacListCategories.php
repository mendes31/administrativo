<?php

namespace App\adms\Controllers\sac;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SacCategoriesRepository;
use App\adms\Views\Services\LoadViewService;

class SacListCategories
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        $filters = [
            'name' => $_GET['search'] ?? '',
            'is_active' => $_GET['is_active'] ?? '',
        ];

        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }

        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50, 100])) {
            $this->limitResult = (int)$_GET['per_page'];
        }

        $categoriesRepo = new SacCategoriesRepository();
        $totalCategories = $categoriesRepo->getTotalCategories($filters);
        $this->data['categories'] = $categoriesRepo->getAllCategories((int)$page, (int)$this->limitResult, $filters);

        $pagination = PaginationService::generatePagination(
            (int)$totalCategories,
            (int)$this->limitResult,
            (int)$page,
            'sac-list-categories',
            array_merge(['per_page' => $this->limitResult], $filters)
        );

        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['filters'] = $filters;

        $pageElements = [
            'title_head' => 'Categorias de Chamados - SAC',
            'menu' => 'sac-list-categories',
            'buttonPermission' => ['SacCreateCategory', 'SacUpdateCategory', 'SacDeleteCategory'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/sac/categories/list", $this->data);
        $loadView->loadView();
    }
}
