<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\EmployeeRequestsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar solicitações pendentes de aprovação do gestor
 */
class ListPendingManagerApprovals
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

        // Filtros
        $filters = [
            'status' => 'pending_manager_approval'
        ];

        // Se não for super admin, filtrar apenas solicitações dos seus subordinados
        $isSuperAdmin = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
        $userId = $_SESSION['user_id'] ?? 0;

        if (!$isSuperAdmin) {
            // Buscar IDs dos subordinados diretos
            $usersRepo = new \App\adms\Models\Repository\UsersRepository();
            $subordinates = $usersRepo->getSubordinates($userId);
            $subordinateIds = array_column($subordinates, 'id');
            
            if (empty($subordinateIds)) {
                $this->data['requests'] = [];
                $totalRecords = 0;
            } else {
                $filters['employee_ids'] = $subordinateIds;
            }
        }

        // Buscar solicitações
        $repository = new EmployeeRequestsRepository();
        $this->data['requests'] = $repository->getAllPendingManagerApprovals($filters, $page, $this->limitResult);
        $totalRecords = $repository->countPendingManagerApprovals($filters);

        // Paginação
        $pagination = PaginationService::generatePagination(
            (int) $totalRecords,
            (int) $this->limitResult,
            (int) $page,
            'list-pending-manager-approvals',
            $filters
        );
        $this->data['pagination'] = $pagination['html'] ?? '';

        // Dados para a view
        $this->data['filters'] = $filters;
        $this->data['page'] = $page;
        $this->data['isSuperAdmin'] = $isSuperAdmin;

        // Configurar elementos da página
        $pageElements = [
            'title_head' => 'Solicitações Pendentes - Aprovação do Gestor',
            'menu' => 'list-pending-manager-approvals',
            'buttonPermission' => [
                'ViewEmployeeRequest',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/portal/list_pending_manager_approvals', $this->data);
        $loadView->loadView();
    }
}

