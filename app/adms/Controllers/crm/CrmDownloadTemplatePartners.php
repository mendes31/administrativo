<?php

namespace App\adms\Controllers\crm;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Download template Excel para importação de Parceiros
 */
class CrmDownloadTemplatePartners
{
    public function index(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Parceiros');

        // Cabeçalho
        $headers = ['Código*', 'Nome*', 'Nome Fantasia', 'Tipo Pessoa', 'CPF/CNPJ', 'Email', 'Telefone', 
                    'Celular', 'Segmento*', 'Tipo Parceiro*', 'Prioridade', 'Status', 'Responsável ID', 'Receita Estimada'];
        
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFill()
                  ->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FF2E9263');
            $sheet->getStyle($col . '1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $col++;
        }

        // Linha de exemplo
        $sheet->setCellValue('A2', 'P00999');
        $sheet->setCellValue('B2', 'Exemplo Farmácia Ltda');
        $sheet->setCellValue('C2', 'Farmácia Exemplo');
        $sheet->setCellValue('D2', 'PJ');
        $sheet->setCellValue('E2', '12.345.678/0001-90');
        $sheet->setCellValue('F2', 'contato@exemplo.com.br');
        $sheet->setCellValue('G2', '(41) 3333-4444');
        $sheet->setCellValue('H2', '(41) 99999-8888');
        $sheet->setCellValue('I2', 'Farma');
        $sheet->setCellValue('J2', 'Lead');
        $sheet->setCellValue('K2', 'Alta');
        $sheet->setCellValue('L2', 'Ativo');
        $sheet->setCellValue('M2', '1');
        $sheet->setCellValue('N2', '50000');

        // Auto-ajustar
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'template_importacao_parceiros.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}

