<?php

declare(strict_types=1);

namespace App\adms\Controllers\projects;

use App\adms\Helpers\PdfInstitutionalHeaderHelper;
use App\adms\Models\Services\SapProjects\SapProjectsFichaPdfService;
use App\adms\Models\Services\SapProjects\SapProjectsService;
use InvalidArgumentException;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

/**
 * JSON do Dashboard/Relatórios SAP de Projetos, ficha PDF e exportação XLSX.
 *
 * action: options | dashboard | detail | pdf | report | export
 */
class SapProjectsData
{
    public function index(): void
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        $input = $this->readInput();
        $action = (string) ($input['action'] ?? 'dashboard');

        try {
            $service = new SapProjectsService();
            if ($action === 'export') {
                $this->exportXlsx($service, (string) ($input['report'] ?? ''), $input);
                exit;
            }
            if ($action === 'pdf') {
                $this->exportPdf($service, (int) ($input['projeto'] ?? 0));
                exit;
            }

            $payload = match ($action) {
                'options' => $service->options(),
                'detail' => $service->projectDetail(
                    (int) ($input['projeto'] ?? 0),
                    (string) ($input['with'] ?? 'summary')
                ),
                'report' => $service->report((string) ($input['report'] ?? ''), $input),
                default => $service->dashboard($input),
            };
            $this->json(['success' => true] + $payload);
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'error' => mb_substr($e->getMessage(), 0, 2000)], 500);
        }
        exit;
    }

    private function exportPdf(SapProjectsService $service, int $projectId): void
    {
        $detail = $service->projectDetail($projectId, 'all');
        $projeto = is_array($detail['projeto'] ?? null) ? $detail['projeto'] : [];
        $pdfService = new SapProjectsFichaPdfService();
        $html = $pdfService->buildHtml($detail);
        $headerMeta = SapProjectsFichaPdfService::documentHeaderMeta($projeto);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        @ini_set('pcre.backtrack_limit', '5000000');
        @ini_set('display_errors', '0');

        $tempDir = (defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 3))
            . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'mpdf';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        // Margem fixa (sem setAutoTopMargin=stretch): evita faixa vazia abaixo do cabeçalho.
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 28,
            'margin_bottom' => 16,
            'margin_header' => 5,
            'margin_footer' => 8,
            'tempDir' => $tempDir,
        ]);
        $mpdf->SetTitle($headerMeta['title'] . ($headerMeta['subtitle'] !== '' ? ' — ' . $headerMeta['subtitle'] : ''));
        $mpdf->SetAuthor('Tiaraju — Projetos SAP');
        $mpdf->SetFooter('<div style="font-size:8pt;color:#8A9189;">Ficha projeto SAP||{PAGENO}/{nbpg}</div>');
        PdfInstitutionalHeaderHelper::applyRepeatingHeader($mpdf, $headerMeta['title'], $headerMeta['subtitle']);
        $mpdf->WriteHTML($html);

        $num = (int) ($projeto['NumProjeto'] ?? $projectId);
        $slug = preg_replace('/\W+/', '_', (string) ($projeto['NomeProjeto'] ?? 'projeto')) ?: 'projeto';
        $mpdf->Output('Ficha_Projeto_' . $num . '_' . $slug . '.pdf', 'I');
    }

    /** @param array<string, mixed> $input */
    private function exportXlsx(SapProjectsService $service, string $report, array $input): void
    {
        $data = $service->report($report, $input);
        $columns = $data['columns'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr(preg_replace('~[\\\\/?*\[\]:]~', ' ', $data['title']) ?? 'Relatorio', 0, 31));

        $sheet->setCellValue('A1', $data['title'] . ' — SAP B1 Gerenciamento de Projetos');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->setCellValue('A2', 'Gerado em ' . date('d/m/Y H:i'));
        $sheet->setCellValue('A3', 'Filtros: ' . ($data['filters_text'] !== [] ? implode(' · ', $data['filters_text']) : 'nenhum'));
        $line = 4;
        if (!empty($data['aviso'])) {
            $sheet->setCellValue('A4', 'Aviso: ' . $data['aviso']);
            $line = 5;
        }

        $headerRow = $line + 1;
        foreach ($columns as $i => $col) {
            $cell = Coordinate::stringFromColumnIndex($i + 1) . $headerRow;
            $sheet->setCellValue($cell, $col['label']);
        }
        $lastCol = Coordinate::stringFromColumnIndex(count($columns));
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getFont()->setBold(true);

        $r = $headerRow + 1;
        foreach ($data['rows'] as $row) {
            foreach ($columns as $i => $col) {
                $cell = Coordinate::stringFromColumnIndex($i + 1) . $r;
                $value = $row[$col['key']] ?? null;
                if ($col['type'] === 'date' && is_string($value) && $value !== '') {
                    $p = explode('-', $value);
                    $value = count($p) === 3 ? "{$p[2]}/{$p[1]}/{$p[0]}" : $value;
                }
                $sheet->setCellValue($cell, $value);
                if ($col['type'] === 'money') {
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0.00');
                }
            }
            if (!empty($row['_excluido'])) {
                $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFont()->setStrikethrough(true)->getColor()->setRGB('8A9189');
            }
            $r++;
        }

        $sheet->setCellValue("A{$r}", 'Total (sem cancelados)');
        foreach ($columns as $i => $col) {
            if (array_key_exists($col['key'], $data['totals'])) {
                $cell = Coordinate::stringFromColumnIndex($i + 1) . $r;
                $sheet->setCellValue($cell, $data['totals'][$col['key']]);
                if ($col['type'] === 'money') {
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0.00');
                }
            }
        }
        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFont()->setBold(true);

        foreach (range(1, count($columns)) as $i) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }

        $filename = 'projetos_sap_' . $report . '_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        (new Xlsx($spreadsheet))->save('php://output');
    }

    /** @return array<string, mixed> */
    private function readInput(): array
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (stripos($contentType, 'application/json') !== false) {
                $decoded = json_decode((string) file_get_contents('php://input'), true);
                return is_array($decoded) ? $decoded : [];
            }
            return $_POST;
        }

        return $_GET;
    }

    /** @param array<string, mixed> $payload */
    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
