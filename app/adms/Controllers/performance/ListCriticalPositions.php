<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\CriticalPositionsRepository;
use App\adms\Views\Services\LoadViewService;

class ListCriticalPositions
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
            unset($_SESSION['list_critical_positions_filters']);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-critical-positions');
            exit;
        }

        $filters = [];
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $filters['status'] = $_GET['status'];
        }
        if (!empty($_GET['risk_level'])) {
            $filters['risk_level'] = $_GET['risk_level'];
        }
        if (!empty($_GET['search'])) {
            $filters['search'] = trim((string) $_GET['search']);
        }

        if (!empty($filters)) {
            $_SESSION['list_critical_positions_filters'] = $filters;
        } elseif (isset($_SESSION['list_critical_positions_filters'])) {
            $filters = $_SESSION['list_critical_positions_filters'];
        } else {
            $filters['status'] = 'active';
        }

        $repository = new CriticalPositionsRepository();
        $this->data['items'] = $repository->getAll($filters, $page, $this->limitResult);
        $total = $repository->count($filters);
        $pagination = PaginationService::generatePagination(
            (int) $total,
            (int) $this->limitResult,
            (int) $page,
            'list-critical-positions',
            $filters
        );
        $this->data['pagination'] = $pagination['html'] ?? '';
        $this->data['filters'] = $filters;

        $pageElements = [
            'title_head' => 'Sucessão — Cargos Críticos',
            'menu' => 'list-critical-positions',
            'buttonPermission' => [
                'CreateCriticalPosition',
                'ViewCriticalPosition',
                'UpdateCriticalPosition',
                'ListTalentNominations',
            ],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        (new LoadViewService('adms/Views/performance/list_critical_positions', $this->data))->loadView();
    }
}
