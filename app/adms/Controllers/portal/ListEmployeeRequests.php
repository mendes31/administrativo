<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\EmployeeRequestsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar solicitações do colaborador
 */
class ListEmployeeRequests
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

        // Limpar filtros
        if (isset($_GET['limpar'])) {
            unset($_SESSION['list_employee_requests_filters']);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-employee-requests');
            exit;
        }

        // Filtros
        $filters = [];
        if (isset($_GET['request_type']) && !empty($_GET['request_type'])) {
            $filters['request_type'] = $_GET['request_type'];
        }
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $filters['status'] = $_GET['status'];
        }
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }

        // Colaborador vê apenas suas solicitações
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        if (!$isSuperAdmin) {
            $filters['employee_id'] = $_SESSION['user_id'] ?? 0;
        } elseif (isset($_GET['employee_id']) && !empty($_GET['employee_id'])) {
            $filters['employee_id'] = (int)$_GET['employee_id'];
        }

        if (!empty($filters)) {
            $_SESSION['list_employee_requests_filters'] = $filters;
        } elseif (isset($_SESSION['list_employee_requests_filters'])) {
            $filters = $_SESSION['list_employee_requests_filters'];
        }

        // Buscar solicitações
        $repository = new EmployeeRequestsRepository();
        $this->data['requests'] = $repository->getAll($filters, $page, $this->limitResult);
        $totalRecords = $repository->count($filters);

        // Paginação
        $pagination = PaginationService::generatePagination(
            (int) $totalRecords,
            (int) $this->limitResult,
            (int) $page,
            'list-employee-requests',
            $filters
        );
        $this->data['pagination'] = $pagination['html'] ?? '';

        // Dados para a view
        $this->data['filters'] = $filters;
        $this->data['page'] = $page;

        // Configurar elementos da página
        $pageElements = [
            'title_head' => 'Minhas Solicitações',
            'menu' => 'list-employee-requests',
            'buttonPermission' => [
                'CreateEmployeeRequest',
                'ViewEmployeeRequest',
                'UpdateEmployeeRequest',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/portal/list_requests', $this->data);
        $loadView->loadView();
    }
}

