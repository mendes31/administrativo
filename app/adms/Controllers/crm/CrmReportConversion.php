<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmOpportunitiesRepository;
use App\adms\Models\Repository\CrmPipelineStagesRepository;
use Mpdf\Mpdf;

/**
 * Controller para Relatório de Conversão do Funil
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmReportConversion
{
    public function index(): void
    {
        // Capturar filtros
        $filters = [
            'responsible_user_id' => $_GET['responsible_user_id'] ?? '',
            'periodo_inicio' => $_GET['periodo_inicio'] ?? date('Y-m-01', strtotime('-6 months')),
            'periodo_fim' => $_GET['periodo_fim'] ?? date('Y-m-t'),
        ];

        $opportunitiesRepo = new CrmOpportunitiesRepository();
        
        // Buscar dados do funil
        $funnelData = $opportunitiesRepo->getFunnelData($filters);
        
        // Calcular conversão entre etapas
        $conversionData = [];
        for ($i = 0; $i < count($funnelData) - 1; $i++) {
            $currentStage = $funnelData[$i];
            $nextStage = $funnelData[$i + 1];
            
            $conversionRate = 0;
            if ($currentStage['count'] > 0) {
                $conversionRate = ($nextStage['count'] / $currentStage['count']) * 100;
            }
            
            $conversionData[] = [
                'from' => $currentStage['name'],
                'to' => $nextStage['name'],
                'from_count' => $currentStage['count'],
                'to_count' => $nextStage['count'],
                'conversion_rate' => $conversionRate,
            ];
        }

        // Gerar HTML
        $html = $this->generateHtml($funnelData, $conversionData, $filters);

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

            $filename = 'relatorio_conversao_funil_' . date('Y-m-d_His') . '.pdf';
            $mpdf->Output($filename, 'I');
        } catch (\Exception $e) {
            $_SESSION['msg'] = "Erro ao gerar relatório PDF: " . $e->getMessage();
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-dashboard");
            exit;
        }
    }

    private function generateHtml($funnelData, $conversionData, $filters): string
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
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { padding: 10px; text-align: center; border: 1px solid #ddd; }
                th { background-color: #2E9263; color: white; font-weight: bold; }
                tr:nth-child(even) { background-color: #f8f9fa; }
                .good { color: #28a745; font-weight: bold; }
                .bad { color: #dc3545; font-weight: bold; }
                .footer { margin-top: 30px; text-align: center; color: #666; font-size: 11px; border-top: 1px solid #ddd; padding-top: 10px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>📈 Relatório de Conversão do Funil</h1>
                <p class="date">Período: ' . date('d/m/Y', strtotime($filters['periodo_inicio'])) . ' a ' . date('d/m/Y', strtotime($filters['periodo_fim'])) . '</p>
                <p class="date">Gerado em: ' . date('d/m/Y \à\s H:i') . '</p>
            </div>

            <h2 style="color: #2E9263;">Conversão Entre Etapas</h2>
            <table>
                <thead>
                    <tr>
                        <th>De</th>
                        <th>→</th>
                        <th>Para</th>
                        <th>Quantidade Inicial</th>
                        <th>Quantidade Final</th>
                        <th>Taxa de Conversão</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($conversionData as $data) {
            $rateClass = $data['conversion_rate'] >= 50 ? 'good' : 'bad';
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($data['from']) . '</td>
                        <td>→</td>
                        <td>' . htmlspecialchars($data['to']) . '</td>
                        <td>' . $data['from_count'] . '</td>
                        <td>' . $data['to_count'] . '</td>
                        <td class="' . $rateClass . '">' . number_format($data['conversion_rate'], 1) . '%</td>
                    </tr>';
        }

        $html .= '
                </tbody>
            </table>

            <h2 style="color: #2E9263; margin-top: 30px;">Resumo do Funil</h2>
            <table>
                <thead>
                    <tr>
                        <th>Etapa</th>
                        <th>Quantidade</th>
                        <th>Valor Total</th>
                        <th>% do Pipeline</th>
                    </tr>
                </thead>
                <tbody>';

        $totalValue = array_sum(array_column($funnelData, 'total_value'));

        foreach ($funnelData as $stage) {
            $percentage = $totalValue > 0 ? ($stage['total_value'] / $totalValue) * 100 : 0;
            $html .= '
                    <tr>
                        <td><strong>' . htmlspecialchars($stage['name']) . '</strong></td>
                        <td>' . $stage['count'] . '</td>
                        <td>R$ ' . number_format($stage['total_value'], 2, ',', '.') . '</td>
                        <td>' . number_format($percentage, 1) . '%</td>
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

