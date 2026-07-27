<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Models\Repository\RhCandidatoAnexoAccessLogRepository;
use Dompdf\Dompdf;

/**
 * Exportação PDF do log de download de currículos.
 */
class ExportRhCandidatoAnexoAccessLogsPdf
{
    public function index(): void
    {
        $filtros = [
            'actor_nome' => trim((string) ($_GET['actor_nome'] ?? '')),
            'candidato_id' => trim((string) ($_GET['candidato_id'] ?? '')),
            'candidato_nome' => trim((string) ($_GET['candidato_nome'] ?? '')),
            'source' => trim((string) ($_GET['source'] ?? '')),
            'ip' => trim((string) ($_GET['ip'] ?? '')),
            'data_inicio' => trim((string) ($_GET['data_inicio'] ?? '')),
            'data_fim' => trim((string) ($_GET['data_fim'] ?? '')),
        ];

        $repo = new RhCandidatoAnexoAccessLogRepository();
        $logs = $repo->getAll(1, 10000, $filtros);

        $html = '<h2 style="text-align:center;">Log de download de currículos</h2>';
        $html .= '<p style="font-size:11px;text-align:center;color:#666;">Gerado em '
            . htmlspecialchars(date('d/m/Y H:i:s')) . '</p>';
        $html .= '<table border="1" cellpadding="4" cellspacing="0" width="100%" '
            . 'style="font-size:10px;border-collapse:collapse;">';
        $html .= '<thead><tr style="background:#f0f0f0;">'
            . '<th>ID</th><th>Data/Hora</th><th>Quem baixou</th><th>Candidato</th>'
            . '<th>Fonte</th><th>Modo</th><th>IP</th>'
            . '</tr></thead><tbody>';

        if ($logs === []) {
            $html .= '<tr><td colspan="7" style="text-align:center;color:#888;">Nenhum log encontrado.</td></tr>';
        } else {
            foreach ($logs as $log) {
                $created = !empty($log['created_at'])
                    ? date('d/m/Y H:i:s', strtotime((string) $log['created_at']))
                    : '-';
                $candidato = trim((string) ($log['candidato_nome'] ?? ''));
                if ($candidato === '') {
                    $candidato = '#' . (int) ($log['rh_candidato_id'] ?? 0);
                } else {
                    $candidato .= ' (#' . (int) ($log['rh_candidato_id'] ?? 0) . ')';
                }
                $html .= '<tr>';
                $html .= '<td>' . (int) ($log['id'] ?? 0) . '</td>';
                $html .= '<td>' . htmlspecialchars($created) . '</td>';
                $html .= '<td>' . htmlspecialchars((string) ($log['actor_name'] ?? '-')) . '</td>';
                $html .= '<td>' . htmlspecialchars($candidato) . '</td>';
                $html .= '<td>' . htmlspecialchars((string) ($log['source'] ?? '-')) . '</td>';
                $html .= '<td>' . htmlspecialchars((string) ($log['delivery_mode'] ?? '-')) . '</td>';
                $html .= '<td>' . htmlspecialchars((string) ($log['ip_address'] ?? '-')) . '</td>';
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('log_download_curriculos_' . date('Y-m-d_H-i-s') . '.pdf', ['Attachment' => true]);
        exit;
    }
}
