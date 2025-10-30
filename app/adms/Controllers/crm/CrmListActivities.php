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
        // Usar CrmPermissionService para verificar hierarquia
        $permissionService = new \App\adms\Models\Services\CrmPermissionService();
        $isGestor = $permissionService::isManager();
        $isSuperAdmin = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
        $this->data['is_gestor'] = $isGestor;
        
        // Obter IDs permitidos (usuário + subordinados do departamento comercial)
        $allowedUserIds = $permissionService::getAllowedUserIds();

        // Capturar modo de visualização (list ou calendar)
        $this->data['view_mode'] = $_GET['view'] ?? 'list';

        // Capturar filtros
        $filters = [
            'responsible_user_id' => $_GET['responsible_user_id'] ?? '',
            'type' => $_GET['type'] ?? '',
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
        ];
        
        // APLICAR FILTRO AUTOMÁTICO POR HIERARQUIA
        if (!$isSuperAdmin) {
            // Se filtrou por um usuário específico, validar se tem permissão
            if (!empty($filters['responsible_user_id'])) {
                if (!in_array($filters['responsible_user_id'], $allowedUserIds)) {
                    // Usuário sem permissão - resetar filtro e mostrar alerta
                    $filters['responsible_user_id'] = '';
                    $_SESSION['msg'] = "Você não tem permissão para visualizar este usuário.";
                    $_SESSION['msg_type'] = "warning";
                }
            }
            
            // FILTRO AUTOMÁTICO: Se não filtrou por usuário específico, aplicar filtro por IDs permitidos
            if (empty($filters['responsible_user_id']) && !empty($allowedUserIds)) {
                $filters['allowed_user_ids'] = $allowedUserIds; // Array de IDs permitidos
            }
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

        // Dados para filtros e formulários
        // SEMPRE carregar lista de usuários (filtrada pela hierarquia)
        // Vendedores verão apenas eles mesmos, gerentes verão a equipe
        $this->data['users'] = $permissionService::getCommercialDepartmentUsers();

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

