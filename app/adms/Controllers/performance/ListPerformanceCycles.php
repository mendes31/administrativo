<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Listar ciclos de desempenho.
 */
class ListPerformanceCycles
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
            unset($_SESSION['list_performance_cycles_filters']);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-cycles');
            exit;
        }

        $filters = [];
        if (!empty($_GET['year'])) {
            $filters['year'] = (int) $_GET['year'];
        }
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $filters['status'] = $_GET['status'];
        }
        if (!empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }

        if ($filters !== []) {
            $_SESSION['list_performance_cycles_filters'] = $filters;
        } elseif (isset($_SESSION['list_performance_cycles_filters'])) {
            $filters = $_SESSION['list_performance_cycles_filters'];
        }

        $repository = new PerformanceCyclesRepository();
        $this->data['cycles'] = $repository->getAll($filters, $page, $this->limitResult);
        $totalRecords = $repository->count($filters);

        $pagination = PaginationService::generatePagination(
            (int) $totalRecords,
            (int) $this->limitResult,
            (int) $page,
            'list-performance-cycles',
            $filters
        );
        $this->data['pagination'] = $pagination['html'] ?? '';
        $this->data['filters'] = $filters;
        $this->data['page'] = $page;
        $this->data['totalRecords'] = $totalRecords;

        $pageElements = [
            'title_head' => 'Ciclos de Desempenho',
            'menu' => 'list-performance-cycles',
            'buttonPermission' => [
                'CreatePerformanceCycle',
                'ViewPerformanceCycle',
                'UpdatePerformanceCycle',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/performance/list_cycles', $this->data);
        $loadView->loadView();
    }
}
