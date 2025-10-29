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

        $filters = [
            'search' => $_GET['search'] ?? '',
            'stage_id' => $_GET['stage_id'] ?? '',
            'responsible_user_id' => $_GET['responsible_user_id'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];

        // Buscar oportunidades
        $opportunitiesRepo = new CrmOpportunitiesRepository();
        $result = $opportunitiesRepo->getAllOpportunities($filters, $this->page, $this->perPage);

        $this->data['opportunities'] = $result['data'];
        $this->data['pagination'] = $result['pagination'];
        $this->data['filters'] = $filters;

        // Dados para filtros
        $stagesRepo = new CrmPipelineStagesRepository();
        $this->data['stages'] = $stagesRepo->getActiveStages();

        $usersRepo = new UsersRepository();
        $this->data['users'] = $usersRepo->getAllUsersSelect();

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

