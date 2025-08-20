<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Models\Repository\LgpdRipdRepository;
use Mpdf\Mpdf;
use Exception;

class LgpdRipdExportPdf
{
    private LgpdRipdRepository $ripdRepo;

    public function __construct()
    {
        $this->ripdRepo = new LgpdRipdRepository();
    }

    /**
     * Método padrão - pode receber ID como parâmetro ou redirecionar.
     */
    public function index(string $id = null): void
    {
        if ($id) {
            // Se recebeu ID, exporta o RIPD específico
            $this->exportRipd((int)$id);
        } else {
            // Se não recebeu ID, redireciona para lista
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd");
            exit;
        }
    }

    /**
     * Exporta relatório RIPD individual para PDF.
     */
    public function exportRipd(int $id): void
    {
        try {
            if (ob_get_length()) ob_end_clean();
            header('Content-Type: application/pdf');
            
            $ripd = $this->ripdRepo->getRipdById($id);
            
            if (!$ripd) {
                throw new Exception("Relatório RIPD não encontrado");
            }

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15
            ]);

            $html = $this->generateRipdReportHtml($ripd);
            
            $mpdf->SetTitle("RIPD - " . $ripd['codigo']);
            $mpdf->SetAuthor("Sistema LGPD");
            $mpdf->SetCreator("Sistema Administrativo");
            
            $mpdf->WriteHTML($html);
            
            $filename = "RIPD_" . $ripd['codigo'] . "_" . date('Y-m-d') . ".pdf";
            $mpdf->Output($filename, 'I');
            exit;
            
        } catch (Exception $e) {
            error_log("Erro ao exportar RIPD para PDF: " . $e->getMessage());
            header('Content-Type: text/html; charset=utf-8');
            echo "<h1>Erro ao gerar PDF</h1>";
            echo "<p>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
            exit;
        }
    }

    /**
     * Gera HTML para relatório RIPD individual.
     */
    private function generateRipdReportHtml(array $ripd): string
    {
        return "<!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; }
                .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
                .logo { font-size: 24px; font-weight: bold; color: #2c3e50; }
                .section { margin-bottom: 25px; }
                .section-title { font-size: 16px; font-weight: bold; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 15px; color: #2c3e50; }
                .info-row { margin-bottom: 10px; }
                .label { font-weight: bold; display: inline-block; width: 180px; color: #34495e; }
                .value { color: #2c3e50; }
                .status-approved { color: #27ae60; font-weight: bold; }
                .status-review { color: #f39c12; font-weight: bold; }
                .status-rejected { color: #e74c3c; font-weight: bold; }
                .status-draft { color: #95a5a6; font-weight: bold; }
                .highlight-box { padding: 15px; background-color: #f8f9fa; border-radius: 8px; border-left: 4px solid #3498db; }
                .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #7f8c8d; border-top: 1px solid #ccc; padding-top: 20px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <div class='logo'>RELATÓRIO RIPD</div>
                <div style='margin-top: 10px; font-size: 12px; color: #7f8c8d;'>
                    <strong>Código:</strong> {$ripd['codigo']} | 
                    <strong>Versão:</strong> {$ripd['versao']} | 
                    <strong>Data:</strong> " . date('d/m/Y', strtotime($ripd['data_elaboracao'])) . "
                </div>
            </div>

            <div class='section'>
                <div class='section-title'>📋 Informações Gerais</div>
                <div class='info-row'>
                    <span class='label'>Título:</span>
                    <span class='value'>{$ripd['titulo']}</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Status:</span>
                    <span class='status-" . strtolower(str_replace(' ', '-', $ripd['status'])) . "'>{$ripd['status']}</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Elaborador:</span>
                    <span class='value'>{$ripd['elaborador_nome']}</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Data de Elaboração:</span>
                    <span class='value'>" . date('d/m/Y', strtotime($ripd['data_elaboracao'])) . "</span>
                </div>
            </div>

            <div class='section'>
                <div class='section-title'>🔗 AIPD Relacionada</div>
                <div class='info-row'>
                    <span class='label'>Código AIPD:</span>
                    <span class='value'>{$ripd['aipd_codigo']}</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Título AIPD:</span>
                    <span class='value'>{$ripd['aipd_titulo']}</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Status AIPD:</span>
                    <span class='value'>{$ripd['aipd_status']}</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Nível de Risco:</span>
                    <span class='value'>{$ripd['aipd_nivel_risco']}</span>
                </div>
            </div>

            <div class='section'>
                <div class='section-title'>✅ Conclusão Geral</div>
                <div class='highlight-box'>
                    " . nl2br(htmlspecialchars($ripd['conclusao_geral'] ?? 'Não informado')) . "
                </div>
            </div>

            <div class='section'>
                <div class='section-title'>💡 Recomendações Finais</div>
                <div class='highlight-box'>
                    " . nl2br(htmlspecialchars($ripd['recomendacoes_finais'] ?? 'Não informado')) . "
                </div>
            </div>

            " . (!empty($ripd['proximos_passos']) ? "
            <div class='section'>
                <div class='section-title'>🛣️ Próximos Passos</div>
                <div class='highlight-box'>
                    " . nl2br(htmlspecialchars($ripd['proximos_passos'])) . "
                </div>
            </div>
            " : "") . "

            " . (!empty($ripd['observacoes_revisao']) ? "
            <div class='section'>
                <div class='section-title'>📝 Observações da Revisão</div>
                <div class='highlight-box'>
                    " . nl2br(htmlspecialchars($ripd['observacoes_revisao'])) . "
                </div>
            </div>
            " : "") . "

            " . (!empty($ripd['observacoes_aprovacao']) ? "
            <div class='section'>
                <div class='section-title'>✅ Observações da Aprovação</div>
                <div class='highlight-box'>
                    " . nl2br(htmlspecialchars($ripd['observacoes_aprovacao'])) . "
                </div>
            </div>
            " : "") . "

            <div class='footer'>
                <p>📄 Documento gerado automaticamente pelo Sistema Administrativo</p>
                <p>🕒 Data de geração: " . date('d/m/Y H:i:s') . "</p>
                <p>📊 Este relatório foi elaborado conforme as diretrizes da LGPD</p>
            </div>
        </body>
        </html>";
    }
}
