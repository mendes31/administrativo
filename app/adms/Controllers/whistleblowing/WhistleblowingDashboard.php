<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\WhistleblowingReportsRepository;
use App\adms\Models\Services\WhistleblowingPermissionService;
use App\adms\Views\Services\LoadViewService;

class WhistleblowingDashboard
{
    private array $data = [];

    public function index(): void
    {
        $repo = new WhistleblowingReportsRepository();
        $scopeFilters = WhistleblowingPermissionService::applyReportScopeFilters([]);
        $this->data['stats'] = $repo->getDashboardStats($scopeFilters);

        $pageElements = [
            'title_head' => 'Dashboard — Canal de Denúncias',
            'menu' => 'denuncias-dashboard',
            'buttonPermission' => ['WhistleblowingListReports', 'WhistleblowingViewReport', 'WhistleblowingListCommittees', 'WhistleblowingExportDashboard'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/whistleblowing/dashboard', $this->data);
        $loadView->loadView();
    }
}
