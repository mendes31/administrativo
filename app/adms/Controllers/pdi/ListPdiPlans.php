<?php

declare(strict_types=1);

namespace App\adms\Controllers\pdi;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Models\Repository\PdiPlansRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class ListPdiPlans
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
            unset($_SESSION['list_pdi_plans_filters']);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-pdi-plans');
            exit;
        }

        $filters = [];
        if (!empty($_GET['user_id'])) {
            $filters['user_id'] = (int) $_GET['user_id'];
        }
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $filters['status'] = $_GET['status'];
        }
        if (!empty($_GET['performance_cycle_id'])) {
            $filters['performance_cycle_id'] = (int) $_GET['performance_cycle_id'];
        }
        if (!empty($_GET['search'])) {
            $filters['search'] = trim((string) $_GET['search']);
        }

        if (!empty($filters)) {
            $_SESSION['list_pdi_plans_filters'] = $filters;
        } elseif (isset($_SESSION['list_pdi_plans_filters'])) {
            $filters = $_SESSION['list_pdi_plans_filters'];
        }

        $repository = new PdiPlansRepository();
        $this->data['plans'] = $repository->getAll($filters, $page, $this->limitResult);
        $totalRecords = $repository->count($filters);

        $pagination = PaginationService::generatePagination(
            (int) $totalRecords,
            (int) $this->limitResult,
            (int) $page,
            'list-pdi-plans',
            $filters
        );
        $this->data['pagination'] = $pagination['html'] ?? '';
        $this->data['filters'] = $filters;
        $this->data['page'] = $page;
        $this->data['totalRecords'] = $totalRecords;

        $usersRepo = new UsersRepository();
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        if ($isSuperAdmin) {
            $this->data['employees'] = $usersRepo->getAllUsers(1, 1000);
        } else {
            $userId = $_SESSION['user_id'] ?? 0;
            $allUsers = $usersRepo->getAllUsers(1, 1000, []);
            $this->data['employees'] = array_filter($allUsers, static function ($user) use ($userId) {
                return isset($user['immediate_supervisor']) && (int) $user['immediate_supervisor'] === (int) $userId;
            });
        }

        $this->data['cycles'] = (new PerformanceCyclesRepository())->getAll([], 1, 200);

        $pageElements = [
            'title_head' => 'Planos de Desenvolvimento (PDI)',
            'menu' => 'list-pdi-plans',
            'buttonPermission' => [
                'CreatePdiPlan',
                'ViewPdiPlan',
                'UpdatePdiPlan',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/pdi/list', $this->data);
        $loadView->loadView();
    }
}
