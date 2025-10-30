<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmOpportunitiesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\CrmPipelineStagesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar Oportunidades CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmListOpportunities
{
    private array $data = [];
    private int $page = 1;
    private int $perPage = 20;

    public function index(): void
    {
        // Capturar parâmetros de filtros e paginação
        $this->page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $this->perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

        // Usar CrmPermissionService para verificar hierarquia
        $permissionService = new \App\adms\Models\Services\CrmPermissionService();
        $isManager = $permissionService::isManager();
        $isSuperAdmin = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
        
        // Obter IDs permitidos (usuário + subordinados do departamento comercial)
        $allowedUserIds = $permissionService::getAllowedUserIds();

        $filters = [
            'search' => $_GET['search'] ?? '',
            'stage_id' => $_GET['stage_id'] ?? '',
            'responsible_user_id' => $_GET['responsible_user_id'] ?? '',
            'status' => $_GET['status'] ?? '',
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

        // Buscar oportunidades
        $opportunitiesRepo = new CrmOpportunitiesRepository();
        $result = $opportunitiesRepo->getAllOpportunities($filters, $this->page, $this->perPage);

        $this->data['opportunities'] = $result['data'];
        $this->data['pagination'] = $result['pagination'];
        $this->data['filters'] = $filters;

        // Dados para filtros
        $stagesRepo = new CrmPipelineStagesRepository();
        $this->data['stages'] = $stagesRepo->getActiveStages();

        // Filtrar apenas usuários do departamento comercial (respeitando hierarquia)
        $permissionService = new \App\adms\Models\Services\CrmPermissionService();
        $this->data['users'] = $permissionService::getCommercialDepartmentUsers();

        // Layout
        $pageElements = [
            'title_head' => 'Oportunidades - CRM',
            'menu' => 'crm-list-opportunities',
            'buttonPermission' => ['CrmListOpportunities', 'CrmCreateOpportunity'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/opportunities/list", $this->data);
        $loadView->loadView();
    }
}

