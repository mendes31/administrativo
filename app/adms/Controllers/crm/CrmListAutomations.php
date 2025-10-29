<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmAutomationsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Listar Automações do CRM
 * 
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmListAutomations
{
    private array $data = [];

    public function index(): void
    {
        $repo = new CrmAutomationsRepository();
        
        // Filtros
        $filters = [
            'entity_type' => $_GET['entity_type'] ?? '',
            'is_active' => isset($_GET['is_active']) ? (int)$_GET['is_active'] : null
        ];
        
        $this->data['automations'] = $repo->getAllAutomations($filters);
        $this->data['filters'] = $filters;
        
        // Layout
        $pageElements = [
            'title_head' => 'Automações - CRM',
            'menu' => 'crm-list-automations',
            'buttonPermission' => ['CrmListAutomations', 'CrmCreateAutomation'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/automations/list", $this->data);
        $loadView->loadView();
    }
}

