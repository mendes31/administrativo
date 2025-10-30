<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmActivitiesRepository;
use App\adms\Models\Repository\CrmOpportunitiesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Dashboard Gerencial do CRM
 * Permite ao gestor acompanhar atividades dos colaboradores
 * 
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmManagerDashboard
{
    private array $data = [];

    public function index(): void
    {
        // Filtros
        $filters = [
            'user_id' => $_GET['user_id'] ?? '',
            'periodo_inicio' => $_GET['periodo_inicio'] ?? date('Y-m-01'), // Primeiro dia do mês
            'periodo_fim' => $_GET['periodo_fim'] ?? date('Y-m-d'), // Hoje
            'status' => $_GET['status'] ?? ''
        ];

        $activitiesRepo = new CrmActivitiesRepository();
        $opportunitiesRepo = new CrmOpportunitiesRepository();
        
        // Buscar todos os usuários com atividades no CRM
        $usersRepo = new UsersRepository();
        $this->data['users'] = $usersRepo->getAllUsersSelect();

        // KPIs por usuário
        $this->data['user_stats'] = [];
        
        // Se filtrou por usuário específico
        if (!empty($filters['user_id'])) {
            $this->data['user_stats'] = $this->getUserStats((int)$filters['user_id'], $filters, $activitiesRepo, $opportunitiesRepo);
        } else {
            // Estatísticas gerais de todos os usuários
            foreach ($this->data['users'] as $user) {
                $this->data['user_stats'][$user['id']] = $this->getUserStats($user['id'], $filters, $activitiesRepo, $opportunitiesRepo);
                $this->data['user_stats'][$user['id']]['name'] = $user['name'];
            }
        }

        $this->data['filters'] = $filters;
        
        // Layout
        $pageElements = [
            'title_head' => 'Dashboard Gerencial - CRM',
            'menu' => 'crm-manager-dashboard',
            'buttonPermission' => ['CrmManagerDashboard'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/manager/dashboard", $this->data);
        $loadView->loadView();
    }

    /**
     * Obter estatísticas de um usuário
     */
    private function getUserStats(int $userId, array $filters, CrmActivitiesRepository $activitiesRepo, CrmOpportunitiesRepository $opportunitiesRepo): array
    {
        $userFilters = array_merge($filters, ['responsible_user_id' => $userId]);
        
        return [
            // Atividades
            'total_activities' => $activitiesRepo->getTotalActivities($userFilters),
            'completed_activities' => $activitiesRepo->getCompletedActivities($userFilters),
            'pending_activities' => $activitiesRepo->getPendingActivities($userFilters),
            'overdue_activities' => $activitiesRepo->getOverdueActivitiesCount($userFilters),
            'activities_by_type' => $activitiesRepo->getActivitiesByType($userFilters),
            'recent_activities' => $activitiesRepo->getRecentActivitiesWithDetails($userFilters, 10), // Últimas 10 atividades
            
            // Oportunidades
            'total_opportunities' => $opportunitiesRepo->getTotalOpenOpportunities($userFilters),
            'total_value' => $opportunitiesRepo->getTotalPipelineValue($userFilters),
            'won_opportunities' => $opportunitiesRepo->getWonOpportunities($userFilters),
            'conversion_rate' => $opportunitiesRepo->getConversionRate($userFilters),
        ];
    }
}

