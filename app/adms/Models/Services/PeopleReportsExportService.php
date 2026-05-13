<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\EmploymentHistoryRepository;
use App\adms\Models\Repository\PerformanceReviewsRepository;
use App\adms\Models\Repository\TrainingApplicationsRepository;
use App\adms\Models\Repository\UsersRepository;

/**
 * Geração de CSV para Relatórios de RH (People Reports).
 */
final class PeopleReportsExportService
{
    private const CSV_DELIMITER = ';';

    /**
     * @param array<string, mixed> $filters Output de {@see PeopleAnalyticsFilterParser::parseFromRequest()}
     */
    public static function outputHeadcountCsv(array $filters): void
    {
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

        $fn = 'people_headcount_' . date('Ymd_His') . '.csv';
        self::sendCsvHeaders($fn);

        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, ['Resumo do período'], self::CSV_DELIMITER);
        fputcsv($out, ['Data início', $filters['period_start']], self::CSV_DELIMITER);
        fputcsv($out, ['Data fim', $filters['period_end']], self::CSV_DELIMITER);
        fputcsv($out, ['Colaboradores (universo filtrado)', (string) ($metrics['total_employees'] ?? 0)], self::CSV_DELIMITER);
        fputcsv($out, ['Ativos (cadastro)', (string) ($metrics['active_employees'] ?? 0)], self::CSV_DELIMITER);
        fputcsv($out, ['Admissões no período', (string) ($metrics['admissions_in_period'] ?? 0)], self::CSV_DELIMITER);
        fputcsv($out, ['Desligamentos no período', (string) ($metrics['terminations_in_period'] ?? 0)], self::CSV_DELIMITER);
        fputcsv($out, ['Taxa de turnover (%)', (string) ($metrics['turnover_rate'] ?? '0')], self::CSV_DELIMITER);
        fputcsv($out, [], self::CSV_DELIMITER);

        fputcsv($out, ['Por departamento (ativos — snapshot fim do período)'], self::CSV_DELIMITER);
        fputcsv($out, ['Departamento', 'Quantidade'], self::CSV_DELIMITER);
        foreach ($metrics['active_department_segments'] ?? [] as $row) {
            fputcsv($out, [
                (string) ($row['name'] ?? ''),
                (string) ($row['count'] ?? 0),
            ], self::CSV_DELIMITER);
        }
        fputcsv($out, [], self::CSV_DELIMITER);

        fputcsv($out, ['Por cargo (top segmentos — ativos)'], self::CSV_DELIMITER);
        fputcsv($out, ['Cargo', 'Quantidade'], self::CSV_DELIMITER);
        foreach ($metrics['active_position_top_segments'] ?? [] as $row) {
            fputcsv($out, [
                (string) ($row['name'] ?? ''),
                (string) ($row['count'] ?? 0),
            ], self::CSV_DELIMITER);
        }
        fputcsv($out, [], self::CSV_DELIMITER);

        fputcsv($out, ['Detalhe — colaboradores (universo filtrado)'], self::CSV_DELIMITER);
        fputcsv($out, [
            'ID', 'Nome', 'Departamento', 'Cargo', 'Status', 'Data admissão', 'Data desligamento',
        ], self::CSV_DELIMITER);
        foreach ($users as $u) {
            fputcsv($out, [
                (string) ($u['id'] ?? ''),
                (string) ($u['name'] ?? ''),
                (string) ($u['name_dep'] ?? ''),
                (string) ($u['name_pos'] ?? ''),
                (string) ($u['status'] ?? ''),
                (string) ($u['data_admissao'] ?? ''),
                (string) ($u['data_desligamento'] ?? ''),
            ], self::CSV_DELIMITER);
        }

        fclose($out);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public static function outputTurnoverCsv(array $filters): void
    {
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

        $ps = $filters['period_start'];
        $pe = $filters['period_end'];

        $terminated = array_values(array_filter($users, static function (array $u) use ($ps, $pe): bool {
            $td = $u['data_desligamento'] ?? null;
            if (empty($td)) {
                return false;
            }

            return $td >= $ps && $td <= $pe;
        }));

        $fn = 'people_turnover_' . date('Ymd_His') . '.csv';
        self::sendCsvHeaders($fn);

        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, ['Resumo'], self::CSV_DELIMITER);
        fputcsv($out, ['Data início', $ps], self::CSV_DELIMITER);
        fputcsv($out, ['Data fim', $pe], self::CSV_DELIMITER);
        fputcsv($out, ['Desligamentos no período', (string) ($metrics['terminations_in_period'] ?? count($terminated))], self::CSV_DELIMITER);
        fputcsv($out, ['Admissões no período', (string) ($metrics['admissions_in_period'] ?? 0)], self::CSV_DELIMITER);
        fputcsv($out, ['Taxa de turnover (%)', (string) ($metrics['turnover_rate'] ?? '0')], self::CSV_DELIMITER);
        fputcsv($out, ['Fórmula', (string) ($metrics['turnover_formula'] ?? '')], self::CSV_DELIMITER);
        fputcsv($out, [], self::CSV_DELIMITER);

