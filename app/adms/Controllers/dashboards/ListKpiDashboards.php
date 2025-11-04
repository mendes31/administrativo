<?php

namespace App\adms\Controllers\dashboards;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\KpiDashboardRepository;

class ListKpiDashboards extends PageLayoutService
{
    private KpiDashboardRepository $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new KpiDashboardRepository();
    }

    public function index(): void
    {
        $userId = $_SESSION['user_id'] ?? 0;
        
        $dashboards = $this->repository->getAccessibleDashboards($userId);
        
        $this->loadView('dashboards/list', [
            'dashboards' => $dashboards
        ]);
    }
}

