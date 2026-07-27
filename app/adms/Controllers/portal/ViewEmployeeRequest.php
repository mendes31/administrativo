<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\EmployeeRequestsRepository;
use App\adms\Models\Services\EmployeeRequestPermissionService;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar solicitação do colaborador
 */
class ViewEmployeeRequest
{
    private array|string|null $data = null;

    public function index(?string $id = null): void
    {
        if (empty($id)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: ID da solicitação não informado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-employee-requests');
            exit;
        }

        $repository = new EmployeeRequestsRepository();
        $request = $repository->getById((int)$id);

        if (!$request) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Solicitação não encontrada!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-employee-requests');
            exit;
        }

        // Verificar permissão: dono, Super Admin, aprovador/delegado, ou árvore (acompanhar)
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $workflow = new \App\adms\Models\Services\EmployeeRequestWorkflowService();
        $canAct = $workflow->canActAsManager($request, $userId);
        $canViewTeam = $workflow->canViewInTeamTree($request, $userId);
        $canViewAsHr = EmployeeRequestPermissionService::canViewAsHrApprover($userId);

        if (!$isSuperAdmin
            && (int) $request['employee_id'] !== $userId
            && !$canAct
            && !$canViewTeam
            && !$canViewAsHr
        ) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Você não tem permissão para acessar esta solicitação!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-employee-requests');
            exit;
        }

        $this->data['request'] = $request;
        $this->data['can_approve_as_manager'] = $canAct && $request['status'] === 'pending_manager_approval';
        $this->data['can_approve_as_hr'] = EmployeeRequestPermissionService::canApproveAsHr($userId)
            && $request['status'] === 'pending_hr_approval';
        $this->data['approval_events'] = $repository->listApprovalEvents((int) $id);

        // Verificar se pode editar
        $canEdit = empty($request['manager_approved_by']) && 
                  empty($request['hr_approved_by']) && 
                  !in_array($request['status'], ['rejected', 'cancelled', 'approved']);
        
        $this->data['canEdit'] = $canEdit;

        $reqId = (int) $id;
        $returnUrl = $_ENV['URL_ADM'] . 'view-employee-request/' . $reqId;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_employee_requests', $reqId, $returnUrl);

        $pageElements = [
            'title_head' => 'Visualizar Solicitação',
            'menu' => 'list-employee-requests',
            'buttonPermission' => [
                'ListEmployeeRequests',
                'UpdateEmployeeRequest',
                'ApproveEmployeeRequestHR',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/portal/view_request', $this->data);
        $loadView->loadView();
    }
}