        fputcsv($out, ['Desligamentos no período (detalhe)'], self::CSV_DELIMITER);
        fputcsv($out, [
            'ID', 'Nome', 'Departamento', 'Cargo', 'Data desligamento', 'Motivo desligamento', 'Impacto desligamento',
        ], self::CSV_DELIMITER);
        foreach ($terminated as $u) {
            fputcsv($out, [
                (string) ($u['id'] ?? ''),
                (string) ($u['name'] ?? ''),
                (string) ($u['name_dep'] ?? ''),
                (string) ($u['name_pos'] ?? ''),
                (string) ($u['data_desligamento'] ?? ''),
                (string) ($u['motivo_desligamento'] ?? ''),
                (string) ($u['tipo_impacto_desligamento'] ?? ''),
            ], self::CSV_DELIMITER);
        }

        fclose($out);
    }

    public static function outputTrainingCsv(string $periodStart, string $periodEnd): void
    {
        $rows = (new TrainingApplicationsRepository())->listForPeopleReportsByPeriod($periodStart, $periodEnd);
        $fn = 'people_treinamentos_' . date('Ymd_His') . '.csv';
        self::sendCsvHeaders($fn);

        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Período', $periodStart . ' a ' . $periodEnd], self::CSV_DELIMITER);
        fputcsv($out, [], self::CSV_DELIMITER);
        fputcsv($out, [
            'Tipo linha', 'ID aplicação', 'Colaborador', 'Departamento', 'Cargo', 'Treino', 'Código', 'Versão',
            'Status', 'Data realização', 'Data agendada', 'Criação registro', 'Nota',
        ], self::CSV_DELIMITER);
        foreach ($rows as $r) {
            fputcsv($out, [
                (string) ($r['report_row_type'] ?? ''),
                (string) ($r['id'] ?? ''),
                (string) ($r['user_name'] ?? ''),
                (string) ($r['department_name'] ?? ''),
                (string) ($r['position_name'] ?? ''),
                (string) ($r['training_name'] ?? ''),
                (string) ($r['training_code'] ?? ''),
                (string) ($r['training_version'] ?? ''),
                (string) ($r['status'] ?? ''),
                (string) ($r['data_realizacao'] ?? ''),
                (string) ($r['data_agendada'] ?? ''),
                (string) ($r['created_at'] ?? ''),
                (string) ($r['nota'] ?? ''),
            ], self::CSV_DELIMITER);
        }
        fclose($out);
    }

    public static function outputPerformanceCsv(string $periodStart, string $periodEnd): void
    {
        $repo = new PerformanceReviewsRepository();
        $rows = $repo->getAll([
            'review_date_from' => $periodStart,
            'review_date_to' => $periodEnd,
        ], 1, 10000);

        $fn = 'people_desempenho_' . date('Ymd_His') . '.csv';
        self::sendCsvHeaders($fn);

        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Avaliações com data da avaliação no período', $periodStart . ' a ' . $periodEnd], self::CSV_DELIMITER);
        fputcsv($out, [], self::CSV_DELIMITER);
        fputcsv($out, [
            'ID', 'Colaborador', 'Avaliador', 'Tipo', 'Período início', 'Período fim', 'Data avaliação', 'Status', 'Nota global',
        ], self::CSV_DELIMITER);
        foreach ($rows as $r) {
            fputcsv($out, [
                (string) ($r['id'] ?? ''),
                (string) ($r['employee_name'] ?? ''),
                (string) ($r['reviewer_name'] ?? ''),
                (string) ($r['review_type'] ?? ''),
                (string) ($r['review_period_start'] ?? ''),
                (string) ($r['review_period_end'] ?? ''),
                (string) ($r['review_date'] ?? ''),
                (string) ($r['status'] ?? ''),
                (string) ($r['overall_score'] ?? ''),
            ], self::CSV_DELIMITER);
        }
        fclose($out);
    }

    private static function sendCsvHeaders(string $filename): void
    {
        if (headers_sent()) {
            return;
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . str_replace(['"', "\n", "\r"], '', $filename) . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
    }
}
