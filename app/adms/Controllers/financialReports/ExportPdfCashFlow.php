<?php
namespace App\adms\Controllers\financialReports;

use App\adms\Models\Repository\FinancialMovementsRepository;
use Mpdf\Mpdf;

class ExportPdfCashFlow
{
    public function index(): void
    {
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: application/pdf');
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        try {
            $mes = $_GET['mes'] ?? date('m');
            $ano = $_GET['ano'] ?? date('Y');
            $repo = new \App\adms\Models\Repository\FinancialMovementsRepository();
            $efetivo = $repo->getCashFlow($mes, $ano, 'efetivo');
            $previsto = $repo->getCashFlow($mes, $ano, 'previsto');

            // Mapear os dados por dia para facilitar busca
            $dadosEfetivo = [];
            foreach ($efetivo as $linha) {
                $dadosEfetivo[(int)$linha['dia']] = $linha;
            }
            $dadosPrevisto = [];
            foreach ($previsto as $linha) {
                $dadosPrevisto[(int)$linha['dia']] = $linha;
            }

            $diasNoMes = cal_days_in_month(CAL_GREGORIAN, (int)$mes, (int)$ano);
            $nomeMes = [
                '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
                '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
                '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
            ];
            $mesExtenso = $nomeMes[str_pad($mes, 2, '0', STR_PAD_LEFT)] ?? $mes;

            $mpdf = new \Mpdf\Mpdf();

            // CSS para as tabelas
            $css = '<style>
                body { font-family: Arial, sans-serif; }
                .cabecalho-pdf { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
                table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
                th, td { border: 1px solid #333; padding: 6px 8px; font-size: 12px; }
                th { color: #fff; font-weight: bold; }
                .thead-efetivo { background: #0d6efd; }
                .thead-previsto { background: #6c757d; }
                .tr-alt { background: #f2f2f2; }
                .valor-neg { color: #dc3545; font-weight: bold; } /* vermelho */
                .valor-pos { color: #198754; font-weight: bold; } /* verde */
                .valor-zero { color: #6c757d; font-weight: bold; } /* cinza */
            </style>';

            // Função para cor condicional
            $corValor = function($valor) {
                if ($valor < 0) return 'valor-neg';
                if ($valor > 0) return 'valor-pos';
                return 'valor-zero';
            };

            // Página 1: Efetivo
            $htmlEfetivo = $css;
            $htmlEfetivo .= '<div class="cabecalho-pdf">Mês: ' . $mesExtenso . ' / Ano: ' . $ano . '</div>';
            $htmlEfetivo .= '<h2>Relatório Fluxo de Caixa - Valores Efetivos</h2>';
            $htmlEfetivo .= '<table>';
            $htmlEfetivo .= '<tr class="thead-efetivo"><th>Dia</th><th>Receita</th><th>Despesa</th><th>Saldo</th><th>Acumulado</th></tr>';
            $acumulado = 0;
            for ($dia = 1; $dia <= $diasNoMes; $dia++) {
                $receita = isset($dadosEfetivo[$dia]) ? ($dadosEfetivo[$dia]['receita'] ?? 0) : 0;
                $despesa = isset($dadosEfetivo[$dia]) ? ($dadosEfetivo[$dia]['despesa'] ?? 0) : 0;
                $saldo = $receita - $despesa;
                $acumulado += $saldo;
                $trClass = $dia % 2 == 0 ? 'tr-alt' : '';
                $htmlEfetivo .= '<tr class="' . $trClass . '">';
                $htmlEfetivo .= '<td>' . str_pad($dia, 2, '0', STR_PAD_LEFT) . '</td>';
                $htmlEfetivo .= '<td>' . number_format($receita, 2, ',', '.') . '</td>';
                $htmlEfetivo .= '<td>' . number_format($despesa, 2, ',', '.') . '</td>';
                $htmlEfetivo .= '<td class="' . $corValor($saldo) . '">' . number_format($saldo, 2, ',', '.') . '</td>';
                $htmlEfetivo .= '<td class="' . $corValor($acumulado) . '">' . number_format($acumulado, 2, ',', '.') . '</td>';
                $htmlEfetivo .= '</tr>';
            }
            $htmlEfetivo .= '</table>';

            // Página 2: Previsto
            $htmlPrevisto = $css;
            $htmlPrevisto .= '<div class="cabecalho-pdf">Mês: ' . $mesExtenso . ' / Ano: ' . $ano . '</div>';
            $htmlPrevisto .= '<h2>Relatório Fluxo de Caixa - Valores Previsto</h2>';
            $htmlPrevisto .= '<table>';
            $htmlPrevisto .= '<tr class="thead-previsto"><th>Dia</th><th>Receita</th><th>Despesa</th><th>Saldo</th><th>Acumulado</th></tr>';
            $acumulado = 0;
            for ($dia = 1; $dia <= $diasNoMes; $dia++) {
                $receita = isset($dadosPrevisto[$dia]) ? ($dadosPrevisto[$dia]['receita'] ?? 0) : 0;
                $despesa = isset($dadosPrevisto[$dia]) ? ($dadosPrevisto[$dia]['despesa'] ?? 0) : 0;
                $saldo = $receita - $despesa;
                $acumulado += $saldo;
                $trClass = $dia % 2 == 0 ? 'tr-alt' : '';
                $htmlPrevisto .= '<tr class="' . $trClass . '">';
                $htmlPrevisto .= '<td>' . str_pad($dia, 2, '0', STR_PAD_LEFT) . '</td>';
                $htmlPrevisto .= '<td>' . number_format($receita, 2, ',', '.') . '</td>';
                $htmlPrevisto .= '<td>' . number_format($despesa, 2, ',', '.') . '</td>';
                $htmlPrevisto .= '<td class="' . $corValor($saldo) . '">' . number_format($saldo, 2, ',', '.') . '</td>';
                $htmlPrevisto .= '<td class="' . $corValor($acumulado) . '">' . number_format($acumulado, 2, ',', '.') . '</td>';
                $htmlPrevisto .= '</tr>';
            }
            $htmlPrevisto .= '</table>';

            $mpdf->WriteHTML($htmlEfetivo);
            $mpdf->AddPage();
            $mpdf->WriteHTML($htmlPrevisto);
            $mpdf->Output('fluxo-caixa.pdf', 'I');
            exit;
        } catch (\Throwable $e) {
            header('Content-Type: text/html; charset=utf-8');
            echo '<h1>Erro ao gerar PDF</h1><pre>' . $e->getMessage() . "\n" . $e->getTraceAsString() . '</pre>';
            exit;
        }
    }
} 