<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Models\Repository\RhCandidatoAnexoAccessLogRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Exportação Excel do log de download de currículos.
 */
class ExportRhCandidatoAnexoAccessLogsExcel
{
    public function index(): void
    {
        $filtros = [
            'actor_nome' => trim((string) ($_GET['actor_nome'] ?? '')),
            'candidato_id' => trim((string) ($_GET['candidato_id'] ?? '')),
            'candidato_nome' => trim((string) ($_GET['candidato_nome'] ?? '')),
            'source' => trim((string) ($_GET['source'] ?? '')),
            'ip' => trim((string) ($_GET['ip'] ?? '')),
            'data_inicio' => trim((string) ($_GET['data_inicio'] ?? '')),
            'data_fim' => trim((string) ($_GET['data_fim'] ?? '')),
        ];

        $repo = new RhCandidatoAnexoAccessLogRepository();
        $logs = $repo->getAll(1, 10000, $filtros);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            'ID',
            'Data/Hora',
            'Ator',
            'E-mail ator',
            'Candidato ID',
            'Candidato',
            'Anexo ID',
            'Ação',
            'Modo',
            'Fonte',
            'Tipo anexo',
            'IP',
            'User-Agent',
            'Path hash',
        ], null, 'A1');

        $row = 2;
        foreach ($logs as $log) {
            $sheet->setCellValue('A' . $row, $log['id'] ?? '');
            $sheet->setCellValue(
                'B' . $row,
                !empty($log['created_at']) ? date('d/m/Y H:i:s', strtotime((string) $log['created_at'])) : ''
            );
            $sheet->setCellValue('C' . $row, $log['actor_name'] ?? '');
            $sheet->setCellValue('D' . $row, $log['actor_email'] ?? '');
            $sheet->setCellValue('E' . $row, $log['rh_candidato_id'] ?? '');
            $sheet->setCellValue('F' . $row, $log['candidato_nome'] ?? '');
            $sheet->setCellValue('G' . $row, $log['rh_candidato_anexo_id'] ?? '');
            $sheet->setCellValue('H' . $row, $log['action'] ?? '');
            $sheet->setCellValue('I' . $row, $log['delivery_mode'] ?? '');
            $sheet->setCellValue('J' . $row, $log['source'] ?? '');
            $sheet->setCellValue('K' . $row, $log['anexo_tipo'] ?? '');
            $sheet->setCellValue('L' . $row, $log['ip_address'] ?? '');
            $sheet->setCellValue('M' . $row, $log['user_agent'] ?? '');
            $sheet->setCellValue('N' . $row, $log['path_hash'] ?? '');
            $row++;
        }

        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="log_download_curriculos_' . date('Y-m-d_H-i-s') . '.xlsx"');
        header('Cache-Control: max-age=0');
        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }
}
