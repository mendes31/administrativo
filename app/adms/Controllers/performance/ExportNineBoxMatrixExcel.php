<?php

namespace App\adms\Controllers\performance;

use App\adms\Models\Repository\PerformanceReviewsRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Exception;

/**
 * Controller para exportar Matriz 9BOX em Excel
 */
class ExportNineBoxMatrixExcel
{
    public function index(): void
    {
        try {
            if (ob_get_length()) ob_end_clean();
            
            $repository = new PerformanceReviewsRepository();
            
            // Filtros
            $filters = [];
            if (!empty($_GET['department_id'])) {
                $filters['department_id'] = (int)$_GET['department_id'];
            }
            if (!empty($_GET['position_id'])) {
                $filters['position_id'] = (int)$_GET['position_id'];
            }
            if (!empty($_GET['period_start'])) {
                $filters['period_start'] = $_GET['period_start'];
            }
            if (!empty($_GET['period_end'])) {
                $filters['period_end'] = $_GET['period_end'];
            }
            
            $matrixData = $repository->getNineBoxData($filters);
            
            $spreadsheet = new Spreadsheet();
            
            // Aba 1: Resumo da Matriz
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Matriz 9BOX');
            $this->createMatrixSheet($sheet, $matrixData, $filters);
            
            // Aba 2: Detalhamento
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Detalhamento');
            $this->createDetailSheet($sheet2, $matrixData);
            
            // Aba 3: Por Box
            $sheet3 = $spreadsheet->createSheet();
            $sheet3->setTitle('Por Box');
            $this->createByBoxSheet($sheet3, $matrixData);
            
            $spreadsheet->setActiveSheetIndex(0);
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $filename = "Matriz_9BOX_" . date('Y-m-d') . ".xlsx";
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
            
        } catch (Exception $e) {
            error_log("Erro ao exportar Matriz 9BOX Excel: " . $e->getMessage());
            echo "<h1>Erro ao gerar Excel</h1>";
            echo "<p>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    private function createMatrixSheet($sheet, array $matrixData, array $filters): void
    {
        $boxes = $matrixData['boxes'];
        $total = $matrixData['total'];
        
        // Título
        $sheet->setCellValue('A1', 'Matriz 9BOX - Potencial vs Desempenho');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Informações
        $row = 3;
        $sheet->setCellValue('A' . $row, 'Total de Colaboradores Mapeados:');
        $sheet->setCellValue('B' . $row, $total);
        $row++;
        $sheet->setCellValue('A' . $row, 'Data de Geração:');
        $sheet->setCellValue('B' . $row, date('d/m/Y H:i:s'));
        
        // Cabeçalho da matriz
        $row = 6;
        $sheet->setCellValue('A' . $row, 'Potencial');
        $sheet->setCellValue('B' . $row, 'Baixo Desempenho (0-6)');
        $sheet->setCellValue('C' . $row, 'Desempenho Médio (6-8)');
        $sheet->setCellValue('D' . $row, 'Alto Desempenho (8-10)');
        
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E9263']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        
        $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($headerStyle);
        
        // Dados da matriz
        $rows = [
            ['level' => 'Alto Potencial', 'boxes' => [7, 8, 9]],
            ['level' => 'Potencial Médio', 'boxes' => [4, 5, 6]],
            ['level' => 'Baixo Potencial', 'boxes' => [1, 2, 3]]
        ];
        
        $row++;
        foreach ($rows as $matrixRow) {
            $sheet->setCellValue('A' . $row, $matrixRow['level']);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            
            $col = 'B';
            foreach ($matrixRow['boxes'] as $boxNum) {
                $boxData = $boxes[$boxNum] ?? [];
                $count = count($boxData);
                
                $boxLabels = [
                    1 => 'Box 1: Reposicionar', 2 => 'Box 2: Manter', 3 => 'Box 3: Desenvolver',
                    4 => 'Box 4: Monitorar', 5 => 'Box 5: Manter', 6 => 'Box 6: Desenvolver',
                    7 => 'Box 7: Desenvolver', 8 => 'Box 8: Promover', 9 => 'Box 9: Estrela'
                ];
                
                $value = $boxLabels[$boxNum] . "\n(" . $count . " colaborador" . ($count != 1 ? 'es' : '') . ")";
                
                if (!empty($boxData)) {
                    $value .= "\n\n";
                    foreach ($boxData as $emp) {
                        $value .= "• " . $emp['employee_name'] . "\n";
                    }
                }
                
                $sheet->setCellValue($col . $row, $value);
                $sheet->getStyle($col . $row)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                $col++;
            }
            
            $sheet->getRowDimension($row)->setRowHeight(80);
            $row++;
        }
        
        // Bordas
        $sheet->getStyle('A6:D' . ($row - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
        ]);
        
        // Ajustar largura das colunas
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(35);
        $sheet->getColumnDimension('D')->setWidth(35);
    }
    
    private function createDetailSheet($sheet, array $matrixData): void
    {
        $employees = $matrixData['employees'];
        
        // Cabeçalho
        $headers = ['Colaborador', 'Departamento', 'Cargo', 'Desempenho', 'Potencial', 'Box', 'Ação Recomendada', 'Data Avaliação'];
        $col = 'A';
        $row = 1;
        
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }
        
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E9263']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
        
        // Dados
        $row = 2;
        $boxActions = [
            1 => 'Reposicionar', 2 => 'Manter', 3 => 'Desenvolver',
            4 => 'Monitorar', 5 => 'Manter', 6 => 'Desenvolver',
            7 => 'Desenvolver', 8 => 'Promover', 9 => 'Estrela'
        ];
        
        foreach ($employees as $emp) {
            $sheet->setCellValue('A' . $row, $emp['employee_name']);
            $sheet->setCellValue('B' . $row, $emp['department_name'] ?? '');
            $sheet->setCellValue('C' . $row, $emp['position_name'] ?? '');
            $sheet->setCellValue('D' . $row, number_format($emp['performance_score'], 1));
            $sheet->setCellValue('E' . $row, number_format($emp['potential_score'], 1));
            $sheet->setCellValue('F' . $row, 'Box ' . $emp['box']);
            $sheet->setCellValue('G' . $row, $boxActions[$emp['box']] ?? '');
            $sheet->setCellValue('H' . $row, date('d/m/Y', strtotime($emp['review_date'])));
            $row++;
        }
        
        // Ajustar largura
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Bordas
        $sheet->getStyle('A1:H' . ($row - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
    }
    
    private function createByBoxSheet($sheet, array $matrixData): void
    {
        $boxes = $matrixData['boxes'];
        $boxLabels = [
            1 => 'Box 1: Reposicionar', 2 => 'Box 2: Manter', 3 => 'Box 3: Desenvolver',
            4 => 'Box 4: Monitorar', 5 => 'Box 5: Manter', 6 => 'Box 6: Desenvolver',
            7 => 'Box 7: Desenvolver', 8 => 'Box 8: Promover', 9 => 'Box 9: Estrela'
        ];
        
        $row = 1;
        foreach ($boxes as $boxNum => $employees) {
            if (empty($employees)) continue;
            
            $sheet->setCellValue('A' . $row, $boxLabels[$boxNum] . ' (' . count($employees) . ' colaboradores)');
            $sheet->mergeCells('A' . $row . ':F' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row)->getFill()
                  ->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FFE9ECEF');
            $row++;
            
            // Cabeçalho
            $headers = ['Colaborador', 'Departamento', 'Cargo', 'Desempenho', 'Potencial', 'Data'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $col++;
            }
            
            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D3D3D3']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
            
            $row++;
            
            // Dados
            foreach ($employees as $emp) {
                $sheet->setCellValue('A' . $row, $emp['employee_name']);
                $sheet->setCellValue('B' . $row, $emp['department_name'] ?? '');
                $sheet->setCellValue('C' . $row, $emp['position_name'] ?? '');
                $sheet->setCellValue('D' . $row, number_format($emp['performance_score'], 1));
                $sheet->setCellValue('E' . $row, number_format($emp['potential_score'], 1));
                $sheet->setCellValue('F' . $row, date('d/m/Y', strtotime($emp['review_date'])));
                $row++;
            }
            
            $row += 2; // Espaço entre boxes
        }
        
        // Ajustar largura
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}

