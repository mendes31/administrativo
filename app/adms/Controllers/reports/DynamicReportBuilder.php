<?php

namespace App\adms\Controllers\reports;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Views\Services\LoadViewService;

class DynamicReportBuilder
{
    private array $data = [];

    public function index(): void
    {
        $repo = new DynamicReportsRepository();
        $this->data['availableTables'] = $repo->getAvailableTables();
        
        if (!empty($_GET['id'])) {
            $reportId = (int)$_GET['id'];
            $this->data['report'] = $repo->getById($reportId);
            
            if (!$this->data['report']) {
                $_SESSION['error'] = 'Relatório não encontrado';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
                exit;
            }
        }
        
        $pageElements = [
            'title_head' => 'Construtor de Relatórios',
            'menu' => 'relatorios',
            'buttonPermission' => []
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/reports/builder', $this->data);
        $loadView->loadView();
    }
}

