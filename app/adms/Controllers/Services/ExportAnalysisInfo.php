<?php

namespace App\adms\Controllers\Services;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para exibir informações sobre a exportação da análise
 */
class ExportAnalysisInfo
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data = [];

        $pageElements = [
            'title_head' => 'Exportar Análise Completa do Projeto',
            'menu' => 'export-analysis-info',
            'buttonPermission' => [],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/Services/export_analysis_info', $this->data);
        $loadView->loadView();
    }
}

