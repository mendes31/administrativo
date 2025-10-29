<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmPartnersRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Controller para Exportar Parceiros em Excel
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmExportPartners
{
    public function index(): void
    {
        // Capturar filtros (mesmos da listagem)
        $filters = [
            'search' => $_GET['search'] ?? '',
            'segment' => $_GET['segment'] ?? '',
            'partner_type' => $_GET['partner_type'] ?? '',
            'status' => $_GET['status'] ?? '',
            'responsible_user_id' => $_GET['responsible_user_id'] ?? '',
        ];

        $partnersRepo = new CrmPartnersRepository();
        $partners = $partnersRepo->getAllPartners(1, 10000, $filters)['data']; // Todos os registros

        // Criar Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Parceiros CRM');

        // Cabeçalho
        $headers = ['Código', 'Nome', 'Nome Fantasia', 'Tipo Pessoa', 'CPF/CNPJ', 'Email', 'Telefone', 
                    'Celular', 'Segmento', 'Tipo Parceiro', 'Prioridade', 'Status', 'Responsável', 
                    'Receita Estimada', 'Cadastrado em'];
        
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
        foreach ($partners as $partner) {
            $sheet->setCellValue('A' . $row, $partner['code'] ?? '');
            $sheet->setCellValue('B' . $row, $partner['name'] ?? '');
            $sheet->setCellValue('C' . $row, $partner['trading_name'] ?? '');
            $sheet->setCellValue('D' . $row, $partner['type_person'] ?? '');
            $sheet->setCellValue('E' . $row, $partner['document'] ?? '');
            $sheet->setCellValue('F' . $row, $partner['email'] ?? '');
            $sheet->setCellValue('G' . $row, $partner['phone'] ?? '');
            $sheet->setCellValue('H' . $row, $partner['mobile'] ?? '');
            $sheet->setCellValue('I' . $row, $partner['segment'] ?? '');
            $sheet->setCellValue('J' . $row, $partner['partner_type'] ?? '');
            $sheet->setCellValue('K' . $row, $partner['priority'] ?? '');
            $sheet->setCellValue('L' . $row, $partner['status'] ?? '');
            $sheet->setCellValue('M' . $row, $partner['responsible_name'] ?? '');
            $sheet->setCellValue('N' . $row, 'R$ ' . number_format($partner['estimated_revenue'] ?? 0, 2, ',', '.'));
            $sheet->setCellValue('O' . $row, $partner['created_at'] ? date('d/m/Y H:i', strtotime($partner['created_at'])) : '');
            $row++;
        }

        // Auto-ajustar largura das colunas
        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Gerar arquivo
        $writer = new Xlsx($spreadsheet);
        $filename = 'parceiros_crm_' . date('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}

