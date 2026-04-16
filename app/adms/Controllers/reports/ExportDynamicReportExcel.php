<?php

namespace App\adms\Controllers\reports;

use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Models\Services\DynamicQueryBuilderService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Exporta o resultado completo de um relatório dinâmico (sem paginação da tela) para .xlsx.
 */
class ExportDynamicReportExcel
{
    public function index(?string $id = null): void
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '600');

        if (empty($id)) {
            $_SESSION['error'] = 'ID do relatório não fornecido';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
            exit;
        }

        $repo = new DynamicReportsRepository();
        $report = $repo->getById((int)$id);
        if (!$report) {
            $_SESSION['error'] = 'Relatório não encontrado';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
            exit;
        }

        $viewerId = (int) ($_SESSION['user_id'] ?? 0);
        if (!$repo->userCanAccessReport($report, $viewerId)) {
            $_SESSION['error'] = 'Você não tem permissão para exportar este relatório.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
            exit;
        }

        $report['cache_namespace'] = 'report_' . $id;
        $report['export_all'] = true;
        $report['force_refresh'] = !empty($_GET['refresh']);

        $queryBuilder = new DynamicQueryBuilderService();
        $result = $queryBuilder->executeReport($report);

        if (empty($result['success'])) {
            $_SESSION['error'] = 'Erro ao exportar: ' . ($result['error'] ?? 'desconhecido');
            header('Location: ' . $_ENV['URL_ADM'] . 'view-dynamic-report/' . (int)$id);
            exit;
        }

        $rows = $result['data'] ?? [];
        if ($rows === []) {
            $_SESSION['error'] = 'Nenhum dado para exportar.';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-dynamic-report/' . (int)$id);
            exit;
        }

        $headers = array_keys($rows[0]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $titleBase = preg_replace('/[^\p{L}\p{N} _.-]/u', '', (string)($report['name'] ?? 'Relatorio'));
        $titleBase = trim(mb_substr($titleBase !== '' ? $titleBase : 'Relatorio', 0, 31));
        $sheet->setTitle($titleBase);

        $headerFill = Fill::FILL_SOLID;
        $headerArgb = 'FF2E9263';

        for ($i = 0; $i < count($headers); $i++) {
            $letter = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($letter . '1', $headers[$i]);
            $sheet->getStyle($letter . '1')->getFill()
                ->setFillType($headerFill)
                ->getStartColor()->setARGB($headerArgb);
            $sheet->getStyle($letter . '1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($letter . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $rowNum = 2;
        foreach ($rows as $row) {
            for ($i = 0; $i < count($headers); $i++) {
                $letter = Coordinate::stringFromColumnIndex($i + 1);
                $key = $headers[$i];
                $val = $row[$key] ?? '';
                if (is_array($val) || is_object($val)) {
                    $val = json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } elseif ($val === null) {
                    $val = '';
                } else {
                    $val = (string)$val;
                }
                $sheet->setCellValueExplicit($letter . $rowNum, $val, DataType::TYPE_STRING);
            }
            $rowNum++;
        }

        for ($i = 1; $i <= count($headers); $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $asciiName = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)($report['name'] ?? 'relatorio'));
        $asciiName = $asciiName !== '' ? $asciiName : 'relatorio';
        $filename = $asciiName . '_' . date('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}
