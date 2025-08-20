<?php
namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class FlowCashCompetenceRepository extends DbConnection
{
    public function getCashFlow($year)
    {
        $conn = $this->getConnection();
        $months = range(1, 12);
        $result = [];

        // Saldo Inicial: apenas contas Corrente
        $stmt = $conn->query("SELECT SUM(balance) as saldo_inicial FROM adms_bank_accounts WHERE type = 'Corrente'");
        $saldoInicial = (float) $stmt->fetch(PDO::FETCH_ASSOC)['saldo_inicial'];
        // Saldo Aplicacoes: apenas contas Aplicação
        $stmt = $conn->query("SELECT SUM(balance) as saldo_aplicacoes FROM adms_bank_accounts WHERE type = 'Aplicação'");
        $saldoAplicacoes = (float) $stmt->fetch(PDO::FETCH_ASSOC)['saldo_aplicacoes'];

        $acumulado = 0;
        foreach ($months as $m) {
            $mes = str_pad($m, 2, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare('SELECT SUM(movement_value) as receita FROM adms_movements WHERE type = "Entrada" AND YEAR(created_at) = :year AND MONTH(created_at) = :month');
            $stmt->execute(['year' => $year, 'month' => $mes]);
            $receita = (float) $stmt->fetch(PDO::FETCH_ASSOC)['receita'];

            $stmt = $conn->prepare('SELECT SUM(movement_value) as despesa FROM adms_movements WHERE type = "Saída" AND YEAR(created_at) = :year AND MONTH(created_at) = :month');
            $stmt->execute(['year' => $year, 'month' => $mes]);
            $despesa = (float) $stmt->fetch(PDO::FETCH_ASSOC)['despesa'];

            // Saldo Inicial: mês 1 = saldo das contas Corrente, demais meses = acumulado do mês anterior
            $saldoInicialMes = ($m == 1) ? $saldoInicial : $acumulado;
            $saldoAplicacoesMes = ($m == 1) ? $saldoAplicacoes : 0;
            $saldoFinanceiro = $receita - $despesa;
            $acumulado = $saldoInicialMes + $saldoFinanceiro;

            $result[$m] = [
                'saldo_inicial' => $saldoInicialMes,
                'saldo_limites' => 0,
                'saldo_aplicacoes' => $saldoAplicacoesMes,
                'receita' => $receita,
                'despesa' => $despesa,
                'saldo_financeiro' => $saldoFinanceiro,
            ];
        }
        return $result;
    }

    public function getCompetence($year)
    {
        $conn = $this->getConnection();
        $months = range(1, 12);
        $result = [];

        foreach ($months as $m) {
            $mes = str_pad($m, 2, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare('SELECT SUM(original_value) as receber FROM adms_receive WHERE YEAR(issue_date) = :year AND MONTH(issue_date) = :month');
            $stmt->execute(['year' => $year, 'month' => $mes]);
            $receber = (float) $stmt->fetch(PDO::FETCH_ASSOC)['receber'];

            $stmt = $conn->prepare('SELECT SUM(original_value) as pagar FROM adms_pay WHERE YEAR(issue_date) = :year AND MONTH(issue_date) = :month');
            $stmt->execute(['year' => $year, 'month' => $mes]);
            $pagar = (float) $stmt->fetch(PDO::FETCH_ASSOC)['pagar'];

            $result[$m] = [
                'receber' => $receber,
                'pagar' => $pagar,
                'necessidade_caixa' => $receber - $pagar,
            ];
        }
        return $result;
    }

    public function exportToExcel($year, $months, $cashFlow, $competence)
    {
        require_once __DIR__ . '/../../../../vendor/autoload.php';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalho Fluxo de Caixa
        $sheet->setCellValue('A1', 'Fluxo de Caixa');
        $col = 'B';
        foreach ($months as $m) {
            $sheet->setCellValue($col . '1', $m);
            $col++;
        }
        $sheet->setCellValue($col . '1', 'Total');
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '254e7b']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
        ];
        $sheet->getStyle('A1:' . $col . '1')->applyFromArray($headerStyle);

        $linhas = [
            'Saldo Inicial', 'Saldo Limites', 'Saldo Aplicacoes', 'Receita', 'Despesa', 'Saldo Financeiro', 'Acumulado'
        ];
        $row = 2;
        $acumulado = 0;
        foreach ($linhas as $linha) {
            $colData = 'A';
            $sheet->setCellValue($colData . $row, $linha);
            $sheet->getStyle($colData . $row)->applyFromArray([
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4a90e2']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
            ]);
            $colData++;
            $totalLinha = 0;
            for ($i=1; $i<=12; $i++) {
                $valor = 0;
                switch ($linha) {
                    case 'Saldo Inicial': $valor = $cashFlow[$i]['saldo_inicial']; break;
                    case 'Saldo Limites': $valor = $cashFlow[$i]['saldo_limites']; break;
                    case 'Saldo Aplicacoes': $valor = $cashFlow[$i]['saldo_aplicacoes']; break;
                    case 'Receita': $valor = $cashFlow[$i]['receita']; break;
                    case 'Despesa': $valor = $cashFlow[$i]['despesa']; break;
                    case 'Saldo Financeiro': $valor = $cashFlow[$i]['saldo_financeiro']; break;
                    case 'Acumulado':
                        $acumulado = ($i == 1 ? $cashFlow[$i]['saldo_inicial'] : $acumulado) + $cashFlow[$i]['saldo_financeiro'];
                        $valor = $acumulado;
                        break;
                }
                $totalLinha += $valor;
                $sheet->setCellValue($colData . $row, $valor);
                $sheet->getStyle($colData . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
                $sheet->getStyle($colData . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle($colData . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'eaf3fa']
                    ]
                ]);
                $colData++;
            }
            $sheet->setCellValue($colData . $row, $totalLinha);
            $sheet->getStyle($colData . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
            $sheet->getStyle($colData . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($colData . $row)->applyFromArray([
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'eaf3fa']
                ]
            ]);
            $row++;
        }

        // Espaço
        $row++;

        // Cabeçalho Demonstrativo de Contas
        $sheet->setCellValue('A' . $row, 'Demonstrativo de Contas');
        $col = 'B';
        foreach ($months as $m) {
            $sheet->setCellValue($col . $row, $m);
            $col++;
        }
        $sheet->setCellValue($col . $row, 'Total');
        $sheet->getStyle('A' . $row . ':' . $col . $row)->applyFromArray($headerStyle);

        $linhas = [
            'Contas a Receber', 'Contas a Pagar', 'Necessidade de Caixa', 'Necessidade Acumulada'
        ];
        $row++;
        $necessidadeAcumulada = 0;
        foreach ($linhas as $linha) {
            $colData = 'A';
            $sheet->setCellValue($colData . $row, $linha);
            $sheet->getStyle($colData . $row)->applyFromArray([
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4a90e2']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
            ]);
            $colData++;
            $totalLinha = 0;
            for ($i=1; $i<=12; $i++) {
                $valor = 0;
                switch ($linha) {
                    case 'Contas a Receber': $valor = $competence[$i]['receber']; break;
                    case 'Contas a Pagar': $valor = $competence[$i]['pagar']; break;
                    case 'Necessidade de Caixa': $valor = $competence[$i]['necessidade_caixa']; break;
                    case 'Necessidade Acumulada':
                        $necessidadeAcumulada = ($i == 1 ? $competence[$i]['necessidade_caixa'] : $necessidadeAcumulada) + $competence[$i]['necessidade_caixa'];
                        $valor = $necessidadeAcumulada;
                        break;
                }
                $totalLinha += $valor;
                $sheet->setCellValue($colData . $row, $valor);
                $sheet->getStyle($colData . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
                $sheet->getStyle($colData . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle($colData . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'eaf3fa']
                    ]
                ]);
                $colData++;
            }
            $sheet->setCellValue($colData . $row, $totalLinha);
            $sheet->getStyle($colData . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
            $sheet->getStyle($colData . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($colData . $row)->applyFromArray([
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'eaf3fa']
                ]
            ]);
            $row++;
        }

        // Após preencher as duas tabelas, ajustar o autoSize das colunas para todas ficarem iguais
        $lastCol = $col; // $col já está na próxima coluna após o último mês+Total
        foreach (range('A', $lastCol) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Fluxo_Caixa_Competencia_' . $year . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    public function exportToPDF($year, $months, $cashFlow, $competence)
    {
        require_once __DIR__ . '/../../../../vendor/autoload.php';
        $html = '<h2 style="margin-bottom:20px;">Fluxo de Caixa por Competência - ' . $year . '</h2>';
        // Fluxo de Caixa
        $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;font-size:12px;">';
        $html .= '<thead><tr>';
        $html .= '<th style="background:#254e7b;color:#fff;">Fluxo de Caixa</th>';
        foreach ($months as $m) {
            $html .= '<th style="background:#254e7b;color:#fff;">' . $m . '</th>';
        }
        $html .= '<th style="background:#254e7b;color:#fff;">Total</th>';
        $html .= '</tr></thead><tbody>';
        $linhas = [
            'Saldo Inicial', 'Saldo Limites', 'Saldo Aplicacoes', 'Receita', 'Despesa', 'Saldo Financeiro', 'Acumulado'
        ];
        $acumulado = 0;
        foreach ($linhas as $linha) {
            $html .= '<tr>';
            $html .= '<td style="background:#4a90e2;color:#fff;">' . $linha . '</td>';
            $totalLinha = 0;
            for ($i=1; $i<=12; $i++) {
                $valor = 0;
                switch ($linha) {
                    case 'Saldo Inicial': $valor = $cashFlow[$i]['saldo_inicial']; break;
                    case 'Saldo Limites': $valor = $cashFlow[$i]['saldo_limites']; break;
                    case 'Saldo Aplicacoes': $valor = $cashFlow[$i]['saldo_aplicacoes']; break;
                    case 'Receita': $valor = $cashFlow[$i]['receita']; break;
                    case 'Despesa': $valor = $cashFlow[$i]['despesa']; break;
                    case 'Saldo Financeiro': $valor = $cashFlow[$i]['saldo_financeiro']; break;
                    case 'Acumulado':
                        $acumulado = ($i == 1 ? $cashFlow[$i]['saldo_inicial'] : $acumulado) + $cashFlow[$i]['saldo_financeiro'];
                        $valor = $acumulado;
                        break;
                }
                $totalLinha += $valor;
                $cor = '';
                if ($linha == 'Saldo Financeiro' || $linha == 'Acumulado') {
                    $cor = $valor < 0 ? 'background:#d9534f;color:#fff;' : 'background:#198754;color:#fff;';
                }
                if ($linha == 'Acumulado' && $valor == 0) $cor = 'background:#ffe600;color:#222;';
                $html .= '<td style="background:#eaf3fa;' . $cor . '">' . number_format($valor, 2, ',', '.') . '</td>';
            }
            $html .= '<td style="background:#eaf3fa;">' . number_format($totalLinha, 2, ',', '.') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table><br>';

        // Demonstrativo de Contas
        $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;font-size:12px;">';
        $html .= '<thead><tr>';
        $html .= '<th style="background:#254e7b;color:#fff;">Demonstrativo de Contas</th>';
        foreach ($months as $m) {
            $html .= '<th style="background:#254e7b;color:#fff;">' . $m . '</th>';
        }
        $html .= '<th style="background:#254e7b;color:#fff;">Total</th>';
        $html .= '</tr></thead><tbody>';
        $linhas = [
            'Contas a Receber', 'Contas a Pagar', 'Necessidade de Caixa', 'Necessidade Acumulada'
        ];
        $necessidadeAcumulada = 0;
        foreach ($linhas as $linha) {
            $html .= '<tr>';
            $html .= '<td style="background:#4a90e2;color:#fff;">' . $linha . '</td>';
            $totalLinha = 0;
            for ($i=1; $i<=12; $i++) {
                $valor = 0;
                switch ($linha) {
                    case 'Contas a Receber': $valor = $competence[$i]['receber']; break;
                    case 'Contas a Pagar': $valor = $competence[$i]['pagar']; break;
                    case 'Necessidade de Caixa': $valor = $competence[$i]['necessidade_caixa']; break;
                    case 'Necessidade Acumulada':
                        $necessidadeAcumulada = ($i == 1 ? $competence[$i]['necessidade_caixa'] : $necessidadeAcumulada) + $competence[$i]['necessidade_caixa'];
                        $valor = $necessidadeAcumulada;
                        break;
                }
                $totalLinha += $valor;
                $cor = '';
                if ($linha == 'Necessidade de Caixa' || $linha == 'Necessidade Acumulada') {
                    $cor = $valor < 0 ? 'background:#d9534f;color:#fff;' : 'background:#198754;color:#fff;';
                }
                $html .= '<td style="background:#eaf3fa;' . $cor . '">' . number_format($valor, 2, ',', '.') . '</td>';
            }
            $html .= '<td style="background:#eaf3fa;">' . number_format($totalLinha, 2, ',', '.') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        $mpdf = new \Mpdf\Mpdf(['orientation' => 'L']);
        $mpdf->WriteHTML($html);
        $mpdf->Output('Fluxo_Caixa_Competencia_' . $year . '.pdf', 'D');
        exit;
    }
} 