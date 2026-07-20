<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\EmployeeTicketsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criar chamado do colaborador
 */
class CreateEmployeeTicket
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
        $pageElements = [
            'title_head' => 'Criar Chamado',
            'menu' => 'list-employee-tickets',
            'buttonPermission' => [
                'ListEmployeeTickets',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/portal/create_ticket', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        $employeeId = $_SESSION['user_id'] ?? 0;
        
        if (empty($employeeId)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Usuário não identificado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-employee-tickets');
            exit;
        }

        $data = [
            'employee_id' => $employeeId,
            'ticket_type' => $_POST['ticket_type'] ?? '',
            'priority' => $_POST['priority'] ?? 'medium',
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? null,
            'status' => 'open',
        ];

        // Validações
        if (empty($data['ticket_type'])) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Tipo de chamado é obrigatório!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-employee-ticket');
            exit;
        }

        if (empty($data['title'])) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Título é obrigatório!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-employee-ticket');
            exit;
        }

        if (empty($data['description'])) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Descrição é obrigatória!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-employee-ticket');
            exit;
        }

        $repository = new EmployeeTicketsRepository();
        
        try {
            $id = $repository->create($data);
            
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Chamado criado com sucesso!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-employee-ticket/' . $id);
            exit;
        } catch (\Exception $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao criar chamado: ' . $e->getMessage() . '</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-employee-ticket');
            exit;
        }
    }
}

