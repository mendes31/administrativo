<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\EmployeeRequestsRepository;
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
        // Buscar tipos de solicitação ativos para o formulário
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
        $employeeId = $_SESSION['user_id'] ?? 0;
        
        if (empty($employeeId)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Usuário não identificado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-employee-requests');
            exit;
        }

        // Buscar dados do colaborador para verificar se tem gestor
        $usersRepo = new \App\adms\Models\Repository\UsersRepository();
        $employee = $usersRepo->getUser($employeeId);
        
        // Buscar configuração do tipo de solicitação
        $requestTypesRepo = new \App\adms\Models\Repository\RequestTypesRepository();
        $requestTypeCode = $_POST['request_type'] ?? '';
        $requestTypeConfig = $requestTypesRepo->getByCode($requestTypeCode);
        
        // Verificar se precisa de aprovação do gestor:
        // 1. Tipo de solicitação requer gestor (configurado na tabela)
        // 2. Colaborador tem gestor definido
        $requiresManagerApproval = false;
        if ($requestTypeConfig && !empty($requestTypeConfig['requires_manager_approval'])) {
            $requiresManagerApproval = !empty($employee['immediate_supervisor_id']);
        }

        $data = [
            'employee_id' => $employeeId,
            'request_type' => $requestTypeCode,
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? null,
            'start_date' => $_POST['start_date'] ?? null,
            'end_date' => $_POST['end_date'] ?? null,
            'days_requested' => !empty($_POST['days_requested']) ? (int)$_POST['days_requested'] : null,
            'amount' => !empty($_POST['amount']) ? $_POST['amount'] : null,
            'requires_manager_approval' => $requiresManagerApproval,
            'status' => $requiresManagerApproval ? 'pending_manager_approval' : 'pending_hr_approval',
        ];

        // Validações
        if (empty($data['request_type'])) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Tipo de solicitação é obrigatório!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-employee-request');
            exit;
        }

        if (empty($data['title'])) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Título é obrigatório!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-employee-request');
            exit;
        }

        $repository = new EmployeeRequestsRepository();
        
        try {
            $id = $repository->create($data);
            
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Solicitação criada com sucesso!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-employee-request/' . $id);
            exit;
        } catch (\Exception $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao criar solicitação: ' . $e->getMessage() . '</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-employee-request');
            exit;
        }
    }
}

