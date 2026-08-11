<?php

namespace App\adms\Controllers\reports;

use App\adms\Helpers\DynamicReportValueFormatter;
use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Models\Services\DynamicQueryBuilderService;
use Dompdf\Dompdf;

/**
 * Exporta o resultado completo de um relatório dinâmico (sem paginação da tela) para PDF.
 */
class ExportDynamicReportPdf
{
    public function index(?string $id = null): void
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '600');

        if (empty($id)) {
            $_SESSION['error'] = 'ID do relatório não fornecido';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
            exit;
        }

        $repo = new DynamicReportsRepository();
        $report = $repo->getById((int)$id);
        if (!$report) {
            $_SESSION['error'] = 'Relatório não encontrado';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
            exit;
        }

        $viewerId = (int) ($_SESSION['user_id'] ?? 0);
        if (!$repo->userCanViewReport($report, $viewerId)) {
            $_SESSION['error'] = 'Você não tem permissão para exportar este relatório.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
            exit;
        }

        $report['cache_namespace'] = 'report_' . $id;
        $report['export_all'] = true;
        $report['force_refresh'] = !empty($_GET['refresh']);

        $queryBuilder = new DynamicQueryBuilderService();
        $result = $queryBuilder->executeReport($report);

        if (empty($result['success'])) {
            $_SESSION['error'] = 'Erro ao gerar PDF: ' . ($result['error'] ?? 'desconhecido');
            header('Location: ' . $_ENV['URL_ADM'] . 'view-dynamic-report/' . (int)$id);
            exit;
        }

        $rows = $result['data'] ?? [];
        $reportTitle = htmlspecialchars($report['name'] ?? 'Relatório');
        $connectionLabels = [
            'local' => 'Banco Local',
            'sap_b1' => 'SAP B1 HANA',
            'sap_api' => 'SAP API',
        ];
        $connType = $result['connection_type'] ?? 'local';
        $connectionLabel = htmlspecialchars($connectionLabels[$connType] ?? (string)$connType);

        $html = '<html><head><meta charset="UTF-8"><style>
            body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; }
            h1 { font-size: 15px; margin-bottom: 4px; }
            table { width: 100%; border-collapse: collapse; margin-top: 8px; table-layout: fixed; }
            th, td { border: 1px solid #ccc; padding: 3px 4px; word-wrap: break-word; overflow-wrap: break-word; vertical-align: top; }
            th { background: #e8e8e8; font-weight: bold; font-size: 8px; }
            .text-center { text-align: center; }
            .small { font-size: 8px; color: #666; }
        </style></head><body>';

        $html .= '<h1>' . $reportTitle . '</h1>';
        $html .= '<div class="small">Gerado em ' . date('d/m/Y H:i') . ' | Conexão: ' . $connectionLabel . '</div>';

        if ($rows === []) {
            $html .= '<p class="small" style="margin-top:12px;">Nenhum registro retornado pela consulta.</p>';
        } else {
            $headers = array_keys($rows[0]);
            $columnTypes = DynamicReportValueFormatter::inferColumnTypes($rows);
            $html .= '<table><thead><tr>';
            foreach ($headers as $h) {
                $html .= '<th>' . htmlspecialchars((string)$h) . '</th>';
            }
            $html .= '</tr></thead><tbody>';

            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($headers as $h) {
                    $val = $row[$h] ?? '';
                    if (is_array($val) || is_object($val)) {
                        $val = json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    } elseif ($val === null) {
                        $val = '';
                    } else {
                        $val = DynamicReportValueFormatter::formatCell($val, $columnTypes[$h] ?? 'text');
                    }
                    $html .= '<td>' . htmlspecialchars($val) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
            $html .= '<div class="small" style="margin-top:8px;">Total: ' . count($rows) . ' registro(s)</div>';
        }

        $html .= '</body></html>';

        $asciiName = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)($report['name'] ?? 'relatorio'));
        $asciiName = $asciiName !== '' ? $asciiName : 'relatorio';
        $filename = $asciiName . '_' . date('Y-m-d_His') . '.pdf';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }
}
