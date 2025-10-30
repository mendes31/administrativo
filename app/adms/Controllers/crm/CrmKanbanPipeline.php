<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmOpportunitiesRepository;
use App\adms\Models\Repository\CrmPipelineStagesRepository;
use App\adms\Models\Repository\CrmPartnersRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para exibir o Pipeline Kanban do CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmKanbanPipeline
{
    private array|string|null $data = null;

    public function index(): void
    {
        // Usar CrmPermissionService para verificar hierarquia
        $permissionService = new \App\adms\Models\Services\CrmPermissionService();
        $isManager = $permissionService::isManager();
        $isSuperAdmin = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
        
        // Obter IDs permitidos (usuário + subordinados do departamento comercial)
        $allowedUserIds = $permissionService::getAllowedUserIds();
        
        // Filtros
        $filters = [
            'responsible_user_id' => $_GET['responsible_user_id'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        // APLICAR FILTRO AUTOMÁTICO POR HIERARQUIA
        // Se não é Super Admin, filtrar automaticamente pelos IDs permitidos
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

        // Repositories
        $stagesRepo = new CrmPipelineStagesRepository();
        $opportunitiesRepo = new CrmOpportunitiesRepository();
        $usersRepo = new UsersRepository();

        // Buscar TODAS as etapas ativas (incluindo finais: Ganho e Perdido)
        $this->data['stages'] = $stagesRepo->getActiveStages();

        // Buscar oportunidades por etapa
        foreach ($this->data['stages'] as &$stage) {
            $stage['opportunities'] = $opportunitiesRepo->getOpportunitiesByStage($stage['id'], $filters);
            $stage['total_value'] = $opportunitiesRepo->getTotalValueByStage($stage['id'], $filters);
            $stage['count'] = count($stage['opportunities']);
        }

        // Valor total do pipeline
        $this->data['total_pipeline_value'] = $opportunitiesRepo->getTotalPipelineValue($filters);

        // Filtrar apenas usuários do departamento comercial (respeitando hierarquia)
        $permissionService = new \App\adms\Models\Services\CrmPermissionService();
        $this->data['users'] = $permissionService::getCommercialDepartmentUsers();

        // Filtros para a view
        $this->data['filters'] = $filters;

        // Layout da página
        $pageElements = [
            'title_head' => 'Pipeline de Vendas - CRM',
            'menu' => 'crm-kanban-pipeline',
            'buttonPermission' => ['CrmCreateOpportunity', 'CrmMoveOpportunity'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar VIEW
        $loadView = new LoadViewService("adms/Views/crm/opportunities/kanban", $this->data);
        $loadView->loadView();
    }
}

