<?php

namespace App\adms\Controllers\reports;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Models\Services\DynamicQueryBuilderService;
use App\adms\Views\Services\LoadViewService;

class ViewDynamicReport
{
    private array $data = [];

    public function index(?string $id = null): void
    {
        if (empty($id)) {
            $_SESSION['error'] = 'ID do relatório não fornecido';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
            exit;
        }
        
        $repo = new DynamicReportsRepository();
        $this->data['report'] = $repo->getById((int)$id);
        
        if (!$this->data['report']) {
            $_SESSION['error'] = 'Relatório não encontrado';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
            exit;
        }
        
        $queryBuilder = new DynamicQueryBuilderService();
        $this->data['result'] = $queryBuilder->executeReport($this->data['report']);
        
        if ($this->data['result']['success']) {
            $repo->logExecution((int)$id, $_SESSION['user_id'] ?? 0,
                $this->data['result']['execution_time'], $this->data['result']['rows_count']);
        }
        
        $pageElements = [
            'title_head' => $this->data['report']['name'],
            'menu' => 'relatorios',
            'buttonPermission' => []
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/reports/view', $this->data);
        $loadView->loadView();
    }
}

