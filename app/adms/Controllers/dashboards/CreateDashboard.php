<?php

namespace App\adms\Controllers\dashboards;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\DashboardsRepository;
use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Views\Services\LoadViewService;

class CreateDashboard
{
    private array $data = [];

    public function index(?string $param = null): void
    {
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
        }
        
        // Listar todos os relatórios disponíveis
        $reportsRepo = $reportsRepo ?? new DynamicReportsRepository();
        $this->data['reports'] = $reportsRepo->getUserReports($_SESSION['user_id'] ?? 0, true);
        
        $pageElements = [
            'title_head' => 'Criar Dashboard',
            'menu' => 'CreateDashboard',
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
            'created_by' => $_SESSION['user_id'] ?? 0,
            'is_public' => isset($_POST['is_public']),
            'category' => $_POST['category'] ?? null,
            'measures_config' => json_decode($_POST['measures_config'] ?? '[]', true),
            'kpis_config' => json_decode($_POST['kpis_config'] ?? '[]', true),
            'charts_config' => json_decode($_POST['charts_config'] ?? '[]', true),
            'filters_config' => json_decode($_POST['filters_config'] ?? '[]', true),
            'layout' => $_POST['layout'] ?? 'default'
        ];
        
        if (empty($data['name']) || empty($data['dynamic_report_id'])) {
            $_SESSION['error'] = 'Nome e Relatório são obrigatórios!';
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? $_ENV['URL_ADM'] . 'create-dashboard');
            exit;
        }
        
        $repo = new DashboardsRepository();
        $dashboardId = $repo->create($data);
        
        $_SESSION['success'] = 'Dashboard criado com sucesso!';
        header('Location: ' . $_ENV['URL_ADM'] . 'view-dashboard/' . $dashboardId);
        exit;
    }
}

