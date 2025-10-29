<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmOpportunitiesRepository;
use App\adms\Models\Repository\CrmActivitiesRepository;
use App\adms\Models\Repository\UsersRepository;
use Mpdf\Mpdf;

/**
 * Controller para Relatório de Performance por Vendedor
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmReportPerformance
{
    public function index(): void
    {
        // Capturar período
        $periodo_inicio = $_GET['periodo_inicio'] ?? date('Y-m-01');
        $periodo_fim = $_GET['periodo_fim'] ?? date('Y-m-t');

        $usersRepo = new UsersRepository();
        $opportunitiesRepo = new CrmOpportunitiesRepository();
        $activitiesRepo = new CrmActivitiesRepository();

        // Buscar vendedores ativos (todos os usuários para simplificar)
        $users = $usersRepo->getAllUsersSelect();

        $performanceData = [];

        foreach ($users as $user) {
            $userId = $user['id'];
            
            $filters = [
                'responsible_user_id' => $userId,
                'periodo_inicio' => $periodo_inicio,
                'periodo_fim' => $periodo_fim,
            ];

            $performanceData[] = [
                'name' => $user['name'],
                'total_opportunities' => $opportunitiesRepo->getTotalOpenOpportunities($filters),
                'total_value' => $opportunitiesRepo->getTotalPipelineValue($filters),
                'conversion_rate' => $opportunitiesRepo->getConversionRate($filters),
                'total_activities' => $activitiesRepo->getTotalActivitiesThisMonth($filters),
            ];
        }

        // Ordenar por valor total
        usort($performanceData, function($a, $b) {
            return $b['total_value'] <=> $a['total_value'];
        });

        // Gerar HTML
        $html = $this->generateHtml($performanceData, $periodo_inicio, $periodo_fim);

        // Gerar PDF
        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'L', // Landscape
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 20,
                'margin_bottom' => 20,
            ]);

            $mpdf->WriteHTML($html);

            $filename = 'relatorio_performance_' . date('Y-m-d_His') . '.pdf';
            $mpdf->Output($filename, 'I');
        } catch (\Exception $e) {
            $_SESSION['msg'] = "Erro ao gerar relatório PDF: " . $e->getMessage();
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-dashboard");
            exit;
        }
    }

    private function generateHtml($performanceData, $periodo_inicio, $periodo_fim): string
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
                .ranking-1 { background-color: #ffd700 !important; font-weight: bold; }
                .ranking-2 { background-color: #c0c0c0 !important; }
                .ranking-3 { background-color: #cd7f32 !important; }
                .footer { margin-top: 30px; text-align: center; color: #666; font-size: 11px; border-top: 1px solid #ddd; padding-top: 10px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>🏆 Relatório de Performance por Vendedor</h1>
                <p class="date">Período: ' . date('d/m/Y', strtotime($periodo_inicio)) . ' a ' . date('d/m/Y', strtotime($periodo_fim)) . '</p>
                <p class="date">Gerado em: ' . date('d/m/Y \à\s H:i') . '</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Ranking</th>
                        <th>Vendedor</th>
                        <th>Oportunidades</th>
                        <th>Valor Total</th>
                        <th>Taxa Conversão</th>
                        <th>Atividades</th>
                    </tr>
                </thead>
                <tbody>';

        $ranking = 1;
        foreach ($performanceData as $data) {
            if ($data['total_value'] == 0) continue; // Pular vendedores sem vendas

            $rankingClass = '';
            if ($ranking === 1) $rankingClass = 'ranking-1';
            elseif ($ranking === 2) $rankingClass = 'ranking-2';
            elseif ($ranking === 3) $rankingClass = 'ranking-3';

            $html .= '
                    <tr class="' . $rankingClass . '">
                        <td><strong>' . $ranking . 'º</strong></td>
                        <td>' . htmlspecialchars($data['name']) . '</td>
                        <td>' . $data['total_opportunities'] . '</td>
                        <td><strong>R$ ' . number_format($data['total_value'], 2, ',', '.') . '</strong></td>
                        <td>' . number_format($data['conversion_rate'], 1) . '%</td>
                        <td>' . $data['total_activities'] . '</td>
                    </tr>';

            $ranking++;
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

