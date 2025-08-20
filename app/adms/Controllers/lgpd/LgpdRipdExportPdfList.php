<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Models\Repository\LgpdRipdRepository;
use Exception;

/**
 * Controller responsável pela exportação de lista de relatórios RIPD em PDF.
 */
class LgpdRipdExportPdfList
{
    private LgpdRipdRepository $ripdRepo;

    public function __construct()
    {
        $this->ripdRepo = new LgpdRipdRepository();
    }

    /**
     * Método padrão - exporta lista completa de RIPDs.
     */
    public function index(): void
    {
        try {
            $this->exportRipdList();
        } catch (Exception $e) {
            error_log("Erro em LgpdRipdExportPdfList::index: " . $e->getMessage());
            echo "<h1>Erro ao gerar PDF</h1>";
            echo "<p>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    /**
     * Exporta lista de todos os relatórios RIPD para PDF.
     */
    public function exportRipdList(): void
    {
        try {
            if (ob_get_length()) ob_end_clean();
            header('Content-Type: application/pdf');
            
            $ripds = $this->ripdRepo->getAllRipd();
            $estatisticas = $this->ripdRepo->getEstatisticas();
            
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15
            ]);

            $html = $this->generateRipdListHtml($ripds, $estatisticas);
            
            $mpdf->SetTitle("Lista de Relatórios RIPD");
            $mpdf->SetAuthor("Sistema LGPD");
            $mpdf->SetCreator("Sistema Administrativo");
            
            $mpdf->WriteHTML($html);
            
            $filename = "Lista_RIPD_" . date('Y-m-d') . ".pdf";
            $mpdf->Output($filename, 'I');
            exit;
            
        } catch (Exception $e) {
            error_log("Erro ao exportar lista RIPD para PDF: " . $e->getMessage());
            header('Content-Type: text/html; charset=utf-8');
            echo "<h1>Erro ao gerar PDF</h1>";
            echo "<p>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
            exit;
        }
    }

    /**
     * Gera HTML para lista de RIPDs.
     */
    private function generateRipdListHtml(array $ripds, array $estatisticas): string
    {
        $total = $estatisticas['total'] ?? 0;
        $aprovados = $estatisticas['aprovados'] ?? 0;
        $em_revisao = $estatisticas['em_revisao'] ?? 0;
        $em_rascunho = $total - ($aprovados + $em_revisao);

        $html = "<!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
                .logo { font-size: 24px; font-weight: bold; }
                .stats { display: flex; justify-content: space-around; margin: 20px 0; }
                .stat-item { text-align: center; padding: 15px; background-color: #f8f9fa; border-radius: 8px; }
                .stat-number { font-size: 24px; font-weight: bold; color: #007bff; }
                .stat-label { font-size: 12px; color: #666; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='header'>
                <div class='logo'>LISTA DE RELATÓRIOS RIPD</div>
                <div style='margin-top: 10px; font-size: 12px;'>
                    Data de geração: " . date('d/m/Y H:i:s') . "
                </div>
            </div>

            <div class='stats'>
                <div class='stat-item'>
                    <div class='stat-number'>{$total}</div>
                    <div class='stat-label'>Total de RIPDs</div>
                </div>
                <div class='stat-item'>
                    <div class='stat-number'>{$aprovados}</div>
                    <div class='stat-label'>Aprovados</div>
                </div>
                <div class='stat-item'>
                    <div class='stat-number'>{$em_revisao}</div>
                    <div class='stat-label'>Em Revisão</div>
                </div>
                <div class='stat-item'>
                    <div class='stat-number'>{$em_rascunho}</div>
                    <div class='stat-label'>Em Rascunho</div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Título</th>
                        <th>Status</th>
                        <th>Data Elaboração</th>
                        <th>Elaborador</th>
                        <th>AIPD Relacionada</th>
                    </tr>
                </thead>
                <tbody>";

        if (!empty($ripds)) {
            foreach ($ripds as $ripd) {
                $html .= "
                    <tr>
                        <td>{$ripd['codigo']}</td>
                        <td>{$ripd['titulo']}</td>
                        <td>{$ripd['status']}</td>
                        <td>" . date('d/m/Y', strtotime($ripd['data_elaboracao'])) . "</td>
                        <td>{$ripd['elaborador_nome']}</td>
                        <td>{$ripd['aipd_codigo']} - {$ripd['aipd_titulo']}</td>
                    </tr>";
            }
        } else {
            $html .= "
                <tr>
                    <td colspan='6' style='text-align: center; color: #666;'>
                        Nenhum RIPD encontrado
                    </td>
                </tr>";
        }

        $html .= "
                </tbody>
            </table>

            <div style='margin-top: 40px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ccc; padding-top: 20px;'>
                <p>Documento gerado automaticamente pelo Sistema Administrativo</p>
                <p>Total de registros: " . count($ripds) . "</p>
            </div>
        </body>
        </html>";

        return $html;
    }
}
