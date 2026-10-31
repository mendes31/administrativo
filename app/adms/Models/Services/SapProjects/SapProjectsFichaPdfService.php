<?php

declare(strict_types=1);

namespace App\adms\Models\Services\SapProjects;

/**
 * HTML da ficha imprimível de um projeto SAP (resumo do projeto → parceiro → docs → itens).
 */
final class SapProjectsFichaPdfService
{
    /**
     * @return array{title: string, subtitle: string}
     */
    public static function documentHeaderMeta(array $projeto): array
    {
        $num = (int) ($projeto['NumProjeto'] ?? 0);
        $nome = trim((string) ($projeto['NomeProjeto'] ?? ''));

        return [
            'title' => 'FICHA DO PROJETO SAP B1' . ($num > 0 ? ' Nº ' . $num : ''),
            'subtitle' => $nome !== '' ? $nome : 'Gerenciamento de Projetos (OPMG)',
        ];
    }

    /** @param array<string, mixed> $detail Retorno de SapProjectsService::projectDetail() */
    public function buildHtml(array $detail): string
    {
        $esc = static fn (mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        $money = static fn (mixed $v): string => 'R$ ' . number_format((float) ($v ?? 0), 2, ',', '.');
        $brDate = static function (mixed $v) use ($esc): string {
            $s = substr((string) ($v ?? ''), 0, 10);
            if ($s === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
                return $esc($v);
            }
            [$y, $m, $d] = explode('-', $s);

            return $d . '/' . $m . '/' . $y;
        };
        $fmt = static function (array $field) use ($esc, $money): string {
            $type = (string) ($field['type'] ?? 'text');
            $value = $field['value'] ?? null;
            if ($type === 'money') {
                $txt = $money($value);
                $tone = (string) ($field['tone'] ?? '');
                if ($tone === 'good' && (float) $value > 0) {
                    $txt = '+' . $txt;
                }
                if ($tone === 'good') {
                    return '<span style="color:#146C3A;font-weight:700">' . $esc($txt) . '</span>';
                }
                if ($tone === 'bad') {
                    return '<span style="color:#B42318;font-weight:700">' . $esc($txt) . '</span>';
                }

                return $txt;
            }
            if ($type === 'int') {
                return $esc((string) (int) $value);
            }
            if ($type === 'flag') {
                return $esc((string) $value);
            }

            return $esc((string) $value);
        };

        $p = is_array($detail['projeto'] ?? null) ? $detail['projeto'] : [];
        $totais = is_array($detail['totais'] ?? null) ? $detail['totais'] : [];
        $identificacao = is_array($detail['identificacao'] ?? null) ? $detail['identificacao'] : [];
        $resumo = is_array($detail['resumo'] ?? null) ? $detail['resumo'] : [];
        $parceiros = is_array($detail['parceiros'] ?? null) ? $detail['parceiros'] : [];
        $gerado = $esc($detail['generated_at'] ?? date('Y-m-d H:i:s'));
        $aviso = trim((string) ($detail['aviso_itens'] ?? ''));
        $chave = (int) ($p['Chave'] ?? 0);
        $numProj = (int) ($p['NumProjeto'] ?? 0);
        $nomeProj = trim((string) ($p['NomeProjeto'] ?? ''));

        $fieldRowsHtml = static function (array $fields) use ($fmt, $esc, $brDate): string {
            if ($fields === []) {
                return '';
            }
            $html = '<table class="resumo" width="100%" cellspacing="0" cellpadding="0"><tbody>';
            foreach (array_chunk($fields, 2) as $pair) {
                $html .= '<tr>';
                foreach ($pair as $field) {
                    $type = (string) ($field['type'] ?? 'text');
                    $cell = $type === 'date'
                        ? $brDate($field['value'] ?? '')
                        : $fmt($field);
                    $span = count($pair) === 1 ? ' colspan="3"' : '';
                    $html .= '<th>' . $esc((string) $field['label']) . '</th><td' . $span . '>' . $cell . '</td>';
                }
                $html .= '</tr>';
            }

            return $html . '</tbody></table>';
        };

        $idHtml = '<h2 class="sec">Dados do projeto selecionado</h2>'
            . '<p class="hint">AbsEntry ' . $esc((string) $chave)
            . ($numProj > 0 ? ' · Nº ' . $esc((string) $numProj) : '')
            . ($nomeProj !== '' ? ' · ' . $esc($nomeProj) : '')
            . ' — informações exclusivas deste projeto.</p>'
            . $fieldRowsHtml($identificacao);

        $resumoHtml = '';
        if ($resumo !== []) {
            $resumoHtml .= '<h2 class="sec">Resumo financeiro (SAP)</h2>';
            $resumoHtml .= '<p class="hint">Aba Resumo deste projeto no SAP B1 (PMG8).</p>';
            $resumoHtml .= $fieldRowsHtml($resumo);
        }

        $partnersHtml = '';
        $partnersHtml .= '<h2 class="sec">Documentos por parceiro</h2>';
        $partnersHtml .= '<p class="hint">Somente notas vinculadas a este projeto · '
            . (int) ($totais['QtdParceiros'] ?? 0) . ' parceiro(s) · '
            . (int) ($totais['QtdDocumentos'] ?? 0) . ' documento(s) · '
            . 'no projeto <strong>' . $money($totais['ValorNoProjeto'] ?? 0) . '</strong>'
            . ' · total notas ' . $money($totais['ValorTotalNota'] ?? 0)
            . '</p>';

        foreach ($parceiros as $partner) {
            $pName = trim((string) ($partner['Nome'] ?? 'Sem fornecedor'));
            $pCode = trim((string) ($partner['Codigo'] ?? ''));
            $title = $pCode !== '' ? $pCode . ' — ' . $pName : $pName;
            $partnersHtml .= '<table class="partner" width="100%" cellspacing="0" cellpadding="0"><thead><tr>'
                . '<th class="partner-h" colspan="2">' . $esc($title) . '</th>'
                . '</tr></thead><tbody>'
                . '<tr><td class="partner-sum" colspan="2">'
                . (int) ($partner['QtdDocumentos'] ?? 0) . ' documento(s)'
                . ' &nbsp;·&nbsp; no projeto: <strong>' . $money($partner['ValorNoProjeto'] ?? 0) . '</strong>'
                . ' &nbsp;·&nbsp; total notas: ' . $money($partner['ValorTotalNota'] ?? 0)
                . '</td></tr>';

            $docs = is_array($partner['documentos'] ?? null) ? $partner['documentos'] : [];
            foreach ($docs as $doc) {
                $excl = !empty($doc['_excluido']);
                $docCls = $excl ? ' doc-excl' : '';
                $partnersHtml .= '<tr><td class="doc-wrap' . $docCls . '" colspan="2">';
                $partnersHtml .= '<div class="doch">'
                    . $esc((string) ($doc['TipoDocumento'] ?? 'Documento'))
                    . ' · NF ' . $esc((string) ($doc['NumNF'] ?? ''))
                    . ' · Doc ' . $esc((string) ($doc['NumDocumento'] ?? ''))
                    . ' · ' . $brDate($doc['DataDocumento'] ?? '')
                    . ($excl ? ' <span class="badge">cancelada</span>' : '')
                    . '</div>';
                $partnersHtml .= '<table class="vals" width="100%" cellspacing="0" cellpadding="0"><tr>'
                    . '<td>No projeto: <strong>' . $money($doc['ValorNoProjeto'] ?? 0) . '</strong></td>'
                    . '<td>Total nota: ' . $money($doc['ValorTotalNota'] ?? 0) . '</td>'
                    . '<td>Status: ' . $esc((string) ($doc['StatusDocumento'] ?? '')) . '</td>'
                    . '</tr></table>';

                $itens = is_array($doc['itens'] ?? null) ? $doc['itens'] : [];
                if ($itens !== []) {
                    $partnersHtml .= '<table class="items" width="100%" cellspacing="0" cellpadding="0"><thead><tr>'
                        . '<th width="16%">Item</th>'
                        . '<th width="44%">Descrição</th>'
                        . '<th class="n" width="12%">Qtd</th>'
                        . '<th class="n" width="16%">Total linha</th>'
                        . '<th width="12%">Vinculada</th>'
                        . '</tr></thead><tbody>';
                    foreach ($itens as $item) {
                        $partnersHtml .= '<tr' . (!empty($item['_excluido']) ? ' class="excl"' : '') . '>'
                            . '<td>' . $esc((string) ($item['CodigoItem'] ?? '')) . '</td>'
                            . '<td>' . $esc((string) ($item['DescricaoItem'] ?? '')) . '</td>'
                            . '<td class="n">' . number_format((float) ($item['Quantidade'] ?? 0), 2, ',', '.') . '</td>'
                            . '<td class="n">' . $money($item['TotalLinha'] ?? 0) . '</td>'
                            . '<td>' . $esc((string) ($item['LinhaVinculada'] ?? '')) . '</td>'
                            . '</tr>';
                    }
                    $partnersHtml .= '</tbody></table>';
                } else {
                    $partnersHtml .= '<p class="muted">Sem itens de linha para esta nota (ou consulta de itens indisponível).</p>';
                }
                $partnersHtml .= '</td></tr>';
            }
            $partnersHtml .= '</tbody></table>';
        }

        if ($parceiros === []) {
            $partnersHtml .= '<p class="muted">Nenhum documento vinculado a este projeto.</p>';
        }

        $avisoBlock = $aviso !== ''
            ? '<div class="warn"><strong>Itens:</strong> ' . $esc($aviso) . '</div>'
            : '';

        return <<<HTML
<style>
body{font-family:DejaVu Sans,sans-serif;font-size:9pt;color:#1B241E;line-height:1.35;margin:0;padding:0}
.stamp{margin:0 0 6px;padding:0;font-size:8pt;color:#8A9189;line-height:1.3}
h2.sec{font-size:11pt;margin:10px 0 4px;padding:0 0 3px;color:#12532F;border-bottom:2px solid #CDE9D6}
h2.sec:first-of-type{margin-top:4px}
.hint{margin:0 0 6px;font-size:8pt;color:#55605A;line-height:1.3}
.resumo{width:100%;border-collapse:collapse;margin:0 0 8px}
.resumo th,.resumo td{border:1px solid #E2E6E0;padding:5px 8px;text-align:left;vertical-align:middle;line-height:1.3;font-size:8.5pt}
.resumo th{width:18%;background:#FAFBFD;color:#55605A;font-weight:700;font-size:7.5pt;text-transform:uppercase}
.resumo td{width:32%}
.warn{background:#FDEEE0;border:1px solid #F3CFA3;color:#8A4413;padding:8px 10px;margin:0 0 8px;font-size:8pt;line-height:1.35}
.partner{width:100%;border-collapse:collapse;margin:0 0 10px;page-break-inside:auto}
.partner-h{background:#E7F4EC;border-left:5px solid #1B7A49;border-bottom:1px solid #CDE9D6;color:#12532F;font-size:10pt;font-weight:700;text-align:left;padding:10px 12px;line-height:1.35}
.partner-sum{padding:8px 12px 10px;font-size:8.5pt;color:#55605A;background:#F7FAF8;border-bottom:1px solid #E2E6E0;line-height:1.4}
.doc-wrap{padding:12px 12px 14px;border-bottom:1px solid #EEF1F5;vertical-align:top}
.doc-wrap.doc-excl{background:#F7F7F7}
.doch{font-weight:700;font-size:9pt;margin:0 0 6px;line-height:1.4;color:#1B241E}
.vals{width:100%;border-collapse:collapse;margin:0 0 8px}
.vals td{border:0;padding:2px 14px 2px 0;font-size:8.5pt;color:#55605A;line-height:1.4}
.items{width:100%;border-collapse:collapse;margin:4px 0 0}
.items th,.items td{border:1px solid #E2E6E0;padding:6px 8px;text-align:left;font-size:8pt;vertical-align:middle;line-height:1.35}
.items th{background:#FAFBFD;color:#55605A;text-transform:uppercase;font-size:7.5pt;letter-spacing:.02em;font-weight:700}
.items td.n,.items th.n{text-align:right;white-space:nowrap}
.excl td{color:#8A9189;text-decoration:line-through}
.badge{display:inline-block;background:#FDEEE0;color:#8A4413;font-size:7pt;font-weight:700;padding:2px 6px;margin-left:4px}
.muted{color:#8A9189;font-size:8.5pt;margin:6px 0 0;line-height:1.4;padding:0}
</style>
<p class="stamp">Gerado em {$gerado} · ficha exclusiva do projeto selecionado · valores sem impostos na base PMG4 · notas canceladas fora dos totais</p>
{$idHtml}
{$resumoHtml}
{$avisoBlock}
{$partnersHtml}
HTML;
    }
}
