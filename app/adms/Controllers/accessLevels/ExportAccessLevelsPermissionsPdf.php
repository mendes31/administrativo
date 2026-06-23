<?php

declare(strict_types=1);

namespace App\adms\Controllers\accessLevels;

use App\adms\Models\Repository\AccessLevelsPagesRepository;
use Mpdf\Mpdf;
use Throwable;

class ExportAccessLevelsPermissionsPdf
{
    public function index(): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        @ini_set('memory_limit', '768M');
        @set_time_limit(300);

        $filterName = isset($_GET['name']) ? trim((string) $_GET['name']) : '';

        try {
            $repo = new AccessLevelsPagesRepository();
            $levels = $repo->getPermittedPagesGroupedByAccessLevel($filterName);

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-L',
                'margin_left' => 12,
                'margin_right' => 12,
                'margin_top' => 16,
                'margin_bottom' => 16,
                'tempDir' => sys_get_temp_dir(),
            ]);

            $mpdf->SetTitle('Permissões por Nível de Acesso');
            $mpdf->SetAuthor('Sistema Administrativo');

            $mpdf->WriteHTML($this->buildStylesAndHeader($filterName));

            if ($levels === []) {
                $mpdf->WriteHTML('<p class="empty">Nenhum nível de acesso encontrado.</p>');
            } else {
                foreach ($levels as $level) {
                    $mpdf->WriteHTML($this->buildLevelBlock($level));
                }
            }

            $filename = 'permissoes_niveis_acesso_' . date('Y-m-d_His') . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');

            $mpdf->Output($filename, 'D');
        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
            echo '<p>Erro ao gerar PDF: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        exit;
    }

    private function buildStylesAndHeader(string $filterName): string
    {
        $projectRoot = realpath(__DIR__ . '/../../../..');
        $logoPath = $projectRoot . '/public/adms/image/logo/logo.png';
        $logoImg = '';
        if (is_file($logoPath)) {
            $logoImg = '<img src="data:image/png;base64,'
                . base64_encode((string) file_get_contents($logoPath))
                . '" alt="Logo" class="logo">';
        }

        $meta = 'Gerado em ' . date('d/m/Y H:i:s') . ' — apenas páginas autorizadas (permission = 1)';
        if ($filterName !== '') {
            $meta .= ' — filtro nome: ' . htmlspecialchars($filterName);
        }

        return '<style>
            body { font-family: sans-serif; font-size: 9pt; color: #222; }
            .header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #1b6e3a; padding-bottom: 8px; }
            .logo { height: 42px; }
            h1 { margin: 6px 0 2px; color: #1b6e3a; font-size: 15pt; }
            .meta { color: #666; font-size: 8pt; }
            .level-block { margin-bottom: 14px; }
            h2 { margin: 0 0 6px; color: #0d6efd; font-size: 11pt; page-break-after: avoid; }
            .count { color: #666; font-weight: normal; font-size: 8pt; }
            table { width: 100%; border-collapse: collapse; font-size: 8pt; margin-bottom: 4px; }
            th { background: #f1f3f5; border: 1px solid #dee2e6; padding: 4px 5px; text-align: left; }
            td { border: 1px solid #dee2e6; padding: 3px 5px; vertical-align: top; }
            .empty { color: #888; font-style: italic; }
        </style>
        <div class="header">'
            . $logoImg
            . '<h1>Permissões por Nível de Acesso</h1>'
            . '<div class="meta">' . $meta . '</div>'
            . '</div>';
    }

    /**
     * @param array{
     *     id: int,
     *     name: string,
     *     permissions_authorized_count: int,
     *     permissions_pages_total: int,
     *     pages: list<array{id: int, name: string, controller_url: string, group_name: string}>
     * } $level
     */
    private function buildLevelBlock(array $level): string
    {
        $pageCount = count($level['pages']);
        $authCount = (int) ($level['permissions_authorized_count'] ?? $pageCount);
        $totalCount = (int) ($level['permissions_pages_total'] ?? $authCount);
        $html = '<div class="level-block">';
        $html .= '<h2>' . htmlspecialchars($level['name'])
            . ' <span class="count">(ID ' . (int) $level['id'] . ' — '
            . $authCount . '/' . $totalCount . ')</span></h2>';

        if ($pageCount === 0) {
            $html .= '<p class="empty">Nenhuma página autorizada.</p>';
        } else {
            $html .= '<table><thead><tr>'
                . '<th style="width:7%;">ID</th>'
                . '<th style="width:38%;">Página</th>'
                . '<th style="width:30%;">URL</th>'
                . '<th style="width:25%;">Grupo</th>'
                . '</tr></thead><tbody>';

            foreach ($level['pages'] as $page) {
                $html .= '<tr>'
                    . '<td>' . (int) $page['id'] . '</td>'
                    . '<td>' . htmlspecialchars($page['name']) . '</td>'
                    . '<td>' . htmlspecialchars($page['controller_url']) . '</td>'
                    . '<td>' . htmlspecialchars($page['group_name']) . '</td>'
                    . '</tr>';
            }

            $html .= '</tbody></table>';
        }

        $html .= '</div>';

        return $html;
    }
}
