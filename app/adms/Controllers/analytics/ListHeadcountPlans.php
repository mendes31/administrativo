<?php

declare(strict_types=1);

namespace App\adms\Controllers\analytics;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\HeadcountPlansRepository;
use App\adms\Models\Services\HeadcountPlanService;
use App\adms\Views\Services\LoadViewService;

class ListHeadcountPlans
{
    private array|string|null $data = null;

    public function index(string|int|null $page = null): void
    {
        $page = (int) ($page ?: ($_GET['page'] ?? 1));
        $filters = [];
        foreach (['status', 'department_id', 'period_year', 'period_month'] as $key) {
            if (isset($_GET[$key]) && $_GET[$key] !== '') {
                $filters[$key] = $_GET[$key];
            }
        }
        $repo = new HeadcountPlansRepository();
        $service = new HeadcountPlanService($repo);
        $rows = $repo->getAll($filters, $page, 20);
        foreach ($rows as &$row) {
            $metrics = $service->withActual($row);
            $row['actual_count'] = $metrics['actual_count'];
            $row['gap'] = $metrics['gap'];
        }
        unset($row);

        $this->data['plans'] = $rows;
        $total = $repo->count($filters);
        $pagination = PaginationService::generatePagination($total, 20, $page, 'list-headcount-plans', $filters);
        $this->data['pagination'] = $pagination['html'] ?? '';
        $this->data['filters'] = $filters;
        $this->data['departments'] = (new DepartmentsRepository())->getAllDepartmentsSelect();

        $pageElements = [
            'title_head' => 'Planejamento de Quadro',
            'menu' => 'list-headcount-plans',
            'buttonPermission' => ['CreateHeadcountPlan', 'ViewHeadcountPlan', 'UpdateHeadcountPlan'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/analytics/list_headcount_plans', $this->data))->loadView();
    }
}
