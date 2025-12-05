<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\EmployeeTicketsRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\ScreenResolutionHelper;

/**
 * Controller para listar chamados do colaborador
 */
class ListEmployeeTickets
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

        $resolution = ScreenResolutionHelper::getScreenResolution();
        $responsiveClasses = ScreenResolutionHelper::getResponsiveClasses($resolution['category']);
        $paginationSettings = ScreenResolutionHelper::getPaginationSettings($resolution['category']);

        if (isset($_GET['limpar'])) {
            unset($_SESSION['list_employee_tickets_filters']);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-employee-tickets');
            exit;
        }

        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $paginationSettings['options'])) {
            $this->limitResult = (int)$_GET['per_page'];
        } else {
            $this->limitResult = $paginationSettings['per_page'];
        }

        $employeeId = $_SESSION['user_id'] ?? 0;
        
        $filters = ['employee_id' => $employeeId];
        if (isset($_GET['ticket_type']) && !empty($_GET['ticket_type'])) {
            $filters['ticket_type'] = $_GET['ticket_type'];
        }
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $filters['status'] = $_GET['status'];
        }
        if (isset($_GET['priority']) && !empty($_GET['priority'])) {
            $filters['priority'] = $_GET['priority'];
        }

        if (!empty($filters)) {
            $_SESSION['list_employee_tickets_filters'] = $filters;
        } elseif (isset($_SESSION['list_employee_tickets_filters'])) {
            $filters = $_SESSION['list_employee_tickets_filters'];
        }

        $repository = new EmployeeTicketsRepository();
        $this->data['tickets'] = $repository->getByEmployeeId($employeeId, $filters, $page, $this->limitResult);
        $totalTickets = $repository->getTotalByEmployeeId($employeeId, $filters);

        $pagination = PaginationService::generatePagination(
            (int) $totalTickets,
            (int) $this->limitResult,
            (int) $page,
            'list-employee-tickets',
            array_merge($filters, ['per_page' => $this->limitResult])
        );
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['filters'] = $filters;

        $pageElements = [
            'title_head' => 'Meus Chamados',
            'menu' => 'list-employee-tickets',
            'buttonPermission' => [
                'CreateEmployeeTicket',
                'ViewEmployeeTicket',
            ],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $this->data['responsiveClasses'] = $responsiveClasses;
        $this->data['paginationSettings'] = $paginationSettings;
        $this->data['screenResolution'] = $resolution;

        $loadView = new LoadViewService('adms/Views/portal/list_tickets', $this->data);
        $loadView->loadView();
    }
}

