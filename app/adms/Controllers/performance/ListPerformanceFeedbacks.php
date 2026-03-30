<?php

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\PerformanceFeedbacksRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar feedbacks de desempenho
 */
class ListPerformanceFeedbacks
{
    private array|string|null $data = null;
    private int $limitResult = 20;

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
            unset($_SESSION['list_performance_feedbacks_filters']);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-feedbacks');
            exit;
        }

        // Salvar filtros na sessão
        $filters = [];
        if (isset($_GET['employee_id']) && !empty($_GET['employee_id'])) {
            $filters['employee_id'] = (int)$_GET['employee_id'];
        }
        if (isset($_GET['given_by']) && !empty($_GET['given_by'])) {
            $filters['given_by'] = (int)$_GET['given_by'];
        }
        if (isset($_GET['feedback_type']) && $_GET['feedback_type'] !== '') {
            $filters['feedback_type'] = $_GET['feedback_type'];
        }
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }

        if (!empty($filters)) {
            $_SESSION['list_performance_feedbacks_filters'] = $filters;
        } elseif (isset($_SESSION['list_performance_feedbacks_filters'])) {
            $filters = $_SESSION['list_performance_feedbacks_filters'];
        }

        // Buscar feedbacks
        $repository = new PerformanceFeedbacksRepository();
        $this->data['feedbacks'] = $repository->getAll($filters, $page, $this->limitResult);
        $totalRecords = $repository->count($filters);

        // Paginação
        $pagination = PaginationService::generatePagination(
            (int) $totalRecords,
            (int) $this->limitResult,
            (int) $page,
            'list-performance-feedbacks',
            $filters
        );
        $this->data['pagination'] = $pagination['html'] ?? '';

        // Dados para a view
        $this->data['filters'] = $filters;
        $this->data['page'] = $page;
        $this->data['totalRecords'] = $totalRecords;

        // Buscar usuários para filtro
        $usersRepo = new UsersRepository();
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        
        if ($isSuperAdmin) {
            $this->data['employees'] = $usersRepo->getAllUsers(1, 1000);
        } else {
            $userId = $_SESSION['user_id'] ?? 0;
            $allUsers = $usersRepo->getAllUsers(1, 1000, []);
            $this->data['employees'] = array_filter($allUsers, function($user) use ($userId) {
                return isset($user['immediate_supervisor']) && $user['immediate_supervisor'] == $userId;
            });
        }

        // Configurar elementos da página
        $pageElements = [
            'title_head' => 'Listar Feedbacks de Desempenho',
            'menu' => 'list-performance-feedbacks',
            'buttonPermission' => [
                'CreatePerformanceFeedback',
                'ViewPerformanceFeedback',
                'UpdatePerformanceFeedback',
                'DeletePerformanceFeedback',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/performance/list_feedbacks', $this->data);
        $loadView->loadView();
    }
}

