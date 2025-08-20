<?php

namespace App\adms\Controllers\logs;

use App\adms\Models\Repository\LogAlteracoesRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportLogExcel
{
    public function index(): void
    {
        $filtros = $this->getFiltros();
        $repo = new LogAlteracoesRepository();
        $logs = $repo->getAll(1, 10000, $filtros);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // Cabeçalhos
        $headers = ['ID', 'Tabela', 'ID Objeto', 'Identificador', 'Usuário', 'Data/Hora', 'Tipo', 'IP', 'User Agent'];
        $sheet->fromArray($headers, null, 'A1');
        // Dados
        $row = 2;
        foreach ($logs as $log) {
            $sheet->setCellValue('A' . $row, $log['id']);
            $sheet->setCellValue('B' . $row, $log['tabela']);
            $sheet->setCellValue('C' . $row, $log['objeto_id']);
            $sheet->setCellValue('D' . $row, $log['identificador']);
            $sheet->setCellValue('E' . $row, $log['usuario_nome'] ?: $log['usuario_id']);
            $sheet->setCellValue('F' . $row, date('d/m/Y H:i:s', strtotime($log['data_alteracao'])));
            $sheet->setCellValue('G' . $row, $log['tipo_operacao']);
            $sheet->setCellValue('H' . $row, $log['ip']);
            $sheet->setCellValue('I' . $row, $log['user_agent']);
            $row++;
        }
        // Ajustar largura das colunas
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        // Download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="logs_alteracoes_' . date('Y-m-d_H-i-s') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    
    private function getFiltros(): array
    {
        return [
            'tabela' => $_GET['tabela'] ?? '',
            'objeto_id' => $_GET['objeto_id'] ?? '',
            'identificador' => $_GET['identificador'] ?? '',
            'usuario_nome' => $_GET['usuario_nome'] ?? '',
            'tipo' => $_GET['tipo'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
        ];
    }
} 