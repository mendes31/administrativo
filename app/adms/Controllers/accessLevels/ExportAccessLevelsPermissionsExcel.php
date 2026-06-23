<?php

declare(strict_types=1);

namespace App\adms\Controllers\accessLevels;

use App\adms\Models\Repository\AccessLevelsPagesRepository;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportAccessLevelsPermissionsExcel
{
    public function index(): void
    {
        $filterName = isset($_GET['name']) ? trim((string) $_GET['name']) : '';

        $repo = new AccessLevelsPagesRepository();
        $levels = $repo->getPermittedPagesGroupedByAccessLevel($filterName);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Permissões');

        $sheet->setCellValue('A1', 'Relatório de Permissões por Nível de Acesso');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $subtitle = 'Gerado em ' . date('d/m/Y H:i:s') . ' — somente páginas autorizadas';
        if ($filterName !== '') {
            $subtitle .= ' — filtro nome: ' . $filterName;
        }
        $sheet->setCellValue('A2', $subtitle);
        $sheet->mergeCells('A2:D2');
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        $row = 4;

        foreach ($levels as $level) {
            $levelLabel = (string) $level['name'] . ' (ID ' . (int) $level['id'] . ')';
            $sheet->setCellValue('A' . $row, $levelLabel);
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE7F1FF');
            $row++;

            $headers = ['ID Página', 'Nome da Página', 'URL (slug)', 'Grupo'];
            $col = 1;
            foreach ($headers as $header) {
                $cell = Coordinate::stringFromColumnIndex($col) . $row;
                $sheet->setCellValue($cell, $header);
                $sheet->getStyle($cell)->getFont()->setBold(true);
                $sheet->getStyle($cell)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF1F3F5');
                $col++;
            }
            $row++;

            if ($level['pages'] === []) {
                $sheet->setCellValue('A' . $row, '—');
                $sheet->setCellValue('B' . $row, 'Nenhuma página autorizada');
                $row++;
            } else {
                foreach ($level['pages'] as $page) {
                    $sheet->setCellValue('A' . $row, (int) $page['id']);
                    $sheet->setCellValue('B' . $row, $page['name']);
                    $sheet->setCellValue('C' . $row, $page['controller_url']);
                    $sheet->setCellValue('D' . $row, $page['group_name']);
                    $row++;
                }
            }

            $row++;
        }

        foreach (range('A', 'D') as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
        $sheet->getStyle('A1:D' . max(4, $row - 1))->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        $filename = 'permissoes_niveis_acesso_' . date('Y-m-d_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
