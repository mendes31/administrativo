<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\EmployeeRequestsRepository;
use App\adms\Models\Services\EmployeeRequestWorkflowService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criar solicitação do colaborador
 */
class CreateEmployeeRequest
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        } else {
            $this->showForm();
        }
    }

    private function showForm(): void
    {
        $requestTypesRepo = new \App\adms\Models\Repository\RequestTypesRepository();
        $this->data['requestTypes'] = $requestTypesRepo->getAllActive();

        $pageElements = [
            'title_head' => 'Criar Solicitação',
            'menu' => 'list-employee-requests',
            'buttonPermission' => [
                'ListEmployeeRequests',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/portal/create_request', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        $employeeId = (int) ($_SESSION['user_id'] ?? 0);

        if ($employeeId <= 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Usuário não identificado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-employee-requests');
            exit;
        }

        $requestTypeCode = (string) ($_POST['request_type'] ?? '');
        $workflow = new EmployeeRequestWorkflowService();
        $assignment = $workflow->resolveInitialAssignment($employeeId, $requestTypeCode);

        $data = [
            'employee_id' => $employeeId,
            'request_type' => $requestTypeCode,
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? null,
            'start_date' => $_POST['start_date'] ?? null,
            'end_date' => $_POST['end_date'] ?? null,
            'days_requested' => !empty($_POST['days_requested']) ? (int) $_POST['days_requested'] : null,
            'amount' => !empty($_POST['amount']) ? $_POST['amount'] : null,
            'requires_manager_approval' => $assignment['requires_manager_approval'],
            'status' => $assignment['status'],
            'current_stage_code' => $assignment['current_stage_code'],
            'current_approver_user_id' => $assignment['current_approver_user_id'],
            'original_approver_user_id' => $assignment['original_approver_user_id'],
            'stage_started_at' => date('Y-m-d H:i:s'),
            'escalate_after_hours' => $assignment['escalate_after_hours'],
            'max_escalation_levels' => $assignment['max_escalation_levels'] ?? 1,
            'escalation_count' => $assignment['escalation_count'] ?? 0,
            'via_delegation' => $assignment['via_delegation'],
        ];

        if ($data['request_type'] === '') {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Tipo de solicitação é obrigatório!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-employee-request');
            exit;
        }

        if ($data['title'] === '') {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Título é obrigatório!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-employee-request');
            exit;
        }

        $repository = new EmployeeRequestsRepository();
        $id = $repository->create($data);

        if ($id > 0) {
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Solicitação criada com sucesso!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-employee-request/' . $id);
            exit;
        }

        $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao criar solicitação!</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'create-employee-request');
        exit;
    }
}
