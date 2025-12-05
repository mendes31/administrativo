<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\EmployeeRequestsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar solicitações pendentes de aprovação do RH
 */
class ListPendingHRApprovals
{
    private array|string|null $data = null;
    private int $limitResult = 20;

    public function index(string|int|null $page = null): void
    {
        if ($page === null || $page === '') {
            $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        } elseif (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        } else {
            $page = is_numeric($page) ? (int)$page : 1;
        }

        // Verificar se é super admin ou RH
        $isSuperAdmin = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
        
        if (!$isSuperAdmin) {
            // TODO: Verificar se tem permissão de RH
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Você não tem permissão para acessar esta página!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'dashboard');
            exit;
        }

        // Filtros
        $filters = [
            'status' => 'pending_hr_approval'
        ];

        // Filtros adicionais
        if (isset($_GET['request_type']) && !empty($_GET['request_type'])) {
            $filters['request_type'] = $_GET['request_type'];
        }
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }

        // Buscar solicitações
        $repository = new EmployeeRequestsRepository();
        $this->data['requests'] = $repository->getAllPendingHRApprovals($filters, $page, $this->limitResult);
        $totalRecords = $repository->countPendingHRApprovals($filters);

        // Paginação
        $pagination = PaginationService::generatePagination(
            (int) $totalRecords,
            (int) $this->limitResult,
            (int) $page,
            'list-pending-hr-approvals',
            $filters
        );
        $this->data['pagination'] = $pagination['html'] ?? '';

        // Dados para a view
        $this->data['filters'] = $filters;
        $this->data['page'] = $page;

        // Buscar tipos de solicitação para filtro
        $requestTypesRepo = new \App\adms\Models\Repository\RequestTypesRepository();
        $this->data['requestTypes'] = $requestTypesRepo->getAllActive();

        // Configurar elementos da página
        $pageElements = [
            'title_head' => 'Solicitações Pendentes - Aprovação do RH',
            'menu' => 'list-pending-hr-approvals',
            'buttonPermission' => [
                'ViewEmployeeRequest',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/portal/list_pending_hr_approvals', $this->data);
        $loadView->loadView();
    }
}

