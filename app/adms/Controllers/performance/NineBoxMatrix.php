<?php

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Models\Repository\PerformanceReviewsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para Matriz 9BOX
 */
class NineBoxMatrix
{
    private array|string|null $data = null;

    public function index(): void
    {
        $repository = new PerformanceReviewsRepository();
        $departmentsRepo = new DepartmentsRepository();
        $positionsRepo = new PositionsRepository();
        
        // Filtros
        $filters = [];
        if (!empty($_GET['department_id'])) {
            $filters['department_id'] = (int)$_GET['department_id'];
        }
        if (!empty($_GET['position_id'])) {
            $filters['position_id'] = (int)$_GET['position_id'];
        }
        if (!empty($_GET['period_start'])) {
            $filters['period_start'] = $_GET['period_start'];
        }
        if (!empty($_GET['period_end'])) {
            $filters['period_end'] = $_GET['period_end'];
        }
        if (!empty($_GET['performance_cycle_id'])) {
            $filters['performance_cycle_id'] = (int) $_GET['performance_cycle_id'];
        }
        
        // Buscar dados da matriz 9BOX
        $matrixData = $repository->getNineBoxData($filters);
        
        $this->data['boxes'] = $matrixData['boxes'];
        $this->data['employees'] = $matrixData['employees'];
        $this->data['total'] = $matrixData['total'];
        $this->data['filters'] = $filters;
        
        // Dados para filtros
        $this->data['departments'] = $departmentsRepo->getAllDepartmentsSelect();
        $this->data['positions'] = $positionsRepo->getAllPositionsSelect();
        $this->data['cycles'] = (new PerformanceCyclesRepository())->getAll([], 1, 200);
        
        // Estatísticas por box
        $this->data['box_stats'] = [];
        foreach ($matrixData['boxes'] as $boxNum => $employees) {
            $this->data['box_stats'][$boxNum] = count($employees);
        }
        
        $pageElements = [
            'title_head' => 'Matriz 9BOX',
            'menu' => 'nine-box-matrix',
            'buttonPermission' => [
                'ListPerformanceReviews',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/performance/nine_box_matrix', $this->data);
        $loadView->loadView();
    }
}

