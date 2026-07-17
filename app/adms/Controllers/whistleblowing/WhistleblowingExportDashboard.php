<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Models\Repository\LogsRepository;
use App\adms\Models\Repository\WhistleblowingConfigRepository;
use App\adms\Models\Repository\WhistleblowingReportsRepository;
use App\adms\Models\Services\WhistleblowingPermissionService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Exporta indicadores do dashboard (aging, SLA, totais).
 */
final class WhistleblowingExportDashboard
{
    public function index(): void
    {
        $dateFrom = $this->validDate((string) ($_GET['date_from'] ?? ''));
        $dateTo = $this->validDate((string) ($_GET['date_to'] ?? ''));
        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }
        $scopeFilters = WhistleblowingPermissionService::applyReportScopeFilters([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);
        $repo = new WhistleblowingReportsRepository();
        $stats = $repo->getDashboardStats($scopeFilters);

        if (($_ENV['APP_LOGS'] ?? '') === 'Sim') {
            $period = ($dateFrom !== '' || $dateTo !== '')
                ? ' Período: ' . ($dateFrom !== '' ? $dateFrom : 'início') . ' a ' . ($dateTo !== '' ? $dateTo : 'hoje') . '.'
                : '';
            (new LogsRepository())->insertLogs([
                'table_name' => 'adms_whistleblowing_reports',
                'action' => 'exportação',
                'record_id' => 0,
                'description' => 'Exportação Excel do dashboard do Canal de Denúncias.' . $period,
            ]);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumo');
        $sheet->fromArray(['Indicador', 'Valor'], null, 'A1');
        $summary = [
            ['Período inicial', $dateFrom !== '' ? $dateFrom : 'Todos'],
            ['Período final', $dateTo !== '' ? $dateTo : 'Todos'],
            ['Total ativas', (int) ($stats['total'] ?? 0)],
            ['Abertas', (int) ($stats['open'] ?? 0)],
            ['Pendentes triagem', (int) ($stats['pending'] ?? 0)],
            ['Críticas abertas', (int) ($stats['critical'] ?? 0)],
            ['Em investigação', (int) ($stats['investigation'] ?? 0)],
            ['SLA 1ª resposta vencido (' . ($stats['sla_label'] ?? '72h') . ')', (int) ($stats['sla_overdue'] ?? 0)],
            ['Tempo médio 1ª resposta (h)', (float) ($stats['avg_response_hours'] ?? 0)],
            ['Tempo médio encerramento (h)', (float) ($stats['avg_closure_hours'] ?? 0)],
        ];
        if (!empty($stats['sla_closure_label'])) {
            $summary[] = [
                'SLA de encerramento vencido (' . $stats['sla_closure_label'] . ')',
                (int) ($stats['sla_closure_overdue'] ?? 0),
            ];
        }
        $config = new WhistleblowingConfigRepository();
        if ($config->isReporterInactivityEnabled()) {
            $summary[] = [
                'Retorno do denunciante vencido (' . $config->getReporterInactivityDays() . ' dias)',
                (int) ($stats['reporter_response_overdue'] ?? 0),
            ];
        }
        $row = 2;
        foreach ($summary as $line) {
            $sheet->setCellValue('A' . $row, $line[0]);
            $sheet->setCellValue('B' . $row, $line[1]);
            $row++;
        }

        $agingSheet = $spreadsheet->createSheet();
        $agingSheet->setTitle('Aging');
        $agingSheet->fromArray(['Protocolo', 'Status', 'Risco', 'Classificação', 'Comitê', 'Dias parado', 'Dias aberta'], null, 'A1');
        $row = 2;
        foreach ($stats['aging'] ?? [] as $item) {
            $agingSheet->setCellValue('A' . $row, (string) ($item['protocol'] ?? ''));
            $agingSheet->setCellValue('B' . $row, (string) ($item['status'] ?? ''));
            $agingSheet->setCellValue('C' . $row, (string) ($item['risk_level'] ?? ''));
            $agingSheet->setCellValue('D' . $row, (string) ($item['category'] ?? ''));
            $agingSheet->setCellValue('E' . $row, (string) ($item['committee_name'] ?? ''));
            $agingSheet->setCellValue('F' . $row, (int) ($item['days_idle'] ?? 0));
            $agingSheet->setCellValue('G' . $row, (int) ($item['days_open'] ?? 0));
            $row++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="dashboard_denuncias_' . date('Y-m-d') . '.xlsx"');
        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    private function validDate(string $date): string
    {
        $date = trim($date);
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date ? $date : '';
    }
}
