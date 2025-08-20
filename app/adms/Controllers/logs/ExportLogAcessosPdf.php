<?php

namespace App\adms\Controllers\logs;

use App\adms\Models\Repository\LogAcessosRepository;
use Dompdf\Dompdf;

class ExportLogAcessosPdf
{
    public function index(): void
    {
        $filtros = $this->getFiltros();
        $repo = new LogAcessosRepository();
        $logs = $repo->getAll(1, 10000, $filtros);

        // Montar HTML da tabela
        $html = '<h2 style="text-align:center;">Log de Acessos</h2>';
        $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="font-size:12px; border-collapse:collapse;">';
        $html .= '<thead><tr style="background:#f0f0f0;">';
        $html .= '<th>ID</th><th>Usuário</th><th>Email</th><th>Tipo</th><th>IP</th><th>User Agent</th><th>Data/Hora</th>';
        $html .= '</tr></thead><tbody>';
        foreach ($logs as $log) {
            $html .= '<tr>';
            $html .= '<td>' . $log['id'] . '</td>';
            $html .= '<td>' . htmlspecialchars($log['usuario_nome'] ?? '-') . '</td>';
            $html .= '<td>' . htmlspecialchars($log['usuario_email'] ?? '-') . '</td>';
            $html .= '<td>' . htmlspecialchars($log['tipo_acesso']) . '</td>';
            $html .= '<td>' . htmlspecialchars($log['ip']) . '</td>';
            $html .= '<td>' . htmlspecialchars($log['user_agent']) . '</td>';
            $html .= '<td>' . date('d/m/Y H:i:s', strtotime($log['data_acesso'])) . '</td>';
            $html .= '</tr>';
        }
        if (empty($logs)) {
            $html .= '<tr><td colspan="7" style="text-align:center; color:#888;">Nenhum log encontrado.</td></tr>';
        }
        $html .= '</tbody></table>';

        // Gerar PDF
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('logs_acessos_' . date('Y-m-d_H-i-s') . '.pdf', ['Attachment' => true]);
        exit;
    }
    
    private function getFiltros(): array
    {
        return [
            'usuario_nome' => $_GET['usuario_nome'] ?? '',
            'tipo_acesso' => $_GET['tipo_acesso'] ?? '',
            'ip' => $_GET['ip'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
        ];
    }
} 