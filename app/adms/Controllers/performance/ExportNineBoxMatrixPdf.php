<?php

namespace App\adms\Controllers\performance;

use App\adms\Models\Repository\PerformanceReviewsRepository;
use Mpdf\Mpdf;
use Exception;

/**
 * Controller para exportar Matriz 9BOX em PDF
 */
class ExportNineBoxMatrixPdf
{
    public function index(): void
    {
        try {
            if (ob_get_length()) ob_end_clean();
            header('Content-Type: application/pdf');
            
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
            
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-L', // Landscape
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 15,
                'margin_bottom' => 15
            ]);
            
            $html = $this->generatePdfHtml($matrixData, $filters);
            
            $mpdf->SetTitle("Matriz 9BOX - " . date('d/m/Y'));
            $mpdf->SetAuthor("Sistema de Gestão de Pessoas");
            $mpdf->SetCreator("Sistema Administrativo");
            
            $mpdf->WriteHTML($html);
            
            $filename = "Matriz_9BOX_" . date('Y-m-d') . ".pdf";
            $mpdf->Output($filename, 'I');
            exit;
            
        } catch (Exception $e) {
            error_log("Erro ao exportar Matriz 9BOX PDF: " . $e->getMessage());
            echo "<h1>Erro ao gerar PDF</h1>";
            echo "<p>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    private function generatePdfHtml(array $matrixData, array $filters): string
    {
        $boxes = $matrixData['boxes'];
        $total = $matrixData['total'];
        
        $boxLabels = [
            1 => ['title' => 'Reposicionar', 'color' => '#dc3545'],
            2 => ['title' => 'Manter', 'color' => '#ffc107'],
            3 => ['title' => 'Desenvolver', 'color' => '#198754'],
            4 => ['title' => 'Monitorar', 'color' => '#dc3545'],
            5 => ['title' => 'Manter', 'color' => '#ffc107'],
            6 => ['title' => 'Desenvolver', 'color' => '#198754'],
            7 => ['title' => 'Desenvolver', 'color' => '#0dcaf0'],
            8 => ['title' => 'Promover', 'color' => '#0d6efd'],
            9 => ['title' => 'Estrela', 'color' => '#198754']
        ];
        
        $html = '<style>
            body { font-family: Arial, sans-serif; font-size: 9pt; }
            h1 { color: #333; font-size: 18pt; margin-bottom: 10px; }
            h2 { color: #666; font-size: 14pt; margin-top: 15px; margin-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th { background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 8px; text-align: center; font-weight: bold; }
            td { border: 1px solid #dee2e6; padding: 6px; vertical-align: top; }
            .box-header { background-color: #e9ecef; font-weight: bold; text-align: center; }
            .employee-item { margin: 3px 0; font-size: 8pt; }
            .stats { background-color: #f8f9fa; padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        </style>';
        
        $html .= '<h1>Matriz 9BOX - Potencial vs Desempenho</h1>';
        $html .= '<div class="stats">';
        $html .= '<strong>Total de Colaboradores Mapeados:</strong> ' . $total . '<br>';
        $html .= '<strong>Data de Geração:</strong> ' . date('d/m/Y H:i:s');
        if (!empty($filters)) {
            $html .= '<br><strong>Filtros Aplicados:</strong> ';
            $filterText = [];
            if (!empty($filters['department_id'])) $filterText[] = 'Departamento: ' . $filters['department_id'];
            if (!empty($filters['position_id'])) $filterText[] = 'Cargo: ' . $filters['position_id'];
            if (!empty($filters['period_start'])) $filterText[] = 'Período: ' . date('d/m/Y', strtotime($filters['period_start']));
            $html .= implode(', ', $filterText);
        }
        $html .= '</div>';
        
        // Tabela da Matriz
        $html .= '<table>';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th style="width: 12%;">Potencial</th>';
        $html .= '<th style="width: 29.3%; background-color: #ff6b6b; color: white;">Baixo Desempenho<br><small>(0-6)</small></th>';
        $html .= '<th style="width: 29.3%; background-color: #feca57; color: white;">Desempenho Médio<br><small>(6-8)</small></th>';
        $html .= '<th style="width: 29.3%; background-color: #48dbfb; color: white;">Alto Desempenho<br><small>(8-10)</small></th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        $rows = [
            ['level' => 'Alto Potencial', 'boxes' => [7, 8, 9], 'bg' => '#d4edda'],
            ['level' => 'Potencial Médio', 'boxes' => [4, 5, 6], 'bg' => '#fff3cd'],
            ['level' => 'Baixo Potencial', 'boxes' => [1, 2, 3], 'bg' => '#f8d7da']
        ];
        
        foreach ($rows as $row) {
            $html .= '<tr>';
            $html .= '<td class="box-header" style="background-color: ' . $row['bg'] . '; height: 120px; vertical-align: middle;"><strong>' . $row['level'] . '</strong></td>';
            
            foreach ($row['boxes'] as $boxNum) {
                $boxData = $boxes[$boxNum] ?? [];
                $boxLabel = $boxLabels[$boxNum];
                $count = count($boxData);
                
                $html .= '<td style="height: 120px;">';
                $html .= '<div style="text-align: center; margin-bottom: 5px;">';
                $html .= '<strong style="color: ' . $boxLabel['color'] . ';">Box ' . $boxNum . ': ' . $boxLabel['title'] . '</strong><br>';
                $html .= '<small>(' . $count . ' colaborador' . ($count != 1 ? 'es' : '') . ')</small>';
                $html .= '</div>';
                
                if (!empty($boxData)) {
                    $html .= '<div style="font-size: 7pt;">';
                    foreach ($boxData as $employee) {
                        $html .= '<div class="employee-item">';
                        $html .= '• ' . htmlspecialchars($employee['employee_name']);
                        $html .= ' <small>(D: ' . number_format($employee['performance_score'], 1) . 
                                ' | P: ' . number_format($employee['potential_score'], 1) . ')</small>';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                }
                
                $html .= '</td>';
            }
            
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        
        // Lista detalhada por box
        $html .= '<h2>Detalhamento por Box</h2>';
        foreach ($boxes as $boxNum => $employees) {
            if (empty($employees)) continue;
            
            $boxLabel = $boxLabels[$boxNum];
            $html .= '<h3 style="color: ' . $boxLabel['color'] . ';">Box ' . $boxNum . ' - ' . $boxLabel['title'] . ' (' . count($employees) . ' colaboradores)</h3>';
            $html .= '<table>';
            $html .= '<thead><tr>';
            $html .= '<th>Colaborador</th>';
            $html .= '<th>Departamento</th>';
            $html .= '<th>Cargo</th>';
            $html .= '<th>Desempenho</th>';
            $html .= '<th>Potencial</th>';
            $html .= '<th>Data Avaliação</th>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';
            
            foreach ($employees as $emp) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($emp['employee_name']) . '</td>';
                $html .= '<td>' . htmlspecialchars($emp['department_name'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($emp['position_name'] ?? '') . '</td>';
                $html .= '<td style="text-align: center;">' . number_format($emp['performance_score'], 1) . '/10</td>';
                $html .= '<td style="text-align: center;">' . number_format($emp['potential_score'], 1) . '/10</td>';
                $html .= '<td>' . date('d/m/Y', strtotime($emp['review_date'])) . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table><br>';
        }
        
        return $html;
    }
}

