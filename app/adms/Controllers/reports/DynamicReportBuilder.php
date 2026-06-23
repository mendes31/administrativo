<?php

namespace App\adms\Controllers\reports;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Views\Services\LoadViewService;

class DynamicReportBuilder
{
    /**
     * Dados compartilhados com as views.
     * 
     * Usado também pelas classes filhas (`DynamicReportBuilderLocal`, `DynamicReportBuilderSap`),
     * por isso a visibilidade precisa ser `protected` em vez de `private`.
     */
    protected array $data = [];

    public function index(): void
    {
        $repo = new DynamicReportsRepository();
        $this->data['availableTables'] = $repo->getAvailableTables();
        // Prioriza flag recebida via controller específico; mantém fallback por query string
        $sapScope = isset($this->data['is_sap_scope'])
            ? (bool)$this->data['is_sap_scope']
            : (!empty($_GET['source']) && $_GET['source'] === 'sap');
        
        if (!empty($_GET['id'])) {
            $reportId = (int)$_GET['id'];
            $this->data['report'] = $repo->getById($reportId);
            
            if (!$this->data['report']) {
                $_SESSION['error'] = 'Relatório não encontrado';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
                exit;
            }
            $viewerId = (int) ($_SESSION['user_id'] ?? 0);
            if (!$repo->userCanEditReport($this->data['report'], $viewerId)) {
                $_SESSION['error'] = 'Você não tem permissão para editar este relatório.';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
                exit;
            }
        }

        $viewerId = (int) ($_SESSION['user_id'] ?? 0);
        $this->data['users_for_share'] = $repo->listUsersForReportShare($viewerId);
        $existingReportId = isset($this->data['report']['id']) ? (int) $this->data['report']['id'] : 0;
        $this->data['shared_user_ids'] = $existingReportId > 0
            ? $repo->getSharedUserIds($existingReportId)
            : [];
        
        $pageElements = [
            'title_head' => $sapScope ? 'Construtor de Relatórios SAP (API)' : 'Construtor de Relatórios Locais',
            'menu' => $sapScope ? 'ListDynamicReportsSap' : 'ListDynamicReports',
            'menu_override' => $sapScope ? 'ListDynamicReportsSap' : 'ListDynamicReports',
            'buttonPermission' => [],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/reports/builder', $this->data);
        $loadView->loadView();
    }
}

