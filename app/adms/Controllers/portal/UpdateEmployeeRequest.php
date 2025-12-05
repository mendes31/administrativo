<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\EmployeeRequestsRepository;
use App\adms\Models\Repository\RequestTypesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para atualizar solicitação do colaborador
 */
class UpdateEmployeeRequest
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        if (!(int)$id) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Solicitação não encontrada!</div>';
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
        
        if (!$isSuperAdmin && $request['employee_id'] != $userId) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Você não tem permissão para editar esta solicitação!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-employee-requests');
            exit;
        }

        // Verificar se pode editar (sem aprovações)
        $canEdit = $this->canEditRequest($request);
        if (!$canEdit) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Esta solicitação não pode ser editada pois já possui aprovações. Cancele-a e crie uma nova.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-employee-request/' . $id);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int)$id, $request);
        } else {
            $this->showForm((int)$id, $request);
        }
    }

    private function canEditRequest(array $request): bool
    {
        // Não pode editar se já foi aprovada pelo gestor ou RH
        if (!empty($request['manager_approved_by']) || !empty($request['hr_approved_by'])) {
            return false;
        }

        // Não pode editar se foi rejeitada ou cancelada
        if (in_array($request['status'], ['rejected', 'cancelled', 'approved'])) {
            return false;
        }

        return true;
    }

    private function showForm(int $id, array $request): void
    {
        // Buscar tipos de solicitação ativos
        $requestTypesRepo = new RequestTypesRepository();
        $this->data['requestTypes'] = $requestTypesRepo->getAllActive();
        $this->data['request'] = $request;

        $pageElements = [
            'title_head' => 'Editar Solicitação',
            'menu' => 'update-employee-request',
            'buttonPermission' => [
                'ListEmployeeRequests',
                'ViewEmployeeRequest',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/portal/update_request', $this->data);
        $loadView->loadView();
    }

    private function update(int $id, array $request): void
    {
        // Validar CSRF
        if (!CSRFHelper::validateCSRFToken('form_update_employee_request', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-employee-request/' . $id);
            exit;
        }

        // Verificar novamente se pode editar
        if (!$this->canEditRequest($request)) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Esta solicitação não pode ser editada pois já possui aprovações. Cancele-a e crie uma nova.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-employee-request/' . $id);
            exit;
        }

        // Buscar configuração do tipo de solicitação
        $requestTypesRepo = new RequestTypesRepository();
        $requestTypeCode = $_POST['request_type'] ?? $request['request_type'];
        $requestTypeConfig = $requestTypesRepo->getByCode($requestTypeCode);

        // Buscar dados do colaborador para verificar se tem gestor
        $usersRepo = new \App\adms\Models\Repository\UsersRepository();
        $employee = $usersRepo->getUser($request['employee_id']);

        // Verificar se precisa de aprovação do gestor
        $requiresManagerApproval = false;
        if ($requestTypeConfig && !empty($requestTypeConfig['requires_manager_approval'])) {
            $requiresManagerApproval = !empty($employee['immediate_supervisor_id']);
        }

        $data = [
            'request_type' => $requestTypeCode,
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'days_requested' => !empty($_POST['days_requested']) ? (int)$_POST['days_requested'] : null,
            'amount' => !empty($_POST['amount']) ? $_POST['amount'] : null,
            'requires_manager_approval' => $requiresManagerApproval,
            'status' => $requiresManagerApproval ? 'pending_manager_approval' : 'pending_hr_approval',
        ];

        // Validações
        if (empty($data['title'])) {
            $_SESSION['error'] = 'Título é obrigatório.';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-employee-request/' . $id);
            exit;
        }

        // Se mudou o tipo e agora requer aprovação do gestor, resetar aprovações anteriores
        if ($requiresManagerApproval && empty($request['manager_approved_by'])) {
            $data['manager_approved_by'] = null;
            $data['manager_approved_at'] = null;
            $data['manager_rejection_reason'] = null;
        }

        $repository = new EmployeeRequestsRepository();
        
        if ($repository->update($id, $data)) {
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Solicitação atualizada com sucesso!</div>';
            GenerateLog::generateLog("info", "Solicitação atualizada.", ['id' => $id, 'data' => $data]);
            header('Location: ' . $_ENV['URL_ADM'] . 'view-employee-request/' . $id);
            exit;
        } else {
            $_SESSION['error'] = 'Erro ao atualizar solicitação. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-employee-request/' . $id);
            exit;
        }
    }
}

