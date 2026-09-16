<?php
/** @var array $this->data */
$an = $this->data['analytics'] ?? [];
$from = (string) ($an['periodo']['date_from'] ?? '');
$to = (string) ($an['periodo']['date_to'] ?? '');
$fromBr = $from !== '' ? date('d/m/Y', strtotime($from)) : '—';
$toBr = $to !== '' ? date('d/m/Y', strtotime($to)) : '—';
$warning = trim((string) ($an['warning'] ?? ''));
$rows = $an['itens'] ?? [];
$abc = $an['abc'] ?? ['A' => ['qtd' => 0, 'valor' => 0, 'share' => 0], 'B' => ['qtd' => 0, 'valor' => 0, 'share' => 0], 'C' => ['qtd' => 0, 'valor' => 0, 'share' => 0]];
$mix = $an['mix'] ?? [];
$fmtMoeda = static fn (float $v): string => 'R$ ' . number_format($v, 2, ',', '.');
$fmtPct = static fn (float $v): string => number_format($v, 1, ',', '.') . '%';
$fmtQtd = static fn (float $v): string => number_format($v, 3, ',', '.');
$mixTotal = 0.0;
foreach ($mix as $m) {
    $mixTotal += (float) ($m['valor'] ?? 0);
}
?>
<style>
<?php include './app/adms/Views/partials/sales_analysis_css.php'; ?>
</style>

<div class="container-fluid px-2 px-md-3">
  <?php include './app/adms/Views/partials/alerts.php'; ?>
  <div class="crm-sales-an">
    <?php include './app/adms/Views/partials/sales_nav.php'; ?>
    <div class="csd-banner">
      <div>
        <h1>Produto</h1>
        <p>ABC de SKU, desconto, devolução e mix dos grupos 104/106 — sem custo nem margem</p>
      </div>
      <div class="csd-periodo-tag"><?= htmlspecialchars($fromBr . ' a ' . $toBr, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <?php if ($warning !== ''): ?>
      <div class="csd-warn"><?= htmlspecialchars($warning, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php include './app/adms/Views/partials/sales_analysis_filters.php'; ?>

    <section class="csd-kpi-row">
      <div class="csd-kpi c-green">
        <p class="label">Líquido de venda</p>
        <p class="value"><?= $fmtMoeda((float) ($an['faturamento_liquido'] ?? 0)) ?></p>
        <p class="delta"><?= count($rows) ?> SKUs no recorte</p>
      </div>
      <?php foreach (['A', 'B', 'C'] as $cls): ?>
        <?php $item = $abc[$cls] ?? ['qtd' => 0, 'valor' => 0, 'share' => 0]; ?>
        <div class="csd-kpi">
          <p class="label">Classe <?= $cls ?></p>
          <p class="value"><?= (int) $item['qtd'] ?> SKUs</p>
          <p class="delta"><?= $fmtPct((float) $item['share']) ?> · <?= $fmtMoeda((float) $item['valor']) ?></p>
        </div>
      <?php endforeach; ?>
    </section>

    <div class="csd-panel">
      <h2>Mix por grupo de item</h2>
      <?php if ($mix === []): ?>
        <p class="csd-note">Sem mix no recorte.</p>
      <?php else: ?>
        <div class="table-scroll" style="max-height:220px;">
          <table>
            <thead>
              <tr>
                <th>Grupo</th>
                <th class="num">Líquido</th>
                <th class="num">Share</th>
                <th class="num">NFs</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($mix as $m): ?>
                <?php $valor = (float) ($m['valor'] ?? 0); ?>
                <tr>
                  <td><?= htmlspecialchars((string) ($m['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="num"><?= $fmtMoeda($valor) ?></td>
                  <td class="num"><?= $fmtPct($mixTotal > 0 ? $valor / $mixTotal * 100 : 0) ?></td>
                  <td class="num"><?= isset($m['qtd_nfs']) ? (int) $m['qtd_nfs'] : '—' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <p class="csd-note">Só natureza venda (bonificação e brinde ficam no cockpit). Classe A = até 80% do líquido acumulado. % desconto usa o bruto sem desconto da linha. Custo e margem não entram enquanto os custos SAP não estiverem confiáveis.</p>

    <div class="csd-panel">
      <h2>Itens vendidos</h2>
      <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>ABC</th>
              <th>Item</th>
              <th>Grupo</th>
              <th class="num">Qtd</th>
              <th class="num">Líquido</th>
              <th class="num">Share</th>
              <th class="num">Acum.</th>
              <th class="num">% desc.</th>
              <th class="num">Taxa dev.</th>
              <th class="num">NFs</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($rows === []): ?>
              <tr><td colspan="10">Nenhum item no recorte.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
              <?php $cls = strtolower((string) ($row['classe_abc'] ?? 'c')); ?>
              <tr>
                <td><span class="abc <?= htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($row['classe_abc'] ?? 'C'), ENT_QUOTES, 'UTF-8') ?></span></td>
                <td class="name-cell">
                  <span class="cliente-code"><?= htmlspecialchars((string) ($row['item_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                  <span class="cliente-nome"><?= htmlspecialchars((string) ($row['item_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </td>
                <td class="col-text"><?= htmlspecialchars((string) ($row['grupo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="num"><?= $fmtQtd((float) ($row['quantidade'] ?? 0)) ?></td>
                <td class="num"><?= $fmtMoeda((float) ($row['liquido'] ?? 0)) ?></td>
                <td class="num"><?= $fmtPct((float) ($row['pct_share'] ?? 0)) ?></td>
                <td class="num"><?= $fmtPct((float) ($row['pct_acum'] ?? 0)) ?></td>
                <td class="num"><?= $fmtPct((float) ($row['pct_desconto'] ?? 0)) ?></td>
                <td class="num"><?= $fmtPct((float) ($row['taxa_devolucao'] ?? 0)) ?></td>
                <td class="num"><?= isset($row['qtd_nfs']) ? (int) $row['qtd_nfs'] : '—' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
