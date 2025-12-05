<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\EmployeeRequestsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar solicitações pendentes de aprovação
 * Mostra diferentes listagens baseado no perfil do usuário:
 * - Gestor: solicitações de seus subordinados aguardando aprovação do gestor
 * - RH/Super Admin: todas as solicitações aguardando aprovação do RH
 */
class PendingApprovals
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

        $isSuperAdmin = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
        $userId = $_SESSION['user_id'] ?? 0;
        
        $repository = new EmployeeRequestsRepository();
        $usersRepo = new UsersRepository();
        
        // Determinar qual tipo de aprovação o usuário pode fazer
        $userRole = $this->determineUserRole($userId, $isSuperAdmin, $usersRepo);
        
        // Buscar solicitações pendentes baseado no perfil
        $filters = $this->buildFilters($userRole, $userId, $isSuperAdmin);
        
        // Se o filtro retornar status 'none', não há solicitações
        if (isset($filters['status']) && $filters['status'] === 'none') {
            $this->data['requests'] = [];
            $totalRecords = 0;
        } else {
            $this->data['requests'] = $repository->getAll($filters, $page, $this->limitResult);
            $totalRecords = $repository->count($filters);
        }
        
        // Paginação
        $pagination = PaginationService::generatePagination(
            (int) $totalRecords,
            (int) $this->limitResult,
            (int) $page,
            'pending-approvals',
            []
        );
        $this->data['pagination'] = $pagination['html'] ?? '';
        
        // Dados para a view
        $this->data['userRole'] = $userRole;
        $this->data['page'] = $page;
        $this->data['totalRecords'] = $totalRecords;
        
        // Configurar elementos da página
        $pageTitle = match($userRole) {
            'manager' => 'Solicitações Pendentes - Aprovação do Gestor',
            'hr' => 'Solicitações Pendentes - Aprovação do RH',
            default => 'Solicitações Pendentes'
        };
        
        $pageElements = [
            'title_head' => $pageTitle,
            'menu' => 'pending-approvals',
            'buttonPermission' => [
                'ViewEmployeeRequest',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/portal/pending_approvals', $this->data);
        $loadView->loadView();
    }

    /**
     * Determinar o papel do usuário (gestor, RH, ou ambos)
     */
    private function determineUserRole(int $userId, bool $isSuperAdmin, UsersRepository $usersRepo): string
    {
        if ($isSuperAdmin) {
            // Super Admin pode aprovar como RH
            return 'hr';
        }
        
        // Verificar se o usuário é gestor de alguém
        $user = $usersRepo->getUser($userId);
        if (empty($user)) {
            return 'none';
        }
        
        // Buscar se há subordinados
        $subordinates = $usersRepo->getSubordinates($userId);
        
        if (!empty($subordinates)) {
            // É gestor, mas também pode ser RH se tiver permissão
            // Por enquanto, retornamos 'manager' se for gestor
            return 'manager';
        }
        
        return 'none';
    }

    /**
     * Construir filtros baseado no papel do usuário
     */
    private function buildFilters(string $userRole, int $userId, bool $isSuperAdmin): array
    {
        $filters = [];
        
        if ($userRole === 'manager') {
            // Gestor: apenas solicitações de seus subordinados aguardando aprovação do gestor
            $usersRepo = new UsersRepository();
            $subordinates = $usersRepo->getSubordinates($userId);
            
            if (empty($subordinates)) {
                return ['status' => 'none']; // Retornar vazio
            }
            
            $subordinateIds = array_column($subordinates, 'id');
            $filters['employee_ids'] = $subordinateIds;
            $filters['status'] = 'pending_manager_approval';
            
        } elseif ($userRole === 'hr' || $isSuperAdmin) {
            // RH/Super Admin: todas as solicitações aguardando aprovação do RH
            $filters['status'] = 'pending_hr_approval';
            // Não filtrar por employee_id para ver todas
        }
        
        return $filters;
    }
}

