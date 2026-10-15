<?php

use App\adms\Models\Services\FormacaoPrecoCalculator as FpCalc;

$editable = !empty($this->data['editable']);
$itens = $this->data['itens'] ?? [];
$form = $this->data['form'] ?? [];
$ro = $editable ? '' : 'readonly';

$fmtPct = static function (mixed $v): string {
    if ($v === null || $v === '') {
        return '';
    }
    return FpCalc::formatPercent((float) $v);
};
$fmtMoney = static function (mixed $v, int $d = 2): string {
    if ($v === null || $v === '') {
        return '';
    }
    return FpCalc::formatMoney((float) $v, $d);
};
$out = static function (?array $calc, string $method, string $key): string {
    $v = $calc[$method][$key] ?? null;
    if ($v === null || $v === '') {
        return '';
    }
    if (in_array($key, ['status', 'validacao'], true)) {
        return (string) $v;
    }
    if (in_array($key, ['margem_rl', 'margem_pb', 'markup'], true)) {
        return FpCalc::formatPercent((float) $v);
    }
    return FpCalc::formatMoney((float) $v);
};

$rs = '<span class="fp-unit" aria-hidden="true">R$</span>';
$pc = '<span class="fp-unit" aria-hidden="true">%</span>';

if ($itens === [] && $editable) {
    $itens[] = [
        'codigo_produto' => '',
        'produto' => '',
        'forecast' => null,
        'icms' => FpCalc::parsePercentInput($form['icms_padrao'] ?? '12') ?? 0.12,
        'pis' => FpCalc::parsePercentInput($form['pis_padrao'] ?? '1,65') ?? 0.0165,
        'cofins' => FpCalc::parsePercentInput($form['cofins_padrao'] ?? '7,60') ?? 0.076,
        'custo_variavel' => null,
        'custo_fixo' => null,
        'meta_pb' => null,
        'meta_rl' => null,
        'meta_mk' => null,
        'preco_informado' => null,
        'frete_pct' => 0,
        'comissao_pct' => 0,
        'desconto_pct' => 0,
        'calc' => null,
    ];
}
?>
<style>
.fp-wrap { overflow: hidden; width: 100%; }
.fp-grid { width: 100%; table-layout: fixed; font-size: .68rem; margin-bottom: 0; }
.fp-grid th {
    white-space: normal; word-break: break-word; line-height: 1.15;
    font-size: .62rem; font-weight: 600; vertical-align: bottom;
    padding: .2rem .12rem;
}
.fp-grid td { vertical-align: middle; padding: .12rem .08rem; overflow: hidden; }
.fp-grid .form-control {
    width: 100%; min-width: 0; max-width: 100%;
    font-size: .68rem; padding: .12rem .18rem; height: calc(1.35em + .24rem);
}
.fp-grid .fp-prod { min-width: 0; text-overflow: ellipsis; }
.fp-val { display: flex; align-items: center; gap: .1rem; min-width: 0; }
.fp-val .form-control, .fp-val .fp-out { flex: 1 1 auto; min-width: 0; }
.fp-unit {
    font-size: .52rem; font-style: normal; font-weight: 700;
    color: #6b7870; line-height: 1; flex: 0 0 auto; letter-spacing: -.02em;
}
.fp-grid th .fp-unit { vertical-align: super; margin-left: .04rem; }
.fp-grid th.fp-cmp-pb .fp-unit, .fp-grid td.fp-cmp-pb .fp-unit,
.fp-grid th.fp-cmp-rl .fp-unit, .fp-grid td.fp-cmp-rl .fp-unit,
.fp-grid th.fp-cmp-mk .fp-unit, .fp-grid td.fp-cmp-mk .fp-unit { color: inherit; opacity: .72; }
.fp-out { font-variant-numeric: tabular-nums; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fp-status { white-space: normal; line-height: 1.15; font-size: .62rem; }
.fp-idx { width: 1.6rem; text-align: center; }
.fp-cod { width: 5.5%; }
.fp-prod-col { width: 24%; }
.fp-narrow { width: 5.2%; }
.fp-del-col { width: 1.7rem; text-align: center; }
.fp-tabs .nav-link { font-size: .8rem; padding: .35rem .65rem; }
.fp-status-bad { color: #b3261e; font-weight: 600; }
.fp-meta { background: #fff8e8; }
.fp-grid th.fp-meta, .fp-grid td.fp-meta { background: #fff8e8; }
.fp-grid .btn.fp-del { padding: 0 .25rem; line-height: 1.2; }
.fp-grid th.fp-cmp-pb, .fp-grid td.fp-cmp-pb { background: #d7ebf8; color: #15344c; }
.fp-grid th.fp-cmp-pb.fp-cmp-pct, .fp-grid td.fp-cmp-pb.fp-cmp-pct { background: #b3d4ee; }
.fp-grid th.fp-cmp-rl, .fp-grid td.fp-cmp-rl { background: #d5f1e3; color: #14402c; }
.fp-grid th.fp-cmp-rl.fp-cmp-pct, .fp-grid td.fp-cmp-rl.fp-cmp-pct { background: #b0e0c6; }
.fp-grid th.fp-cmp-mk, .fp-grid td.fp-cmp-mk { background: #f6e4c8; color: #4a3010; }
.fp-grid th.fp-cmp-mk.fp-cmp-pct, .fp-grid td.fp-cmp-mk.fp-cmp-pct { background: #ebc894; }
.fp-grid .fp-cmp-start { border-left: 3px solid currentColor; }
.fp-grid th.fp-cmp-pb.fp-cmp-start, .fp-grid td.fp-cmp-pb.fp-cmp-start { border-left-color: #1d6ea8; }
.fp-grid th.fp-cmp-rl.fp-cmp-start, .fp-grid td.fp-cmp-rl.fp-cmp-start { border-left-color: #1f8a5a; }
.fp-grid th.fp-cmp-mk.fp-cmp-start, .fp-grid td.fp-cmp-mk.fp-cmp-start { border-left-color: #b56a14; }
.fp-grid .fp-cmp-own { font-weight: 700; }
.fp-chip { display: inline-block; padding: .05rem .45rem; border-radius: .2rem; font-size: .75rem; }
.fp-chip-pb { background: #b3d4ee; color: #15344c; }
.fp-chip-rl { background: #b0e0c6; color: #14402c; }
.fp-chip-mk { background: #ebc894; color: #4a3010; }
@media (max-width: 767.98px) {
    .fp-tabs { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .fp-tabs .nav-item { flex-shrink: 0; }
    .fp-tabs .nav-link { white-space: nowrap; }
    .fp-wrap { overflow: visible; }
    .fp-grid { table-layout: auto; font-size: .875rem; }
    .fp-grid thead { display: none; }
    .fp-grid tbody tr.fp-row {
        display: block;
        margin-bottom: .75rem;
        padding: .65rem .75rem;
        border: 1px solid #dee2e6;
        border-radius: .375rem;
        background: #fff;
        box-shadow: 0 .125rem .25rem rgba(0,0,0,.05);
    }
    .fp-grid tbody td {
        display: block;
        width: 100% !important;
        border: 0;
        padding: .35rem 0;
        overflow: visible;
    }
    .fp-grid tbody td::before {
        content: attr(data-label);
        display: block;
        font-size: .72rem;
        font-weight: 600;
        color: #6c757d;
        margin-bottom: .2rem;
    }
    .fp-grid tbody td.fp-idx { display: none !important; }
    .fp-grid tbody td.fp-del-col::before { content: none; }
    .fp-grid tbody td.fp-del-col .fp-del { width: 100%; }
    .fp-grid .form-control { font-size: .875rem; height: auto; padding: .35rem .5rem; }
    .fp-out { white-space: normal; overflow: visible; text-overflow: clip; }
    .fp-grid tbody td .fp-val { width: 100%; }
    .fp-unit { font-size: .7rem; }
    .fp-actions-row .btn { width: 100%; }
}
</style>

<ul class="nav nav-tabs fp-tabs mb-3 flex-nowrap overflow-auto" id="fp-tabs" role="tablist">
    <li class="nav-item"><button type="button" class="nav-link active" data-fp-tab="produtos">Produtos</button></li>
    <li class="nav-item"><button type="button" class="nav-link" data-fp-tab="pb">Preço bruto</button></li>
    <li class="nav-item"><button type="button" class="nav-link" data-fp-tab="rl">Receita líquida</button></li>
    <li class="nav-item"><button type="button" class="nav-link" data-fp-tab="mk">Markup sobre custo</button></li>
    <li class="nav-item"><button type="button" class="nav-link" data-fp-tab="cmp">Comparativo</button></li>
    <li class="nav-item"><button type="button" class="nav-link" data-fp-tab="exemplo">Exemplo prático</button></li>
    <li class="nav-item"><button type="button" class="nav-link" data-fp-tab="expl">Explicações</button></li>
</ul>

<div class="fp-pane" data-fp-pane="produtos">
    <p class="small text-muted">Dados comuns da linha: produto, custo, impostos, frete, comissão e desconto. Valores em R$ e percentuais em % (símbolo pequeno ao lado). A meta e o preço informado ficam nas abas de cálculo.</p>
</div>
<div class="fp-pane d-none" data-fp-pane="pb">
    <p class="small text-muted">Informe a <strong>meta de lucro sobre o preço bruto</strong> (amarelo) <em>ou</em> o preço informado — nunca os dois. À direita, as % reais seguem a ordem das abas: <span class="fp-chip fp-chip-pb">PB</span> <span class="fp-chip fp-chip-rl">RL</span> <span class="fp-chip fp-chip-mk">Mk</span>.</p>
</div>
<div class="fp-pane d-none" data-fp-pane="rl">
    <p class="small text-muted">Informe a <strong>meta sobre a receita líquida</strong> (amarelo) <em>ou</em> o preço informado. As % reais à direita seguem a ordem das abas: <span class="fp-chip fp-chip-pb">PB</span> <span class="fp-chip fp-chip-rl">RL</span> <span class="fp-chip fp-chip-mk">Mk</span>.</p>
</div>
<div class="fp-pane d-none" data-fp-pane="mk">
    <p class="small text-muted">Informe o <strong>markup sobre o custo</strong> (amarelo) <em>ou</em> o preço informado. As % reais à direita seguem a ordem das abas: <span class="fp-chip fp-chip-pb">PB</span> <span class="fp-chip fp-chip-rl">RL</span> <span class="fp-chip fp-chip-mk">Mk</span>. Markup não tem teto de 100%.</p>
</div>
<div class="fp-pane d-none" data-fp-pane="cmp">
    <p class="small text-muted mb-2">Três preços independentes (um por meta). Sem frete/comissão, metas calibradas convergem; com despesas, separam. O PDF traz também as colunas ocultas da planilha.</p>
    <p class="small mb-2">
        <span class="fp-chip fp-chip-pb">Azul — pelo Preço bruto</span>
        <span class="fp-chip fp-chip-rl">Verde — pela Receita líquida</span>
        <span class="fp-chip fp-chip-mk">Âmbar — pelo Markup</span>
        · tom mais forte = % reais · <strong>negrito</strong> = % do próprio método
    </p>
</div>

<div class="fp-pane" data-fp-pane="grid">
<div class="fp-wrap mb-2">
<table class="table table-sm table-bordered fp-grid mb-0" id="fp-grid">
    <thead class="table-light">
        <tr>
            <th class="fp-idx" data-fp-g="always">#</th>
            <th class="fp-cod" data-fp-g="produtos" title="Código do produto">Cód.</th>
            <th class="fp-prod-col" data-fp-g="always">Produto</th>
            <th data-fp-g="produtos" title="Forecast (volume)">Forecast</th>
            <th data-fp-g="produtos" title="ICMS %">ICMS <?= $pc ?></th>
            <th data-fp-g="produtos" title="PIS %">PIS <?= $pc ?></th>
            <th data-fp-g="produtos" title="COFINS %">COFINS <?= $pc ?></th>
            <th data-fp-g="produtos" title="Imposto efetivo %">Imp. <?= $pc ?></th>
            <th data-fp-g="produtos" title="Custo variável unitário">CVAR <?= $rs ?></th>
            <th data-fp-g="produtos" title="Custo fixo unitário">CFIX <?= $rs ?></th>
            <th data-fp-g="produtos">Custo <?= $rs ?></th>
            <th data-fp-g="produtos" title="Frete %">Frete <?= $pc ?></th>
            <th data-fp-g="produtos" title="Comissão %">Com. <?= $pc ?></th>
            <th data-fp-g="produtos" title="Desconto %">Desc. <?= $pc ?></th>
            <th class="fp-meta fp-narrow" data-fp-g="pb" title="Meta lucro sobre preço bruto %">Meta PB <?= $pc ?></th>
            <th class="fp-meta fp-narrow" data-fp-g="pb" title="Preço informado pelo parceiro">P. inf. <?= $rs ?></th>
            <th data-fp-g="pb">Status</th>
            <th data-fp-g="pb">Vendido <?= $rs ?></th>
            <th data-fp-g="pb">Tabela <?= $rs ?></th>
            <th data-fp-g="pb">Rec. líq. <?= $rs ?></th>
            <th data-fp-g="pb">Lucro <?= $rs ?></th>
            <th class="fp-cmp-pb fp-cmp-pct fp-cmp-own" data-fp-g="pb" title="Lucro / preço bruto">PB real <?= $pc ?></th>
            <th class="fp-cmp-rl fp-cmp-pct" data-fp-g="pb" title="Lucro / receita líquida">RL real <?= $pc ?></th>
            <th class="fp-cmp-mk fp-cmp-pct" data-fp-g="pb" title="Lucro / custo">Mk real <?= $pc ?></th>
            <th class="fp-meta fp-narrow" data-fp-g="rl" title="Meta margem sobre receita líquida %">Meta RL <?= $pc ?></th>
            <th class="fp-meta fp-narrow" data-fp-g="rl" title="Preço informado pelo parceiro">P. inf. <?= $rs ?></th>
            <th data-fp-g="rl">Status</th>
            <th data-fp-g="rl">Vendido <?= $rs ?></th>
            <th data-fp-g="rl">Tabela <?= $rs ?></th>
            <th data-fp-g="rl">Rec. líq. <?= $rs ?></th>
            <th data-fp-g="rl">Lucro <?= $rs ?></th>
            <th class="fp-cmp-pb fp-cmp-pct" data-fp-g="rl">PB real <?= $pc ?></th>
            <th class="fp-cmp-rl fp-cmp-pct fp-cmp-own" data-fp-g="rl">RL real <?= $pc ?></th>
            <th class="fp-cmp-mk fp-cmp-pct" data-fp-g="rl">Mk real <?= $pc ?></th>
            <th class="fp-meta fp-narrow" data-fp-g="mk" title="Meta markup sobre custo %">Meta MK <?= $pc ?></th>
            <th class="fp-meta fp-narrow" data-fp-g="mk" title="Preço informado pelo parceiro">P. inf. <?= $rs ?></th>
            <th data-fp-g="mk">Status</th>
            <th data-fp-g="mk">Vendido <?= $rs ?></th>
            <th data-fp-g="mk">Tabela <?= $rs ?></th>
            <th data-fp-g="mk">Rec. líq. <?= $rs ?></th>
            <th data-fp-g="mk">Lucro <?= $rs ?></th>
            <th class="fp-cmp-pb fp-cmp-pct" data-fp-g="mk">PB real <?= $pc ?></th>
            <th class="fp-cmp-rl fp-cmp-pct" data-fp-g="mk">RL real <?= $pc ?></th>
            <th class="fp-cmp-mk fp-cmp-pct fp-cmp-own" data-fp-g="mk">Mk real <?= $pc ?></th>
            <th class="fp-cmp-pb fp-cmp-start" data-fp-g="cmp">Preço PB <?= $rs ?></th>
            <th class="fp-cmp-pb fp-cmp-pct fp-cmp-own" data-fp-g="cmp">PB% PB</th>
            <th class="fp-cmp-pb fp-cmp-pct" data-fp-g="cmp">RL% PB</th>
            <th class="fp-cmp-pb fp-cmp-pct" data-fp-g="cmp">Mk% PB</th>
            <th class="fp-cmp-rl fp-cmp-start" data-fp-g="cmp">Preço RL <?= $rs ?></th>
            <th class="fp-cmp-rl fp-cmp-pct" data-fp-g="cmp">PB% RL</th>
            <th class="fp-cmp-rl fp-cmp-pct fp-cmp-own" data-fp-g="cmp">RL% RL</th>
            <th class="fp-cmp-rl fp-cmp-pct" data-fp-g="cmp">Mk% RL</th>
            <th class="fp-cmp-mk fp-cmp-start" data-fp-g="cmp">Preço MK <?= $rs ?></th>
            <th class="fp-cmp-mk fp-cmp-pct" data-fp-g="cmp">PB% MK</th>
            <th class="fp-cmp-mk fp-cmp-pct" data-fp-g="cmp">RL% MK</th>
            <th class="fp-cmp-mk fp-cmp-pct fp-cmp-own" data-fp-g="cmp">Mk% MK</th>
            <?php if ($editable): ?><th class="fp-del-col" data-fp-g="always"></th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($itens as $i => $item):
        $calc = is_array($item['calc'] ?? null) ? $item['calc'] : null;
        $idx = (int) $i;
        ?>
        <tr class="fp-row">
            <td class="fp-idx" data-fp-g="always" data-label="#"><?= $idx + 1 ?></td>
            <td class="fp-cod" data-fp-g="produtos" data-label="Código"><input type="text" name="itens[<?= $idx ?>][codigo_produto]" class="form-control" <?= $ro ?> value="<?= htmlspecialchars((string) ($item['codigo_produto'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></td>
            <td class="fp-prod-col" data-fp-g="always" data-label="Produto">
                <input type="text" name="itens[<?= $idx ?>][produto]" class="form-control fp-prod" <?= $ro ?>
                       title="<?= htmlspecialchars((string) ($item['produto'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                       value="<?= htmlspecialchars((string) ($item['produto'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </td>
            <td data-fp-g="produtos" data-label="Forecast"><input type="text" name="itens[<?= $idx ?>][forecast]" class="form-control fp-in" <?= $ro ?> value="<?= htmlspecialchars($fmtMoney($item['forecast'] ?? null, 0), ENT_QUOTES, 'UTF-8') ?>"></td>
            <td data-fp-g="produtos" data-label="ICMS %"><span class="fp-val"><input type="text" name="itens[<?= $idx ?>][icms]" class="form-control fp-in fp-pct" <?= $ro ?> value="<?= htmlspecialchars($fmtPct($item['icms'] ?? null), ENT_QUOTES, 'UTF-8') ?>"><?= $pc ?></span></td>
            <td data-fp-g="produtos" data-label="PIS %"><span class="fp-val"><input type="text" name="itens[<?= $idx ?>][pis]" class="form-control fp-in fp-pct" <?= $ro ?> value="<?= htmlspecialchars($fmtPct($item['pis'] ?? null), ENT_QUOTES, 'UTF-8') ?>"><?= $pc ?></span></td>
            <td data-fp-g="produtos" data-label="COFINS %"><span class="fp-val"><input type="text" name="itens[<?= $idx ?>][cofins]" class="form-control fp-in fp-pct" <?= $ro ?> value="<?= htmlspecialchars($fmtPct($item['cofins'] ?? null), ENT_QUOTES, 'UTF-8') ?>"><?= $pc ?></span></td>
            <td data-fp-g="produtos" data-label="Imposto efetivo %"><span class="fp-val"><span class="fp-out" data-k="imposto"><?= htmlspecialchars($fmtPct($calc['imposto_efetivo'] ?? null), ENT_QUOTES, 'UTF-8') ?></span><?= $pc ?></span></td>
            <td data-fp-g="produtos" data-label="CVAR"><span class="fp-val"><?= $rs ?><input type="text" name="itens[<?= $idx ?>][custo_variavel]" class="form-control fp-in" <?= $ro ?> value="<?= htmlspecialchars($fmtMoney($item['custo_variavel'] ?? null), ENT_QUOTES, 'UTF-8') ?>"></span></td>
            <td data-fp-g="produtos" data-label="CFIX"><span class="fp-val"><?= $rs ?><input type="text" name="itens[<?= $idx ?>][custo_fixo]" class="form-control fp-in" <?= $ro ?> value="<?= htmlspecialchars($fmtMoney($item['custo_fixo'] ?? null), ENT_QUOTES, 'UTF-8') ?>"></span></td>
            <td data-fp-g="produtos" data-label="Custo"><span class="fp-val"><?= $rs ?><span class="fp-out" data-k="custo"><?= htmlspecialchars($fmtMoney($calc['custo_total'] ?? null), ENT_QUOTES, 'UTF-8') ?></span></span></td>
            <td data-fp-g="produtos" data-label="Frete %"><span class="fp-val"><input type="text" name="itens[<?= $idx ?>][frete]" class="form-control fp-in fp-pct" <?= $ro ?> value="<?= htmlspecialchars($fmtPct($item['frete_pct'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"><?= $pc ?></span></td>
            <td data-fp-g="produtos" data-label="Comissão %"><span class="fp-val"><input type="text" name="itens[<?= $idx ?>][comissao]" class="form-control fp-in fp-pct" <?= $ro ?> value="<?= htmlspecialchars($fmtPct($item['comissao_pct'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"><?= $pc ?></span></td>
            <td data-fp-g="produtos" data-label="Desconto %"><span class="fp-val"><input type="text" name="itens[<?= $idx ?>][desconto]" class="form-control fp-in fp-pct" <?= $ro ?> value="<?= htmlspecialchars($fmtPct($item['desconto_pct'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"><?= $pc ?></span></td>
            <td class="fp-meta fp-narrow" data-fp-g="pb" data-label="Meta PB %"><span class="fp-val"><input type="text" name="itens[<?= $idx ?>][meta_pb]" class="form-control fp-in fp-pct" <?= $ro ?> value="<?= htmlspecialchars($fmtPct($item['meta_pb'] ?? null), ENT_QUOTES, 'UTF-8') ?>"><?= $pc ?></span></td>
            <td class="fp-meta fp-narrow" data-fp-g="pb" data-label="Preço informado"><span class="fp-val"><?= $rs ?><input type="text" name="itens[<?= $idx ?>][preco_informado]" class="form-control fp-in fp-preco-inf" <?= $ro ?> value="<?= htmlspecialchars($fmtMoney($item['preco_informado'] ?? null), ENT_QUOTES, 'UTF-8') ?>"></span></td>
            <td data-fp-g="pb" data-label="Status"><span class="fp-out fp-status" data-k="pb.status"><?= htmlspecialchars((string) ($calc['pb']['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
            <td data-fp-g="pb" data-label="Preço vendido"><span class="fp-val"><?= $rs ?><span class="fp-out" data-k="pb.preco_vendido"><?= htmlspecialchars($out($calc, 'pb', 'preco_vendido'), ENT_QUOTES, 'UTF-8') ?></span></span></td>
            <td data-fp-g="pb" data-label="Tabela"><span class="fp-val"><?= $rs ?><span class="fp-out" data-k="pb.preco_tabela"><?= htmlspecialchars($out($calc, 'pb', 'preco_tabela'), ENT_QUOTES, 'UTF-8') ?></span></span></td>
            <td data-fp-g="pb" data-label="Receita líquida"><span class="fp-val"><?= $rs ?><span class="fp-out" data-k="pb.receita_liquida"><?= htmlspecialchars($out($calc, 'pb', 'receita_liquida'), ENT_QUOTES, 'UTF-8') ?></span></span></td>
            <td data-fp-g="pb" data-label="Lucro"><span class="fp-val"><?= $rs ?><span class="fp-out" data-k="pb.lucro_unitario"><?= htmlspecialchars($out($calc, 'pb', 'lucro_unitario'), ENT_QUOTES, 'UTF-8') ?></span></span></td>
            <td class="fp-cmp-pb fp-cmp-pct fp-cmp-own" data-fp-g="pb" data-label="PB real %"><span class="fp-val"><span class="fp-out" data-k="pb.margem_pb"><?= htmlspecialchars($out($calc, 'pb', 'margem_pb'), ENT_QUOTES, 'UTF-8') ?></span><?= $pc ?></span></td>
            <td class="fp-cmp-rl fp-cmp-pct" data-fp-g="pb" data-label="RL real %"><span class="fp-val"><span class="fp-out" data-k="pb.margem_rl"><?= htmlspecialchars($out($calc, 'pb', 'margem_rl'), ENT_QUOTES, 'UTF-8') ?></span><?= $pc ?></span></td>
            <td class="fp-cmp-mk fp-cmp-pct" data-fp-g="pb" data-label="Markup real %"><span class="fp-val"><span class="fp-out" data-k="pb.markup"><?= htmlspecialchars($out($calc, 'pb', 'markup'), ENT_QUOTES, 'UTF-8') ?></span><?= $pc ?></span></td>
            <td class="fp-meta fp-narrow" data-fp-g="rl" data-label="Meta RL %"><span class="fp-val"><input type="text" name="itens[<?= $idx ?>][meta_rl]" class="form-control fp-in fp-pct" <?= $ro ?> value="<?= htmlspecialchars($fmtPct($item['meta_rl'] ?? null), ENT_QUOTES, 'UTF-8') ?>"><?= $pc ?></span></td>
            <td class="fp-meta fp-narrow" data-fp-g="rl" data-label="Preço informado"><span class="fp-val"><?= $rs ?><input type="text" class="form-control fp-in fp-preco-inf" <?= $ro ?> value="<?= htmlspecialchars($fmtMoney($item['preco_informado'] ?? null), ENT_QUOTES, 'UTF-8') ?>"></span></td>
            <td data-fp-g="rl" data-label="Status"><span class="fp-out fp-status" data-k="rl.status"><?= htmlspecialchars((string) ($calc['rl']['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
            <td data-fp-g="rl" data-label="Preço vendido"><span class="fp-val"><?= $rs ?><span class="fp-out" data-k="rl.preco_vendido"><?= htmlspecialchars($out($calc, 'rl', 'preco_vendido'), ENT_QUOTES, 'UTF-8') ?></span></span></td>
            <td data-fp-g="rl" data-label="Tabela"><span class="fp-val"><?= $rs ?><span class="fp-out" data-k="rl.preco_tabela"><?= htmlspecialchars($out($calc, 'rl', 'preco_tabela'), ENT_QUOTES, 'UTF-8') ?></span></span></td>
            <td data-fp-g="rl" data-label="Receita líquida"><span class="fp-val"><?= $rs ?><span class="fp-out" data-k="rl.receita_liquida"><?= htmlspecialchars($out($calc, 'rl', 'receita_liquida'), ENT_QUOTES, 'UTF-8') ?></span></span></td>
            <td data-fp-g="rl" data-label="Lucro"><span class="fp-val"><?= $rs ?><span class="fp-out" data-k="rl.lucro_unitario"><?= htmlspecialchars($out($calc, 'rl', 'lucro_unitario'), ENT_QUOTES, 'UTF-8') ?></span></span></td>
            <td class="fp-cmp-pb fp-cmp-pct" data-fp-g="rl" data-label="PB real %"><span class="fp-val"><span class="fp-out" data-k="rl.margem_pb"><?= htmlspecialchars($out($calc, 'rl', 'margem_pb'), ENT_QUOTES, 'UTF-8') ?></span><?= $pc ?></span></td>
            <td class="fp-cmp-rl fp-cmp-pct fp-cmp-own" data-fp-g="rl" data-label="RL real %"><span class="fp-val"><span class="fp-out" data-k="rl.margem_rl"><?= htmlspecialchars($out($calc, 'rl', 'margem_rl'), ENT_QUOTES, 'UTF-8') ?></span><?= $pc ?></span></td>
            <td class="fp-cmp-mk fp-cmp-pct" data-fp-g="rl" data-label="Markup real %"><span class="fp-val"><span class="fp-out" data-k="rl.markup"><?= htmlspecialchars($out($calc, 'rl', 'markup'), ENT_QUOTES, 'UTF-8') ?></span><?= $pc ?></span></td>
            <td class="fp-meta fp-narrow" data-fp-g="mk" data-label="Meta MK %"><span class="fp-val"><input type="text" name="itens[<?= $idx ?>][meta_mk]" class="form-control fp-in fp-pct" <?= $ro ?> value="<?= htmlspecialchars($fmtPct($item['meta_mk'] ?? null), ENT_QUOTES, 'UTF-8') ?>"><?= $pc ?></span></td>
            <td class="fp-meta fp-narrow" data-fp-g="mk" data-label="Preço informado"><span class="fp-val"><?= $rs ?><input type="text" class="form-control fp-in fp-preco-inf" <?= $ro ?> value="<?= htmlspecialchars($fmtMoney($item['preco_informado'] ?? null), ENT_QUOTES, 'UTF-8') ?>"></span></td>
            <td data-fp-g="mk" data-label="Status"><span class="fp-out fp-status" data-k="mk.status"><?= htmlspecialchars((string) ($calc['mk']['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
            <td data-fp-g="mk" data-label="Preço vendido"><span class="fp-val"><?= $rs ?><span class="fp-out" data-k="mk.preco_vendido"><?= htmlspecialchars($out($calc, 'mk', 'preco_vendido'), ENT_QUOTES, 'UTF-8') ?></span></span></td>
            <td data-fp-g="mk" data-label="Tabela"><span class="fp-val"><?= $rs ?><span class="fp-out" data-k="mk.preco_tabela"><?= htmlspecialchars($out($calc, 'mk', 'preco_tabela'), ENT_QUOTES, 'UTF-8') ?></span></span></td>
            <td data-fp-g="mk" data-label="Receita líquida"><span class="fp-val"><?= $rs ?><span class="fp-out" data-k="mk.receita_liquida"><?= htmlspecialchars($out($calc, 'mk', 'receita_liquida'), ENT_QUOTES, 'UTF-8') ?></span></span></td>
            <td data-fp-g="mk" data-label="Lucro"><span class="fp-val"><?= $rs ?><span class="fp-out" data-k="mk.lucro_unitario"><?= htmlspecialchars($out($calc, 'mk', 'lucro_unitario'), ENT_QUOTES, 'UTF-8') ?></span></span></td>
            <td class="fp-cmp-pb fp-cmp-pct" data-fp-g="mk" data-label="PB real %"><span class="fp-val"><span class="fp-out" data-k="mk.margem_pb"><?= htmlspecialchars($out($calc, 'mk', 'margem_pb'), ENT_QUOTES, 'UTF-8') ?></span><?= $pc ?></span></td>
            <td class="fp-cmp-rl fp-cmp-pct" data-fp-g="mk" data-label="RL real %"><span class="fp-val"><span class="fp-out" data-k="mk.margem_rl"><?= htmlspecialchars($out($calc, 'mk', 'margem_rl'), ENT_QUOTES, 'UTF-8') ?></span><?= $pc ?></span></td>
            <td class="fp-cmp-mk fp-cmp-pct fp-cmp-own" data-fp-g="mk" data-label="Markup real %"><span class="fp-val"><span class="fp-out" data-k="mk.markup"><?= htmlspecialchars($out($calc, 'mk', 'markup'), ENT_QUOTES, 'UTF-8') ?></span><?= $pc ?></span></td>
            <td class="fp-cmp-pb fp-cmp-start" data-fp-g="cmp" data-label="Preço PB"><span class="fp-val"><?= $rs ?><span class="fp-out" data-k="pb.preco_vendido"><?= htmlspecialchars($out($calc, 'pb', 'preco_vendido'), ENT_QUOTES, 'UTF-8') ?></span></span></td>
            <td class="fp-cmp-pb fp-cmp-pct fp-cmp-own" data-fp-g="cmp" data-label="PB% pelo PB"><span class="fp-val"><span class="fp-out" data-k="pb.margem_pb"><?= htmlspecialchars($out($calc, 'pb', 'margem_pb'), ENT_QUOTES, 'UTF-8') ?></span><?= $pc ?></span></td>
            <td class="fp-cmp-pb fp-cmp-pct" data-fp-g="cmp" data-label="RL% pelo PB"><span class="fp-val"><span class="fp-out" data-k="pb.margem_rl"><?= htmlspecialchars($out($calc, 'pb', 'margem_rl'), ENT_QUOTES, 'UTF-8') ?></span><?= $pc ?></span></td>
            <td class="fp-cmp-pb fp-cmp-pct" data-fp-g="cmp" data-label="Mk% pelo PB"><span class="fp-val"><span class="fp-out" data-k="pb.markup"><?= htmlspecialchars($out($calc, 'pb', 'markup'), ENT_QUOTES, 'UTF-8') ?></span><?= $pc ?></span></td>
            <td class="fp-cmp-rl fp-cmp-start" data-fp-g="cmp" data-label="Preço RL"><span class="fp-val"><?= $rs ?><span class="fp-out" data-k="rl.preco_vendido"><?= htmlspecialchars($out($calc, 'rl', 'preco_vendido'), ENT_QUOTES, 'UTF-8') ?></span></span></td>
            <td class="fp-cmp-rl fp-cmp-pct" data-fp-g="cmp" data-label="PB% pelo RL"><span class="fp-val"><span class="fp-out" data-k="rl.margem_pb"><?= htmlspecialchars($out($calc, 'rl', 'margem_pb'), ENT_QUOTES, 'UTF-8') ?></span><?= $pc ?></span></td>
            <td class="fp-cmp-rl fp-cmp-pct fp-cmp-own" data-fp-g="cmp" data-label="RL% pelo RL"><span class="fp-val"><span class="fp-out" data-k="rl.margem_rl"><?= htmlspecialchars($out($calc, 'rl', 'margem_rl'), ENT_QUOTES, 'UTF-8') ?></span><?= $pc ?></span></td>
            <td class="fp-cmp-rl fp-cmp-pct" data-fp-g="cmp" data-label="Mk% pelo RL"><span class="fp-val"><span class="fp-out" data-k="rl.markup"><?= htmlspecialchars($out($calc, 'rl', 'markup'), ENT_QUOTES, 'UTF-8') ?></span><?= $pc ?></span></td>
            <td class="fp-cmp-mk fp-cmp-start" data-fp-g="cmp" data-label="Preço MK"><span class="fp-val"><?= $rs ?><span class="fp-out" data-k="mk.preco_vendido"><?= htmlspecialchars($out($calc, 'mk', 'preco_vendido'), ENT_QUOTES, 'UTF-8') ?></span></span></td>
            <td class="fp-cmp-mk fp-cmp-pct" data-fp-g="cmp" data-label="PB% pelo MK"><span class="fp-val"><span class="fp-out" data-k="mk.margem_pb"><?= htmlspecialchars($out($calc, 'mk', 'margem_pb'), ENT_QUOTES, 'UTF-8') ?></span><?= $pc ?></span></td>
            <td class="fp-cmp-mk fp-cmp-pct" data-fp-g="cmp" data-label="RL% pelo MK"><span class="fp-val"><span class="fp-out" data-k="mk.margem_rl"><?= htmlspecialchars($out($calc, 'mk', 'margem_rl'), ENT_QUOTES, 'UTF-8') ?></span><?= $pc ?></span></td>
            <td class="fp-cmp-mk fp-cmp-pct fp-cmp-own" data-fp-g="cmp" data-label="Mk% pelo MK"><span class="fp-val"><span class="fp-out" data-k="mk.markup"><?= htmlspecialchars($out($calc, 'mk', 'markup'), ENT_QUOTES, 'UTF-8') ?></span><?= $pc ?></span></td>
            <?php if ($editable): ?>
                <td class="fp-del-col" data-fp-g="always" data-label="Remover"><button type="button" class="btn btn-outline-danger btn-sm fp-del" title="Remover linha">&times;</button></td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php if ($editable): ?>
    <div class="d-flex flex-wrap gap-2 mb-3 fp-actions-row">
        <button type="button" class="btn btn-outline-primary btn-sm" id="fp-add-row">Adicionar produto</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="fp-add-exemplo">Incluir exemplo (Probiótico Saúde Vaginal)</button>
    </div>
<?php endif; ?>
</div>

<div class="fp-pane d-none" data-fp-pane="exemplo">
    <p class="small text-muted">Mesma lógica da aba EXEMPLO_PRATICO da planilha: uma meta aplicada aos três métodos. Custo padrão R$ 28,69 e imposto efetivo 20,14% (Probiótico Saúde Vaginal x 30 sticks).</p>
    <div class="row g-3" id="fp-sandbox">
        <div class="col-md-4">
            <label class="form-label">Meta % (nos 3 métodos)</label>
            <input type="range" id="sb-meta" min="5" max="70" value="35" step="1" class="form-range">
            <div class="small" id="sb-meta-l">35%</div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Frete %</label>
            <input type="range" id="sb-frete" min="0" max="20" value="0" step="1" class="form-range">
            <div class="small" id="sb-frete-l">0%</div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Comissão %</label>
            <input type="range" id="sb-comissao" min="0" max="20" value="0" step="1" class="form-range">
            <div class="small" id="sb-comissao-l">0%</div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Desconto % (só tabela)</label>
            <input type="range" id="sb-desconto" min="0" max="30" value="0" step="1" class="form-range">
            <div class="small" id="sb-desconto-l">0%</div>
        </div>
        <div class="col-12">
            <div class="row g-2">
                <div class="col-md-4"><div class="border rounded p-3" id="sb-box-mk"><div class="small text-muted">Markup</div><div class="fs-5 fw-bold sb-price"></div><div class="small sb-extra"></div></div></div>
                <div class="col-md-4"><div class="border rounded p-3" id="sb-box-rl"><div class="small text-muted">Receita líquida</div><div class="fs-5 fw-bold sb-price"></div><div class="small sb-extra"></div></div></div>
                <div class="col-md-4"><div class="border rounded p-3" id="sb-box-pb"><div class="small text-muted">Preço bruto</div><div class="fs-5 fw-bold sb-price"></div><div class="small sb-extra"></div></div></div>
            </div>
            <p class="small text-muted mt-2 mb-0" id="sb-teto"></p>
        </div>
    </div>
</div>

<div class="fp-pane d-none" data-fp-pane="expl">
    <?php include __DIR__ . '/_explicacoes.php'; ?>
</div>
