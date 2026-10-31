<?php

declare(strict_types=1);

namespace App\adms\Controllers\projects;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Services\SapProjects\SapProjectsService;
use App\adms\Models\Services\SapProjects\SapProjectsSql;
use App\adms\Views\Services\LoadViewService;

/**
 * Shell do Dashboard SAP de Projetos (Gerenciamento de Projetos do SAP B1).
 * Os dados vêm de SapProjectsData (JSON) para a tela abrir na hora.
 */
class SapProjectsDashboard
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->render('dashboard', 'sap-projects-dashboard');
    }

    protected function render(string $aba, string $menu): void
    {
        $pageElements = [
            'title_head' => $aba === 'relatorios' ? 'Relatórios SAP de Projetos' : 'Dashboard SAP de Projetos',
            'menu' => $menu,
            'buttonPermission' => ['SapProjectsDashboard', 'SapProjectsReports', 'SapProjectsData'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = $pageLayoutService->configurePageElements($pageElements);

        $base = $_ENV['URL_ADM'] ?? '';
        $perms = is_array($this->data['buttonPermission'] ?? null) ? $this->data['buttonPermission'] : [];
        $this->data['api_url'] = $base . 'sap-projects-data';
        $this->data['dashboard_url'] = $base . 'sap-projects-dashboard';
        $this->data['reports_url'] = $base . 'sap-projects-reports';
        $this->data['can_dashboard'] = in_array('SapProjectsDashboard', $perms, true);
        $this->data['can_reports'] = in_array('SapProjectsReports', $perms, true);
        $this->data['aba'] = $aba;
        $this->data['reports'] = array_map(
            static fn (array $r): array => ['title' => $r['title'], 'scopes' => $r['scopes']],
            SapProjectsService::REPORTS
        );
        $this->data['filter_scopes'] = array_map(
            static fn (array $f): string => $f['scope'],
            SapProjectsSql::FILTERS
        );

        $loadView = new LoadViewService('adms/Views/projects/sap/dashboard', $this->data);
        $loadView->loadView();
    }
}
