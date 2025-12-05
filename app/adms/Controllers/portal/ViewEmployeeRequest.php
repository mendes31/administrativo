<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\EmployeeRequestsRepository;
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

        // Verificar permissão
        $isSuperAdmin = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
        $userId = $_SESSION['user_id'] ?? 0;
        $isManager = !$isSuperAdmin && !empty($request['immediate_supervisor_id']) && $request['immediate_supervisor_id'] == $userId;
        
        // Permitir acesso se: super admin, próprio colaborador, ou gestor do colaborador
        if (!$isSuperAdmin && $request['employee_id'] != $userId && !$isManager) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Você não tem permissão para acessar esta solicitação!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-employee-requests');
            exit;
        }

        $this->data['request'] = $request;

        // Verificar se pode editar
        $canEdit = empty($request['manager_approved_by']) && 
                  empty($request['hr_approved_by']) && 
                  !in_array($request['status'], ['rejected', 'cancelled', 'approved']);
        
        $this->data['canEdit'] = $canEdit;

        $pageElements = [
            'title_head' => 'Visualizar Solicitação',
            'menu' => 'view-employee-request',
            'buttonPermission' => [
                'ListEmployeeRequests',
                'UpdateEmployeeRequest',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/portal/view_request', $this->data);
        $loadView->loadView();
    }
}

