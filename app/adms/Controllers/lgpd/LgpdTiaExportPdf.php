<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Models\Repository\LgpdTiaRepository;
use Mpdf\Mpdf;
use Exception;

/**
 * Controller responsável pela exportação de relatórios TIA em PDF.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdTiaExportPdf
{
    /** @var LgpdTiaRepository $tiaRepo */
    private LgpdTiaRepository $tiaRepo;

    public function __construct()
    {
        $this->tiaRepo = new LgpdTiaRepository();
    }

    /**
     * Método padrão - exporta lista completa de TIAs.
     *
     * @return void
     */
    public function index(): void
    {
        // IMPORTANTE: Nenhuma saída antes deste ponto!
        
        try {
            // Verificar se as variáveis de ambiente estão carregadas
            if (!isset($_ENV['URL_ADM'])) {
                throw new Exception("Variáveis de ambiente não carregadas");
            }
            
            // Sempre exportar lista completa
            $this->exportTiaList();
            
        } catch (Exception $e) {
            // Log do erro
            error_log("Erro em LgpdTiaExportPdf::index: " . $e->getMessage());
            
            // Exibir erro amigável
            header('Content-Type: text/html; charset=utf-8');
            echo "<h1>Erro ao gerar PDF</h1>";
            echo "<p>Ocorreu um erro ao gerar o PDF da lista de TIA.</p>";
            echo "<p><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><a href='javascript:history.back()'>Voltar</a></p>";
            exit;
        }
    }

    /**
     * Exporta um teste TIA específico para PDF.
     *
     * @param int $id ID do teste TIA
     * @return void
     */
    public function exportTia(int $id): void
    {
        try {
            // IMPORTANTE: Limpar buffer e definir headers ANTES de qualquer saída
            if (ob_get_length()) ob_end_clean();
            header('Content-Type: application/pdf');
            
            $tia = $this->tiaRepo->getTiaById($id);
            
            if (!$tia) {
                throw new Exception("Teste TIA não encontrado");
            }

            $dataGroups = $this->tiaRepo->getDataGroupsByTiaId($id);
            
            // Configurar mPDF
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15
            ]);

            // Gerar HTML do relatório
            $html = $this->generateTiaReportHtml($tia, $dataGroups);
            
            // Configurar metadados
            $mpdf->SetTitle("Relatório TIA - " . $tia['codigo']);
            $mpdf->SetAuthor("Sistema LGPD");
            $mpdf->SetCreator("Sistema Administrativo");
            
            // Escrever conteúdo
            $mpdf->WriteHTML($html);
            
            // Output do PDF
            $filename = "TIA_" . $tia['codigo'] . "_" . date('Y-m-d') . ".pdf";
            $mpdf->Output($filename, 'I');
            exit;
            
        } catch (Exception $e) {
            error_log("Erro ao exportar TIA para PDF: " . $e->getMessage());
            header('Content-Type: text/html; charset=utf-8');
            echo "<h1>Erro ao gerar PDF</h1>";
            echo "<p>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
            exit;
        }
    }

    /**
     * Exporta lista de todos os testes TIA para PDF.
     *
     * @return void
     */
    public function exportTiaList(): void
    {
        try {
            // IMPORTANTE: Limpar buffer e definir headers ANTES de qualquer saída
            if (ob_get_length()) ob_end_clean();
            header('Content-Type: application/pdf');
            
            $tias = $this->tiaRepo->getAllTia();
            $estatisticas = $this->tiaRepo->getEstatisticas();
            
            // Configurar mPDF
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15
            ]);

            // Gerar HTML da lista
            $html = $this->generateTiaListHtml($tias, $estatisticas);
            
            // Configurar metadados
            $mpdf->SetTitle("Lista de Testes TIA");
            $mpdf->SetAuthor("Sistema LGPD");
            $mpdf->SetCreator("Sistema Administrativo");
            
            // Escrever conteúdo
            $mpdf->WriteHTML($html);
            
            // Output do PDF
            $filename = "Lista_TIA_" . date('Y-m-d') . ".pdf";
            $mpdf->Output($filename, 'I');
            exit;
            
        } catch (Exception $e) {
            error_log("Erro ao exportar lista TIA para PDF: " . $e->getMessage());
            header('Content-Type: text/html; charset=utf-8');
            echo "<h1>Erro ao gerar PDF</h1>";
            echo "<p>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
            exit;
        }
    }

    /**
     * Gera HTML para relatório de TIA individual.
     *
     * @param array $tia
     * @param array $dataGroups
     * @return string
     */
    private function generateTiaReportHtml(array $tia, array $dataGroups): string
    {
        $resultadoClass = $this->getResultadoClass($tia['resultado']);
        $statusClass = $this->getStatusClass($tia['status']);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; }
                .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
                .logo { font-size: 24px; font-weight: bold; color: #2c3e50; }
                .subtitle { font-size: 14px; color: #7f8c8d; margin-top: 5px; }
                .section { margin-bottom: 25px; }
                .section-title { font-size: 16px; font-weight: bold; color: #2c3e50; border-bottom: 1px solid #bdc3c7; padding-bottom: 5px; margin-bottom: 15px; }
                .info-row { margin-bottom: 10px; }
                .label { font-weight: bold; color: #34495e; display: inline-block; width: 150px; }
                .value { color: #2c3e50; }
                .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
                .badge-{$resultadoClass} { background-color: {$this->getResultadoColor($tia['resultado'])}; color: white; }
                .badge-{$statusClass} { background-color: {$this->getStatusColor($tia['status'])}; color: white; }
                .data-groups { margin-top: 15px; }
                .data-group-item { background-color: #f8f9fa; padding: 8px; margin-bottom: 5px; border-radius: 4px; border-left: 4px solid #007bff; }
                .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #7f8c8d; border-top: 1px solid #bdc3c7; padding-top: 20px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <div class='logo'>RELATÓRIO TIA</div>
                <div class='subtitle'>Teste de Impacto às Atividades</div>
                <div class='subtitle'>" . date('d/m/Y H:i') . "</div>
            </div>

            <div class='section'>
                <div class='section-title'>Informações Básicas</div>
                <div class='info-row'>
                    <span class='label'>Código:</span>
                    <span class='value'>{$tia['codigo']}</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Título:</span>
                    <span class='value'>{$tia['titulo']}</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Departamento:</span>
                    <span class='value'>{$tia['departamento_nome']}</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Responsável:</span>
                    <span class='value'>" . ($tia['responsavel_nome'] ?? 'Não definido') . "</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Data do Teste:</span>
                    <span class='value'>" . date('d/m/Y', strtotime($tia['data_teste'])) . "</span>
                </div>
            </div>

            <div class='section'>
                <div class='section-title'>Descrição da Atividade</div>
                <div class='value'>" . nl2br(htmlspecialchars($tia['descricao'] ?? 'Não informado')) . "</div>
            </div>

            <div class='section'>
                <div class='section-title'>Resultado e Status</div>
                <div class='info-row'>
                    <span class='label'>Resultado:</span>
                    <span class='badge badge-{$resultadoClass}'>{$tia['resultado']}</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Status:</span>
                    <span class='badge badge-{$statusClass}'>{$tia['status']}</span>
                </div>
            </div>

            <div class='section'>
                <div class='section-title'>Justificativa</div>
                <div class='value'>" . nl2br(htmlspecialchars($tia['justificativa'] ?? 'Não informado')) . "</div>
            </div>

            <div class='section'>
                <div class='section-title'>Recomendações</div>
                <div class='value'>" . nl2br(htmlspecialchars($tia['recomendacoes'] ?? 'Não informado')) . "</div>
            </div>";

        if (!empty($dataGroups)) {
            $html .= "
            <div class='section'>
                <div class='section-title'>Grupos de Dados Relacionados</div>
                <div class='data-groups'>";
            
            foreach ($dataGroups as $group) {
                $html .= "
                <div class='data-group-item'>
                    <strong>{$group['nome']}</strong> - {$group['descricao']}
                </div>";
            }
            
            $html .= "</div></div>";
        }

        if (!empty($tia['ropa_atividade'])) {
            $html .= "
            <div class='section'>
                <div class='section-title'>ROPA Relacionada</div>
                <div class='value'>{$tia['ropa_atividade']}</div>
            </div>";
        }

        $html .= "
            <div class='footer'>
                <p>Relatório gerado automaticamente pelo Sistema LGPD</p>
                <p>Data de geração: " . date('d/m/Y H:i:s') . "</p>
            </div>
        </body>
        </html>";

        return $html;
    }

    /**
     * Processa as estatísticas para exibição no PDF.
     *
     * @param array $estatisticas
     * @return array
     */
    private function processEstatisticas(array $estatisticas): array
    {
        $processed = [
            'total' => 0,
            'em_andamento' => 0,
            'concluidos' => 0,
            'recentes_30_dias' => 0
        ];
        
        // Processar por status
        if (isset($estatisticas['por_status']) && is_array($estatisticas['por_status'])) {
            foreach ($estatisticas['por_status'] as $status) {
                if (isset($status['status']) && isset($status['total'])) {
                    switch ($status['status']) {
                        case 'Em Andamento':
                            $processed['em_andamento'] = (int) $status['total'];
                            break;
                        case 'Concluído':
                            $processed['concluidos'] = (int) $status['total'];
                            break;
                    }
                }
            }
        }
        
        // Processar outros campos
        if (isset($estatisticas['recentes_30_dias'])) {
            $processed['recentes_30_dias'] = (int) $estatisticas['recentes_30_dias'];
        }
        
        return $processed;
    }

    /**
     * Gera HTML para lista de testes TIA.
     *
     * @param array $tias
     * @param array $estatisticas
     * @return string
     */
    private function generateTiaListHtml(array $tias, array $estatisticas): string
    {
        // Processar estatísticas
        $stats = $this->processEstatisticas($estatisticas);
        $total = count($tias);
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; font-size: 10px; line-height: 1.3; }
                .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
                .logo { font-size: 20px; font-weight: bold; color: #2c3e50; }
                .subtitle { font-size: 12px; color: #7f8c8d; margin-top: 5px; }
                .stats { display: flex; justify-content: space-around; margin-bottom: 30px; }
                .stat-item { text-align: center; }
                .stat-number { font-size: 18px; font-weight: bold; color: #2c3e50; }
                .stat-label { font-size: 10px; color: #7f8c8d; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: bold; }
                .badge { padding: 2px 6px; border-radius: 3px; font-size: 8px; font-weight: bold; }
                .badge-baixo { background-color: #28a745; color: white; }
                .badge-medio { background-color: #ffc107; color: black; }
                .badge-alto { background-color: #fd7e14; color: white; }
                .badge-aipd { background-color: #dc3545; color: white; }
                .badge-andamento { background-color: #17a2b8; color: white; }
                .badge-concluido { background-color: #28a745; color: white; }
                .badge-aprovado { background-color: #20c997; color: white; }
                .footer { margin-top: 30px; text-align: center; font-size: 8px; color: #7f8c8d; border-top: 1px solid #bdc3c7; padding-top: 15px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <div class='logo'>LISTA DE TESTES TIA</div>
                <div class='subtitle'>Relatório Consolidado</div>
                <div class='subtitle'>" . date('d/m/Y H:i') . "</div>
            </div>

            <div class='stats'>
                <div class='stat-item'>
                    <div class='stat-number'>{$total}</div>
                    <div class='stat-label'>Total de Testes</div>
                </div>
                <div class='stat-item'>
                    <div class='stat-number'>{$stats['em_andamento']}</div>
                    <div class='stat-label'>Em Andamento</div>
                </div>
                <div class='stat-item'>
                    <div class='stat-number'>{$stats['concluidos']}</div>
                    <div class='stat-label'>Concluídos</div>
                </div>
                <div class='stat-item'>
                    <div class='stat-number'>{$stats['recentes_30_dias']}</div>
                    <div class='stat-label'>Últimos 30 dias</div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Título</th>
                        <th>Departamento</th>
                        <th>Data</th>
                        <th>Resultado</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>";

        foreach ($tias as $tia) {
            $resultadoClass = $this->getResultadoClass($tia['resultado']);
            $statusClass = $this->getStatusClass($tia['status']);
            
            $html .= "
                <tr>
                    <td>{$tia['codigo']}</td>
                    <td>" . htmlspecialchars(substr($tia['titulo'], 0, 40)) . (strlen($tia['titulo']) > 40 ? '...' : '') . "</td>
                    <td>{$tia['departamento_nome']}</td>
                    <td>" . date('d/m/Y', strtotime($tia['data_teste'])) . "</td>
                    <td><span class='badge badge-{$resultadoClass}'>{$tia['resultado']}</span></td>
                    <td><span class='badge badge-{$statusClass}'>{$tia['status']}</span></td>
                </tr>";
        }

        $html .= "
                </tbody>
            </table>

            <div class='footer'>
                <p>Relatório gerado automaticamente pelo Sistema LGPD</p>
                <p>Data de geração: " . date('d/m/Y H:i:s') . "</p>
            </div>
        </body>
        </html>";

        return $html;
    }

    /**
     * Retorna a classe CSS para o resultado.
     *
     * @param string $resultado
     * @return string
     */
    private function getResultadoClass(string $resultado): string
    {
        return match($resultado) {
            'Baixo Risco' => 'baixo',
            'Médio Risco' => 'medio',
            'Alto Risco' => 'alto',
            'Necessita AIPD' => 'aipd',
            default => 'medio'
        };
    }

    /**
     * Retorna a classe CSS para o status.
     *
     * @param string $status
     * @return string
     */
    private function getStatusClass(string $status): string
    {
        return match($status) {
            'Em Andamento' => 'andamento',
            'Concluído' => 'concluido',
            'Aprovado' => 'aprovado',
            default => 'andamento'
        };
    }

    /**
     * Retorna a cor para o resultado.
     *
     * @param string $resultado
     * @return string
     */
    private function getResultadoColor(string $resultado): string
    {
        return match($resultado) {
            'Baixo Risco' => '#28a745',
            'Médio Risco' => '#ffc107',
            'Alto Risco' => '#fd7e14',
            'Necessita AIPD' => '#dc3545',
            default => '#ffc107'
        };
    }

    /**
     * Retorna a cor para o status.
     *
     * @param string $status
     * @return string
     */
    private function getStatusColor(string $status): string
    {
        return match($status) {
            'Em Andamento' => '#17a2b8',
            'Concluído' => '#28a745',
            'Aprovado' => '#20c997',
            default => '#17a2b8'
        };
    }
}
