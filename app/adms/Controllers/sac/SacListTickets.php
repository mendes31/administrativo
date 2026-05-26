<?php

declare(strict_types=1);

namespace App\adms\Controllers\sac;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SacTicketsRepository;
use App\adms\Models\Repository\SacCategoriesRepository;
use App\adms\Models\Repository\SacClientsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar chamados do SAC
 *
 * @package App\adms\Controllers\sac
 * @author Rafael Mendes
 */
class SacListTickets
{
    private array $data = [];
    private int $page = 1;
    private int $perPage = 20;

    public function index(): void
    {
        $this->page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $this->perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'client_id' => $_GET['client_id'] ?? '',
            'assigned_user_id' => $_GET['assigned_user_id'] ?? '',
            'department_id' => $_GET['department_id'] ?? '',
            'channel' => $_GET['channel'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
        ];

        $ticketsRepo = new SacTicketsRepository();
        $tickets = $ticketsRepo->getAllTickets($this->page, $this->perPage, $filters);
        $totalTickets = $ticketsRepo->getTotalTickets($filters);

        $this->data['tickets'] = $tickets;
        $this->data['pagination'] = [
            'total' => $totalTickets,
            'per_page' => $this->perPage,
            'current_page' => $this->page,
            'last_page' => (int)ceil($totalTickets / $this->perPage),
        ];
        $this->data['filters'] = $filters;

        // Dados para dropdowns de filtro
        $categoriesRepo = new SacCategoriesRepository();
        $this->data['categories'] = $categoriesRepo->getActiveCategories();

        $clientsRepo = new SacClientsRepository();
        $this->data['clients'] = $clientsRepo->getActiveClients();

        $usersRepo = new UsersRepository();
        $this->data['users'] = $usersRepo->getAllUsersSelect();

        $departmentsRepo = new DepartmentsRepository();
        $this->data['departments'] = $departmentsRepo->getAllDepartmentsSelect();

        $this->data['statuses'] = ['Aberto', 'Em análise', 'Em atendimento', 'Aguardando cliente', 'Resolvido', 'Encerrado'];
        $this->data['priorities'] = ['Baixa', 'Média', 'Alta', 'Urgente'];
        $this->data['channels'] = ['WhatsApp', 'E-mail', 'Telefone', 'Portal'];

        // Layout
        $pageElements = [
            'title_head' => 'Chamados - SAC',
            'menu' => 'sac-list-tickets',
            'buttonPermission' => ['SacCreateTicket', 'SacViewTicket', 'SacUpdateTicket', 'SacDeleteTicket'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/sac/tickets/list", $this->data);
        $loadView->loadView();
    }
}
