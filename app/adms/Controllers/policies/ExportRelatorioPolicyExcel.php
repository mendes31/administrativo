<?php

namespace App\adms\Controllers\policies;

use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Models\Services\InformativoRelatorioService;
use App\adms\Models\Services\PoliticaRelatorioService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportRelatorioPolicyExcel
{
    public function index(): void
    {
        $policyId = (int) ($_GET['policy_id'] ?? 0);
        if ($policyId <= 0) {
            http_response_code(422);
            echo 'ID inválido';
            return;
        }

        $usuarioFilter = trim((string) ($_GET['usuario_filter'] ?? ''));

        $repo = new PoliciesRepository();
        $policy = $repo->getPolicyById($policyId);
        if (!$policy) {
            http_response_code(404);
            echo 'Política não encontrada';
            return;
        }

        $relatorio = (new PoliticaRelatorioService())->build($policyId, $policy, $usuarioFilter);
        $kpis = $relatorio['kpis'];
        $requiresAck = $relatorio['requires_ack'];

        $spreadsheet = new Spreadsheet();

        $resumo = $spreadsheet->getActiveSheet();
        $resumo->setTitle('Resumo');
        $resumoRows = [
            ['Indicador', 'Valor'],
            ['Total de ativos', $kpis['total']],
            ['Visualizaram', $kpis['visualizaram']],
            ['% Visualização (ativos)', InformativoRelatorioService::formatPct($kpis['pct_visualizacao'])],
            ['Pendentes de visualização', $kpis['pendentes']],
            ['% Pendentes de visualização (ativos)', InformativoRelatorioService::formatPct($kpis['pct_pendentes'])],
            ['Cientes', $requiresAck ? $kpis['cientes'] : 'N/A'],
            ['% Ciência (ativos)', $requiresAck ? InformativoRelatorioService::formatPct($kpis['pct_ciencia']) : 'N/A'],
            ['Pendentes de ciência', $requiresAck ? $kpis['pendentes_ciencia'] : 'N/A'],
            ['% Pendentes de ciência (ativos)', $requiresAck ? InformativoRelatorioService::formatPct($kpis['pct_pendentes_ciencia']) : 'N/A'],
            ['Inativos com histórico', count($relatorio['inativos_historico'])],
            ['Inativos omitidos (sem visualização/ciência)', $relatorio['excluidos_sem_historico']],
        ];
        $resumo->fromArray($resumoRows, null, 'A1');
        $resumo->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2C3E50');
        $resumo->getStyle('A1:B1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        foreach (range('A', 'B') as $c) {
            $resumo->getColumnDimension($c)->setAutoSize(true);
        }

        $this->writeSheet($spreadsheet->createSheet(), 'Ativos', $relatorio['ativos']);
        $this->writeSheet($spreadsheet->createSheet(), 'Inativos histórico', $relatorio['inativos_historico']);

        $writer = new Xlsx($spreadsheet);
        $filename = 'relatorio_politica_' . $policyId . '_' . date('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function writeSheet(Worksheet $sheet, string $title, array $rows): void
    {
        $sheet->setTitle(mb_substr($title, 0, 31));

        $headers = [
            'Usuário',
            'Visualizou',
            'Data Visualização',
            'Está Ciente?',
            'Data da Ciência',
            'Status',
        ];

        $col = 1;
        foreach ($headers as $header) {
            $cell = Coordinate::stringFromColumnIndex($col) . '1';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF2C3E50');
            $sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }

        $row = 2;
        foreach ($rows as $dado) {
            $sheet->setCellValueExplicit(
                'A' . $row,
                (string) ($dado['usuario_nome'] ?? '') . "\n" . (string) ($dado['usuario_email'] ?? ''),
                DataType::TYPE_STRING
            );
            $sheet->setCellValue('B' . $row, (string) ($dado['visualizou'] ?? ''));
            $sheet->setCellValue('C' . $row, (string) ($dado['data_visualizacao'] ?? '-'));
            $sheet->setCellValue('D' . $row, (string) ($dado['esta_ciente'] ?? ''));
            $sheet->setCellValue('E' . $row, (string) ($dado['data_ciencia'] ?? '-'));
            $sheet->setCellValue('F' . $row, (string) ($dado['status'] ?? ''));
            $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
            $row++;
        }

        foreach (range('A', 'F') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }
    }
}
