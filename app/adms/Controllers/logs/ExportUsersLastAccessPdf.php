<?php

declare(strict_types=1);

namespace App\adms\Controllers\logs;

use App\adms\Models\Repository\LogAcessosRepository;
use Mpdf\Mpdf;
use Throwable;

class ExportUsersLastAccessPdf
{
    use UsersLastAccessExportTrait;

    public function index(): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $filtros = $this->resolveUsersLastAccessFilters();

        try {
            $repo = new LogAcessosRepository();
            $users = $repo->listAllUsersLastLogin($filtros);

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-L',
                'margin_left' => 12,
                'margin_right' => 12,
                'margin_top' => 16,
                'margin_bottom' => 16,
                'tempDir' => sys_get_temp_dir(),
            ]);

            $mpdf->SetTitle('Último acesso por usuário');
            $mpdf->WriteHTML($this->buildHeader($filtros, count($users)));

            if ($users === []) {
                $mpdf->WriteHTML('<p class="empty">Nenhum usuário encontrado com os filtros informados.</p>');
            } else {
                $rows = '';
                foreach ($users as $row) {
                    $rows .= '<tr>'
                        . '<td>' . htmlspecialchars((string) ($row['user_name'] ?? '—')) . '</td>'
                        . '<td>' . htmlspecialchars((string) ($row['user_email'] ?? '')) . '</td>'
                        . '<td>' . htmlspecialchars((string) ($row['user_username'] ?? '')) . '</td>'
                        . '<td>' . htmlspecialchars((string) ($row['user_status'] ?? '')) . '</td>'
                        . '<td>' . htmlspecialchars($this->formatUltimoLogin($row['ultimo_login'] ?? null)) . '</td>'
                        . '<td>' . htmlspecialchars((string) ($row['ultimo_ip'] ?? '—')) . '</td>'
                        . '</tr>';
                }

                $mpdf->WriteHTML(
                    '<table><thead><tr>'
                    . '<th>Usuário</th><th>E-mail</th><th>@usuário</th><th>Status</th><th>Último login</th><th>IP</th>'
                    . '</tr></thead><tbody>' . $rows . '</tbody></table>'
                );
            }

            $filename = 'ultimo_acesso_usuarios_' . date('Y-m-d_His') . '.pdf';
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

    /**
     * @param array{usuario_nome: string, status: string, apenas_nunca: string, sort: string} $filtros
     */
    private function buildHeader(array $filtros, int $total): string
    {
        $projectRoot = realpath(__DIR__ . '/../../../..');
        $logoPath = $projectRoot . '/public/adms/image/logo/logo.png';
        $logoImg = '';
        if (is_file($logoPath)) {
            $logoImg = '<img src="data:image/png;base64,'
                . base64_encode((string) file_get_contents($logoPath))
                . '" alt="Logo" class="logo">';
        }

        return '<style>
            body { font-family: sans-serif; font-size: 9pt; color: #222; }
            .header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #1b6e3a; padding-bottom: 8px; }
            .logo { height: 42px; }
            h1 { margin: 6px 0 2px; color: #1b6e3a; font-size: 15pt; }
            .meta { color: #666; font-size: 8pt; }
            table { width: 100%; border-collapse: collapse; font-size: 8pt; }
            th { background: #f1f3f5; border: 1px solid #dee2e6; padding: 4px 5px; text-align: left; }
            td { border: 1px solid #dee2e6; padding: 3px 5px; vertical-align: top; }
            .empty { color: #888; font-style: italic; }
        </style>
        <div class="header">'
            . $logoImg
            . '<h1>Último acesso por usuário</h1>'
            . '<div class="meta">Gerado em ' . date('d/m/Y H:i:s')
            . ' — ' . $total . ' registro(s)'
            . ' — Filtros: ' . htmlspecialchars($this->buildUsersLastAccessFilterSummary($filtros))
            . '</div></div>';
    }
}
