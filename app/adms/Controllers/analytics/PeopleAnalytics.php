<?php

namespace App\adms\Controllers\analytics;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CountryHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\EmploymentHistoryRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\IntegratedPeopleIndicatorsService;
use App\adms\Models\Services\PeopleAnalyticsFilterParser;
use App\adms\Models\Services\PeopleAnalyticsMetricsService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para People Analytics (KPIs de RH com filtros globais).
 */
class PeopleAnalytics
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = PeopleAnalyticsFilterParser::parseFromRequest();
        $usersRepo = new UsersRepository();
        $users = $usersRepo->getUsersForPeopleAnalytics(PeopleAnalyticsFilterParser::usersRepoFilterPayload($filters));

        $historyRepo = new EmploymentHistoryRepository();
        $historyByUser = $historyRepo->getGroupedByUserIds(array_column($users, 'id'));

        $metrics = (new PeopleAnalyticsMetricsService())->compute(
            $users,
            $historyByUser,
            $filters['period_start'],
            $filters['period_end']
        );
        $metrics['integrated'] = (new IntegratedPeopleIndicatorsService())->collect();

        $countries = CountryHelper::getCountries();
        uasort($countries, static fn ($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));

        $this->data = array_merge($metrics, [
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
            'metrics_json_url' => ($_ENV['URL_ADM'] ?? '') . 'people-analytics/metrics',
        ]);

        $pageElements = [
            'title_head' => 'People Analytics',
            'menu' => 'people-analytics',
            'buttonPermission' => [
                'PeopleReports',
                'ListUsers',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/analytics/people_analytics', $this->data);
        $loadView->loadView();
    }

    /**
     * Endpoint JSON com os mesmos filtros da tela (GET), para integrações ou AJAX.
     */
    public function metrics(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $filters = PeopleAnalyticsFilterParser::parseFromRequest();
            $usersRepo = new UsersRepository();
            $users = $usersRepo->getUsersForPeopleAnalytics(PeopleAnalyticsFilterParser::usersRepoFilterPayload($filters));
            $historyRepo = new EmploymentHistoryRepository();
            $historyByUser = $historyRepo->getGroupedByUserIds(array_column($users, 'id'));
            $metrics = (new PeopleAnalyticsMetricsService())->compute(
                $users,
                $historyByUser,
                $filters['period_start'],
                $filters['period_end']
            );
            $metrics['integrated'] = (new IntegratedPeopleIndicatorsService())->collect();

            echo json_encode(['success' => true, 'data' => $metrics], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }
}
