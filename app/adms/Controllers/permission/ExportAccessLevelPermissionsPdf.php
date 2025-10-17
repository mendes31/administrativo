<?php

namespace App\adms\Controllers\permission;

use App\adms\Models\Repository\AccessLevelsRepository;
use App\adms\Models\Repository\AccessLevelsPagesRepository;
use App\adms\Models\Repository\PagesRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\adms\Models\Repository\UsersAccessLevelsRepository;

class ExportAccessLevelPermissionsPdf
{
    public function index(int|string $id = 0): void
    {
        // O roteador envia o ID como primeiro parâmetro
        $levelId = (int)$id;
        if ($levelId <= 0) {
            http_response_code(422);
            echo 'Nível inválido';
            return;
        }

        $levelRepo = new AccessLevelsRepository();
        $level = $levelRepo->getAccessLevel($levelId);
        if (!$level) {
            http_response_code(404);
            echo 'Nível não encontrado';
            return;
        }

        $pagesRepo = new PagesRepository();
        $pages = $pagesRepo->getAllPagesFull();
        $permRepo = new AccessLevelsPagesRepository();
        $perms = $permRepo->getPagesAccessLevelsArray($levelId, true) ?? [];

        // Logo
        $projectRoot = realpath(__DIR__ . '/../../../..');
        $logoPath = $projectRoot . '/public/adms/image/logo/logo.png';
        $logo = is_file($logoPath) ? '<img src="data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) . '" style="height:50px;" />' : '<strong>TIARAJU</strong>';

        // Contagem
        $totalPaginas = count($pages);
        $autorizadas = count($perms);
        $revogadas = $totalPaginas - $autorizadas;

        // Tabela por grupo
        $rows = '';
        foreach ($pages as $pg) {
            $groupName = $pg['agp_name'] ?? $pg['group_page'] ?? ($pg['group'] ?? '-');
            $authorized = isset($perms[$pg['id']]) ? 'Autorizado' : 'Negado';
            $rows .= '<tr>'
                . '<td>' . htmlspecialchars($groupName) . '</td>'
                . '<td>' . htmlspecialchars($pg['name'] ?? '-') . '</td>'
                . '<td>' . $authorized . '</td>'
                . '</tr>';
        }

        // Usuários vinculados ao nível selecionado (usado no card e no bloco final)
        $usersRepo = new UsersAccessLevelsRepository();
        $users = $this->getUsersByLevel($usersRepo, $levelId);
        $usuariosVinculados = count($users);

        // Indicador de cobertura (perm. ativas / total de páginas)
        $cobertura = $totalPaginas > 0 ? round(($autorizadas / $totalPaginas) * 100) : 0;

        // Cards uniformes (4 colunas, 25% cada)
        $cards = '<table width="100%" cellspacing="0" cellpadding="10" style="text-align:center;font-weight:600;margin:12px 0 18px 0;">'
            . '<tr>'
            . '<td style="width:25%;background:#0d6efd;color:#fff;border-radius:8px;">' . $totalPaginas . '<br><span style="font-weight:400;">TOTAL DE PÁGINAS</span></td>'
            . '<td style="width:25%;background:#198754;color:#fff;border-radius:8px;">' . $autorizadas . '<br><span style="font-weight:400;">PERMISSÕES ATIVAS</span></td>'
            . '<td style="width:25%;background:#17a2b8;color:#fff;border-radius:8px;">' . $usuariosVinculados . '<br><span style="font-weight:400;">USUÁRIOS VINCULADOS</span></td>'
            . '<td style="width:25%;background:#ffc107;border-radius:8px;">' . $cobertura . '%<br><span style="font-weight:400;">COBERTURA</span></td>'
            . '</tr></table>';
        $usersBlock = '';
        if (!empty($users)) {
            $usersRows = '';
            foreach ($users as $u) {
                $usersRows .= '<div style="padding:8px 12px;border-radius:8px;background:#f8f9fa;margin-bottom:8px;">'
                    . '<strong>' . htmlspecialchars($u['name']) . '</strong><br>'
                    . '<span style="color:#6c757d">' . htmlspecialchars($u['email']) . '</span>'
                    . '</div>';
            }
            $usersBlock = '<div style="margin-top:16px;">'
                . '<div style="background:#198754;color:#fff;border-radius:8px;padding:10px 12px;margin-bottom:8px;font-weight:700;">Usuários Vinculados</div>'
                . $usersRows
                . '</div>';
        }

        $html = '<html><head><meta charset="utf-8"></head><body style="font-family:DejaVu Sans, sans-serif;">'
            . '<div style="border:2px solid #1b6e3a;border-radius:10px;padding:16px;text-align:center;margin-bottom:12px;">' . $logo . '<div style="color:#607d8b;margin-top:6px;font-size:16px;">Sistema Administrativo</div><div style="color:#607d8b;margin-top:4px;font-size:16px;">Relatório de Permissões de Nível de Acesso</div></div>'
            . '<div style="background:#198754;color:#fff;border-radius:8px;padding:10px 12px;margin-bottom:12px;text-align:center;font-weight:700;">' . htmlspecialchars($level['name']) . '</div>'
            . $cards
            . '<table width="100%" border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse;font-size:12px;">'
            . '<thead style="background:#2c3e50;color:#fff;"><tr><th>Grupo</th><th>Página</th><th>Situação</th></tr></thead><tbody>' . $rows . '</tbody></table>'
            . $usersBlock
            . '</body></html>';

        try {
            error_log('[ExportAccessLevelPermissionsPdf] Iniciando export do nível ' . $levelId);
            ini_set('memory_limit', '512M');
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfOutput = $dompdf->output();
            if (ob_get_length()) { ob_end_clean(); }
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="permissoes_nivel_' . $levelId . '_' . date('Y-m-d_H-i-s') . '.pdf"');
            header('Content-Length: ' . strlen($pdfOutput));
            echo $pdfOutput;
        } catch (\Throwable $t) {
            header('Content-Type: text/html; charset=utf-8');
            echo '<pre>Erro ao gerar PDF: ' . htmlspecialchars($t->getMessage()) . '</pre>';
        }
        exit;
    }

    private function getUsersByLevel(UsersAccessLevelsRepository $repo, int $levelId): array
    {
        $sql = 'SELECT u.id, u.name, u.email
                FROM adms_users u
                INNER JOIN adms_users_access_levels ul ON ul.adms_user_id = u.id
                WHERE ul.adms_access_level_id = :level
                AND u.status = :status
                ORDER BY u.name ASC';
        $stmt = $repo->getConnection()->prepare($sql);
        $stmt->bindValue(':level', $levelId, \PDO::PARAM_INT);
        $stmt->bindValue(':status', 'Ativo');
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}


