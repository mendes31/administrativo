<?php

namespace App\adms\Controllers\workShifts;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\WorkShiftsRepository;
use App\adms\Views\Services\LoadViewService;

class ListWorkShifts
{
    private array|string|null $data = null;

    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 20, 50, 100], true)) {
            $this->limitResult = (int) $_GET['per_page'];
        }
        $filterDescription = isset($_GET['description']) ? trim((string) $_GET['description']) : '';

        $repo = new WorkShiftsRepository();
        $total = $repo->getAmountWorkShifts($filterDescription);
        $this->data['work_shifts'] = $repo->getAllWorkShifts((int) $page, $this->limitResult, $filterDescription);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'list-work-shifts',
            ['per_page' => $this->limitResult, 'description' => $filterDescription]
        );
        $this->data['per_page'] = $this->limitResult;
        $this->data['filter_description'] = $filterDescription;

        $pageElements = [
            'title_head' => 'Turnos de trabalho',
            'menu' => 'list-work-shifts',
            'buttonPermission' => ['CreateWorkShift', 'ViewWorkShift', 'UpdateWorkShift', 'DeleteWorkShift'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/workShifts/list', $this->data);
        $loadView->loadView();
    }
}
