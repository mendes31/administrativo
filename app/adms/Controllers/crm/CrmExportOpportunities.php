<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmOpportunitiesRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Controller para Exportar Oportunidades em Excel
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmExportOpportunities
{
    public function index(): void
    {
        // Capturar filtros
        $filters = [
            'search' => $_GET['search'] ?? '',
            'stage_id' => $_GET['stage_id'] ?? '',
            'responsible_user_id' => $_GET['responsible_user_id'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];

        $opportunitiesRepo = new CrmOpportunitiesRepository();
        $opportunities = $opportunitiesRepo->getAllOpportunities($filters, 1, 10000)['data'];

        // Criar Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Oportunidades CRM');

        // Cabeçalho
        $headers = ['Código', 'Título', 'Parceiro', 'Etapa', 'Valor', 'Probabilidade (%)', 
                    'Responsável', 'Status', 'Previsão Fechamento', 'Próxima Ação', 'Criado em'];
        
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFill()
                  ->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FF2E9263');
            $sheet->getStyle($col . '1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }

        // Dados
        $row = 2;
        foreach ($opportunities as $opp) {
            $sheet->setCellValue('A' . $row, $opp['code'] ?? '');
            $sheet->setCellValue('B' . $row, $opp['title'] ?? '');
            $sheet->setCellValue('C' . $row, $opp['partner_name'] ?? '');
            $sheet->setCellValue('D' . $row, $opp['stage_name'] ?? '');
            $sheet->setCellValue('E' . $row, 'R$ ' . number_format($opp['value'] ?? 0, 2, ',', '.'));
            $sheet->setCellValue('F' . $row, ($opp['probability'] ?? 0) . '%');
            $sheet->setCellValue('G' . $row, $opp['responsible_name'] ?? '');
            $sheet->setCellValue('H' . $row, $opp['status'] ?? '');
            $sheet->setCellValue('I' . $row, !empty($opp['expected_close_date']) ? date('d/m/Y', strtotime($opp['expected_close_date'])) : '');
            $sheet->setCellValue('J' . $row, $opp['next_action'] ?? '');
            $sheet->setCellValue('K' . $row, !empty($opp['created_at']) ? date('d/m/Y H:i', strtotime($opp['created_at'])) : '');
            $row++;
        }

        // Auto-ajustar largura
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Gerar arquivo
        $writer = new Xlsx($spreadsheet);
        $filename = 'oportunidades_crm_' . date('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}

