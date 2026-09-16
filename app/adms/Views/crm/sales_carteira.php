<?php
/** @var array $this->data */
$an = $this->data['analytics'] ?? [];
$from = (string) ($an['periodo']['date_from'] ?? '');
$to = (string) ($an['periodo']['date_to'] ?? '');
$fromBr = $from !== '' ? date('d/m/Y', strtotime($from)) : '—';
$toBr = $to !== '' ? date('d/m/Y', strtotime($to)) : '—';
$warning = trim((string) ($an['warning'] ?? ''));
$rows = $an['clientes'] ?? [];
$abc = $an['abc'] ?? ['A' => ['qtd' => 0, 'valor' => 0, 'share' => 0], 'B' => ['qtd' => 0, 'valor' => 0, 'share' => 0], 'C' => ['qtd' => 0, 'valor' => 0, 'share' => 0]];
$fmtMoeda = static fn (float $v): string => 'R$ ' . number_format($v, 2, ',', '.');
$fmtPct = static fn (float $v): string => number_format($v, 1, ',', '.') . '%';
$fmtData = static function (string $d): string {
    if ($d === '') {
        return '—';
    }
    $t = strtotime($d);
    return $t ? date('d/m/Y', $t) : $d;
};
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
        <h1>Carteira de clientes</h1>
        <p>Concentração, Pareto ABC e clientes novos no recorte — sem custo nem margem</p>
      </div>
      <div class="csd-periodo-tag"><?= htmlspecialchars($fromBr . ' a ' . $toBr, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <?php if ($warning !== ''): ?>
      <div class="csd-warn"><?= htmlspecialchars($warning, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php include './app/adms/Views/partials/sales_analysis_filters.php'; ?>

    <section class="csd-kpi-row">
      <div class="csd-kpi c-green">
        <p class="label">Clientes no recorte</p>
        <p class="value"><?= (int) ($an['clientes_ativos'] ?? 0) ?></p>
        <p class="delta"><?= $fmtMoeda((float) ($an['faturamento_liquido'] ?? 0)) ?> líquido</p>
      </div>
      <div class="csd-kpi c-blue">
        <p class="label">Novos no período</p>
        <p class="value"><?= (int) ($an['novos'] ?? 0) ?></p>
        <p class="delta"><?= $fmtMoeda((float) ($an['valor_novos'] ?? 0)) ?> · primeira fatura no cache neste intervalo</p>
      </div>
      <div class="csd-kpi">
        <p class="label">Recorrentes</p>
        <p class="value"><?= (int) ($an['recorrentes'] ?? 0) ?></p>
        <p class="delta"><?= $fmtMoeda((float) ($an['valor_recorrentes'] ?? 0)) ?></p>
      </div>
      <div class="csd-kpi c-orange">
        <p class="label">Top 10 da carteira</p>
        <p class="value"><?= $fmtPct((float) ($an['top10_share'] ?? 0)) ?></p>
        <p class="delta"><?= $fmtMoeda((float) ($an['top10_valor'] ?? 0)) ?> do líquido positivo</p>
      </div>
    </section>

    <section class="csd-kpi-row">
      <?php foreach (['A', 'B', 'C'] as $cls): ?>
        <?php $item = $abc[$cls] ?? ['qtd' => 0, 'valor' => 0, 'share' => 0]; ?>
        <div class="csd-kpi">
          <p class="label">Classe <?= $cls ?></p>
          <p class="value"><?= (int) $item['qtd'] ?> clientes</p>
          <p class="delta"><?= $fmtPct((float) $item['share']) ?> · <?= $fmtMoeda((float) $item['valor']) ?></p>
        </div>
      <?php endforeach; ?>
    </section>

    <p class="csd-note">Classe A = até 80% do líquido acumulado; B até 95%; C o restante. “Novo” é a primeira fatura de venda encontrada no cache (até 36 meses), não a primeira NF histórica da empresa. Custo e margem não entram nesta tela.</p>

    <div class="csd-panel">
      <h2>Clientes do recorte</h2>
      <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>ABC</th>
              <th>Cliente</th>
              <th>Grupo</th>
              <th>Tipo</th>
              <th>1ª fatura (cache)</th>
              <th class="num">Líquido</th>
              <th class="num">Share</th>
              <th class="num">Acum.</th>
              <th class="num">Devolução</th>
              <th class="num">NFs</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($rows === []): ?>
              <tr><td colspan="10">Nenhum cliente no recorte.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
              <?php
              $cls = strtolower((string) ($row['classe_abc'] ?? 'c'));
              $tipo = (string) ($row['tipo_carteira'] ?? 'recorrente');
              ?>
              <tr>
                <td><span class="abc <?= htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($row['classe_abc'] ?? 'C'), ENT_QUOTES, 'UTF-8') ?></span></td>
                <td class="name-cell">
                  <span class="cliente-code"><?= htmlspecialchars((string) ($row['card_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                  <span class="cliente-nome"><?= htmlspecialchars((string) ($row['cliente'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </td>
                <td class="col-text"><?= htmlspecialchars((string) ($row['grupo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <?php if ($tipo === 'novo'): ?>
                    <span class="tag-novo">Novo</span>
                  <?php else: ?>
                    <span class="tag-rec">Recorrente</span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($fmtData((string) ($row['primeira'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="num"><?= $fmtMoeda((float) ($row['liquido'] ?? 0)) ?></td>
                <td class="num"><?= $fmtPct((float) ($row['pct_share'] ?? 0)) ?></td>
                <td class="num"><?= $fmtPct((float) ($row['pct_acum'] ?? 0)) ?></td>
                <td class="num"><?= $fmtMoeda((float) ($row['devolucao'] ?? 0)) ?></td>
                <td class="num"><?= isset($row['qtd_nfs']) ? (int) $row['qtd_nfs'] : '—' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
