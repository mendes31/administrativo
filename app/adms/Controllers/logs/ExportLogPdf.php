<?php

namespace App\adms\Controllers\logs;

use App\adms\Models\Repository\LogAlteracoesRepository;
use App\adms\Models\Repository\LogAlteracoesDetalhesRepository;
use App\adms\Models\Repository\LgpdBasesLegaisRepository;
use Dompdf\Dompdf;

class ExportLogPdf
{
    public function index(): void
    {
        $filtros = $this->getFiltros();
        $repo = new LogAlteracoesRepository();

        // Quando estamos no modo detalhes, queremos as instâncias em ordem crescente de ID Log
        // para que o número da instância acompanhe a ordem dos detalhes.
        if (!empty($_GET['detalhes'])) {
            $logs = $repo->getAll(1, 10000, $filtros, 'id', 'ASC');
        } else {
            $logs = $repo->getAll(1, 10000, $filtros);
        }

        // Se solicitado modo detalhes (detalhes=1) e houver filtro por tabela/objeto,
        // gerar PDF no formato "Detalhes das modificações agrupados por instância"
        if (!empty($_GET['detalhes']) && !empty($filtros['tabela']) && !empty($filtros['objeto_id']) && !empty($logs)) {
            $logIds = array_column($logs, 'id');
            $detRepo = new LogAlteracoesDetalhesRepository();
            $detalhes = $detRepo->getByLogIds($logIds);

            // Agrupar por instância
            $grupos = [];
            foreach ($detalhes as $det) {
                $grupos[$det['log_alteracao_id']][] = $det;
            }

            // Cabeçalho com LOGO
            $projectRoot = realpath(__DIR__ . '/../../../..');
            $logoPath = $projectRoot . '/public/adms/image/logo/logo.png';
            $logoImg = '';
            if (is_file($logoPath)) {
                $b64 = base64_encode(file_get_contents($logoPath));
                $logoImg = '<img src="data:image/png;base64,' . $b64 . '" alt="Logo" style="height:60px;">';
            } else {
                $logoImg = '<div style="font-size:28px;color:#1b6e3a;font-weight:700;">TIARAJU</div>';
            }

            $identificador = $logs[0]['identificador'] ?? '';
            $tabela = $logs[0]['tabela'] ?? $filtros['tabela'];
            $objId = $logs[0]['objeto_id'] ?? $filtros['objeto_id'];

            $header = '<div style="border:2px solid #1b6e3a;border-radius:10px;padding:12px;text-align:center;margin-bottom:10px;">'
                . $logoImg
                . '<div style="color:#607d8b;margin-top:4px;font-size:14px;">Sistema Administrativo</div>'
                . '<div style="color:#607d8b;margin-top:2px;font-size:14px;">Log de Modificações</div>'
                . '</div>';

            // Informações do registro (genéricas)
            $infoRegistro = '<table width="100%" cellspacing="0" cellpadding="4" style="font-size:11px;margin-bottom:6px;">'
                . '<tr>'
                . '<td><strong>Tabela:</strong> ' . htmlspecialchars($tabela) . '</td>'
                . '<td style="text-align:right;"><strong>ID Objeto:</strong> ' . htmlspecialchars($objId) . '</td>'
                . '</tr><tr>'
                . '<td colspan="2"><strong>Identificador:</strong> ' . htmlspecialchars($identificador) . '</td>'
                . '</tr>'
                . '</table>';

            // Informações específicas para algumas tabelas (ex.: lgpd_bases_legais)
            if ($tabela === 'lgpd_bases_legais') {
                $basesRepo = new LgpdBasesLegaisRepository();
                $base = $basesRepo->getById((int)$objId);
                if ($base) {
                    $statusTxt = ($base['status'] ?? '') === '1' || ($base['status'] ?? '') === 1 || ($base['status'] ?? '') === 'Ativo'
                        ? 'Ativo'
                        : 'Inativo';

                    $infoRegistro .= '<table width="100%" cellspacing="0" cellpadding="4" style="font-size:11px;margin-bottom:10px;border:1px solid #ccc;border-collapse:collapse;">'
                        . '<tr style="background:#f5f5f5;">'
                        . '<td colspan="2"><strong>Detalhes da Base Legal LGPD</strong></td>'
                        . '</tr>'
                        . '<tr>'
                        . '<td width="70%"><strong>Base Legal:</strong> ' . htmlspecialchars($base['base_legal'] ?? '') . '</td>'
                        . '<td width="30%"><strong>Status:</strong> ' . htmlspecialchars($statusTxt) . '</td>'
                        . '</tr>'
                        . '<tr>'
                        . '<td colspan="2"><strong>Descrição:</strong> ' . htmlspecialchars($base['descricao'] ?? '') . '</td>'
                        . '</tr>'
                        . '<tr>'
                        . '<td colspan="2"><strong>Exemplo:</strong> ' . htmlspecialchars($base['exemplo'] ?? '') . '</td>'
                        . '</tr>'
                        . '<tr>'
                        . '<td><strong>Criado em:</strong> ' . (!empty($base['created_at']) ? date('d/m/Y', strtotime($base['created_at'])) : '-') . '</td>'
                        . '<td><strong>Atualizado em:</strong> ' . (!empty($base['updated_at']) ? date('d/m/Y', strtotime($base['updated_at'])) : '-') . '</td>'
                        . '</tr>'
                        . '</table>';
                }
            }

            // Tabela 1: resumo das instâncias
            $tableInst = '<h4 style="margin:6px 0;">Instâncias registradas</h4>'
                . '<table width="100%" border="1" cellspacing="0" cellpadding="5" style="border-collapse:collapse;font-size:11px;margin-bottom:12px;">'
                . '<thead style="background:#e0e0e0;">'
                . '<tr>'
                . '<th style="width:6%;">#</th>'
                . '<th style="width:10%;">ID Log</th>'
                . '<th style="width:18%;">Data/Hora</th>'
                . '<th style="width:10%;">Tipo</th>'
                . '<th style="width:14%;">Usuário</th>'
                . '<th style="width:12%;">IP</th>'
                . '<th>Hostname</th>'
                . '</tr></thead><tbody>';

            $instancia = 1;
            foreach ($logs as $log) {
                $tableInst .= '<tr>'
                    . '<td>' . $instancia++ . '</td>'
                    . '<td>' . $log['id'] . '</td>'
                    . '<td>' . date('d/m/Y H:i:s', strtotime($log['data_alteracao'])) . '</td>'
                    . '<td>' . htmlspecialchars($log['tipo_operacao']) . '</td>'
                    . '<td>' . htmlspecialchars($log['usuario_nome'] ?: $log['usuario_id']) . '</td>'
                    . '<td>' . htmlspecialchars($log['ip']) . '</td>'
                    . '<td>' . htmlspecialchars($log['hostname'] ?? '') . '</td>'
                    . '</tr>';
            }
            if (empty($logs)) {
                $tableInst .= '<tr><td colspan="7" style="text-align:center;color:#888;">Nenhuma instância encontrada.</td></tr>';
            }
            $tableInst .= '</tbody></table>';

            // Tabela 2: detalhes agrupados por instância
            $tableDet = '<h4 style="margin:6px 0;">Detalhes das modificações (agrupados por instância)</h4>'
                . '<table width="100%" border="1" cellpadding="4" cellspacing="0" style="font-size:11px; border-collapse:collapse;">'
                . '<thead><tr style="background:#e0e0e0;">'
                . '<th style="width:4%;">#</th><th style="width:14%;">Data</th><th style="width:8%;">Tipo</th>'
                . '<th style="width:20%;">Campo modificado</th><th style="width:27%;">Valor anterior</th>'
                . '<th style="width:27%;">Novo valor</th><th style="width:12%;">Usuário</th>'
                . '</tr></thead><tbody>';

            $instancia = 1;
            foreach ($grupos as $logId => $lista) {
                $tableDet .= '<tr style="background:#f5f5f5;font-weight:bold;"><td colspan="7">Instância ' . $instancia++ . ' — ID Log: ' . $logId . '</td></tr>';
                foreach ($lista as $det) {
                    $tipo = strtoupper($det['tipo_operacao'] ?? '');
                    $tableDet .= '<tr>'
                        . '<td></td>'
                        . '<td>' . date('d/m/Y H:i:s', strtotime($det['data_alteracao'])) . '</td>'
                        . '<td>' . htmlspecialchars($tipo) . '</td>'
                        . '<td>' . htmlspecialchars($det['campo'] ?? '') . '</td>'
                        . '<td>' . htmlspecialchars($det['valor_anterior'] ?? '') . '</td>'
                        . '<td>' . htmlspecialchars($det['valor_novo'] ?? '') . '</td>'
                        . '<td>' . htmlspecialchars($det['usuario_nome'] ?? '') . '</td>'
                        . '</tr>';
                }
            }
            if (empty($grupos)) {
                $tableDet .= '<tr><td colspan="7" style="text-align:center; color:#888;">Nenhum detalhe encontrado.</td></tr>';
            }
            $tableDet .= '</tbody></table>';

            $html = '<html><head><meta charset="utf-8"></head>'
                . '<body style="font-family:DejaVu Sans, sans-serif;">'
                . $header . $infoRegistro . $tableInst . $tableDet
                . '</body></html>';
        } else {
            // Modo padrão: tabela de instâncias
            $html = '<h2 style="text-align:center;">Log de Modificações</h2>';
            $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="font-size:12px; border-collapse:collapse;">';
            $html .= '<thead><tr style="background:#f0f0f0;">';
            $html .= '<th>ID</th><th>Tabela</th><th>ID Objeto</th><th>Identificador</th><th>Usuário</th><th>Data/Hora</th><th>Tipo</th><th>IP</th><th>User Agent</th>';
            $html .= '</tr></thead><tbody>';
            foreach ($logs as $log) {
                $html .= '<tr>';
                $html .= '<td>' . $log['id'] . '</td>';
                $html .= '<td>' . htmlspecialchars($log['tabela']) . '</td>';
                $html .= '<td>' . $log['objeto_id'] . '</td>';
                $html .= '<td>' . htmlspecialchars($log['identificador']) . '</td>';
                $html .= '<td>' . htmlspecialchars($log['usuario_nome'] ?: $log['usuario_id']) . '</td>';
                $html .= '<td>' . date('d/m/Y H:i:s', strtotime($log['data_alteracao'])) . '</td>';
                $html .= '<td>' . htmlspecialchars($log['tipo_operacao']) . '</td>';
                $html .= '<td>' . htmlspecialchars($log['ip']) . '</td>';
                $html .= '<td>' . htmlspecialchars($log['user_agent']) . '</td>';
                $html .= '</tr>';
            }
            if (empty($logs)) {
                $html .= '<tr><td colspan="9" style="text-align:center; color:#888;">Nenhum log encontrado.</td></tr>';
            }
            $html .= '</tbody></table>';
        }

        // Gerar PDF
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('logs_alteracoes_' . date('Y-m-d_H-i-s') . '.pdf', ['Attachment' => true]);
        exit;
    }
    
    private function getFiltros(): array
    {
        return [
            'tabela' => $_GET['tabela'] ?? '',
            'objeto_id' => $_GET['objeto_id'] ?? '',
            'identificador' => $_GET['identificador'] ?? '',
            'usuario_nome' => $_GET['usuario_nome'] ?? '',
            'tipo' => $_GET['tipo'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
        ];
    }
} 