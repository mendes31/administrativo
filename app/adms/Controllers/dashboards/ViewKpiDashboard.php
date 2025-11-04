<?php

namespace App\adms\Controllers\dashboards;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\KpiDashboardRepository;

class ViewKpiDashboard extends PageLayoutService
{
    private KpiDashboardRepository $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new KpiDashboardRepository();
    }

    public function index(): void
    {
        $dashboardId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        
        if (!$dashboardId) {
            $_SESSION['msg'] = "<p class='alert alert-danger'>Dashboard não encontrado!</p>";
            header("Location: " . $_ENV['URL_ADM'] . "list-kpi-dashboards");
            exit;
        }
        
        $dashboard = $this->repository->findById($dashboardId);
        
        if (!$dashboard) {
            $_SESSION['msg'] = "<p class='alert alert-danger'>Dashboard não encontrado!</p>";
            header("Location: " . $_ENV['URL_ADM'] . "list-kpi-dashboards");
            exit;
        }
        
        $widgets = $this->repository->getWidgets($dashboardId);
        
        $this->loadView('dashboards/view', [
            'dashboard' => $dashboard,
            'widgets' => $widgets
        ]);
    }
}

