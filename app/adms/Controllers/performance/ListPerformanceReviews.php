<?php

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Models\Repository\PerformanceReviewsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar avaliações de desempenho
 */
class ListPerformanceReviews
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int|null $page = null): void
    {
        // Capturar parâmetros de filtro
        if ($page === null || $page === '') {
            $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        } elseif (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        } else {
            $page = is_numeric($page) ? (int)$page : 1;
        }

        // Verificar se o usuário clicou em "Limpar"
        if (isset($_GET['limpar'])) {
            unset($_SESSION['list_performance_reviews_filters']);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-reviews');
            exit;
        }

        // Salvar filtros na sessão
        $filters = [];
        if (isset($_GET['employee_id']) && !empty($_GET['employee_id'])) {
            $filters['employee_id'] = (int)$_GET['employee_id'];
        }
        if (isset($_GET['review_type']) && !empty($_GET['review_type'])) {
            $filters['review_type'] = $_GET['review_type'];
        }
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $filters['status'] = $_GET['status'];
        }
        if (!empty($_GET['performance_cycle_id'])) {
            $filters['performance_cycle_id'] = (int) $_GET['performance_cycle_id'];
        }
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }

        if (!empty($filters)) {
            $_SESSION['list_performance_reviews_filters'] = $filters;
        } elseif (isset($_SESSION['list_performance_reviews_filters'])) {
            $filters = $_SESSION['list_performance_reviews_filters'];
        }

        // Buscar avaliações
        $repository = new PerformanceReviewsRepository();
        $this->data['reviews'] = $repository->getAll($filters, $page, $this->limitResult);
        $totalRecords = $repository->count($filters);

        // Paginação
        $pagination = PaginationService::generatePagination(
            (int) $totalRecords,
            (int) $this->limitResult,
            (int) $page,
            'list-performance-reviews',
            $filters
        );
        $this->data['pagination'] = $pagination['html'] ?? '';

        // Dados para a view
        $this->data['filters'] = $filters;
        $this->data['page'] = $page;
        $this->data['limitResult'] = $this->limitResult;
        $this->data['totalRecords'] = $totalRecords;

        // Buscar usuários para filtro (apenas gestores e super admin)
        $usersRepo = new \App\adms\Models\Repository\UsersRepository();
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        
        if ($isSuperAdmin) {
            $this->data['employees'] = $usersRepo->getAllUsers(1, 1000);
        } else {
            // Gestor vê apenas sua equipe (subordinados diretos via immediate_supervisor)
            $userId = $_SESSION['user_id'] ?? 0;
            $allUsers = $usersRepo->getAllUsers(1, 1000, []);
            // Filtrar apenas subordinados diretos (pode ser melhorado depois)
            $this->data['employees'] = array_filter($allUsers, function($user) use ($userId) {
                return isset($user['immediate_supervisor']) && $user['immediate_supervisor'] == $userId;
            });
        }

        $this->data['cycles'] = (new PerformanceCyclesRepository())->getAll([], 1, 200);

        // Configurar elementos da página seguindo padrão do projeto
        $pageElements = [
            'title_head' => 'Listar Avaliações de Desempenho',
            'menu' => 'list-performance-reviews',
            'buttonPermission' => [
                'CreatePerformanceReview',
                'ViewPerformanceReview',
                'UpdatePerformanceReview',
                'DeletePerformanceReview',
                'PerformanceDashboard',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/performance/list', $this->data);
        $loadView->loadView();
    }
}

