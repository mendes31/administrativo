<?php

declare(strict_types=1);

namespace App\adms\Controllers\logs;

use App\adms\Models\Repository\LogAcessosRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportUsersLastAccessExcel
{
    use UsersLastAccessExportTrait;

    public function index(): void
    {
        $filtros = $this->resolveUsersLastAccessFilters();
        $repo = new LogAcessosRepository();
        $users = $repo->listAllUsersLastLogin($filtros);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Último acesso');

        $sheet->setCellValue('A1', 'Último acesso por usuário');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $subtitle = 'Gerado em ' . date('d/m/Y H:i:s')
            . ' — ' . count($users) . ' registro(s)'
            . ' — Filtros: ' . $this->buildUsersLastAccessFilterSummary($filtros);
        $sheet->setCellValue('A2', $subtitle);
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        $headers = ['Usuário', 'E-mail', '@usuário', 'Status', 'Último login', 'IP'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $sheet->getStyle($col . '4')->getFont()->setBold(true);
            $sheet->getStyle($col . '4')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF1F3F5');
            $col++;
        }

        $row = 5;
        foreach ($users as $user) {
            $sheet->setCellValue('A' . $row, (string) ($user['user_name'] ?? ''));
            $sheet->setCellValue('B' . $row, (string) ($user['user_email'] ?? ''));
            $sheet->setCellValue('C' . $row, (string) ($user['user_username'] ?? ''));
            $sheet->setCellValue('D' . $row, (string) ($user['user_status'] ?? ''));
            $sheet->setCellValue('E' . $row, $this->formatUltimoLogin($user['ultimo_login'] ?? null));
            $sheet->setCellValue('F' . $row, (string) ($user['ultimo_ip'] ?? ''));
            $row++;
        }

        foreach (range('A', 'F') as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
        $sheet->getStyle('A1:F' . max(4, $row - 1))->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        $filename = 'ultimo_acesso_usuarios_' . date('Y-m-d_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
