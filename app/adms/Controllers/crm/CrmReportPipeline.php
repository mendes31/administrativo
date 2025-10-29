<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmOpportunitiesRepository;
use App\adms\Models\Repository\CrmPipelineStagesRepository;
use Mpdf\Mpdf;

/**
 * Controller para Relatório de Pipeline em PDF
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmReportPipeline
{
    public function index(): void
    {
        // Capturar filtros
        $filters = [
            'responsible_user_id' => $_GET['responsible_user_id'] ?? '',
            'periodo_inicio' => $_GET['periodo_inicio'] ?? '',
            'periodo_fim' => $_GET['periodo_fim'] ?? '',
        ];

        $opportunitiesRepo = new CrmOpportunitiesRepository();
        $stagesRepo = new CrmPipelineStagesRepository();

        // Buscar dados
        $funnelData = $opportunitiesRepo->getFunnelData($filters);
        $totalPipeline = $opportunitiesRepo->getTotalPipelineValue($filters);
        $conversionRate = $opportunitiesRepo->getConversionRate($filters);
        $totalOpportunities = $opportunitiesRepo->getTotalOpenOpportunities($filters);

        // Gerar HTML do relatório
        $html = $this->generateHtml($funnelData, $totalPipeline, $conversionRate, $totalOpportunities, $filters);

        // Gerar PDF
        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 20,
                'margin_bottom' => 20,
            ]);

            $mpdf->WriteHTML($html);

            $filename = 'relatorio_pipeline_' . date('Y-m-d_His') . '.pdf';
            $mpdf->Output($filename, 'I'); // I = inline browser, D = download
        } catch (\Exception $e) {
            $_SESSION['msg'] = "Erro ao gerar relatório PDF: " . $e->getMessage();
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-dashboard");
            exit;
        }
    }

    private function generateHtml($funnelData, $totalPipeline, $conversionRate, $totalOpportunities, $filters): string
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #2E9263; padding-bottom: 15px; }
                .header h1 { color: #2E9263; margin: 0; }
                .header .date { color: #666; font-size: 12px; }
                .kpi-box { background: #f8f9fa; padding: 15px; margin-bottom: 15px; border-left: 4px solid #2E9263; }
                .kpi-box h3 { margin: 0 0 5px 0; color: #2E9263; font-size: 16px; }
                .kpi-box .value { font-size: 24px; font-weight: bold; color: #333; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
                th { background-color: #2E9263; color: white; font-weight: bold; }
                tr:nth-child(even) { background-color: #f8f9fa; }
                .footer { margin-top: 30px; text-align: center; color: #666; font-size: 11px; border-top: 1px solid #ddd; padding-top: 10px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>📊 Relatório do Pipeline de Vendas</h1>
                <p class="date">Gerado em: ' . date('d/m/Y \à\s H:i') . '</p>
            </div>

            <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                <div class="kpi-box" style="width: 48%;">
                    <h3>Valor Total do Pipeline</h3>
                    <div class="value">R$ ' . number_format($totalPipeline, 2, ',', '.') . '</div>
                </div>
                <div class="kpi-box" style="width: 48%;">
                    <h3>Oportunidades Abertas</h3>
                    <div class="value">' . $totalOpportunities . '</div>
                </div>
            </div>

            <div class="kpi-box">
                <h3>Taxa de Conversão (últimos 6 meses)</h3>
                <div class="value">' . number_format($conversionRate, 1) . '%</div>
            </div>

            <h2 style="color: #2E9263; margin-top: 30px;">Funil de Vendas por Etapa</h2>
            <table>
                <thead>
                    <tr>
                        <th>Etapa</th>
                        <th style="text-align: right;">Quantidade</th>
                        <th style="text-align: right;">Valor Total</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($funnelData as $stage) {
            $html .= '
                    <tr>
                        <td><strong>' . htmlspecialchars($stage['name']) . '</strong></td>
                        <td style="text-align: right;">' . $stage['count'] . '</td>
                        <td style="text-align: right;">R$ ' . number_format($stage['total_value'], 2, ',', '.') . '</td>
                    </tr>';
        }

        $html .= '
                </tbody>
            </table>

            <div class="footer">
                <p>Tiaraju - Sistema de Gestão Administrativo | CRM</p>
            </div>
        </body>
        </html>';

        return $html;
    }
}

