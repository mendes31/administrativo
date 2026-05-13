<?php

declare(strict_types=1);

namespace App\adms\Controllers\analytics;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CountryHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Services\PeopleAnalyticsFilterParser;
use App\adms\Models\Services\PeopleReportsExportService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para Relatórios de RH (exportações CSV alinhadas aos filtros do People Analytics).
 */
class PeopleReports
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = PeopleAnalyticsFilterParser::parseFromRequest();
        $countries = CountryHelper::getCountries();
        uasort($countries, static fn ($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));

        $this->data = [
            'filter_period_start' => $filters['period_start'],
            'filter_period_end' => $filters['period_end'],
            'filter_departamento_ids' => $filters['departamento_ids'],
            'filter_cargo_ids' => $filters['cargo_ids'],
            'filter_sexo' => $filters['sexo'],
            'filter_estado_civil' => $filters['estado_civil'],
            'filter_pais_iso' => $filters['pais_residencia_iso'],
            'filter_filhos' => $filters['filhos'],
            'departments_options' => (new DepartmentsRepository())->getAllDepartmentsSelect(),
            'positions_options' => (new PositionsRepository())->getAllPositionsSelect(),
            'countries_options_pa' => $countries,
            'export_query' => PeopleAnalyticsFilterParser::buildQueryString($filters),
        ];

        $pageElements = [
            'title_head' => 'Relatórios de RH',
            'menu' => 'people-reports',
            'buttonPermission' => [
                'PeopleReports',
                'PeopleAnalytics',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/analytics/people_reports', $this->data);
        $loadView->loadView();
    }

    public function exportHeadcountCsv(): void
    {
        PeopleReportsExportService::outputHeadcountCsv(PeopleAnalyticsFilterParser::parseFromRequest());
        exit;
    }

    public function exportTurnoverCsv(): void
    {
        PeopleReportsExportService::outputTurnoverCsv(PeopleAnalyticsFilterParser::parseFromRequest());
        exit;
    }

    public function exportTrainingCsv(): void
    {
        $f = PeopleAnalyticsFilterParser::parseFromRequest();
        PeopleReportsExportService::outputTrainingCsv($f['period_start'], $f['period_end']);
        exit;
    }

    public function exportPerformanceCsv(): void
    {
        $f = PeopleAnalyticsFilterParser::parseFromRequest();
        PeopleReportsExportService::outputPerformanceCsv($f['period_start'], $f['period_end']);
        exit;
    }
}
