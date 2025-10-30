<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmActivitiesRepository;
use App\adms\Models\Repository\CrmPartnersRepository;
use App\adms\Models\Repository\CrmOpportunitiesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar/agendar Atividades CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmListActivities
{
    private array $data = [];

    public function index(): void
    {
        // Verificar se é gestor
        $isGestor = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
        $this->data['is_gestor'] = $isGestor;

        // Capturar modo de visualização (list ou calendar)
        $this->data['view_mode'] = $_GET['view'] ?? 'list';

        // Capturar filtros
        $filters = [];
        
        if ($isGestor) {
            $filters = [
                'responsible_user_id' => $_GET['responsible_user_id'] ?? '',
                'type' => $_GET['type'] ?? '',
                'status' => $_GET['status'] ?? '',
                'priority' => $_GET['priority'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? '',
            ];
        } else {
            // Vendedor: sempre filtrar por seu ID
            $filters['responsible_user_id'] = $_SESSION['user_id'];
            $filters['type'] = $_GET['type'] ?? '';
            $filters['status'] = $_GET['status'] ?? '';
            $filters['priority'] = $_GET['priority'] ?? '';
            $filters['date_from'] = $_GET['date_from'] ?? '';
            $filters['date_to'] = $_GET['date_to'] ?? '';
        }

        $this->data['filters'] = $filters;

        // Buscar atividades
        $activitiesRepo = new CrmActivitiesRepository();
        
        if ($this->data['view_mode'] === 'calendar-month') {
            // Para calendário mensal, buscar do mês
            $month = $_GET['month'] ?? date('Y-m');
            $this->data['selected_month'] = $month;
            $this->data['activities'] = $activitiesRepo->getActivitiesByMonth($month, $filters);
        } elseif ($this->data['view_mode'] === 'calendar-week') {
            // Para calendário semanal, buscar da semana
            $date = $_GET['date'] ?? date('Y-m-d');
            $this->data['selected_date'] = $date;
            $this->data['activities'] = $activitiesRepo->getActivitiesByWeek($date, $filters);
        } else {
            // Para lista, buscar todas com filtro
            $this->data['activities'] = $activitiesRepo->getAllActivities($filters);
        }

        // Dados para filtros
        if ($isGestor) {
            $usersRepo = new UsersRepository();
            $this->data['users'] = $usersRepo->getAllUsersSelect();
        }

        $this->data['activity_types'] = ['Ligação', 'Reunião', 'E-mail', 'Tarefa'];
        $this->data['statuses'] = ['Pendente', 'Concluída', 'Cancelada'];
        $this->data['priorities'] = ['Baixa', 'Média', 'Alta', 'Urgente'];
        
        // Dados para modais de criação/edição
        $partnersRepo = new CrmPartnersRepository();
        $opportunitiesRepo = new CrmOpportunitiesRepository();
        $this->data['partners'] = $partnersRepo->getAllPartnersSelect();
        $this->data['opportunities'] = $opportunitiesRepo->getAllOpportunitiesSelect();

        // Layout
        $pageElements = [
            'title_head' => 'Agenda de Atividades - CRM',
            'menu' => 'crm-list-activities',
            'buttonPermission' => ['CrmListActivities', 'CrmCreateActivity'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/activities/list", $this->data);
        $loadView->loadView();
    }
}

