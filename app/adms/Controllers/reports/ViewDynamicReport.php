<?php

namespace App\adms\Controllers\reports;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Models\Services\DynamicQueryBuilderService;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class ViewDynamicReport
{
    private array $data = [];

    public function index(?string $id = null): void
    {
        // Sem limite de memória para relatórios grandes (todas as vendas, etc)
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '600'); // 10 minutos
        
        if (empty($id)) {
            $_SESSION['error'] = 'ID do relatório não fornecido';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
            exit;
        }
        
        $forceRefresh = !empty($_GET['refresh']);
        $incremental = !empty($_GET['incremental']) && $_GET['incremental'] === '1';
        
        // Paginação
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 25, 50, 100, 500]) 
            ? (int)$_GET['per_page'] 
            : 25;

        $repo = new DynamicReportsRepository();
        $this->data['report'] = $repo->getById((int)$id);
        
        if (!$this->data['report']) {
            $_SESSION['error'] = 'Relatório não encontrado';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
            exit;
        }

        $viewerId = (int) ($_SESSION['user_id'] ?? 0);
        if (!$repo->userCanViewReport($this->data['report'], $viewerId)) {
            $_SESSION['error'] = 'Você não tem permissão para acessar este relatório.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
            exit;
        }

        $reportId = (int) $id;
        $returnUrl = $_ENV['URL_ADM'] . 'view-dynamic-report/' . $reportId;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_dynamic_reports', $reportId, $returnUrl);

        $this->data['report']['cache_namespace'] = 'report_' . $id;
        $this->data['report']['force_refresh'] = $forceRefresh;
        $this->data['report']['incremental'] = $incremental;
        $this->data['report']['page'] = $page;
        $this->data['report']['per_page'] = $perPage;

        $queryBuilder = new DynamicQueryBuilderService();
        $this->data['result'] = $queryBuilder->executeReport($this->data['report']);
        $this->data['force_refresh'] = $forceRefresh;
        
        // Gerar paginação se houver dados
        if ($this->data['result']['success'] && !empty($this->data['result']['data'])) {
            $totalRows = $this->data['result']['total_rows'] ?? count($this->data['result']['data']);
            $filters = ['per_page' => $perPage];
            if ($forceRefresh) {
                $filters['refresh'] = '1';
            }
            if ($incremental) {
                $filters['incremental'] = '1';
            }
            $pagination = PaginationService::generatePagination(
                $totalRows,
                $perPage,
                $page,
                'view-dynamic-report/' . $id,
                $filters
            );
            $this->data['pagination'] = $pagination;
        }
        
        if ($this->data['result']['success']) {
            // Garantir que execution_time seja sempre float, mesmo se null ou não existir
            $executionTime = isset($this->data['result']['execution_time']) && $this->data['result']['execution_time'] !== null
                ? (float)$this->data['result']['execution_time']
                : 0.0;
            
            // Garantir que rows_count seja sempre int
            $rowsCount = isset($this->data['result']['rows_count']) && $this->data['result']['rows_count'] !== null
                ? (int)$this->data['result']['rows_count']
                : (int)(count($this->data['result']['data'] ?? []));
            
            $repo->logExecution((int)$id, $_SESSION['user_id'] ?? 0, $executionTime, $rowsCount);
        }
        
        // Se vier de um dashboard, manter menu de Dashboards em destaque
        $fromDashboard = !empty($_GET['dashboard_id']);

        $pageElements = [
            'title_head' => $this->data['report']['name'],
            'menu' => $fromDashboard ? 'ListDashboards' : 'relatorios',
            'buttonPermission' => ['ExportDynamicReportExcel', 'ExportDynamicReportPdf'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/reports/view', $this->data);
        $loadView->loadView();
    }
}

