<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\UserFormHelper;

/**
 * HTML tipográfico do relatório de vistoria de equipamento (PDF / impressão).
 */
class SstEquipamentoVistoriaPdfService
{
    /**
     * @param array<string, mixed> $vistoria
     * @param list<array<string, mixed>> $respostas
     * @param list<array<string, mixed>> $anexos
     */
    public function buildHtml(array $vistoria, array $respostas, array $anexos): string
    {
        $esc = static fn (?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        $upload = new SstAnexosUploadService();

        $codigo = $esc($vistoria['equipamento_codigo'] ?? null);
        $tipo = $esc($vistoria['tipo_nome'] ?? null);
        $filial = $esc(UserFormHelper::empresaContratanteLabel($vistoria['empresa_contratante'] ?? null));
        $local = $esc($vistoria['localizacao'] ?? null);
        $competencia = $esc($vistoria['competencia'] ?? null);
        $status = $esc($vistoria['status'] ?? null);
        $resultado = $esc($vistoria['resultado'] ?? null);
        $obsRaw = trim((string) ($vistoria['observacao'] ?? ''));
        $obs = $obsRaw !== '' ? nl2br($esc($obsRaw)) : '—';
        $executor = $esc($vistoria['executor_nome'] ?? null);
        $dept = $esc($vistoria['departamento_nome'] ?? null);
        $prevista = !empty($vistoria['data_prevista'])
            ? date('d/m/Y', strtotime((string) $vistoria['data_prevista']))
            : '—';
        $realizada = !empty($vistoria['data_realizada'])
            ? date('d/m/Y H:i', strtotime((string) $vistoria['data_realizada']))
            : '—';
        $assinaturaEm = !empty($vistoria['assinatura_confirmada_em'])
            ? date('d/m/Y H:i', strtotime((string) $vistoria['assinatura_confirmada_em']))
            : '—';
        $assinaturaIp = trim((string) ($vistoria['assinatura_ip'] ?? ''));
        $gerado = date('d/m/Y H:i');

        $rows = '';
        foreach ($respostas as $i => $r) {
            $resp = (string) ($r['resposta'] ?? '—');
            $bg = $resp === 'Não conforme' ? 'background:#f8d7da;' : '';
            $rows .= '<tr style="' . $bg . '">'
                . '<td style="text-align:center">' . ($i + 1) . '</td>'
                . '<td>' . $esc($r['descricao_snapshot'] ?? null) . '</td>'
                . '<td style="text-align:center">' . $esc($resp) . '</td>'
                . '<td>' . $esc($r['observacao'] ?? null) . '</td>'
                . '</tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="4" style="text-align:center">Sem itens no checklist.</td></tr>';
        }

        $assinaturaExtra = '';
        if ($assinaturaIp !== '') {
            $assinaturaExtra .= ' · IP ' . $esc($assinaturaIp);
        }
        if ($executor !== '') {
            $assinaturaExtra .= ' · por ' . $executor;
        }

        $fotosHtml = '';
        $imgCount = 0;
        foreach ($anexos as $anexo) {
            $mime = strtolower((string) ($anexo['mime_type'] ?? ''));
            $path = (string) ($anexo['file_path'] ?? '');
            $abs = $upload->absolutePath($path);
            $isImage = str_starts_with($mime, 'image/') || (bool) preg_match('/\.(jpe?g|png|gif|webp)$/i', $path);
            if (!$isImage || $abs === '' || !is_file($abs)) {
                continue;
            }
            $imgCount++;
            $quando = !empty($anexo['created_at'])
                ? date('d/m/Y H:i', strtotime((string) $anexo['created_at']))
                : '—';
            $por = trim((string) ($anexo['uploaded_by_name'] ?? ''));
            $nome = $esc($anexo['file_name'] ?? null);
            $src = htmlspecialchars(str_replace('\\', '/', $abs), ENT_QUOTES, 'UTF-8');
            $fotosHtml .= '<div style="display:inline-block;width:48%;vertical-align:top;margin:0 1% 14px;page-break-inside:avoid">'
                . '<div style="border:1px solid #ccc;padding:6px;text-align:center">'
                . '<img src="' . $src . '" style="max-width:100%;max-height:220px" />'
                . '</div>'
                . '<div style="font-size:10px;color:#444;margin-top:4px">'
                . '<strong>Foto ' . $imgCount . '</strong> · ' . $quando
                . ($por !== '' ? ' · ' . $esc($por) : '')
                . '<br>' . $nome
                . '</div></div>';
        }
        if ($fotosHtml === '') {
            $fotosHtml = '<p style="color:#666;font-size:12px">Nenhuma foto anexada a esta vistoria.</p>';
        }

        return '<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
h1 { font-size: 16px; margin: 0 0 4px; }
h2 { font-size: 13px; margin: 18px 0 8px; border-bottom: 1px solid #333; padding-bottom: 3px; }
.meta { color: #555; font-size: 10px; margin-bottom: 12px; }
.grid td { padding: 3px 8px 3px 0; vertical-align: top; }
.grid .lbl { color: #666; width: 110px; }
table.chk { width: 100%; border-collapse: collapse; margin-top: 4px; }
table.chk th, table.chk td { border: 1px solid #999; padding: 5px 6px; }
table.chk th { background: #eee; font-size: 10px; }
.assinatura { margin-top: 14px; padding: 8px; border: 1px solid #aaa; background: #f7f7f7; font-size: 10px; }
</style>
</head>
<body>
<h1>Relatório de Vistoria — Equipamento SST</h1>
<div class="meta">Documento gerado em ' . $gerado . '</div>

<table class="grid" width="100%">
<tr><td class="lbl">Equipamento</td><td><strong>' . $codigo . '</strong></td><td class="lbl">Grupo</td><td>' . $tipo . '</td></tr>
<tr><td class="lbl">Filial</td><td>' . $filial . '</td><td class="lbl">Localização</td><td>' . $local . '</td></tr>
<tr><td class="lbl">Departamento</td><td>' . $dept . '</td><td class="lbl">Competência</td><td>' . $competencia . '</td></tr>
<tr><td class="lbl">Prevista</td><td>' . $prevista . '</td><td class="lbl">Realizada</td><td>' . $realizada . '</td></tr>
<tr><td class="lbl">Status</td><td>' . $status . '</td><td class="lbl">Resultado</td><td><strong>' . $resultado . '</strong></td></tr>
<tr><td class="lbl">Executor</td><td colspan="3">' . $executor . '</td></tr>
</table>

<h2>Checklist</h2>
<table class="chk">
<thead><tr><th style="width:36px">#</th><th>Item</th><th style="width:110px">Resposta</th><th>Observação</th></tr></thead>
<tbody>' . $rows . '</tbody>
</table>

<h2>Observação geral</h2>
<p>' . $obs . '</p>

<div class="assinatura">
<strong>Assinatura eletrônica</strong><br>
Confirmada em ' . $assinaturaEm . $assinaturaExtra . '
</div>

<h2>Fotos da vistoria</h2>
' . $fotosHtml . '
</body>
</html>';
    }
}
