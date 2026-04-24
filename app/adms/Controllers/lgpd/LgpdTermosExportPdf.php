<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Helpers\FormatHelper;
use App\adms\Models\Repository\LgpdTermosRepository;
use Mpdf\Mpdf;
use Throwable;

class LgpdTermosExportPdf
{
    private function resolveLogoAbsolutePath(): ?string
    {
        $root = dirname(__DIR__, 4);
        $candidates = [
            $root . '/public/adms/image/logo/Logo-Tiaraju.png',
            $root . '/public/adms/image/logo/logo.png',
            $root . '/public/adms/image/logo/logo.jpg',
            $root . '/public/adms/image/logo/logo.jpeg',
        ];
        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }
        return null;
    }

    private function logoImgTagForPdf(?string $path, int $maxW = 92): string
    {
        if ($path === null || !is_file($path)) {
            return '<span style="font-size:11pt;font-weight:700;color:#0f766e;">TIARAJU</span>';
        }
        $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return '<span style="font-size:11pt;font-weight:700;color:#0f766e;">TIARAJU</span>';
        }
        $b64 = base64_encode($raw);
        return '<img src="data:' . $mime . ';base64,' . $b64 . '" style="max-width:' . $maxW . 'px;max-height:52px;" alt="Logo">';
    }

    public function index(int|string|null $id = null): void
    {
        try {
            if (ob_get_length()) {
                ob_end_clean();
            }
            @set_time_limit(60);
            @ini_set('memory_limit', '512M');

            $termId = (int)$id;
            if ($termId <= 0) {
                $_SESSION['error'] = 'Termo LGPD inválido para exportação.';
                header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-termos');
                exit;
            }

            $repo = new LgpdTermosRepository();
            $termo = $repo->getById($termId);
            if (!$termo) {
                $_SESSION['error'] = 'Termo LGPD não encontrado.';
                header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-termos');
                exit;
            }

            $titulo = htmlspecialchars((string)($termo['titulo'] ?? 'Termo LGPD'));
            $versao = htmlspecialchars((string)($termo['versao'] ?? ''));
            $tipo = htmlspecialchars((string)($termo['tipo'] ?? ''));
            $status = htmlspecialchars((string)($termo['status'] ?? ''));
            $inicio = FormatHelper::formatDate($termo['data_inicio_vigencia'] ?? null, 'd/m/Y H:i');
            $fim = !empty($termo['data_fim_vigencia'])
                ? FormatHelper::formatDate($termo['data_fim_vigencia'], 'd/m/Y H:i')
                : 'Sem data de fim';
            $criadoEm = FormatHelper::formatDate($termo['created_at'] ?? null, 'd/m/Y H:i');
            $conteudo = (string)($termo['conteudo'] ?? '');
            $conteudo = preg_replace('#<script\b[^>]*>(.*?)</script>#is', '', $conteudo) ?? $conteudo;
            $logoHtml = $this->logoImgTagForPdf($this->resolveLogoAbsolutePath());

            $html = '<html><head><meta charset="UTF-8"><style>
            body{font-family:DejaVu Sans,Arial,sans-serif;font-size:12px;line-height:1.4;color:#1f2937;}
            .header-table{width:100%;border-collapse:collapse;margin-bottom:10px;}
            .header-cell{border:1px solid #000;padding:6px;text-align:center;vertical-align:middle;}
            .header-title{font-size:12pt;font-weight:700;letter-spacing:0.4px;}
            .header-sub{font-size:9pt;margin-top:3px;}
            .meta{width:100%;border-collapse:collapse;margin:12px 0;}
            .meta td{padding:5px 8px;border:1px solid #d1d5db;}
            .meta .label{width:24%;font-weight:700;background:#f8fafc;}
            .content{border:1px solid #d1d5db;border-radius:6px;padding:12px;background:#ffffff;}
            h1,h2,h3,h4{margin:10px 0 6px 0;}
            p{margin:0 0 6px 0;}
            ul{margin:0 0 8px 18px;}
            .footer{margin-top:14px;font-size:10px;color:#64748b;text-align:right;}
        </style></head><body>';

            $html .= '<table class="header-table"><tr>'
            . '<td class="header-cell" style="width:22%;">' . $logoHtml . '</td>'
            . '<td class="header-cell" style="width:56%;">'
            . '<div class="header-title">TERMO LGPD</div>'
            . '<div class="header-sub">Sistema Administrativo</div>'
            . '</td>'
            . '<td class="header-cell" style="width:22%;">' . $logoHtml . '</td>'
            . '</tr></table>';
            $html .= '<table class="meta">';
            $html .= '<tr><td class="label">ID</td><td>' . (int)$termo['id'] . '</td><td class="label">Versão</td><td>' . $versao . '</td></tr>';
            $html .= '<tr><td class="label">Título</td><td colspan="3">' . $titulo . '</td></tr>';
            $html .= '<tr><td class="label">Tipo</td><td>' . $tipo . '</td><td class="label">Status</td><td>' . $status . '</td></tr>';
            $html .= '<tr><td class="label">Início Vigência</td><td>' . htmlspecialchars((string)$inicio) . '</td><td class="label">Fim Vigência</td><td>' . htmlspecialchars((string)$fim) . '</td></tr>';
            $html .= '<tr><td class="label">Criado em</td><td colspan="3">' . htmlspecialchars((string)$criadoEm) . '</td></tr>';
            $html .= '</table>';
            $html .= '<div class="content">' . $conteudo . '</div>';
            $html .= '<div class="footer">Gerado em ' . date('d/m/Y H:i:s') . '</div>';
            $html .= '</body></html>';

            $tempDir = dirname(__DIR__, 4) . '/storage/cache/mpdf';
            if (!is_dir($tempDir)) {
                @mkdir($tempDir, 0775, true);
            }
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 12,
                'margin_right' => 12,
                'margin_top' => 12,
                'margin_bottom' => 14,
                'tempDir' => $tempDir,
            ]);
            $mpdf->SetTitle('Termo LGPD - ' . (int)$termo['id']);
            $mpdf->SetAuthor('Sistema Administrativo');
            $mpdf->SetFooter('{PAGENO}/{nbpg}');
            $mpdf->WriteHTML($html);
            $filename = 'termo_lgpd_' . (int)$termo['id'] . '_' . date('Y-m-d_His') . '.pdf';
            $mpdf->Output($filename, 'D');
            exit;
        } catch (Throwable $e) {
            error_log('Erro ao gerar PDF de termo LGPD: ' . $e->getMessage());
            $_SESSION['error'] = 'Erro ao gerar PDF do termo LGPD.';
            header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-termos-view/' . (int)$id);
            exit;
        }
    }
}

