<?php

namespace App\adms\Controllers\informativos;

use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Repository\UsersRepository;
use Dompdf\Dompdf;

class ExportRelatorioInformativoPdf
{
    public function index(): void
    {
        $informativoId = (int)($_GET['informativo_id'] ?? 0);
        if ($informativoId <= 0) {
            http_response_code(422);
            echo 'ID inválido';
            return;
        }

        $repo = new InformativosRepository();
        $informativo = $repo->getInformativoById($informativoId);
        if (!$informativo) {
            http_response_code(404);
            echo 'Informativo não encontrado';
            return;
        }

        // Interpretar requires_ack
        $requiresAck = false;
        $val = $informativo['requires_ack'] ?? null;
        if ($val === 1 || $val === '1' || $val === true || $val === 'true' || $val === 'Sim' || $val === 'sim') {
            $requiresAck = true;
        }

        // Filtro opcional (mesma lógica da tela/Excel): por nome/email.
        $usuarioFilter = trim((string)($_GET['usuario_filter'] ?? ''));
        $usuarioFilterLower = mb_strtolower($usuarioFilter);

        // Montar linhas do relatório
        $usersRepo = new UsersRepository();
        $usuarios = $usersRepo->getAllUsers(1, 10000, []);

        if ($usuarioFilterLower !== '') {
            $usuarios = array_values(array_filter($usuarios, function (array $u) use ($usuarioFilterLower) {
                $haystack = mb_strtolower((string)($u['name'] ?? '') . ' ' . (string)($u['email'] ?? ''));
                return mb_strpos($haystack, $usuarioFilterLower) !== false;
            }));
        }

        $rowsHtml = '';
        $visualizaram = 0;
        $cientes = 0;
        foreach ($usuarios as $usuario) {
            $read = $repo->getReadByUser($informativoId, (int)$usuario['id']);
            $visualizou = $read ? 'SIM' : 'NÃO';
            if ($visualizou === 'SIM') $visualizaram++;
            $dataVisualizacao = $read && $read['read_at'] ? date('d/m/Y H:i:s', strtotime($read['read_at'])) : '-';
            $estaCiente = $requiresAck ? (($read && !empty($read['acknowledged'])) ? 'SIM' : 'NÃO') : 'N/A';
            if ($estaCiente === 'SIM') $cientes++;
            $dataCiencia = $requiresAck && $read && !empty($read['ack_at']) ? date('d/m/Y H:i:s', strtotime($read['ack_at'])) : '-';
            $status = !$read ? 'PENDENTE' : ($requiresAck ? (!empty($read['acknowledged']) ? 'CIENTE' : 'VISUALIZOU MAS NÃO CIENTE') : 'VISUALIZOU');

            $rowsHtml .= '<tr>'
                . '<td><strong>' . htmlspecialchars($usuario['name']) . '</strong><br><small>' . htmlspecialchars($usuario['email']) . '</small></td>'
                . '<td>' . $visualizou . '</td>'
                . '<td>' . $dataVisualizacao . '</td>'
                . '<td>' . $estaCiente . '</td>'
                . '<td>' . $dataCiencia . '</td>'
                . '<td>' . $status . '</td>'
                . '</tr>';
        }

        $total = count($usuarios);
        $pendentes = $total - $visualizaram;
        $cientesCard = $requiresAck ? $cientes : 'N/A';

        // Cabeçalho com LOGO (usa data URI para Dompdf)
        $projectRoot = realpath(__DIR__ . '/../../../..');
        $logoPath = $projectRoot . '/public/adms/image/logo/logo.png';
        $logoImg = '';
        if (is_file($logoPath)) {
            $b64 = base64_encode(file_get_contents($logoPath));
            $logoImg = '<img src="data:image/png;base64,' . $b64 . '" alt="Logo" style="height:60px;">';
        } else {
            // fallback: texto
            $logoImg = '<div style="font-size:34px;color:#1b6e3a;font-weight:700;">TIARAJU</div>';
        }

        $header = '<div style="border:2px solid #1b6e3a;border-radius:10px;padding:16px;text-align:center;margin-bottom:12px;">'
            . $logoImg
            . '<div style="color:#607d8b;margin-top:6px;font-size:16px;">Sistema Administrativo</div>'
            . '<div style="color:#607d8b;margin-top:4px;font-size:16px;">Relatório de Informativo</div>'
            . '</div>';

        $infoTop = '<div style="background:#0d6efd;color:#fff;border-radius:8px;padding:10px 12px;margin-bottom:12px;">'
            . '<strong>' . htmlspecialchars($informativo['titulo']) . '</strong>'
            . '</div>'
            . '<table width="100%" cellspacing="0" cellpadding="2" style="font-size:12px;margin-bottom:10px;">'
            . '<tr>'
            . '<td><strong>Categoria:</strong> ' . htmlspecialchars($informativo['categoria_nome'] ?? $informativo['categoria']) . '</td>'
            . '<td><strong>Departamento:</strong> ' . htmlspecialchars($informativo['department_name'] ?? 'N/A') . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td><strong>Exige Ciência:</strong> ' . ($requiresAck ? 'SIM' : 'NÃO') . '</td>'
            . '<td><strong>Status:</strong> ' . ($informativo['ativo'] ? 'ATIVO' : 'INATIVO') . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td colspan="2"><strong>Criado em:</strong> ' . date('d/m/Y H:i:s', strtotime($informativo['created_at'])) . '</td>'
            . '</tr>'
            . '</table>';

        $cards = '<table width="100%" cellspacing="0" cellpadding="8" style="text-align:center;font-weight:600;margin:10px 0 16px 0;">'
            . '<tr>'
            . '<td style="background:#0d6efd;color:#fff;border-radius:8px;">' . $total . '<br><span style="font-weight:400;">Total de Usuários</span></td>'
            . '<td style="background:#198754;color:#fff;border-radius:8px;">' . $visualizaram . '<br><span style="font-weight:400;">Visualizaram</span></td>'
            . '<td style="background:#ffc107;border-radius:8px;">' . $pendentes . '<br><span style="font-weight:400;">Pendentes</span></td>'
            . '<td style="background:#0dcaf0;color:#fff;border-radius:8px;">' . $cientesCard . '<br><span style="font-weight:400;">Cientes</span></td>'
            . '</tr>'
            . '</table>';

        $table = '<table width="100%" border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse;font-size:12px;">'
            . '<thead style="background:#2c3e50;color:#fff;">'
            . '<tr>'
            . '<th style="text-align:left;">Usuário</th>'
            . '<th style="text-align:left;">Visualizou</th>'
            . '<th style="text-align:left;">Data Visualização</th>'
            . '<th style="text-align:left;">Está Ciente?</th>'
            . '<th style="text-align:left;">Data da Ciência</th>'
            . '<th style="text-align:left;">Status</th>'
            . '</tr>'
            . '</thead><tbody>' . $rowsHtml . '</tbody></table>';

        $html = '<html><head><meta charset="utf-8"></head><body style="font-family:DejaVu Sans, sans-serif;">'
            . $header . $infoTop . $cards . $table . '</body></html>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('relatorio_informativo_' . $informativoId . '_' . date('Y-m-d_H-i-s') . '.pdf', ['Attachment' => true]);
        exit;
    }
}


