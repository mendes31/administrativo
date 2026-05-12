<?php

namespace App\adms\Controllers\dashboards;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\KpiDashboardRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class ViewKpiDashboard
{
    public function index(): void
    {
        $dashboardId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$dashboardId) {
            $_SESSION['msg'] = "<p class='alert alert-danger'>Dashboard não encontrado!</p>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-kpi-dashboards');
            exit;
        }

        $repository = new KpiDashboardRepository();
        $dashboard = $repository->findById($dashboardId);

        if (!$dashboard) {
            $_SESSION['msg'] = "<p class='alert alert-danger'>Dashboard não encontrado!</p>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-kpi-dashboards');
            exit;
        }

        $widgets = $repository->getWidgets($dashboardId);

        $returnUrl = $_ENV['URL_ADM'] . 'view-kpi-dashboard?id=' . $dashboardId;
        $logResumo = LogResumoService::getResumo('adms_kpi_dashboards', $dashboardId, $returnUrl);

        $pageElements = [
            'title_head' => $dashboard['name'] ?? 'Dashboard KPI',
            'menu' => 'ListKpiDashboards',
            'buttonPermission' => ['ListKpiDashboards', 'ViewKpiDashboard', 'CreateKpiDashboard', 'UpdateKpiDashboard', 'DeleteKpiDashboard'],
        ];

        $pageLayoutService = new PageLayoutService();
        $viewData = array_merge(
            [
                'dashboard' => $dashboard,
                'widgets' => $widgets,
                'log_resumo' => $logResumo,
            ],
            $pageLayoutService->configurePageElements($pageElements)
        );

        $loadView = new LoadViewService('adms/Views/dashboards/view', $viewData);
        $loadView->loadView();
    }
}
