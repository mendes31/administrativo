<?php

declare(strict_types=1);

namespace App\adms\Controllers\financialReports;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\PdfInstitutionalHeaderHelper;
use App\adms\Models\Services\FinCostCenterSapService;
use App\adms\Views\Services\LoadViewService;
use Mpdf\Mpdf;
use Throwable;

/**
 * Shell dos dashboards de centros de custo SAP.
 * Os dados vêm de FinCostCenterDashboardData (JSON) para a tela abrir na hora.
 */
class FinCostCenterDashboard
{
    private array|string|null $data = null;

    public function index(): void
    {
        $pageElements = [
            'title_head' => 'Indicadores de Centros de Custo SAP',
            'menu' => 'fin-cost-center-dashboard',
            'buttonPermission' => ['FinCostCenterDashboard', 'FinCostCenterDashboardData'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = $pageLayoutService->configurePageElements($pageElements);

        $base = $_ENV['URL_ADM'] ?? '';
        $this->data['api_url'] = $base . 'fin-cost-center-dashboard-data';
        $this->data['self_url'] = $base . 'fin-cost-center-dashboard';
        $this->data['pdf_url'] = $base . 'fin-cost-center-dashboard/export-pdf';
        $this->data['periodos'] = FinCostCenterSapService::PERIODOS;
        $this->data['periodo_chave'] = (string) ($_GET['periodo'] ?? '3');
        if (!isset(FinCostCenterSapService::PERIODOS[$this->data['periodo_chave']])) {
            $this->data['periodo_chave'] = '3';
        }
        $this->data['data_inicio'] = (string) ($_GET['data_inicio'] ?? '');
        $this->data['data_fim'] = (string) ($_GET['data_fim'] ?? '');
        $this->data['unidade'] = trim((string) ($_GET['unidade'] ?? ''));
        $this->data['centro'] = trim((string) ($_GET['centro'] ?? ''));
        $this->data['modelo'] = trim((string) ($_GET['modelo'] ?? ''));
        $visao = (string) ($_GET['visao'] ?? 'dashboard');
        if (!in_array($visao, ['dashboard', 'meses', 'centros', 'equipes', 'hierarquia', 'lancamentos'], true)) {
            $visao = 'dashboard';
        }
        $this->data['visao'] = $visao;

        $loadView = new LoadViewService('adms/Views/financialReports/costCenterDashboard', $this->data);
        $loadView->loadView();
    }

    /**
     * PDF da hierarquia com o cabeçalho institucional (logo | título | logo) em todas as páginas.
     */
    public function exportPdf(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        @ini_set('display_errors', '0');
        @ini_set('memory_limit', '512M');
        @ini_set('pcre.backtrack_limit', '5000000');

        $titulo = trim((string) ($_POST['titulo'] ?? 'Hierarquia de centros de custo'));
        $subtitulo = trim((string) ($_POST['subtitulo'] ?? ''));
        $html = (string) ($_POST['html'] ?? '');
        if (strlen($html) > 8_000_000) {
            $html = '';
        }
        $html = strip_tags($html, '<div><span><strong><em><br><p><table><tr><td>');
        if (trim(strip_tags($html)) === '') {
            $html = '<p>Nenhum dado para o nível selecionado.</p>';
        }

        try {
            $tempDir = (defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 4))
                . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'mpdf';
            if (!is_dir($tempDir)) {
                @mkdir($tempDir, 0775, true);
            }
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-L',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 24,
                'margin_header' => 4,
                'margin_bottom' => 12,
                'margin_footer' => 8,
                'tempDir' => is_dir($tempDir) ? $tempDir : sys_get_temp_dir(),
            ]);
            $mpdf->SetTitle($titulo);
            $mpdf->SetAuthor('Grupo Tiaraju');
            $mpdf->SetFooter('Hierarquia de centros de custo||{PAGENO}/{nbpg}');
            PdfInstitutionalHeaderHelper::applyRepeatingHeader($mpdf, $titulo, $subtitulo);
            $mpdf->WriteHTML(
                '<style>
                body{font-family:dejavusans,sans-serif;font-size:9pt;color:#1B241E;margin:0;}
                table{width:100%;border-collapse:collapse;margin:0 0 3px 0;}
                td{border:none;vertical-align:middle;padding:5px 8px;}
                td.n{width:34mm;text-align:right;font-weight:bold;white-space:nowrap;}
                .tag{font-size:7pt;font-weight:bold;text-transform:uppercase;}
                .sub{font-weight:normal;font-size:8.5pt;}
                .node{margin:0 0 3px 0;}
                .kids{padding:3px 0 2px 8px;margin:0 0 2px 4px;border-left:3px solid #D5E8DC;}
                .node.h .kids{border-left-color:#B7D4C4;}
                .node.g .kids,.node.f .kids{border-left-color:#D6E2F8;}
                .node.c .kids{border-left-color:#E4D8F2;}
                .node.a .kids{border-left:0;margin:0;padding:0 4px 3px 6px;}
                table.m td{background:#1B7A49;color:#fff;}
                table.h td{background:#0E6B45;color:#fff;}
                table.g td{background:#2F6FDE;color:#fff;}
                table.f td{background:#E67E2E;color:#fff;}
                table.c td{background:#F8F5FC;color:#1B241E;}
                table.c{border-left:4px solid #7B5EA7;}
                table.c .tag{color:#5A3D86;}
                table.a td{background:#fff;color:#1B241E;border-top:1px dashed #C6E0D0;border-bottom:1px dashed #C6E0D0;}
                table.a td.main{border-left:1px dashed #C6E0D0;}
                table.a td.n{border-right:1px dashed #C6E0D0;}
                table.a .tag{color:#1B7A49;}
                .pos{color:#1B7A49;}
                .neg{color:#C0392B;}
                table.m td.n,table.h td.n,table.g td.n,table.f td.n{color:#fff;}
                table.doc{margin:0 0 2px 0;}
                table.doc td{background:#F7FBF8;color:#1B241E;font-size:8pt;font-weight:normal;padding:3px 5px;border-top:1px solid #E4EFE7;border-bottom:1px solid #E4EFE7;}
                table.doc td.t{width:22mm;font-weight:bold;border-left:1px solid #E4EFE7;}
                table.doc td.dt{width:24mm;}
                table.doc td.rf{width:20mm;}
                table.doc td.ru{width:28mm;}
                table.doc td.pr{width:40mm;}
                table.doc td.n{border-right:1px solid #E4EFE7;}
                </style>' . $html
            );
            $mpdf->Output('Hierarquia_centros_de_custo.pdf', 'I');
        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
            echo '<p>Não foi possível gerar o PDF.</p>';
        }
        exit;
    }
}
