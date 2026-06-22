<?php



namespace App\adms\Controllers\trainings;



use App\adms\Controllers\Services\PageLayoutService;

use App\adms\Helpers\PositionDisplayHelper;

use App\adms\Models\Repository\DepartmentsRepository;

use App\adms\Models\Repository\PositionsRepository;

use App\adms\Models\Repository\TrainingsRepository;

use App\adms\Models\Repository\TrainingUsersRepository;

use App\adms\Models\Repository\UsersRepository;

use App\adms\Models\Services\TrainingStatusUpdaterService;

use App\adms\Views\Services\LoadViewService;



class TrainingComplianceDashboard

{

    private TrainingUsersRepository $trainingUsersRepo;

    private TrainingsRepository $trainingsRepo;

    private UsersRepository $usersRepo;

    private DepartmentsRepository $departmentsRepo;

    private PositionsRepository $positionsRepo;



    public function __construct()

    {

        $this->trainingUsersRepo = new TrainingUsersRepository();

        $this->trainingsRepo = new TrainingsRepository();

        $this->usersRepo = new UsersRepository();

        $this->departmentsRepo = new DepartmentsRepository();

        $this->positionsRepo = new PositionsRepository();

    }



    public function index(): void

    {

        TrainingStatusUpdaterService::ensureUpdated();



        $filters = $this->resolveFilters();

        $perPageOptions = [10, 20, 50, 100];
        $perPage = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $perPageOptions, true)
            ? (int)$_GET['per_page']
            : 10;

        $pageDepto = isset($_GET['page_depto']) ? max(1, (int)$_GET['page_depto']) : 1;
        $pageCargo = isset($_GET['page_cargo']) ? max(1, (int)$_GET['page_cargo']) : 1;
        $pageColab = isset($_GET['page_colab']) ? max(1, (int)$_GET['page_colab']) : 1;

        $exportSection = isset($_GET['section']) ? (string)$_GET['section'] : '';
        if (isset($_GET['export']) && $_GET['export'] === 'excel' && $exportSection !== '') {
            $this->exportExcel($filters, $exportSection);
            return;
        }
        if (isset($_GET['export']) && $_GET['export'] === 'pdf' && $exportSection !== '') {
            if ($exportSection === 'completo') {
                $this->exportPdfCompleto($filters);
            } else {
                $this->exportPdf($filters, $exportSection);
            }
            return;
        }

        $data = [
            'title_head' => 'Dashboard de Necessidades de Treinamento',
            'menu' => 'training-compliance-dashboard',
            'buttonPermission' => ['TrainingComplianceDashboard'],
            'filters' => $filters,
            'listDepartments' => $this->departmentsRepo->getAllDepartmentsSelect(),
            'listPositions' => $this->positionsRepo->getAllPositionsSelect(),
            'listUsers' => $this->usersRepo->getAllUsersSelect(),
            'dashboard' => $this->getDashboardData($filters, $pageDepto, $pageCargo, $pageColab, $perPage),
            'paginationSettings' => ['options' => $perPageOptions, 'per_page' => $perPage],
            'pages' => [
                'depto' => $pageDepto,
                'cargo' => $pageCargo,
                'colab' => $pageColab,
            ],
        ];



        $pageLayout = new PageLayoutService();

        $data = array_merge($data, $pageLayout->configurePageElements($data));



        $loadView = new LoadViewService('adms/Views/trainings/complianceDashboard', $data);

