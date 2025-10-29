<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmCustomFieldsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Listar Campos Customizáveis do CRM
 * 
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmListCustomFields
{
    private array $data = [];

    public function index(): void
    {
        $repo = new CrmCustomFieldsRepository();
        
        // Filtros
        $entityType = $_GET['entity_type'] ?? '';
        
        // Buscar campos
        $this->data['fields'] = $repo->getAllFields(['entity_type' => $entityType]);
        $this->data['entity_type_filter'] = $entityType;
        
        // Layout
        $pageElements = [
            'title_head' => 'Campos Customizáveis - CRM',
            'menu' => 'crm-list-custom-fields',
            'buttonPermission' => ['CrmListCustomFields', 'CrmCreateCustomField'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/custom_fields/list", $this->data);
        $loadView->loadView();
    }
}

