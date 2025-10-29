<?php

namespace App\adms\Controllers\crm;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CrmDownloadTemplateOpportunities
{
    public function index(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Oportunidades');

        $headers = ['Código*', 'Título*', 'Código Parceiro*', 'Etapa', 'Valor*', 'Probabilidade%', 
                    'Previsão Fechamento', 'Próxima Ação', 'Descrição'];
        
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFill()
                  ->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FF2E9263');
            $sheet->getStyle($col . '1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $col++;
        }

        // Exemplo
        $sheet->setCellValue('A2', 'OPP00999');
        $sheet->setCellValue('B2', 'Venda de Produto XYZ');
        $sheet->setCellValue('C2', 'P00001');
        $sheet->setCellValue('D2', 'Prospecção');
        $sheet->setCellValue('E2', '50000.00');
        $sheet->setCellValue('F2', '60');
        $sheet->setCellValue('G2', '30/12/2025');
        $sheet->setCellValue('H2', 'Enviar proposta');
        $sheet->setCellValue('I2', 'Cliente interessado em...');

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="template_importacao_oportunidades.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}

