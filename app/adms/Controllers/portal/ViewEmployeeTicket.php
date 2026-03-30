<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\EmployeeTicketsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar chamado do colaborador
 */
class ViewEmployeeTicket
{
    private array|string|null $data = null;

    public function index(string|int $id): void
    {
        $repository = new EmployeeTicketsRepository();
        $ticket = $repository->getById((int)$id);

        if (!$ticket) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Chamado não encontrado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-employee-tickets');
            exit;
        }

        // Verificar se o chamado pertence ao usuário logado
        $employeeId = $_SESSION['user_id'] ?? 0;
        if ($ticket['employee_id'] != $employeeId && !\App\adms\Helpers\UserAccessHelper::hasFullSystemAccess()) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Você não tem permissão para visualizar este chamado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-employee-tickets');
            exit;
        }

        // Buscar histórico
        $history = $repository->getHistory((int)$id);

        $this->data['ticket'] = $ticket;
        $this->data['history'] = $history;

        $pageElements = [
            'title_head' => 'Visualizar Chamado',
            'menu' => 'view-employee-ticket',
            'buttonPermission' => [
                'ListEmployeeTickets',
            ],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/portal/view_ticket', $this->data);
        $loadView->loadView();
    }
}