        $loadView->loadView();

    }



    /**

     * @return array{departamento: ?int, cargo: ?int, colaborador: ?int}

     */

    private function resolveFilters(): array

    {

        if (isset($_GET['limpar'])) {

            unset($_SESSION['training_compliance_filters']);

            header('Location: ' . $_ENV['URL_ADM'] . 'training-compliance-dashboard');

            exit;

        }



        $hasUrlFilters = !empty($_GET['departamento']) || !empty($_GET['cargo']) || !empty($_GET['colaborador']);



        if ($hasUrlFilters) {

            $filters = [

                'departamento' => !empty($_GET['departamento']) ? (int)$_GET['departamento'] : null,

                'cargo' => !empty($_GET['cargo']) ? (int)$_GET['cargo'] : null,

                'colaborador' => !empty($_GET['colaborador']) ? (int)$_GET['colaborador'] : null,

            ];

            $_SESSION['training_compliance_filters'] = $filters;

        } elseif (isset($_SESSION['training_compliance_filters'])) {

            $filters = $_SESSION['training_compliance_filters'];

        } else {

            $filters = [

                'departamento' => null,

                'cargo' => null,

                'colaborador' => null,

            ];

        }



        return $filters;

    }



    /**

     * @param array{departamento: ?int, cargo: ?int, colaborador: ?int} $filters

     */

    private function getDashboardData(
        array $filters,
        int $pageDepto,
        int $pageCargo,
        int $pageColab,
        int $perPage
    ): array {
        $hasFilters = !empty($filters['departamento']) || !empty($filters['cargo']) || !empty($filters['colaborador']);
        $totalObrigacoes = $this->trainingUsersRepo->countComplianceObrigacoes($filters);
        $totalPendentes = $this->trainingUsersRepo->countCompliancePendentes($filters);
        $totalRealizados = $this->trainingUsersRepo->countComplianceRealizados($filters);

        $departmentPagination = $this->trainingUsersRepo->getComplianceStatsByDepartmentPaginated($filters, $pageDepto, $perPage);
        $positionPagination = $this->trainingUsersRepo->getComplianceStatsByPositionPaginated($filters, $pageCargo, $perPage);
        $userPagination = $this->trainingUsersRepo->getComplianceStatsByUserPaginated($filters, $pageColab, $perPage);
        $positionStatsAll = $this->trainingUsersRepo->getComplianceStatsByPositionAll($filters);
        $departmentStatsAll = $this->trainingUsersRepo->getComplianceStatsByDepartmentAll($filters);
        $userStatsAll = $this->trainingUsersRepo->getComplianceStatsByUserAll($filters);

        $positionStatsAll = $this->prepareChartRows($positionStatsAll, $hasFilters);
        $departmentStatsAll = $this->prepareChartRows($departmentStatsAll, $hasFilters);
        $userStatsAll = $this->prepareUserChartRows($userStatsAll, $hasFilters);

        return [
            'totalCadastrados' => $hasFilters
                ? $this->trainingUsersRepo->countComplianceDistinctCodigos($filters)
                : $this->trainingsRepo->countActiveDistinctCodigos(),
            'totalObrigacoes' => $totalObrigacoes,
            'totalRealizados' => $totalRealizados,
            'totalPendentes' => $totalPendentes,
            'percentRealizados' => $this->percentRealizados($totalObrigacoes, $totalRealizados),
            'departmentStats' => $departmentPagination['data'],
            'departmentPagination' => $this->formatPaginationMeta($departmentPagination),
            'positionStats' => $positionPagination['data'],
            'positionPagination' => $this->formatPaginationMeta($positionPagination),
            'positionStatsAll' => $positionStatsAll,
            'departmentStatsAll' => $departmentStatsAll,
            'userStatsAll' => $userStatsAll,
            'userStats' => $userPagination['data'],
            'userPagination' => $this->formatPaginationMeta($userPagination),
            'hasFilters' => $hasFilters,
        ];
    }

    /**
     * @param array{data: array<int, array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int} $pagination
     * @return array{total: int, total_pages: int, current_page: int, per_page: int}
     */
    private function formatPaginationMeta(array $pagination): array
    {
        return [
            'total' => (int)($pagination['total'] ?? 0),
            'total_pages' => (int)($pagination['total_pages'] ?? 1),
            'current_page' => (int)($pagination['page'] ?? 1),
            'per_page' => (int)($pagination['per_page'] ?? 10),
        ];
    }



    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function prepareChartRows(array $rows, bool $hasFilters): array
    {
        if ($hasFilters) {
            $rows = array_values(array_filter(
                $rows,
                static fn(array $row): bool => (int)($row['total_obrigacoes'] ?? 0) > 0
            ));
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function prepareUserChartRows(array $rows, bool $hasFilters): array
    {
        if ($hasFilters) {
            $rows = array_values(array_filter(
                $rows,
                static fn(array $row): bool => (int)($row['total_obrigacoes'] ?? 0) > 0
            ));
        }

        return $rows;
    }

    private function percentRealizados(int $totalObrigacoes, int $realizados): float

    {

        if ($totalObrigacoes <= 0) {

            return 0.0;

        }



        return round(($realizados / $totalObrigacoes) * 100, 1);

    }

    /**
     * @param array{departamento: ?int, cargo: ?int, colaborador: ?int} $filters
     * @return array<string, mixed>
     */
    private function getSummaryTotals(array $filters): array
    {
        $hasFilters = !empty($filters['departamento']) || !empty($filters['cargo']) || !empty($filters['colaborador']);
        $totalObrigacoes = $this->trainingUsersRepo->countComplianceObrigacoes($filters);
        $totalRealizados = $this->trainingUsersRepo->countComplianceRealizados($filters);

        return [
            'totalCadastrados' => $hasFilters
                ? $this->trainingUsersRepo->countComplianceDistinctCodigos($filters)
                : $this->trainingsRepo->countActiveDistinctCodigos(),
            'totalObrigacoes' => $totalObrigacoes,
            'totalRealizados' => $totalRealizados,
            'totalPendentes' => $this->trainingUsersRepo->countCompliancePendentes($filters),
            'percentRealizados' => $this->percentRealizados($totalObrigacoes, $totalRealizados),
        ];
    }

    /**
     * @param array{departamento: ?int, cargo: ?int, colaborador: ?int} $filters
     */
    private function exportPdfCompleto(array $filters): void
    {
        $summary = $this->getSummaryTotals($filters);
        $hasFilters = !empty($filters['departamento']) || !empty($filters['cargo']) || !empty($filters['colaborador']);
        $departmentStats = $this->prepareChartRows(
            $this->trainingUsersRepo->getComplianceStatsByDepartmentAll($filters),
            $hasFilters
        );
        $positionStats = $this->prepareChartRows(
            $this->trainingUsersRepo->getComplianceStatsByPositionAll($filters),
            $hasFilters
        );
        $userStats = $this->prepareUserChartRows(
            $this->trainingUsersRepo->getComplianceStatsByUserAll($filters),
            $hasFilters
        );

        $html = '<div style="margin: 16px; font-family: Arial, sans-serif; font-size: 10px;">';
        $html .= '<h2 style="text-align:center;">Dashboard de Necessidades de Treinamento</h2>';
        $html .= '<p style="text-align:center;color:#666;margin-bottom:16px;">Relatório completo com gráficos e listagens (filtros respeitados)</p>';

        $html .= $this->buildPdfKpiCards($summary, $hasFilters);
        $html .= $this->buildPdfChartsGrid($summary, $departmentStats, $positionStats, $userStats, $hasFilters);
        $html .= $this->buildPdfGroupTable('Estatísticas por Departamento', 'Departamento', $departmentStats, false);
        $html .= $this->buildPdfGroupTable('Estatísticas por Cargo', 'Cargo', $positionStats, true);
        $html .= $this->buildPdfUserTable($userStats);
        $html .= '</div>';

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('dashboard_necessidades_completo.pdf', ['Attachment' => true]);
        exit;
    }

    /**
     * @param array<string, mixed> $summary
     */
    private function buildPdfKpiCards(array $summary, bool $hasFilters): string
    {
        $cad = (int)($summary['totalCadastrados'] ?? 0);
        $obr = (int)($summary['totalObrigacoes'] ?? 0);
        $real = (int)($summary['totalRealizados'] ?? 0);
        $pend = (int)($summary['totalPendentes'] ?? 0);
        $pct = (float)($summary['percentRealizados'] ?? 0);
        $labelCad = $hasFilters ? 'Treinamentos no recorte' : 'Total de Treinamentos Cadastrados';

        $card = static function (string $value, string $label, string $color): string {
            return '<td width="25%" style="border:1px solid ' . $color . ';padding:12px 6px;text-align:center;vertical-align:middle;">'
                . '<div style="font-size:18px;font-weight:bold;color:' . $color . ';margin-bottom:4px;">' . $value . '</div>'
                . '<div style="font-size:8px;color:#666;line-height:1.3;">' . htmlspecialchars($label) . '</div>'
                . '</td>';
        };

        return '<table width="100%" cellpadding="0" cellspacing="6" style="margin-bottom:12px;border-collapse:separate;">'
            . '<tr>'
            . $card(number_format($cad), $labelCad, '#0d6efd')
            . $card(number_format($obr), 'Total de obrigações', '#6c757d')
            . $card(number_format($real), 'Realizados (' . $pct . '%)', '#198754')
            . $card(number_format($pend), 'Pendentes', '#dc3545')
            . '</tr></table>';
    }

    /**
     * @param array<string, mixed> $summary
     * @param array<int, array<string, mixed>> $departmentStats
     * @param array<int, array<string, mixed>> $positionStats
     * @param array<int, array<string, mixed>> $userStats
     */
    private function buildPdfChartsGrid(
        array $summary,
        array $departmentStats,
        array $positionStats,
        array $userStats,
        bool $hasFilters
    ): string {
        $cellStyle = 'width:25%;vertical-align:top;border:1px solid #dee2e6;padding:6px;height:210px;';
        $headerStyle = 'font-size:9px;font-weight:bold;color:#0d6efd;margin-bottom:6px;padding-bottom:4px;border-bottom:1px solid #eee;';

        return '<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;border-collapse:collapse;">'
            . '<tr style="vertical-align:top;">'
            . '<td style="' . $cellStyle . '">'
            . '<div style="' . $headerStyle . '">Resumo visual</div>'
            . $this->buildPdfSummaryChartContent(
                (int)($summary['totalObrigacoes'] ?? 0),
                (int)($summary['totalRealizados'] ?? 0),
                (int)($summary['totalPendentes'] ?? 0),
                (float)($summary['percentRealizados'] ?? 0),
                $hasFilters
            )
            . '</td>'
            . '<td style="' . $cellStyle . '">'
            . '<div style="' . $headerStyle . '">Cumprimento por Depto.</div>'
            . $this->buildPdfStackedBarsChartContent($departmentStats, false, 'group_name', 8)
            . '</td>'
            . '<td style="' . $cellStyle . '">'
            . '<div style="' . $headerStyle . '">Cumprimento por Cargo</div>'
            . $this->buildPdfStackedBarsChartContent($positionStats, true, 'group_name', 8)
            . '</td>'
            . '<td style="' . $cellStyle . '">'
            . '<div style="' . $headerStyle . '">Cumprimento por Colab.</div>'
            . $this->buildPdfStackedBarsChartContent($userStats, false, 'user_name', 8)
            . '</td>'
            . '</tr></table>';
    }

    private function buildPdfSummaryChartContent(
        int $total,
        int $realizados,
        int $pendentes,
        float $percent,
        bool $hasFilters
    ): string {
        if ($total <= 0) {
            return '<p style="color:#888;text-align:center;font-size:9px;margin:20px 0;">Sem obrigações.</p>';
        }

        $max = max($total, 1);
        $barCell = static function (int $value, string $color, string $label) use ($max): string {
            $barMaxPx = 68;
            $height = max(6, (int) round(($value / $max) * $barMaxPx));
            $spacer = $barMaxPx - $height;

            return '<td style="width:33%;text-align:center;vertical-align:bottom;padding:2px;">'
                . '<table cellpadding="0" cellspacing="0" align="center" style="border-collapse:collapse;">'
                . '<tr><td style="height:' . $spacer . 'px;font-size:0;line-height:0;">&nbsp;</td></tr>'
                . '<tr><td style="height:' . $height . 'px;width:28px;background:' . $color . ';font-size:0;">&nbsp;</td></tr>'
                . '</table>'
                . '<div style="font-weight:bold;font-size:9px;margin-top:3px;">' . number_format($value) . '</div>'
                . '<div style="font-size:8px;color:#666;">' . htmlspecialchars($label) . '</div>'
                . '</td>';
        };

        $pctLabel = $hasFilters ? 'Recorte' : 'Geral';

        return '<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:8px;">'
            . '<tr>'
            . $barCell($total, '#0d6efd', 'Total')
            . $barCell($realizados, '#198754', 'Realiz.')
            . $barCell($pendentes, '#dc3545', 'Pend.')
            . '</tr></table>'
            . '<p style="text-align:center;font-size:8px;color:#666;margin:6px 0 0;">'
            . htmlspecialchars($pctLabel) . ': <strong>' . $percent . '%</strong></p>';
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function buildPdfStackedBarsChartContent(
        array $rows,
        bool $formatPosition = false,
        string $nameKey = 'group_name',
        int $maxRows = 8
    ): string {
        $html = '';
        $rendered = 0;

        foreach ($rows as $row) {
            if ($rendered >= $maxRows) {
                break;
            }
            $total = (int)($row['total_obrigacoes'] ?? 0);
            if ($total <= 0) {
                continue;
            }
            $rendered++;
            $realizados = (int)($row['realizados'] ?? 0);
            $pendentes = (int)($row['pendentes'] ?? 0);
            $pctReal = $this->percentRealizados($total, $realizados);
            $pctPend = max(0, round(100 - $pctReal, 1));
            $name = (string)($row[$nameKey] ?? '');
            if ($formatPosition) {
                $name = PositionDisplayHelper::formatForDisplay($name);
            }
            if (mb_strlen($name) > 22) {
                $name = mb_substr($name, 0, 20) . '…';
            }

            $html .= '<div style="margin-bottom:5px;">';
            $html .= '<div style="font-size:7px;margin-bottom:1px;white-space:nowrap;overflow:hidden;">'
                . htmlspecialchars($name)
                . '</div>';
            $html .= '<div style="height:8px;background:#e9ecef;border-radius:1px;overflow:hidden;">';
            if ($realizados > 0) {
                $html .= '<div style="height:8px;width:' . min(100, $pctReal) . '%;background:#198754;float:left;"></div>';
            }
            if ($pendentes > 0) {
                $html .= '<div style="height:8px;width:' . min(100, $pctPend) . '%;background:#dc3545;float:left;"></div>';
            }
            $html .= '</div></div>';
        }

        if ($rendered === 0) {
            return '<p style="color:#888;text-align:center;font-size:8px;margin:20px 0;">Nenhum dado no recorte.</p>';
        }

        if (count(array_filter($rows, static fn(array $r): bool => (int)($r['total_obrigacoes'] ?? 0) > 0)) > $maxRows) {
            $html .= '<p style="font-size:7px;color:#999;text-align:center;margin:4px 0 0;">+ registros na tabela abaixo</p>';
        }

        return $html;
    }

    private function buildPdfSummaryChart(int $total, int $realizados, int $pendentes): string
    {
        $html = '<h3 style="margin:12px 0 6px;">Resumo visual</h3>';
        $html .= $this->buildPdfSummaryChartContent($total, $realizados, $pendentes, $this->percentRealizados($total, $realizados), false);

        return $html;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function buildPdfStackedBarsChart(
        string $title,
        array $rows,
        bool $formatPosition = false,
        string $nameKey = 'group_name'
    ): string {
        $html = '<h3 style="margin:12px 0 6px;">' . htmlspecialchars($title) . ' (Realizados vs Pendentes)</h3>';
        $html .= '<div style="margin-bottom:16px;">';

        $rendered = false;
        foreach ($rows as $row) {
            $total = (int)($row['total_obrigacoes'] ?? 0);
            if ($total <= 0) {
                continue;
            }
            $rendered = true;
            $realizados = (int)($row['realizados'] ?? 0);
            $pendentes = (int)($row['pendentes'] ?? 0);
            $pctReal = $this->percentRealizados($total, $realizados);
            $pctPend = max(0, round(100 - $pctReal, 1));
            $name = (string)($row[$nameKey] ?? '');
            if ($formatPosition) {
                $name = PositionDisplayHelper::formatForDisplay($name);
            }

            $html .= '<div style="margin-bottom:8px;">';
            $html .= '<div style="font-size:9px;margin-bottom:2px;">'
                . htmlspecialchars($name)
                . ' — <span style="color:#198754;">' . $realizados . ' realiz.</span>'
                . ' · <span style="color:#dc3545;">' . $pendentes . ' pend.</span>'
                . '</div>';
            $html .= '<div style="height:10px;background:#e9ecef;border-radius:2px;overflow:hidden;">';
            if ($realizados > 0) {
                $html .= '<div style="height:10px;width:' . min(100, $pctReal) . '%;background:#198754;float:left;"></div>';
            }
            if ($pendentes > 0) {
                $html .= '<div style="height:10px;width:' . min(100, $pctPend) . '%;background:#dc3545;float:left;"></div>';
            }
            $html .= '</div></div>';
        }

        if (!$rendered) {
            $html .= '<p style="color:#888;text-align:center;">Nenhum dado no recorte.</p>';
        }

        $html .= '</div>';

        return $html;
    }



    /**
     * @param array{departamento: ?int, cargo: ?int, colaborador: ?int} $filters
     */
    private function exportPdf(array $filters, string $section): void
    {
        $html = '<div style="margin: 16px; font-family: Arial, sans-serif; font-size: 10px;">';
        $html .= '<h2 style="text-align:center;">Dashboard de Necessidades de Treinamento</h2>';
        $filename = 'dashboard_necessidades_treinamento.pdf';

        switch ($section) {
            case 'departamento':
                $html .= $this->buildPdfGroupTable(
                    'Por Departamento',
                    'Departamento',
                    $this->trainingUsersRepo->getComplianceStatsByDepartmentAll($filters),
                    false
                );
                $filename = 'necessidades_por_departamento.pdf';
                break;
            case 'cargo':
                $html .= $this->buildPdfGroupTable(
                    'Por Cargo',
                    'Cargo',
                    $this->trainingUsersRepo->getComplianceStatsByPositionAll($filters),
                    true
                );
                $filename = 'necessidades_por_cargo.pdf';
                break;
            case 'colaborador':
                $html .= $this->buildPdfUserTable($this->trainingUsersRepo->getComplianceStatsByUserAll($filters));
                $filename = 'necessidades_por_colaborador.pdf';
                break;
            default:
                header('HTTP/1.1 400 Bad Request');
                echo 'Seção de exportação inválida.';
                exit;
        }

        $html .= '</div>';

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    /**
     * @param array{departamento: ?int, cargo: ?int, colaborador: ?int} $filters
     */
    private function exportExcel(array $filters, string $section): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $filename = 'dashboard_necessidades_treinamento.xlsx';

        switch ($section) {
            case 'departamento':
                $this->writeExcelGroupSheet(
                    $spreadsheet,
                    'Por Departamento',
                    'Departamento',
                    $this->trainingUsersRepo->getComplianceStatsByDepartmentAll($filters),
                    false
                );
                $filename = 'necessidades_por_departamento.xlsx';
                break;
            case 'cargo':
                $this->writeExcelGroupSheet(
                    $spreadsheet,
                    'Por Cargo',
                    'Cargo',
                    $this->trainingUsersRepo->getComplianceStatsByPositionAll($filters),
                    true
                );
                $filename = 'necessidades_por_cargo.xlsx';
                break;
            case 'colaborador':
                $this->writeExcelUserSheet($spreadsheet, $this->trainingUsersRepo->getComplianceStatsByUserAll($filters));
                $filename = 'necessidades_por_colaborador.xlsx';
                break;
            case 'completo':
                $summary = $this->getSummaryTotals($filters);
                $this->writeExcelSummarySheet($spreadsheet, $summary);
                $this->writeExcelGroupSheet(
                    $spreadsheet,
                    'Por Departamento',
                    'Departamento',
                    $this->trainingUsersRepo->getComplianceStatsByDepartmentAll($filters),
                    false
                );
                $this->writeExcelGroupSheet(
                    $spreadsheet,
                    'Por Cargo',
                    'Cargo',
                    $this->trainingUsersRepo->getComplianceStatsByPositionAll($filters),
                    true
                );
                $this->writeExcelUserSheet($spreadsheet, $this->trainingUsersRepo->getComplianceStatsByUserAll($filters));
                $filename = 'dashboard_necessidades_completo.xlsx';
                break;
            default:
                header('HTTP/1.1 400 Bad Request');
                echo 'Seção de exportação inválida.';
                exit;
        }

        $spreadsheet->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }



    /**

     * @param array<string, mixed> $summary

     */

    private function writeExcelSummarySheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, array $summary): void

    {

        $sheet = $spreadsheet->createSheet();

        $sheet->setTitle('Resumo');



        $sheet->setCellValue('A1', 'Indicador');

        $sheet->setCellValue('B1', 'Valor');

        $sheet->setCellValue('C1', '%');



        $rows = [

            ['Total de obrigações', (int)($summary['totalObrigacoes'] ?? 0), '100%'],

            ['Realizados', (int)($summary['totalRealizados'] ?? 0), $this->percentRealizados(

                (int)($summary['totalObrigacoes'] ?? 0),

                (int)($summary['totalRealizados'] ?? 0)

            ) . '%'],

            ['Pendentes', (int)($summary['totalPendentes'] ?? 0), $summary['totalObrigacoes'] > 0

                ? round(((int)$summary['totalPendentes'] / (int)$summary['totalObrigacoes']) * 100, 1) . '%'

                : '0%'],

            ['Treinamentos cadastrados (códigos)', (int)($summary['totalCadastrados'] ?? 0), ''],

        ];



        $row = 2;

        foreach ($rows as $item) {

            $sheet->setCellValue('A' . $row, $item[0]);

            $sheet->setCellValue('B' . $row, $item[1]);

            $sheet->setCellValue('C' . $row, $item[2]);

            $row++;

        }



        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        foreach (range('A', 'C') as $col) {

            $sheet->getColumnDimension($col)->setAutoSize(true);

        }

    }



    /**

     * @param array<int, array<string, mixed>> $rows

     */

    private function writeExcelGroupSheet(

        \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet,

        string $title,

        string $groupLabel,

        array $rows,

        bool $formatPosition

    ): void {

        $sheet = $spreadsheet->createSheet();

        $sheet->setTitle($title);



        $headers = [$groupLabel, 'Total', 'Realizados', 'Pendentes', 'Realizados %'];

        $sheet->fromArray($headers, null, 'A1');



        $rowNum = 2;

        foreach ($rows as $row) {

            $total = (int)($row['total_obrigacoes'] ?? 0);

            $realizados = (int)($row['realizados'] ?? 0);

            $name = (string)($row['group_name'] ?? '');

            if ($formatPosition) {

                $name = PositionDisplayHelper::formatForDisplay($name);

            }



            $sheet->setCellValue('A' . $rowNum, $name);

            $sheet->setCellValue('B' . $rowNum, $total);

            $sheet->setCellValue('C' . $rowNum, $realizados);

            $sheet->setCellValue('D' . $rowNum, (int)($row['pendentes'] ?? 0));

            $sheet->setCellValue('E' . $rowNum, $this->percentRealizados($total, $realizados) . '%');

            $rowNum++;

        }



        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        foreach (range('A', 'E') as $col) {

            $sheet->getColumnDimension($col)->setAutoSize(true);

        }

    }



    /**

     * @param array<int, array<string, mixed>> $rows

     */

    private function writeExcelUserSheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, array $rows): void

    {

        $sheet = $spreadsheet->createSheet();

        $sheet->setTitle('Por Colaborador');



        $headers = ['Cargo', 'Colaborador', 'Departamento', 'Nº Treinamentos', 'Realizados', 'Realizados %', 'Pendentes'];

        $sheet->fromArray($headers, null, 'A1');



        $rowNum = 2;

        foreach ($rows as $row) {

            $total = (int)($row['total_obrigacoes'] ?? 0);

            $realizados = (int)($row['realizados'] ?? 0);



            $sheet->setCellValue('A' . $rowNum, PositionDisplayHelper::formatForDisplay((string)($row['position_name'] ?? '')));

            $sheet->setCellValue('B' . $rowNum, (string)($row['user_name'] ?? ''));

            $sheet->setCellValue('C' . $rowNum, (string)($row['department_name'] ?? ''));

            $sheet->setCellValue('D' . $rowNum, $total);

            $sheet->setCellValue('E' . $rowNum, $realizados);

            $sheet->setCellValue('F' . $rowNum, $this->percentRealizados($total, $realizados) . '%');

            $sheet->setCellValue('G' . $rowNum, (int)($row['pendentes'] ?? 0));

            $rowNum++;

        }



        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        foreach (range('A', 'G') as $col) {

            $sheet->getColumnDimension($col)->setAutoSize(true);

        }

    }



    /**

     * @param array<int, array<string, mixed>> $rows

     */

    private function buildPdfGroupTable(string $title, string $groupLabel, array $rows, bool $formatPosition): string

    {

        $html = '<h3 style="margin:12px 0 6px;">' . htmlspecialchars($title) . '</h3>';

        $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="margin-bottom:12px; border-collapse:collapse;">';

        $html .= '<thead><tr style="background:#f0f0f0;">';

        foreach ([$groupLabel, 'Total', 'Realizados', 'Pendentes', 'Realizados %'] as $header) {

            $html .= '<th>' . htmlspecialchars($header) . '</th>';

        }

        $html .= '</tr></thead><tbody>';



        foreach ($rows as $row) {

            $total = (int)($row['total_obrigacoes'] ?? 0);

            $realizados = (int)($row['realizados'] ?? 0);

            $name = (string)($row['group_name'] ?? '');

            if ($formatPosition) {

                $name = PositionDisplayHelper::formatForDisplay($name);

            }



            $html .= '<tr>';

            $html .= '<td>' . htmlspecialchars($name) . '</td>';

            $html .= '<td style="text-align:center;">' . $total . '</td>';

            $html .= '<td style="text-align:center;">' . $realizados . '</td>';

            $html .= '<td style="text-align:center;">' . (int)($row['pendentes'] ?? 0) . '</td>';

            $html .= '<td style="text-align:center;">' . $this->percentRealizados($total, $realizados) . '%</td>';

            $html .= '</tr>';

        }



        if ($rows === []) {

            $html .= '<tr><td colspan="5" style="text-align:center; color:#888;">Nenhum dado encontrado.</td></tr>';

        }



        $html .= '</tbody></table>';



        return $html;

    }



    /**

     * @param array<int, array<string, mixed>> $rows

     */

    private function buildPdfUserTable(array $rows): string

    {

        $html = '<h3 style="margin:12px 0 6px;">Por Colaborador</h3>';

        $html .= '<table border="1" cellpadding="4" cellspacing="0" width="100%" style="border-collapse:collapse;">';

        $html .= '<thead><tr style="background:#f0f0f0;">';

        foreach (['Cargo', 'Colaborador', 'Dept.', 'Nº Trein.', 'Realiz.', 'Realiz. %', 'Pend.'] as $header) {

            $html .= '<th>' . htmlspecialchars($header) . '</th>';

        }

        $html .= '</tr></thead><tbody>';



        foreach ($rows as $row) {

            $total = (int)($row['total_obrigacoes'] ?? 0);

            $realizados = (int)($row['realizados'] ?? 0);



            $html .= '<tr>';

            $html .= '<td>' . htmlspecialchars(PositionDisplayHelper::formatForDisplay((string)($row['position_name'] ?? ''))) . '</td>';

            $html .= '<td>' . htmlspecialchars((string)($row['user_name'] ?? '')) . '</td>';

            $html .= '<td>' . htmlspecialchars((string)($row['department_name'] ?? '')) . '</td>';

            $html .= '<td style="text-align:center;">' . $total . '</td>';

            $html .= '<td style="text-align:center;">' . $realizados . '</td>';

            $html .= '<td style="text-align:center;">' . $this->percentRealizados($total, $realizados) . '%</td>';

            $html .= '<td style="text-align:center;">' . (int)($row['pendentes'] ?? 0) . '</td>';

            $html .= '</tr>';

        }



        if ($rows === []) {

            $html .= '<tr><td colspan="7" style="text-align:center; color:#888;">Nenhum colaborador encontrado.</td></tr>';

        }



        $html .= '</tbody></table>';



        return $html;

    }

}


