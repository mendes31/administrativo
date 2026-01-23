<?php

namespace App\adms\Controllers\dashboards;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\DashboardsRepository;
use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Lista as fontes de dados (relatórios dinâmicos) vinculadas a um dashboard.
 *
 * Por enquanto o dashboard possui apenas um `dynamic_report_id`, mas esta
 * página já está preparada para futuramente receber múltiplas fontes.
 */
class DashboardDataSources
{
    private array $data = [];

    public function index(?string $id = null): void
    {
        if (empty($id)) {
            $_SESSION['error'] = 'Dashboard não especificado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }

        $dashboardId = (int)$id;
        $userId = $_SESSION['user_id'] ?? 0;

        $dashRepo = new DashboardsRepository();

        // Verificar acesso ao dashboard
        if (!$dashRepo->canAccess($dashboardId, $userId)) {
            $_SESSION['error'] = 'Você não tem permissão para acessar este dashboard!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }

        $dashboard = $dashRepo->getById($dashboardId);
        if (!$dashboard) {
            $_SESSION['error'] = 'Dashboard não encontrado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dashboards');
            exit;
        }

        $this->data['dashboard'] = $dashboard;

        // Por enquanto existe apenas uma fonte principal (dynamic_report_id)
        $reportsRepo = new DynamicReportsRepository();
        $reports = [];

        if (!empty($dashboard['dynamic_report_id'])) {
            $report = $reportsRepo->getById((int)$dashboard['dynamic_report_id']);
            if ($report) {
                $reports[] = $report;
            }
        }

        $this->data['reports'] = $reports;

        $pageElements = [
            'title_head' => 'Fontes de Dados - ' . ($dashboard['name'] ?? ''),
            'menu' => 'ListDashboards', // mantém menu de Dashboards em destaque
            'buttonPermission' => []
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/dashboards/data_sources', $this->data);
        $loadView->loadView();
    }
}






