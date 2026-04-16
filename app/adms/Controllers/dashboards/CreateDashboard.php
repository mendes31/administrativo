<?php

namespace App\adms\Controllers\dashboards;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\DashboardsRepository;
use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Models\Repository\SpreadsheetsRepository;
use App\adms\Views\Services\LoadViewService;

class CreateDashboard
{
    private array $data = [];

    public function index(?string $param = null): void
    {
        // Ao entrar em qualquer página de Dashboard, limpar overrides de outros menus
        if (isset($_SESSION['menu_override'])) {
            unset($_SESSION['menu_override']);
        }
        // Verificar se foi passado ID do relatório
        $reportId = $param ? (int)$param : ($_GET['report_id'] ?? null);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->save();
            return;
        }
        
        // Carregar relatório se especificado
        if ($reportId) {
            $reportsRepo = new DynamicReportsRepository();
            $this->data['report'] = $reportsRepo->getById($reportId);
            
            if (!$this->data['report']) {
                $_SESSION['error'] = 'Relatório não encontrado!';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
                exit;
            }
            $viewerId = (int) ($_SESSION['user_id'] ?? 0);
            if (!$reportsRepo->userCanAccessReport($this->data['report'], $viewerId)) {
                $_SESSION['error'] = 'Você não tem permissão para usar este relatório.';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
                exit;
            }
        }
        
        // Listar todos os relatórios disponíveis
        $reportsRepo = $reportsRepo ?? new DynamicReportsRepository();
        $this->data['reports'] = $reportsRepo->getUserReports((int)($_SESSION['user_id'] ?? 0), UserAccessHelper::hasFullSystemAccess());
        
        // Listar todas as planilhas disponíveis
        $spreadsheetsRepo = new SpreadsheetsRepository();
        $this->data['spreadsheets'] = $spreadsheetsRepo->getUserSpreadsheets($_SESSION['user_id'] ?? 0, true);
        
        $pageElements = [
            'title_head' => 'Criar Dashboard',
            // Mantém o menu principal de Dashboards em destaque
            'menu' => 'ListDashboards',
            'buttonPermission' => []
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/dashboards/create', $this->data);
        $loadView->loadView();
    }
    
    private function save(): void
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? null,
            'dynamic_report_id' => (int)($_POST['dynamic_report_id'] ?? 0),
            'spreadsheet_id' => (int)($_POST['spreadsheet_id'] ?? 0),
            'data_source_type' => $_POST['data_source_type'] ?? 'report',
            'created_by' => $_SESSION['user_id'] ?? 0,
            'is_public' => isset($_POST['is_public']),
            'category' => $_POST['category'] ?? null,
            'measures_config' => json_decode($_POST['measures_config'] ?? '[]', true),
            'kpis_config' => json_decode($_POST['kpis_config'] ?? '[]', true),
            'charts_config' => json_decode($_POST['charts_config'] ?? '[]', true),
            'filters_config' => json_decode($_POST['filters_config'] ?? '[]', true),
            'layout' => $_POST['layout'] ?? 'default',
            'relationships' => json_decode($_POST['relationships'] ?? '[]', true)
        ];

        // Garantir que estruturas inválidas não quebrem o processo
        $data['measures_config'] = is_array($data['measures_config']) ? $data['measures_config'] : [];
        $data['kpis_config'] = is_array($data['kpis_config']) ? $data['kpis_config'] : [];
        $data['charts_config'] = is_array($data['charts_config']) ? $data['charts_config'] : [];
        $data['filters_config'] = is_array($data['filters_config']) ? $data['filters_config'] : [];
        $data['relationships'] = is_array($data['relationships']) ? $data['relationships'] : [];
        
        // Validar fonte de dados
        if (empty($data['name'])) {
            $_SESSION['error'] = 'Nome do dashboard é obrigatório!';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? $_ENV['URL_ADM'] . 'create-dashboard'));
            exit;
        }
        
        if ($data['data_source_type'] === 'spreadsheet' && empty($data['spreadsheet_id'])) {
            $_SESSION['error'] = 'Selecione uma planilha como fonte de dados!';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? $_ENV['URL_ADM'] . 'create-dashboard'));
            exit;
        }
        
        if ($data['data_source_type'] === 'report' && empty($data['dynamic_report_id'])) {
            $_SESSION['error'] = 'Selecione um relatório como fonte de dados!';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? $_ENV['URL_ADM'] . 'create-dashboard'));
            exit;
        }
        
        $repo = new DashboardsRepository();
        $dashboardId = $repo->create($data);
        
        $_SESSION['success'] = 'Dashboard criado com sucesso!';
        header('Location: ' . $_ENV['URL_ADM'] . 'view-dashboard/' . $dashboardId);
        exit;
    }
}

