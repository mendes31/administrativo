<?php
namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class CostCenterSummaryRepository extends DbConnection
{
    public function getSummaryByYear($year)
    {
        $sql = "
            SELECT 
                cc.id as cost_center_id,
                cc.name as cost_center_name,
                MONTH(m.created_at) as month,
                SUM(m.movement_value * IF(m.type = 'Saída', -1, 1)) as total
            FROM adms_movements m
            LEFT JOIN adms_pay ap ON ap.id = m.movement_id AND m.movement = 'Conta à Pagar'
            LEFT JOIN adms_receive ar ON ar.id = m.movement_id AND m.movement = 'Conta à Receber'
            LEFT JOIN adms_cost_center cc ON cc.id = COALESCE(ap.cost_center_id, ar.cost_center_id)
            WHERE YEAR(m.created_at) = :year
            GROUP BY cc.id, cc.name, MONTH(m.created_at)
            ORDER BY cc.name ASC, month ASC
        ";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':year', $year, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Exportação para Excel
    public function exportToExcel($year, $costCenters)
    {
        require_once __DIR__ . '/../../../../vendor/autoload.php';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalho
        $sheet->setCellValue('A1', 'Centro de Custo');
        $months = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
        $col = 'B';
        foreach ($months as $m) {
            $sheet->setCellValue($col . '1', $m);
            $col++;
        }
        $sheet->setCellValue($col . '1', 'Total');

        // Estilo do cabeçalho
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '254e7b']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
        ];
        $sheet->getStyle('A1:' . $col . '1')->applyFromArray($headerStyle);

        // Dados
        $zebra1 = '4a90e2'; // azul forte
        $zebra2 = 'b3d1f7'; // azul claro
        $row = 2;
        foreach ($costCenters as $idx => $cc) {
            $bg = ($idx % 2 == 0) ? $zebra1 : $zebra2;
            $fontColor = ($idx % 2 == 0) ? 'FFFFFF' : '222222';
            $colData = 'A';
            $sheet->setCellValue($colData . $row, $cc['name']);
            $sheet->getStyle($colData . $row)->applyFromArray([
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $bg]
                ],
                'font' => ['color' => ['rgb' => $fontColor]],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
            ]);
            $colData++;
            for ($i=1; $i<=12; $i++) {
                $sheet->setCellValue($colData . $row, $cc['months'][$i]);
                $sheet->getStyle($colData . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
                $sheet->getStyle($colData . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle($colData . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $bg]
                    ],
                    'font' => ['color' => ['rgb' => $fontColor]]
                ]);
                $colData++;
            }
            $sheet->setCellValue($colData . $row, $cc['total']);
            $sheet->getStyle($colData . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
            $sheet->getStyle($colData . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($colData . $row)->applyFromArray([
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $bg]
                ],
                'font' => ['color' => ['rgb' => $fontColor]]
            ]);
            $row++;
        }

        // Largura automática
        foreach (range('A', $col) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Resumo_Centro_Custo_' . $year . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
    // Exportação para PDF
    public function exportToPDF($year, $costCenters)
    {
        require_once __DIR__ . '/../../../../vendor/autoload.php';
        $months = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
        $html = '<h2 style="margin-bottom:20px;">Resumo por Centro de Custo - ' . $year . '</h2>';
        $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;font-size:12px;">';
        $html .= '<thead><tr>';
        $html .= '<th align="left" style="background:#254e7b;color:#fff;">Centro de Custo</th>';
        foreach ($months as $m) {
            $html .= '<th align="right" style="background:#254e7b;color:#fff;">' . $m . '</th>';
        }
        $html .= '<th align="right" style="background:#254e7b;color:#fff;">Total</th>';
        $html .= '</tr></thead><tbody>';
        $zebra1 = '#4a90e2';
        $zebra2 = '#b3d1f7';
        foreach ($costCenters as $idx => $cc) {
            $bg = ($idx % 2 == 0) ? $zebra1 : $zebra2;
            $color = ($idx % 2 == 0) ? '#fff' : '#222';
            $html .= '<tr>';
            $html .= '<td align="left" style="background:'.$bg.';color:'.$color.';">' . htmlspecialchars($cc['name']) . '</td>';
            for ($i=1; $i<=12; $i++) {
                $html .= '<td align="right" style="background:'.$bg.';color:'.$color.';">' . number_format($cc['months'][$i], 2, ',', '.') . '</td>';
            }
            $html .= '<td align="right" style="background:'.$bg.';color:'.$color.';">' . number_format($cc['total'], 2, ',', '.') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        $mpdf = new \Mpdf\Mpdf(['orientation' => 'L']);
        $mpdf->WriteHTML($html);
        $mpdf->Output('Resumo_Centro_Custo_' . $year . '.pdf', 'D');
        exit;
    }

    public function getAllCostCenters()
    {
        $sql = "SELECT id, name FROM adms_cost_center ORDER BY name ASC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 