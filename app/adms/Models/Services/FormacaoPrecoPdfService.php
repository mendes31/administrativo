<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Relatório PDF da precificação: análise completa, inclusive colunas ocultas na tela
 * (fator por dentro, status, validação, lucro do projeto, receita bruta).
 */
final class FormacaoPrecoPdfService
{
    /**
     * @param array<string, mixed> $registro
     * @return array{css: string, header: string, body: string}
     */
    public function buildParts(array $registro): array
    {
        return [
            'css' => $this->css(),
            'header' => $this->headerHtml($registro),
            'body' => $this->bodyHtml($registro),
        ];
    }

    /**
     * Documento completo (testes e fallback). No PDF o cabeçalho vai em SetHTMLHeader.
     *
     * @param array<string, mixed> $registro
     */
    public function buildHtml(array $registro): string
    {
        $parts = $this->buildParts($registro);

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'
            . $parts['css'] . '</style></head><body>'
            . $parts['header'] . $parts['body'] . '</body></html>';
    }

    private function css(): string
    {
        return 'body{font-family:dejavusans,sans-serif;font-size:9pt;color:#16241c}
h1{font-size:13pt;margin:0 0 1px}
h2{font-size:11.5pt;margin:0 0 6px;color:#0e6b3a;border-bottom:0.6pt solid #c5d0c8;padding-bottom:2px}
h3{font-size:9pt;margin:8px 0 3px;color:#345}
.muted{color:#5b6b60;font-size:8pt}
table{border-collapse:collapse;width:100%;margin:0 0 7px}
th,td{border:0.4pt solid #c5d0c8;padding:3px 5px;vertical-align:middle}
th{background:#eef2ed;font-weight:600;font-size:8pt;text-align:center}
td{font-size:8.5pt;text-align:center}
td.lbl,th.lbl{text-align:left}
.num{font-variant-numeric:tabular-nums;white-space:nowrap}
.pb{background:#d7ebf8;color:#15344c}
.pb-pct{background:#b3d4ee;color:#15344c}
.rl{background:#d5f1e3;color:#14402c}
.rl-pct{background:#b0e0c6;color:#14402c}
.mk{background:#f6e4c8;color:#4a3010}
.mk-pct{background:#ebc894;color:#4a3010}
.own{font-weight:700}
.oculto{background:#fff8e8}
.meta{background:#fff8e8}
.ok{color:#1c7c54}
.bad{color:#b3261e}
.head td{border:none;padding:0;text-align:left;vertical-align:middle}
.chip{display:inline-block;padding:1px 6px;font-size:7.5pt}
.leg{font-size:7.5pt;color:#5b6b60;margin:2px 0 0}
.fp-hdr{padding-bottom:4px;border-bottom:0.5pt solid #c5d0c8}
.fp-hdr p{margin:3px 0 0;font-size:8pt}
.prod{page-break-inside:avoid;margin:0 0 12px}
.kv th{width:14%;text-align:left;background:#f6f8f5}
.kv td{width:11%;text-align:left}';
    }

    /**
     * @param array<string, mixed> $registro
     */
    private function headerHtml(array $registro): string
    {
        $nome = htmlspecialchars((string) ($registro['nome'] ?? 'Precificação'), ENT_QUOTES, 'UTF-8');
        $obs = trim((string) ($registro['observacao'] ?? ''));
        $icms = FormacaoPrecoCalculator::formatPercent((float) ($registro['icms_padrao'] ?? 0));
        $pis = FormacaoPrecoCalculator::formatPercent((float) ($registro['pis_padrao'] ?? 0));
        $cofins = FormacaoPrecoCalculator::formatPercent((float) ($registro['cofins_padrao'] ?? 0));
        $quando = date('d/m/Y H:i');
        $obsHtml = $obs !== ''
            ? '<p><strong>Observação:</strong> ' . nl2br(htmlspecialchars($obs, ENT_QUOTES, 'UTF-8')) . '</p>'
            : '';

        return '<div class="fp-hdr">
<table class="head"><tr>
<td style="width:92px;border:none;padding:0">' . $this->logoImg() . '</td>
<td style="border:none;padding:0;text-align:left">
<h1>Formação de preço — análise completa</h1>
<div><strong>' . $nome . '</strong></div>
<div class="muted">Emitido em ' . htmlspecialchars($quando, ENT_QUOTES, 'UTF-8')
            . ' · ICMS padrão ' . htmlspecialchars($icms, ENT_QUOTES, 'UTF-8') . '%'
            . ' · PIS ' . htmlspecialchars($pis, ENT_QUOTES, 'UTF-8') . '%'
            . ' · COFINS ' . htmlspecialchars($cofins, ENT_QUOTES, 'UTF-8') . '%</div>
</td></tr></table>
' . $obsHtml . '
<p class="leg">O % do método (amarelo) define o preço. As % reais usam as cores da tela:
<span class="chip pb-pct">azul — PB</span>
<span class="chip rl-pct">verde — RL</span>
<span class="chip mk-pct">âmbar — markup</span>
· <strong>negrito</strong> = % do próprio método.
Células <span class="chip oculto">amarelas</span> são colunas ocultas na tela.</p>
</div>';
    }

    /**
     * @param array<string, mixed> $registro
     */
    private function bodyHtml(array $registro): string
    {
        $itens = is_array($registro['itens'] ?? null) ? $registro['itens'] : [];
        if ($itens === []) {
            return '<p>Nenhum produto neste cenário.</p>';
        }
        $blocos = '';
        foreach ($itens as $i => $item) {
            $blocos .= $this->produtoHtml((int) $i + 1, $item, (int) $i > 0);
        }

        return $blocos;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function produtoHtml(int $n, array $item, bool $novaPagina = false): string
    {
        $calc = is_array($item['calc'] ?? null) ? $item['calc'] : [];
        $titulo = htmlspecialchars((string) ($item['produto'] ?? 'Produto ' . $n), ENT_QUOTES, 'UTF-8');
        $cod = trim((string) ($item['codigo_produto'] ?? ''));
        $forecast = $item['forecast'] ?? null;

        $ent = '<table class="kv">
<tr>
<th class="lbl">Código</th><td class="lbl">' . htmlspecialchars($cod !== '' ? $cod : '—', ENT_QUOTES, 'UTF-8') . '</td>
<th class="lbl">Forecast</th><td>' . $this->m($forecast, true) . '</td>
<th class="lbl">Preço informado</th><td>' . $this->m($item['preco_informado'] ?? null) . '</td>
<th class="lbl oculto">Fator por dentro</th><td class="oculto num">' . $this->n($calc['fator_por_dentro'] ?? null, 4) . '</td>
</tr>
<tr>
<th class="lbl">ICMS %</th><td class="num">' . $this->p($item['icms'] ?? null) . '</td>
<th class="lbl">PIS %</th><td class="num">' . $this->p($item['pis'] ?? null) . '</td>
<th class="lbl">COFINS %</th><td class="num">' . $this->p($item['cofins'] ?? null) . '</td>
<th class="lbl">Imp. efetivo %</th><td class="num">' . $this->p($calc['imposto_efetivo'] ?? null) . '</td>
</tr>
<tr>
<th class="lbl">CVAR</th><td class="num">' . $this->m($item['custo_variavel'] ?? null) . '</td>
<th class="lbl">CFIX</th><td class="num">' . $this->m($item['custo_fixo'] ?? null) . '</td>
<th class="lbl">Custo</th><td class="num">' . $this->m($calc['custo_total'] ?? null) . '</td>
<th class="lbl">Frete / Com. / Desc. %</th>
<td class="num">' . $this->p($item['frete_pct'] ?? null) . ' / ' . $this->p($item['comissao_pct'] ?? null) . ' / ' . $this->p($item['desconto_pct'] ?? null) . '</td>
</tr>
</table>';

        $metodos = [
            'pb' => ['Lucro sobre preço bruto', $item['meta_pb'] ?? null],
            'rl' => ['Margem sobre receita líquida', $item['meta_rl'] ?? null],
            'mk' => ['Markup sobre custo', $item['meta_mk'] ?? null],
        ];

        $resRows = '';
        $impRows = '';
        $hidRows = '';
        foreach ($metodos as $key => [$label, $meta]) {
            $r = is_array($calc[$key] ?? null) ? $calc[$key] : [];
            $st = (string) ($r['status'] ?? '');
            $stClass = str_contains($st, 'INVÁLIDO') || str_contains($st, 'VERIFICAR') || str_contains($st, 'INFORMAR')
                ? 'bad' : 'ok';
            $resRows .= '<tr>
<td class="lbl ' . $key . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td>
<td class="meta num">' . $this->p($meta) . '</td>
<td class="num">' . $this->m($r['preco_base'] ?? null) . '</td>
<td class="num">' . $this->m($r['preco_vendido'] ?? null) . '</td>
<td class="num">' . $this->m($r['preco_tabela'] ?? null) . '</td>
<td class="num">' . $this->m($r['impostos_r'] ?? null) . '</td>
<td class="num">' . $this->m($r['receita_liquida'] ?? null) . '</td>
<td class="num">' . $this->m($r['frete_r'] ?? null) . '</td>
<td class="num">' . $this->m($r['comissao_r'] ?? null) . '</td>
<td class="num">' . $this->m($r['lucro_unitario'] ?? null) . '</td>
<td class="pb-pct num' . ($key === 'pb' ? ' own' : '') . '">' . $this->p($r['margem_pb'] ?? null) . '</td>
<td class="rl-pct num' . ($key === 'rl' ? ' own' : '') . '">' . $this->p($r['margem_rl'] ?? null) . '</td>
<td class="mk-pct num' . ($key === 'mk' ? ' own' : '') . '">' . $this->p($r['markup'] ?? null) . '</td>
</tr>';
            $impRows .= '<tr>
<td class="lbl">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td>
<td class="num">' . $this->m($r['icms_r'] ?? null) . '</td>
<td class="num">' . $this->m($r['base_pis_cofins'] ?? null) . '</td>
<td class="num">' . $this->m($r['pis_r'] ?? null) . '</td>
<td class="num">' . $this->m($r['cofins_r'] ?? null) . '</td>
<td class="num">' . $this->m($r['impostos_r'] ?? null) . '</td>
</tr>';
            $hidRows .= '<tr>
<td class="lbl">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td>
<td class="oculto lbl ' . $stClass . '">' . htmlspecialchars($st !== '' ? $st : '—', ENT_QUOTES, 'UTF-8') . '</td>
<td class="oculto lbl">' . htmlspecialchars((string) (($r['validacao'] ?? '') !== '' ? $r['validacao'] : '—'), ENT_QUOTES, 'UTF-8') . '</td>
<td class="oculto num">' . $this->proj($forecast, $r['lucro_projeto'] ?? null) . '</td>
<td class="oculto num">' . $this->proj($forecast, $r['receita_bruta'] ?? null) . '</td>
</tr>';
        }

        $prefixo = $novaPagina ? '<pagebreak />' : '';

        return $prefixo . '<div class="prod"><h2>' . $n . '. ' . $titulo . '</h2>' . $ent . '
<h3>Resultado por método</h3>
<table>
<thead>
<tr>
<th rowspan="2" class="lbl">Método</th>
<th rowspan="2" class="meta">Meta %</th>
<th colspan="3">Preços</th>
<th colspan="5">Composição</th>
<th colspan="3">% reais (mesmo preço)</th>
</tr>
<tr>
<th>Base s/ taxas</th><th>Vendido</th><th>Tabela</th>
<th>Impostos</th><th>Rec. líquida</th><th>Frete</th><th>Comissão</th><th>Lucro un.</th>
<th class="pb-pct">PB real %</th><th class="rl-pct">RL real %</th><th class="mk-pct">Markup real %</th>
</tr>
</thead>
<tbody>' . $resRows . '</tbody>
</table>
<table>
<thead><tr>
<th class="lbl">Impostos detalhados</th>
<th>ICMS R$</th><th>Base PIS/COFINS</th><th>PIS R$</th><th>COFINS R$</th><th>Total</th>
</tr></thead>
<tbody>' . $impRows . '</tbody>
</table>
<table>
<thead><tr>
<th class="lbl">Colunas ocultas na tela</th>
<th class="oculto">Status</th><th class="oculto">Validação</th>
<th class="oculto">Lucro projeto</th><th class="oculto">Receita bruta</th>
</tr></thead>
<tbody>' . $hidRows . '</tbody>
</table>
</div>';
    }

    private function m(mixed $v, bool $int = false): string
    {
        if ($v === null || $v === '') {
            return '—';
        }
        return FormacaoPrecoCalculator::formatMoney((float) $v, $int ? 0 : 2);
    }

    private function p(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '—';
        }
        return FormacaoPrecoCalculator::formatPercent((float) $v);
    }

    private function n(mixed $v, int $dec): string
    {
        if ($v === null || $v === '' || !is_numeric($v)) {
            return '—';
        }
        return number_format((float) $v, $dec, ',', '.');
    }

    private function proj(mixed $forecast, mixed $v): string
    {
        if ($forecast === null || $forecast === '' || (float) $forecast == 0.0) {
            return '—';
        }
        return $this->m($v);
    }

    private function logoImg(): string
    {
        $root = dirname(__DIR__, 4);
        foreach ([
            $root . '/public/adms/image/logo/Logo-Tiaraju.png',
            $root . '/public/adms/image/logo/logo.png',
        ] as $path) {
            if (!is_file($path)) {
                continue;
            }
            $raw = @file_get_contents($path);
            if ($raw === false) {
                continue;
            }
            return '<img src="data:image/png;base64,' . base64_encode($raw) . '" style="max-width:84px;max-height:44px" alt="Tiaraju">';
        }

        return '<strong>TIARAJU</strong>';
    }
}
