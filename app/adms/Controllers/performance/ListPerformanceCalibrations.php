<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\PerformanceCalibrationsRepository;
use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Views\Services\LoadViewService;

class ListPerformanceCalibrations
{
    private array|string|null $data = null;
    private int $limitResult = 20;

    public function index(string|int|null $page = null): void
    {
        if ($page === null || $page === '') {
            $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
        } elseif (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        } else {
            $page = is_numeric($page) ? (int) $page : 1;
        }

        if (isset($_GET['limpar'])) {
            unset($_SESSION['list_performance_calibrations_filters']);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-calibrations');
            exit;
        }

        $filters = [];
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $filters['status'] = $_GET['status'];
        }
        if (!empty($_GET['performance_cycle_id'])) {
            $filters['performance_cycle_id'] = (int) $_GET['performance_cycle_id'];
        }
        if (!empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }

        if ($filters !== []) {
            $_SESSION['list_performance_calibrations_filters'] = $filters;
        } elseif (isset($_SESSION['list_performance_calibrations_filters'])) {
            $filters = $_SESSION['list_performance_calibrations_filters'];
        }

        $repository = new PerformanceCalibrationsRepository();
        $this->data['calibrations'] = $repository->getAll($filters, $page, $this->limitResult);
        $totalRecords = $repository->count($filters);

        $pagination = PaginationService::generatePagination(
            (int) $totalRecords,
            (int) $this->limitResult,
            (int) $page,
            'list-performance-calibrations',
            $filters
        );
        $this->data['pagination'] = $pagination['html'] ?? '';
        $this->data['filters'] = $filters;
        $this->data['cycles'] = (new PerformanceCyclesRepository())->getAll([], 1, 200);

        $pageElements = [
            'title_head' => 'Calibrações de Desempenho',
            'menu' => 'list-performance-calibrations',
            'buttonPermission' => [
                'CreatePerformanceCalibration',
                'ViewPerformanceCalibration',
                'UpdatePerformanceCalibration',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/performance/list_calibrations', $this->data);
        $loadView->loadView();
    }
}
