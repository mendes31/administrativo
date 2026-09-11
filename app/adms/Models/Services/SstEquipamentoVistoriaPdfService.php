<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\PdfInstitutionalHeaderHelper;
use App\adms\Helpers\UserFormHelper;

/**
 * HTML tipográfico do relatório de vistoria de equipamento (PDF / impressão).
 */
class SstEquipamentoVistoriaPdfService
{
    /**
     * @param array<string, mixed> $vistoria
     * @param list<array<string, mixed>> $respostas
     * @param list<array<string, mixed>> $anexos fotos da vistoria
     * @param list<array<string, mixed>> $naoConformidades
     * @param array<int, list<array<string, mixed>>> $acoesPorNcId id NC => ações
     * @param array<int, list<array<string, mixed>>> $evidenciasPorAcaoId id AC => anexos
     */
    public function buildHtml(
        array $vistoria,
        array $respostas,
        array $anexos,
        array $naoConformidades = [],
        array $acoesPorNcId = [],
        array $evidenciasPorAcaoId = [],
    ): string {
        $esc = static fn (?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        $competencia = $esc($vistoria['competencia'] ?? null);
        $gerado = date('d/m/Y H:i');

        $empresaBlock = PdfInstitutionalHeaderHelper::buildEmpresaInfoTable(
            $vistoria['empresa_contratante'] ?? null,
            'relatório'
        );

        $section = $this->buildVistoriaSectionHtml(
            $vistoria,
            $respostas,
            $anexos,
            $naoConformidades,
            $acoesPorNcId,
            $evidenciasPorAcaoId
        );

        return '<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<style>
' . $this->cssStyles() . '
</style>
</head>
<body>
' . $empresaBlock . '
<div class="meta">Gerado em ' . $gerado . ' · Competência ' . $competencia . '</div>
' . $section . '
</body>
</html>';
    }

    /**
     * @return array{title: string, subtitle: string}
     */
    public static function documentHeaderMeta(): array
    {
        return [
            'title' => 'RELATÓRIO DE VISTORIA — EQUIPAMENTO SST',
            'subtitle' => 'Documento para auditoria / impressão',
        ];
    }

    /**
     * Corpo completo de uma vistoria (checklist, NC, fotos) para embed no relatório de auditoria.
     *
     * @param array<string, mixed> $vistoria
     * @param list<array<string, mixed>> $respostas
     * @param list<array<string, mixed>> $anexos
     * @param list<array<string, mixed>> $naoConformidades
     * @param array<int, list<array<string, mixed>>> $acoesPorNcId
     * @param array<int, list<array<string, mixed>>> $evidenciasPorAcaoId
     */
    public function buildVistoriaSectionHtml(
        array $vistoria,
        array $respostas,
        array $anexos,
        array $naoConformidades = [],
        array $acoesPorNcId = [],
        array $evidenciasPorAcaoId = [],
        ?string $sectionHeading = null,
        bool $pageBreakBefore = false,
    ): string {
        $esc = static fn (?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

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

        $fotosHtml = $this->buildFotosBlock($anexos, 'vistoria', $esc);
        $ncHtml = $this->buildNcEvidenciasHtml($naoConformidades, $acoesPorNcId, $evidenciasPorAcaoId, $esc);

        $wrapStyle = $pageBreakBefore ? ' style="page-break-before:always"' : '';
        $heading = $sectionHeading !== null && $sectionHeading !== ''
            ? '<h2 class="vist-section"' . $wrapStyle . '>' . $esc($sectionHeading) . '</h2>'
            : ($pageBreakBefore ? '<div style="page-break-before:always"></div>' : '');

        return $heading . '
<table class="grid" width="100%">
<tr><td class="lbl">Equipamento</td><td><strong>' . $codigo . '</strong></td><td class="lbl">Grupo</td><td>' . $tipo . '</td></tr>
<tr><td class="lbl">Filial</td><td>' . $filial . '</td><td class="lbl">Localização</td><td>' . $local . '</td></tr>
<tr><td class="lbl">Departamento</td><td>' . $dept . '</td><td class="lbl">Competência</td><td>' . $competencia . '</td></tr>
<tr><td class="lbl">Prevista</td><td>' . $prevista . '</td><td class="lbl">Realizada</td><td>' . $realizada . '</td></tr>
<tr><td class="lbl">Status</td><td>' . $status . '</td><td class="lbl">Resultado</td><td><strong>' . $resultado . '</strong></td></tr>
<tr><td class="lbl">Executor</td><td colspan="3">' . $executor . '</td></tr>
</table>

<h3>Checklist</h3>
<table class="chk">
<thead><tr><th style="width:36px">#</th><th>Item</th><th style="width:110px">Resposta</th><th>Observação</th></tr></thead>
<tbody>' . $rows . '</tbody>
</table>

<h3>Observação geral</h3>
<p>' . $obs . '</p>

<div class="assinatura">
<strong>Assinatura eletrônica</strong><br>
Confirmada em ' . $assinaturaEm . $assinaturaExtra . '
</div>

' . $ncHtml . '

<h3>Fotos da vistoria</h3>
' . $fotosHtml;
    }

    public function cssStyles(): string
    {
        return 'body { font-family: dejavusans, sans-serif; font-size: 11px; color: #222222; }
h1 { font-size: 16px; margin: 0 0 4px; }
h2 { font-size: 13px; margin: 18px 0 8px; border-bottom: 1px solid #333333; padding-bottom: 3px; }
h2.vist-section { font-size: 12px; margin-top: 20px; }
h3 { font-size: 11px; margin: 12px 0 6px; color: #333333; border-bottom: 1px solid #cccccc; padding-bottom: 2px; }
.meta { color: #555555; font-size: 10px; margin-bottom: 12px; }
.grid td { padding: 3px 8px 3px 0; vertical-align: top; }
.grid .lbl { color: #666666; width: 110px; }
table.chk { width: 100%; border-collapse: collapse; margin-top: 4px; }
table.chk th, table.chk td { border: 1px solid #999999; padding: 5px 6px; }
table.chk th { background-color: #eeeeee; font-size: 10px; }
.assinatura { margin-top: 14px; padding: 8px; border: 1px solid #aaaaaa; background-color: #f7f7f7; font-size: 10px; }
.nc-box { border: 1px solid #cc0000; background-color: #fff5f5; padding: 8px; margin: 10px 0; }
.ac-box { border: 1px solid #999999; background-color: #f9f9f9; padding: 6px; margin: 6px 0 8px; }
.vist-detail-wrap { margin-bottom: 18px; }';
    }

    /**
     * @param list<array<string, mixed>> $naoConformidades
     * @param array<int, list<array<string, mixed>>> $acoesPorNcId
     * @param array<int, list<array<string, mixed>>> $evidenciasPorAcaoId
     */
    private function buildNcEvidenciasHtml(
        array $naoConformidades,
        array $acoesPorNcId,
        array $evidenciasPorAcaoId,
        callable $esc,
    ): string {
        if ($naoConformidades === []) {
            return '';
        }

        $html = '<h2>Não conformidades e evidências</h2>';
        $html .= '<p style="font-size:10px;color:#555">O resultado da vistoria permanece Não conforme. Abaixo: NC, ação corretiva e evidências fotográficas quando existirem.</p>';

        foreach ($naoConformidades as $nc) {
            $ncId = (int) ($nc['id'] ?? 0);
            $html .= '<div class="nc-box">';
            $html .= '<strong>' . $esc($nc['codigo'] ?? 'NC') . '</strong> — ' . $esc($nc['descricao'] ?? null);
            $html .= '<br><span style="font-size:10px">Status: <strong>' . $esc($nc['status'] ?? null) . '</strong>';
            if (trim((string) ($nc['observacao'] ?? '')) !== '') {
                $html .= ' · Observação do desvio: ' . $esc($nc['observacao']);
            }
            $html .= '</span>';

            if (($nc['status'] ?? '') === 'Encerrada') {
                $html .= '<div style="margin-top:6px;font-size:10px;color:#00aa55;">Encerrada';
                if (!empty($nc['acao_encerramento_codigo'])) {
                    $html .= ' mediante <strong>' . $esc($nc['acao_encerramento_codigo']) . '</strong>';
                    if (!empty($nc['acao_encerramento_titulo'])) {
                        $html .= ' — ' . $esc($nc['acao_encerramento_titulo']);
                    }
                }
                if (!empty($nc['encerrada_em'])) {
                    $html .= ' em ' . date('d/m/Y H:i', strtotime((string) $nc['encerrada_em']));
                }
                if (!empty($nc['acao_encerramento_descricao'])) {
                    $html .= '<br>Descrição da AC: ' . $esc($nc['acao_encerramento_descricao']);
                }
                if (!empty($nc['acao_encerramento_obs'])) {
                    $html .= '<br>Obs. da AC: ' . $esc($nc['acao_encerramento_obs']);
                }
                $html .= '</div>';
            }

            $acoes = $acoesPorNcId[$ncId] ?? [];
            if ($acoes === []) {
                $html .= '<p style="font-size:10px;color:#666;margin:6px 0 0">Sem ações corretivas cadastradas.</p>';
            } else {
                $html .= '<h3>Ações corretivas</h3>';
                foreach ($acoes as $acao) {
                    $aid = (int) ($acao['id'] ?? 0);
                    $isEncerramento = !empty($nc['encerrada_por_acao_id'])
                        && (int) $nc['encerrada_por_acao_id'] === $aid;
                    $html .= '<div class="ac-box">';
                    $html .= '<strong>' . $esc($acao['codigo'] ?? 'AC') . '</strong> — ' . $esc($acao['titulo'] ?? null);
                    if ($isEncerramento) {
                        $html .= ' <span style="color:#00aa55;font-size:9px">(encerrou a NC)</span>';
                    }
                    $html .= '<br><span style="font-size:10px">Status: ' . $esc($acao['status'] ?? null);
                    if (!empty($acao['responsavel_nome'])) {
                        $html .= ' · Resp.: ' . $esc($acao['responsavel_nome']);
                    }
                    if (!empty($acao['prazo'])) {
                        $html .= ' · Prazo: ' . date('d/m/Y', strtotime((string) $acao['prazo']));
                    }
                    if (!empty($acao['data_conclusao'])) {
                        $html .= ' · Conclusão: ' . date('d/m/Y', strtotime((string) $acao['data_conclusao']));
                    }
                    $html .= '</span>';
                    if (trim((string) ($acao['descricao'] ?? '')) !== '') {
                        $html .= '<br><span style="font-size:10px">' . $esc($acao['descricao']) . '</span>';
                    }
                    if (trim((string) ($acao['observacoes'] ?? '')) !== '') {
                        $html .= '<br><span style="font-size:10px">Obs.: ' . $esc($acao['observacoes']) . '</span>';
                    }

                    $evs = $evidenciasPorAcaoId[$aid] ?? [];
                    if ($evs !== []) {
                        $html .= '<div style="margin-top:6px"><span style="font-size:10px;font-weight:bold">Evidências fotográficas da ação:</span><br>';
                        $html .= $this->buildFotosBlock($evs, 'ac-' . $aid, $esc);
                        $html .= '</div>';
                    } else {
                        $html .= '<p style="font-size:9px;color:#888;margin:4px 0 0">Sem fotos de evidência nesta ação.</p>';
                    }
                    $html .= '</div>';
                }
            }
            $html .= '</div>';
        }

        return $html;
    }

    /** @param list<array<string, mixed>> $anexos */
    private function buildFotosBlock(array $anexos, string $prefix, callable $esc): string
    {
        $upload = new SstAnexosUploadService();
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
            $imgSrc = $this->prepareImageForMpdf($abs);
            if ($imgSrc === null) {
                continue;
            }
            $imgCount++;
            $quando = !empty($anexo['created_at'])
                ? date('d/m/Y H:i', strtotime((string) $anexo['created_at']))
                : '—';
            $por = trim((string) ($anexo['uploaded_by_name'] ?? ''));
            $nome = $esc($anexo['file_name'] ?? null);
            $fotosHtml .= '<table width="100%" style="border-collapse:collapse;margin:0 0 10px 0;"><tr><td style="border:1px solid #cccccc;padding:6px;text-align:center;">'
                . '<img src="' . htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') . '" width="220" />'
                . '</td></tr><tr><td style="font-size:10px;color:#444444;padding-top:4px;">'
                . '<strong>Foto ' . $prefix . '-' . $imgCount . '</strong> · ' . $quando
                . ($por !== '' ? ' · ' . $esc($por) : '')
                . '<br>' . $nome
                . '</td></tr></table>';
        }
        if ($fotosHtml === '') {
            return '<p style="color:#666;font-size:12px">Nenhuma foto anexada.</p>';
        }

        return $fotosHtml;
    }

    /**
     * Redimensiona e grava JPEG temporário para o mPDF (evita HTML gigante com base64).
     */
    private function prepareImageForMpdf(string $absPath): ?string
    {
        $tempDir = (defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 3))
            . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp'
            . DIRECTORY_SEPARATOR . 'mpdf' . DIRECTORY_SEPARATOR . 'images';
        if (!is_dir($tempDir) && !@mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
            return null;
        }

        $dest = $tempDir . DIRECTORY_SEPARATOR . 'pdf_' . md5($absPath . (string) filemtime($absPath)) . '.jpg';
        if (is_file($dest) && filesize($dest) > 0) {
            return str_replace('\\', '/', $dest);
        }

        if (!function_exists('imagecreatefromstring')) {
            // Fallback: caminho original (mPDF lê arquivo local)
            return str_replace('\\', '/', $absPath);
        }

        $raw = @file_get_contents($absPath);
        if ($raw === false || $raw === '') {
            return null;
        }
        $src = @imagecreatefromstring($raw);
        if ($src === false) {
            return str_replace('\\', '/', $absPath);
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $maxW = 900;
        if ($w > $maxW) {
            $nw = $maxW;
            $nh = (int) max(1, round($h * ($maxW / $w)));
            $dst = imagecreatetruecolor($nw, $nh);
            if ($dst === false) {
                imagedestroy($src);

                return str_replace('\\', '/', $absPath);
            }
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
            $src = $dst;
        }

        // Fundo branco para PNG com transparência
        $finalW = imagesx($src);
        $finalH = imagesy($src);
        $canvas = imagecreatetruecolor($finalW, $finalH);
        if ($canvas === false) {
            imagedestroy($src);

            return str_replace('\\', '/', $absPath);
        }
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $src, 0, 0, 0, 0, $finalW, $finalH);
        imagedestroy($src);

        $ok = imagejpeg($canvas, $dest, 72);
        imagedestroy($canvas);
        if (!$ok || !is_file($dest)) {
            return str_replace('\\', '/', $absPath);
        }

        return str_replace('\\', '/', $dest);
    }
}
