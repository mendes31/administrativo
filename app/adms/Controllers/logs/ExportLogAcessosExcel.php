<?php

namespace App\adms\Controllers\logs;

use App\adms\Models\Repository\LogAcessosRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportLogAcessosExcel
{
    public function index(): void
    {
        $filtros = $this->getFiltros();
        $repo = new LogAcessosRepository();
        $logs = $repo->getAll(1, 10000, $filtros);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // Cabeçalhos
        $headers = ['ID', 'Usuário', 'Email', 'Tipo', 'IP', 'User Agent', 'Data/Hora'];
        $sheet->fromArray($headers, null, 'A1');
        // Dados
        $row = 2;
        foreach ($logs as $log) {
            $sheet->setCellValue('A' . $row, $log['id']);
            $sheet->setCellValue('B' . $row, $log['usuario_nome'] ?? '-');
            $sheet->setCellValue('C' . $row, $log['usuario_email'] ?? '-');
            $sheet->setCellValue('D' . $row, $log['tipo_acesso']);
            $sheet->setCellValue('E' . $row, $log['ip']);
            $sheet->setCellValue('F' . $row, $log['user_agent']);
            $sheet->setCellValue('G' . $row, date('d/m/Y H:i:s', strtotime($log['data_acesso'])));
            $row++;
        }
        // Ajustar largura das colunas
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        // Download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="logs_acessos_' . date('Y-m-d_H-i-s') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    
    private function getFiltros(): array
    {
        return [
            'usuario_nome' => $_GET['usuario_nome'] ?? '',
            'tipo_acesso' => $_GET['tipo_acesso'] ?? '',
            'ip' => $_GET['ip'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
        ];
    }
} 