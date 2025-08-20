<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdInventoryRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para exibir relatório integrado do fluxo LGPD
 */
class LgpdWorkflowReport
{
    private array|string|null $data = null;

    /**
     * Exibir relatório do fluxo LGPD
     */
    public function index(): void
    {
        $this->viewReport();
    }

    /**
     * Exibir relatório
     */
    private function viewReport(): void
    {
        $inventoryRepo = new LgpdInventoryRepository();
        
        // Gerar relatório completo
        $report = $inventoryRepo->getCompleteWorkflowReport();
        
        $this->data['report'] = $report;

        $pageElements = [
            'title_head' => 'Relatório Integrado - Fluxo LGPD',
            'menu' => 'lgpd-workflow-report',
            'buttonPermission' => ['ListLgpdInventory', 'ListLgpdRopa', 'ListLgpdDataMapping', 'LgpdWorkflowReport'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/workflow-report", $this->data);
        $loadView->loadView();
    }
} 