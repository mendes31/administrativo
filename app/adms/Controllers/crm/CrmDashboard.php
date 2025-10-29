<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmPartnersRepository;
use App\adms\Models\Repository\CrmOpportunitiesRepository;
use App\adms\Models\Repository\CrmActivitiesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Dashboard CRM com KPIs e gráficos
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmDashboard
{
    private array|string|null $data = null;

    public function index(): void
    {
        // Verificar se é gestor (Super Admin ou permissões específicas)
        $isGestor = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
        $this->data['is_gestor'] = $isGestor;

        // Capturar filtros (apenas para gestores)
        $filters = [];
        if ($isGestor) {
            $filters = [
                'periodo_inicio' => $_GET['periodo_inicio'] ?? '',
                'periodo_fim' => $_GET['periodo_fim'] ?? '',
                'responsible_user_id' => $_GET['responsible_user_id'] ?? '',
                'partner_id' => $_GET['partner_id'] ?? '',
                'filter_stage' => $_GET['filter_stage'] ?? '',
                'filter_segment' => $_GET['filter_segment'] ?? ''
            ];
        } else {
            // Vendedor: sempre filtrar por seu ID
            $filters['responsible_user_id'] = $_SESSION['user_id'];
            $filters['filter_stage'] = $_GET['filter_stage'] ?? '';
            $filters['filter_segment'] = $_GET['filter_segment'] ?? '';
        }

        $this->data['filters'] = $filters;

        $partnersRepo = new CrmPartnersRepository();
        $opportunitiesRepo = new CrmOpportunitiesRepository();
        $activitiesRepo = new CrmActivitiesRepository();

        // KPIs principais (com filtros aplicados)
        $this->data['total_partners'] = $partnersRepo->getTotalPartners($filters);
        $this->data['total_leads'] = $partnersRepo->getTotalLeads($filters);
        $this->data['leads_change'] = $partnersRepo->getLeadsChangePercent();
        
        $this->data['total_opportunities'] = $opportunitiesRepo->getTotalOpenOpportunities($filters);
        $this->data['total_pipeline_value'] = $opportunitiesRepo->getTotalPipelineValue($filters);
        $this->data['conversion_rate'] = $opportunitiesRepo->getConversionRate($filters);
        
        $this->data['total_activities_month'] = $activitiesRepo->getTotalActivitiesThisMonth($filters);
        $this->data['pending_activities'] = $activitiesRepo->getPendingActivities($filters);

        // Dados para gráficos (com filtros)
        $this->data['funnel_data'] = $opportunitiesRepo->getFunnelData($filters);
        $this->data['opportunities_by_month'] = $opportunitiesRepo->getOpportunitiesByMonth($filters);
        $this->data['revenue_by_segment'] = $opportunitiesRepo->getRevenueBySegment($filters);
        $this->data['top_partners'] = $partnersRepo->getTopPartnersByRevenue(5, $filters);
        $this->data['activities_by_type'] = $activitiesRepo->getActivitiesByType($filters);

        // Notificações/Alertas (baseadas no usuário atual)
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            $this->data['overdue_activities'] = $activitiesRepo->getOverdueActivities($userId);
            $this->data['pending_followups'] = $activitiesRepo->getUserTodayActivities($userId);
        }

        // Lista de vendedores (apenas para gestores)
        if ($isGestor) {
            $usersRepo = new \App\adms\Models\Repository\UsersRepository();
            $this->data['users'] = $usersRepo->getAllUsersSelect();
            
            // Lista de parceiros para filtro
            $this->data['partners_list'] = $partnersRepo->getAllPartnersSelect();
        }

        // Layout
        $pageElements = [
            'title_head' => 'Dashboard CRM',
            'menu' => 'crm-dashboard',
            'buttonPermission' => ['CrmDashboard'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/dashboard", $this->data);
        $loadView->loadView();
    }
}

