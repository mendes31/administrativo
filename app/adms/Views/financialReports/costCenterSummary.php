<?php
/** @var array $this->data */
$selectedYear = (int) ($this->data['year'] ?? date('Y'));
$years = range((int) date('Y') - 5, (int) date('Y') + 1);
$months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
$canSapDash = in_array('FinCostCenterDashboard', $this->data['buttonPermission'] ?? [], true);
$sapDashUrl = ($_ENV['URL_ADM'] ?? '') . 'fin-cost-center-dashboard?visao=meses';
$showZeros = !empty($this->data['show_zeros']);
$emptyCount = (int) ($this->data['empty_count'] ?? 0);
$activeCount = (int) ($this->data['active_count'] ?? 0);
$monthTotals = $this->data['month_totals'] ?? array_fill(1, 12, 0.0);
$grandTotal = (float) ($this->data['grand_total'] ?? 0);
$maxAbs = (float) ($this->data['max_abs'] ?? 0);
$rows = $this->data['costCenters'] ?? [];

$money = static function (float $n): string {
    return 'R$ ' . number_format($n, 2, ',', '.');
};
$heat = static function (float $n, float $maxAbs): string {
    if ($maxAbs <= 0.0 || abs($n) < 0.005) {
        return '';
    }
    $p = min(1.0, abs($n) / $maxAbs);
    $a = 0.10 + ($p * 0.42);
    return $n < 0
        ? 'background:rgba(27,122,73,' . $a . ');'
        : 'background:rgba(47,111,222,' . $a . ');';
};
$cls = static function (float $n): string {
    if (abs($n) < 0.005) {
        return 'zero';
    }
    return $n < 0 ? 'neg' : 'pos';
};
$selfQs = static function (array $extra) use ($selectedYear, $showZeros): string {
    $q = array_merge([
        'year' => $selectedYear,
        'show_zeros' => $showZeros ? '1' : '0',
    ], $extra);
    if (($q['show_zeros'] ?? '0') !== '1') {
        unset($q['show_zeros']);
    }
    unset($q['export']);
    return '?' . http_build_query($q);
};
?>
<style>
.ccs-dash{
  --ccs-green:#1B7A49; --ccs-green-dark:#12532F; --ccs-green-tint:#E7F4EC;
  --ccs-orange:#E67E2E; --ccs-orange-tint:#FDEEE0;
  --ccs-red:#D14343; --ccs-blue:#2F6FDE; --ccs-blue-tint:#E8F0FE;
  --ccs-ink:#1B241E; --ccs-mute:#8A9189; --ccs-soft:#55605A;
  --ccs-line:#E2E6E0; --ccs-bg:#F1F4F1; --ccs-surface:#fff;
  --ccs-radius:14px;
  --ccs-shadow:0 1px 2px rgba(18,30,22,0.05), 0 4px 16px rgba(18,30,22,0.06);
  max-width:1700px; margin:0 auto; padding:8px 4px 36px; color:var(--ccs-ink); font-size:14px;
}
.ccs-dash .banner{
  background:linear-gradient(135deg,var(--ccs-green) 0%, var(--ccs-green-dark) 100%);
  border-radius:var(--ccs-radius); padding:18px 22px; color:#fff;
  display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:14px;
}
.ccs-dash .banner h1{margin:0; font-size:1.25rem; font-weight:600;}
.ccs-dash .banner p{margin:4px 0 0; font-size:13px; color:#DCEEE2;}
.ccs-dash .banner-tag{
  background:rgba(255,255,255,.16); border-radius:20px; padding:7px 16px;
  font-size:12.5px; font-weight:500; white-space:nowrap;
}
.ccs-dash .toolbar{
  background:var(--ccs-surface); border:1px solid var(--ccs-line); border-radius:var(--ccs-radius);
  box-shadow:var(--ccs-shadow); padding:14px 16px; display:flex; flex-wrap:wrap; gap:10px; align-items:end; margin-bottom:12px;
}
.ccs-dash .toolbar label{display:flex; flex-direction:column; gap:4px; font-size:11px; font-weight:600; color:var(--ccs-mute); text-transform:uppercase; letter-spacing:.03em; margin:0;}
.ccs-dash .toolbar select{
  font:inherit; font-size:13px; border:1px solid var(--ccs-line); background:var(--ccs-bg);
  border-radius:8px; padding:8px 10px; min-width:110px; outline:none;
}
.ccs-dash .toolbar select:focus{border-color:var(--ccs-green); box-shadow:0 0 0 2px var(--ccs-green-tint);}
.ccs-dash .btn{
  display:inline-flex; align-items:center; height:38px; padding:0 14px; border-radius:8px;
  font-size:12.5px; font-weight:600; text-decoration:none; border:1px solid var(--ccs-line); cursor:pointer;
  background:#fff; color:var(--ccs-ink);
}
.ccs-dash .btn.primary{background:var(--ccs-green); border-color:var(--ccs-green); color:#fff;}
.ccs-dash .btn.primary:hover{background:var(--ccs-green-dark); border-color:var(--ccs-green-dark); color:#fff;}
.ccs-dash .btn.ghost{background:var(--ccs-green-tint); border-color:var(--ccs-green-tint); color:var(--ccs-green-dark);}
.ccs-dash .kpis{display:grid; grid-template-columns:repeat(3,1fr); gap:11px; margin-bottom:12px;}
.ccs-dash .kpi{
  background:#fff; border:1px solid var(--ccs-line); border-radius:var(--ccs-radius);
  padding:14px 15px; box-shadow:var(--ccs-shadow);
}
.ccs-dash .kpi.accent{background:var(--ccs-green-dark); border-color:var(--ccs-green-dark); color:#fff;}
.ccs-dash .kpi.c-green{background:var(--ccs-green-tint); border-color:#CDE9D6;}
.ccs-dash .kpi.c-orange{background:var(--ccs-orange-tint); border-color:#F3CFA3;}
.ccs-dash .kt{font-size:10.5px; text-transform:uppercase; letter-spacing:.04em; font-weight:700; color:var(--ccs-mute);}
.ccs-dash .accent .kt{color:#CDE9D6;}
.ccs-dash .kv{font-size:18px; font-weight:800; margin-top:5px;}
.ccs-dash .accent .kv,.ccs-dash .accent .pos,.ccs-dash .accent .neg{color:#fff!important;}
.ccs-dash .kpi.c-green .kv{color:var(--ccs-green-dark);}
.ccs-dash .kpi.c-orange .kv{color:#8A4413;}
.ccs-dash .kh{font-size:11px; color:var(--ccs-mute); margin-top:3px;}
.ccs-dash .accent .kh{color:#DCEEE2;}
.ccs-dash .pos{color:var(--ccs-green)!important;} .ccs-dash .neg{color:var(--ccs-red)!important;}
.ccs-dash .card{background:#fff; border:1px solid var(--ccs-line); border-radius:var(--ccs-radius); box-shadow:var(--ccs-shadow); overflow:hidden;}
.ccs-dash .cardh{padding:12px 16px; border-bottom:1px solid var(--ccs-line); font-weight:700; display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;}
.ccs-dash .empty{padding:28px 20px; text-align:center; color:var(--ccs-soft);}
.ccs-dash .empty strong{display:block; font-size:16px; margin-bottom:6px; color:var(--ccs-ink);}
.ccs-dash .mtx-wrap{overflow:auto; max-height:70vh;}
.ccs-dash table{width:100%; border-collapse:collapse; font-size:12.5px;}
.ccs-dash th{background:var(--ccs-green); color:#fff; padding:8px; text-align:right; font-size:11px; position:sticky; top:0; z-index:2;}
.ccs-dash td{padding:7px 8px; border-bottom:1px solid #EEF1F5; text-align:right;}
.ccs-dash tbody tr:hover td{background:#FAFBFD;}
.ccs-dash th.tl,.ccs-dash td.tl{text-align:left;}
.ccs-dash th:first-child,.ccs-dash td:first-child{position:sticky; left:0; z-index:1; background:#fff; min-width:220px;}
.ccs-dash th:first-child{z-index:3; background:var(--ccs-green);}
.ccs-dash td.zero{color:#B7BDB8;}
.ccs-dash tfoot td{font-weight:800; background:var(--ccs-green-tint);}
.ccs-dash tfoot td:first-child{background:var(--ccs-green-tint);}
@media (max-width:900px){.ccs-dash .kpis{grid-template-columns:1fr;}}
</style>
<div class="container-fluid px-2 px-md-3">
<div class="ccs-dash">
  <div class="banner">
    <div>
      <h1>Resumo por Centro de Custo</h1>
      <p>Movimentos do Portal (pagar/receber locais). Não é o razão SAP — a matriz equivalente está na aba Meses dos Indicadores SAP.</p>
    </div>
    <span class="banner-tag"><?= (int) $selectedYear ?></span>
  </div>

  <form method="get" class="toolbar">
    <label>Ano
      <select name="year" id="year" onchange="this.form.submit()">
        <?php foreach ($years as $y): ?>
          <option value="<?= (int) $y ?>" <?= (int) $y === $selectedYear ? 'selected' : '' ?>><?= (int) $y ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php if ($showZeros): ?>
      <input type="hidden" name="show_zeros" value="1">
    <?php endif; ?>
    <button type="submit" name="export" value="excel" class="btn">Excel</button>
    <button type="submit" name="export" value="pdf" class="btn">PDF</button>
    <?php if ($emptyCount > 0): ?>
      <?php if ($showZeros): ?>
        <a class="btn ghost" href="<?= htmlspecialchars($selfQs(['show_zeros' => '0', 'export' => null]), ENT_QUOTES, 'UTF-8') ?>">Ocultar zerados (<?= $emptyCount ?>)</a>
      <?php else: ?>
        <a class="btn ghost" href="<?= htmlspecialchars($selfQs(['show_zeros' => '1', 'export' => null]), ENT_QUOTES, 'UTF-8') ?>">Mostrar zerados (<?= $emptyCount ?>)</a>
      <?php endif; ?>
    <?php endif; ?>
    <?php if ($canSapDash): ?>
      <a class="btn primary" href="<?= htmlspecialchars($sapDashUrl, ENT_QUOTES, 'UTF-8') ?>">Abrir matriz SAP (aba Meses)</a>
    <?php endif; ?>
  </form>

  <div class="kpis">
    <div class="kpi accent">
      <div class="kt">Total no ano</div>
      <div class="kv <?= $cls($grandTotal) ?>"><?= $money($grandTotal) ?></div>
      <div class="kh">Entradas − saídas nos movimentos locais</div>
    </div>
    <div class="kpi c-green">
      <div class="kt">Centros com movimento</div>
      <div class="kv"><?= $activeCount ?></div>
      <div class="kh"><?= $emptyCount ?> cadastro(s) sem valor neste ano</div>
    </div>
    <div class="kpi c-orange">
      <div class="kt">Origem</div>
      <div class="kv" style="font-size:16px;">Portal</div>
      <div class="kh">Não inclui lançamentos do SAP B1</div>
    </div>
  </div>

  <div class="card">
    <div class="cardh">
      <span>Centro × mês (Portal)</span>
      <span style="font-weight:500;font-size:12px;color:#8A9189;"><?= count($rows) ?> linha(s)</span>
    </div>
    <?php if ($activeCount === 0 && !$showZeros): ?>
      <div class="empty">
        <strong>Nenhum movimento local em <?= (int) $selectedYear ?>.</strong>
        Esta grade usa contas a pagar/receber do Portal, não o SAP. Por isso aparece zerada mesmo com lançamentos no razão.
        <?php if ($canSapDash): ?>
          <div style="margin-top:14px;"><a class="btn primary" href="<?= htmlspecialchars($sapDashUrl, ENT_QUOTES, 'UTF-8') ?>">Ver a mesma matriz no SAP</a></div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="mtx-wrap">
        <table>
          <thead>
            <tr>
              <th class="tl">Centro de custo</th>
              <?php foreach ($months as $m): ?>
                <th><?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?></th>
              <?php endforeach; ?>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $cc): ?>
              <tr>
                <td class="tl"><strong><?= htmlspecialchars((string) $cc['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                <?php for ($i = 1; $i <= 12; $i++):
                    $v = (float) $cc['months'][$i]; ?>
                  <td class="<?= $cls($v) ?>" style="<?= $heat($v, $maxAbs) ?>"><?= abs($v) < 0.005 ? '—' : $money($v) ?></td>
                <?php endfor; ?>
                <?php $t = (float) $cc['total']; ?>
                <td class="<?= $cls($t) ?>"><strong><?= $money($t) ?></strong></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <?php if ($rows): ?>
            <tfoot>
              <tr>
                <td class="tl">TOTAL</td>
                <?php for ($i = 1; $i <= 12; $i++):
                    $v = (float) ($monthTotals[$i] ?? 0); ?>
                  <td class="<?= $cls($v) ?>"><?= $money($v) ?></td>
                <?php endfor; ?>
                <td class="<?= $cls($grandTotal) ?>"><?= $money($grandTotal) ?></td>
              </tr>
            </tfoot>
          <?php endif; ?>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
</div>
