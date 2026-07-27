<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\EmployeeRequestsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\CrmPermissionService;
use App\adms\Models\Services\EmployeeRequestPermissionService;
use App\adms\Models\Services\EmployeeRequestWorkflowService;
use App\adms\Views\Services\LoadViewService;

/**
 * Fila de aprovações: gestor/delegado (ato) e RH; gestora de área acompanha a árvore.
 */
class PendingApprovals
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

        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $repository = new EmployeeRequestsRepository();
        $usersRepo = new UsersRepository();
        $workflow = new EmployeeRequestWorkflowService();

        $userRole = $this->determineUserRole($userId, $isSuperAdmin, $usersRepo);
        $filters = $this->buildFilters($userRole, $userId, $isSuperAdmin, $workflow);

        if (isset($filters['status']) && $filters['status'] === 'none') {
            $this->data['requests'] = [];
            $totalRecords = 0;
        } else {
            $this->data['requests'] = $repository->getAll($filters, $page, $this->limitResult);
            $totalRecords = $repository->count($filters);
        }

        $pagination = PaginationService::generatePagination(
            (int) $totalRecords,
            (int) $this->limitResult,
            (int) $page,
            'pending-approvals',
            []
        );
        $this->data['pagination'] = $pagination['html'] ?? '';
        $this->data['userRole'] = $userRole;
        $this->data['page'] = $page;
        $this->data['totalRecords'] = $totalRecords;

        $pageTitle = match ($userRole) {
            'manager' => 'Solicitações Pendentes - Aprovação do Gestor',
            'hr' => 'Solicitações Pendentes - Aprovação do RH',
            default => 'Solicitações Pendentes'
        };

        $pageElements = [
            'title_head' => $pageTitle,
            'menu' => 'pending-approvals',
            'buttonPermission' => [
                'ViewEmployeeRequest',
                'ListApprovalDelegations',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/portal/pending_approvals', $this->data);
        $loadView->loadView();
    }

    private function determineUserRole(int $userId, bool $isSuperAdmin, UsersRepository $usersRepo): string
    {
        if ($isSuperAdmin) {
            return 'hr';
        }

        $user = $usersRepo->getUser($userId);
        if (empty($user)) {
            return 'none';
        }

        $subordinates = $usersRepo->getSubordinates($userId);
        $delegationsRepo = new \App\adms\Models\Repository\ApprovalDelegationsRepository();
        $hasDelegation = $delegationsRepo->listDelegatorIdsForDelegate($userId) !== [];

        if (!empty($subordinates) || $hasDelegation) {
            return 'manager';
        }

        // Gestora de área sem subordinados diretos mas com árvore?
        $tree = CrmPermissionService::getAllSubordinates($userId);
        if ($tree !== []) {
            return 'manager';
        }

        if (EmployeeRequestPermissionService::canAccessHrApprovalQueue($userId)) {
            return 'hr';
        }

        return 'none';
    }

    private function buildFilters(
        string $userRole,
        int $userId,
        bool $isSuperAdmin,
        EmployeeRequestWorkflowService $workflow
    ): array {
        $filters = [];

        if ($userRole === 'manager') {
            $mode = $_GET['mode'] ?? 'action';
            if ($mode === 'team') {
                // Acompanhar: árvore completa (diretos + indiretos)
                $teamIds = CrmPermissionService::getAllSubordinates($userId);
                if ($teamIds === []) {
                    return ['status' => 'none'];
                }
                $filters['employee_ids'] = $teamIds;
                $filters['status'] = 'pending_manager_approval';
                $filters['skip_owner_scope'] = true;
            } else {
                // Ação: aprovador corrente = eu ou quem me delegou
                $approverIds = $workflow->approverIdsForActor($userId);
                $filters['current_approver_ids'] = $approverIds;
                $filters['status'] = 'pending_manager_approval';
                $filters['skip_owner_scope'] = true;
            }
        } elseif ($userRole === 'hr') {
            $filters['status'] = 'pending_hr_approval';
            $filters['skip_owner_scope'] = true;
        }

        return $filters;
    }
}
