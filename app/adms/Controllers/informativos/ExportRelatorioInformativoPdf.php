<?php

namespace App\adms\Controllers\informativos;

use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Services\InformativoRelatorioService;
use App\adms\Models\Services\InformativosPermissionService;
use Dompdf\Dompdf;

class ExportRelatorioInformativoPdf
{
    public function index(): void
    {
        $informativoId = (int) ($_GET['informativo_id'] ?? 0);
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

        $perm = new ButtonPermissionUserRepository();
        $relBtn = $perm->buttonPermission(['RelatorioInformativo']);
        if (!is_array($relBtn) || count($relBtn) === 0) {
            http_response_code(403);
            echo 'Sem permissão';
            return;
        }
        $userId = InformativosPermissionService::sessionUserId();
        $userDept = InformativosPermissionService::sessionUserDepartmentId();
        if (!InformativosPermissionService::canManageRecord($informativo, $userId, $userDept)) {
            http_response_code(403);
            echo 'Sem permissão';
            return;
        }

        $usuarioFilter = trim((string) ($_GET['usuario_filter'] ?? ''));
        $relatorio = (new InformativoRelatorioService())->build($informativoId, $informativo, $usuarioFilter);
        $requiresAck = $relatorio['requires_ack'];
        $kpis = $relatorio['kpis'];

        $cientesCard = $requiresAck
            ? $kpis['cientes'] . '<br><span style="font-weight:400;">' . InformativoRelatorioService::formatPct($kpis['pct_ciencia']) . '</span>'
            : 'N/A';

        $ativosTable = $this->buildTableHtml($relatorio['ativos']);
        $inativosTable = $this->buildTableHtml($relatorio['inativos_historico']);

        $projectRoot = realpath(__DIR__ . '/../../../..');
        $logoPath = $projectRoot . '/public/adms/image/logo/logo.png';
        $logoImg = '';
        if (is_file($logoPath)) {
            $b64 = base64_encode(file_get_contents($logoPath));
            $logoImg = '<img src="data:image/png;base64,' . $b64 . '" alt="Logo" style="height:60px;">';
        } else {
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

        $cards = '<table width="100%" cellspacing="0" cellpadding="8" style="text-align:center;font-weight:600;margin:10px 0 12px 0;">'
            . '<tr>'
            . '<td style="background:#0d6efd;color:#fff;border-radius:8px;">' . $kpis['total'] . '<br><span style="font-weight:400;">Total de ativos</span></td>'
            . '<td style="background:#198754;color:#fff;border-radius:8px;">' . $kpis['visualizaram']
            . '<br><span style="font-weight:400;">Visualizaram · ' . InformativoRelatorioService::formatPct($kpis['pct_visualizacao']) . '</span></td>'
            . '<td style="background:#ffc107;border-radius:8px;">' . $kpis['pendentes'] . '<br><span style="font-weight:400;">Pendentes</span></td>'
            . '<td style="background:#0dcaf0;color:#fff;border-radius:8px;">' . $cientesCard . '<br><span style="font-weight:400;">Cientes</span></td>'
            . '</tr>'
            . '</table>'
            . '<p style="font-size:11px;color:#555;margin:0 0 12px 0;">Percentuais calculados sobre colaboradores ativos. '
            . 'Inativos sem visualização ou ciência omitidos: ' . (int) $relatorio['excluidos_sem_historico']
            . '. Inativos com histórico: ' . count($relatorio['inativos_historico']) . '.</p>';

        $html = '<html><head><meta charset="utf-8"></head><body style="font-family:DejaVu Sans, sans-serif;">'
            . $header . $infoTop . $cards
            . '<h3 style="font-size:14px;margin:16px 0 8px 0;">Colaboradores ativos</h3>'
            . $ativosTable
            . '<h3 style="font-size:14px;margin:20px 0 8px 0;">Inativos com visualização ou ciência</h3>'
            . $inativosTable
            . '</body></html>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('relatorio_informativo_' . $informativoId . '_' . date('Y-m-d_H-i-s') . '.pdf', ['Attachment' => true]);
        exit;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function buildTableHtml(array $rows): string
    {
        $rowsHtml = '';
        if ($rows === []) {
            $rowsHtml = '<tr><td colspan="6" style="text-align:center;">Nenhum registro</td></tr>';
        } else {
            foreach ($rows as $dado) {
                $rowsHtml .= '<tr>'
                    . '<td><strong>' . htmlspecialchars((string) ($dado['usuario_nome'] ?? '')) . '</strong><br><small>'
                    . htmlspecialchars((string) ($dado['usuario_email'] ?? '')) . '</small></td>'
                    . '<td>' . htmlspecialchars((string) ($dado['visualizou'] ?? '')) . '</td>'
                    . '<td>' . htmlspecialchars((string) ($dado['data_visualizacao'] ?? '-')) . '</td>'
                    . '<td>' . htmlspecialchars((string) ($dado['esta_ciente'] ?? '')) . '</td>'
                    . '<td>' . htmlspecialchars((string) ($dado['data_ciencia'] ?? '-')) . '</td>'
                    . '<td>' . htmlspecialchars((string) ($dado['status'] ?? '')) . '</td>'
                    . '</tr>';
            }
        }

        return '<table width="100%" border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse;font-size:12px;">'
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
    }
}
