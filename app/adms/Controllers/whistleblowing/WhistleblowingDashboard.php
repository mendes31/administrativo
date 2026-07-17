<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\WhistleblowingConfigRepository;
use App\adms\Models\Repository\WhistleblowingReportsRepository;
use App\adms\Models\Services\WhistleblowingPermissionService;
use App\adms\Models\Services\WhistleblowingSlaService;
use App\adms\Views\Services\LoadViewService;

class WhistleblowingDashboard
{
    private array $data = [];

    public function index(): void
    {
        $dateFrom = $this->validDate((string) ($_GET['date_from'] ?? ''));
        $dateTo = $this->validDate((string) ($_GET['date_to'] ?? ''));
        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }
        $filters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];

        $repo = new WhistleblowingReportsRepository();
        $scopeFilters = WhistleblowingPermissionService::applyReportScopeFilters($filters);
        $this->data['stats'] = $repo->getDashboardStats($scopeFilters);
        $this->data['filters'] = $filters;
        $slaService = new WhistleblowingSlaService();
        $this->data['sla_label'] = $slaService->defaultSlaLabel();
        $this->data['sla_closure_label'] = $slaService->defaultClosureSlaLabel();
        $config = new WhistleblowingConfigRepository();
        $this->data['reporter_inactivity_enabled'] = $config->isReporterInactivityEnabled();
        $this->data['reporter_inactivity_days'] = $config->getReporterInactivityDays();

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

    private function validDate(string $date): string
    {
        $date = trim($date);
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date ? $date : '';
    }
}
