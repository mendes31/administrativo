<?php

namespace App\adms\Controllers\analytics;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CountryHelper;
use App\adms\Helpers\UserFormHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\EmploymentHistoryRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\UsersRepository;
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
        $filters = $this->parseFiltersFromRequest();
        $usersRepo = new UsersRepository();
        $users = $usersRepo->getUsersForPeopleAnalytics([
            'departamento_ids' => $filters['departamento_ids'],
            'cargo_ids' => $filters['cargo_ids'],
            'sexo' => $filters['sexo'],
            'estado_civil' => $filters['estado_civil'],
            'pais_residencia_iso' => $filters['pais_residencia_iso'],
            'filhos' => $filters['filhos'],
        ]);

        $historyRepo = new EmploymentHistoryRepository();
        $historyByUser = $historyRepo->getGroupedByUserIds(array_column($users, 'id'));

        $metrics = (new PeopleAnalyticsMetricsService())->compute(
            $users,
            $historyByUser,
            $filters['period_start'],
            $filters['period_end']
        );

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
            $filters = $this->parseFiltersFromRequest();
            $usersRepo = new UsersRepository();
            $users = $usersRepo->getUsersForPeopleAnalytics([
                'departamento_ids' => $filters['departamento_ids'],
                'cargo_ids' => $filters['cargo_ids'],
                'sexo' => $filters['sexo'],
                'estado_civil' => $filters['estado_civil'],
                'pais_residencia_iso' => $filters['pais_residencia_iso'],
                'filhos' => $filters['filhos'],
            ]);
            $historyRepo = new EmploymentHistoryRepository();
            $historyByUser = $historyRepo->getGroupedByUserIds(array_column($users, 'id'));
            $metrics = (new PeopleAnalyticsMetricsService())->compute(
                $users,
                $historyByUser,
                $filters['period_start'],
                $filters['period_end']
            );

            echo json_encode(['success' => true, 'data' => $metrics], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }

    /**
     * @return array{
     *     period_start: string,
     *     period_end: string,
     *     departamento_ids: int[],
     *     cargo_ids: int[],
     *     sexo: string|null,
     *     estado_civil: string|null,
     *     pais_residencia_iso: string|null,
     *     filhos: string|null
     * }
     */
    private function parseFiltersFromRequest(): array
    {
        $today = date('Y-m-d');
        $defaultStart = date('Y-m-d', strtotime('-12 months'));

        $de = isset($_GET['pa_de']) ? (string) $_GET['pa_de'] : $defaultStart;
        $ate = isset($_GET['pa_ate']) ? (string) $_GET['pa_ate'] : $today;

        $de = $this->normalizeDateOr($de, $defaultStart);
        $ate = $this->normalizeDateOr($ate, $today);
        if ($de > $ate) {
            [$de, $ate] = [$ate, $de];
        }

        return [
            'period_start' => $de,
            'period_end' => $ate,
            'departamento_ids' => $this->parseIdList($_GET['pa_dep'] ?? null),
            'cargo_ids' => $this->parseIdList($_GET['pa_pos'] ?? null),
            'sexo' => UserFormHelper::normalizeSexo($_GET['pa_sexo'] ?? null),
            'estado_civil' => UserFormHelper::normalizeEstadoCivil($_GET['pa_estado_civil'] ?? null),
            'pais_residencia_iso' => UserFormHelper::normalizePaisResidenciaIso($_GET['pa_pais'] ?? null),
            'filhos' => UserFormHelper::normalizeFilhos($_GET['pa_filhos'] ?? null),
        ];
    }

    private function normalizeDateOr(string $value, string $fallback): string
    {
        $d = \DateTime::createFromFormat('Y-m-d', $value);

        return ($d && $d->format('Y-m-d') === $value) ? $value : $fallback;
    }

    /**
     * @return int[]
     */
    private function parseIdList(mixed $raw): array
    {
        if ($raw === null || $raw === '' || $raw === []) {
            return [];
        }
        if (!is_array($raw)) {
            $raw = explode(',', (string) $raw);
        }
        $out = [];
        foreach ($raw as $v) {
            if (is_string($v)) {
                $v = trim($v);
            }
            if ($v === '' || $v === null) {
                continue;
            }
            if (is_numeric($v)) {
                $id = (int) $v;
                if ($id > 0) {
                    $out[$id] = true;
                }
            }
        }

        return array_keys($out);
    }
}
