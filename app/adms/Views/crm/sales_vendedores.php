<?php
/** @var array $this->data */
$an = $this->data['analytics'] ?? [];
$from = (string) ($an['periodo']['date_from'] ?? '');
$to = (string) ($an['periodo']['date_to'] ?? '');
$fromBr = $from !== '' ? date('d/m/Y', strtotime($from)) : '—';
$toBr = $to !== '' ? date('d/m/Y', strtotime($to)) : '—';
$warning = trim((string) ($an['warning'] ?? ''));
$rows = $an['vendedores'] ?? [];
$fmtMoeda = static fn (float $v): string => 'R$ ' . number_format($v, 2, ',', '.');
$fmtPct = static fn (float $v): string => number_format($v, 1, ',', '.') . '%';
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
        <h1>Força de vendas</h1>
        <p>Scorecard por vendedor no recorte — desconto, devolução, remessa e clientes. Sem custo nem margem.</p>
      </div>
      <div class="csd-periodo-tag"><?= htmlspecialchars($fromBr . ' a ' . $toBr, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <?php if ($warning !== ''): ?>
      <div class="csd-warn"><?= htmlspecialchars($warning, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php include './app/adms/Views/partials/sales_analysis_filters.php'; ?>

    <section class="csd-kpi-row">
      <div class="csd-kpi c-green">
        <p class="label">Faturamento líquido</p>
        <p class="value"><?= $fmtMoeda((float) ($an['faturamento_liquido'] ?? 0)) ?></p>
        <p class="delta"><?= count($rows) ?> vendedores no recorte</p>
      </div>
    </section>

    <p class="csd-note">Líquido, desconto e NFs são só natureza venda. Bonificação e brinde aparecem à parte e não entram no faturamento. Ticket = líquido ÷ clientes com fatura. Custo e margem não são exibidos.</p>

    <div class="csd-panel">
      <h2>Vendedores</h2>
      <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>Vendedor</th>
              <th class="num">Líquido</th>
              <th class="num">Share</th>
              <th class="num">% desc.</th>
              <th class="num">Taxa dev.</th>
              <th class="num">Ticket</th>
              <th class="num">Clientes</th>
              <th class="num">NFs</th>
              <th class="num">Bonificação</th>
              <th class="num">Brinde</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($rows === []): ?>
              <tr><td colspan="10">Nenhum vendedor no recorte.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td class="name-cell"><span class="cliente-nome"><?= htmlspecialchars((string) ($row['vendedor'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                <td class="num"><?= $fmtMoeda((float) ($row['liquido'] ?? 0)) ?></td>
                <td class="num"><?= $fmtPct((float) ($row['pct_share'] ?? 0)) ?></td>
                <td class="num"><?= $fmtPct((float) ($row['pct_desconto'] ?? 0)) ?></td>
                <td class="num"><?= $fmtPct((float) ($row['taxa_devolucao'] ?? 0)) ?></td>
                <td class="num"><?= $fmtMoeda((float) ($row['ticket'] ?? 0)) ?></td>
                <td class="num"><?= (int) ($row['clientes'] ?? 0) ?></td>
                <td class="num"><?= isset($row['qtd_nfs']) ? (int) $row['qtd_nfs'] : '—' ?></td>
                <td class="num"><?= $fmtMoeda((float) ($row['bonificacao'] ?? 0)) ?></td>
                <td class="num"><?= $fmtMoeda((float) ($row['brinde'] ?? 0)) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
