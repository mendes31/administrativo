<?php
/** @var array $this->data */
$inv = $this->data['invoices'] ?? [];
$baseUrl = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
$dashUrl = htmlspecialchars($baseUrl . 'crm-sales-dashboard', ENT_QUOTES, 'UTF-8');
$selfUrl = $baseUrl . 'crm-sales-invoices';
$qs = (string) ($this->data['query_string'] ?? '');
$title = htmlspecialchars((string) ($inv['titulo'] ?? 'Notas fiscais'), ENT_QUOTES, 'UTF-8');
$from = htmlspecialchars((string) ($inv['periodo']['date_from'] ?? ''), ENT_QUOTES, 'UTF-8');
$to = htmlspecialchars((string) ($inv['periodo']['date_to'] ?? ''), ENT_QUOTES, 'UTF-8');
$fromBr = $from !== '' ? date('d/m/Y', strtotime($from)) : '—';
$toBr = $to !== '' ? date('d/m/Y', strtotime($to)) : '—';
$rows = $inv['rows'] ?? [];
$escopoItem = !empty($inv['escopo_item']);
$warning = trim((string) ($inv['warning'] ?? ''));
$page = (int) ($inv['page'] ?? 1);
$pages = (int) ($inv['pages'] ?? 1);
$totalRows = (int) ($inv['total_rows'] ?? 0);
$totalValor = (float) ($inv['total_valor'] ?? 0);
$totalQtd = (float) ($inv['total_quantidade'] ?? 0);
$qtdVenda = (int) ($inv['qtd_venda'] ?? 0);
$qtdDev = (int) ($inv['qtd_devolucao'] ?? 0);
$qtdNfs = (int) ($inv['qtd_nfs'] ?? ($qtdVenda - $qtdDev));
$fmtMoeda = static fn (float $v): string => 'R$ ' . number_format($v, 2, ',', '.');
$fmtQtd = static fn (float $v): string => number_format($v, 3, ',', '.');

$tipoLabel = static function (string $tipo): string {
    return stripos($tipo, 'dev') !== false ? 'Devolução' : 'Venda';
};
$docLabel = static function (array $row): string {
    $num = (int) ($row['doc_num'] ?? 0);
    $tipo = (string) ($row['tipo_documento'] ?? '');
    $pref = stripos($tipo, 'dev') !== false ? 'NC' : 'NS';
    return $pref . ' ' . $num;
};
$pageUrl = static function (int $p) use ($selfUrl, $qs): string {
    $join = $qs !== '' ? '&' : '';
    return htmlspecialchars($selfUrl . '?' . $qs . $join . 'page=' . $p, ENT_QUOTES, 'UTF-8');
};
?>

<style>
.crm-sales-inv{max-width:1200px;margin:0 auto;padding:8px 4px 40px;color:#1B241E;font-size:14px;}
.crm-sales-inv .csd-banner{
  background:linear-gradient(135deg,#1B7A49 0%,#12532F 100%);
  border-radius:14px;padding:18px 22px;color:#fff;
  display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:14px;
}
.crm-sales-inv .csd-banner h1{margin:0;font-size:1.25rem;font-weight:600;}
.crm-sales-inv .csd-banner p{margin:4px 0 0;font-size:13px;color:#DCEEE2;}
.crm-sales-inv .csd-banner .csd-back{
  display:inline-flex;align-items:center;gap:6px;flex-shrink:0;
  background:#fff;color:#12532F !important;text-decoration:none !important;
  font-weight:600;font-size:13px;border-radius:10px;padding:8px 14px;
  border:0;box-shadow:0 1px 2px rgba(0,0,0,.08);white-space:nowrap;z-index:2;
}
.crm-sales-inv .csd-banner .csd-back:hover{background:#E7F4EC;color:#0d3d22 !important;}
.crm-sales-inv .csd-warn{
  background:#FDEEE0;border:1px solid #F3CFA3;color:#8A4413;
  border-radius:14px;padding:12px 14px;margin-bottom:12px;
}
.crm-sales-inv .csd-counts{
  display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:14px;
}
.crm-sales-inv .csd-count{
  background:#fff;border:1px solid #E2E6E0;border-radius:14px;padding:12px 14px;
  box-shadow:0 1px 2px rgba(18,30,22,.05);
}
.crm-sales-inv .csd-count .k{font-size:11px;text-transform:uppercase;letter-spacing:.03em;color:#8A9189;font-weight:700;margin:0 0 4px;}
.crm-sales-inv .csd-count .v{font-size:1.35rem;font-weight:700;font-variant-numeric:tabular-nums;margin:0;color:#1B241E;}
.crm-sales-inv .csd-count .s{font-size:12px;color:#55605A;margin:4px 0 0;}
.crm-sales-inv .csd-count.is-dev .v{color:#D14343;}
@media (max-width:640px){.crm-sales-inv .csd-counts{grid-template-columns:1fr;}}
.crm-sales-inv .csd-panel{
  background:#fff;border:1px solid #E2E6E0;border-radius:14px;
  box-shadow:0 1px 2px rgba(18,30,22,.05);padding:14px 16px;
}
.crm-sales-inv table{width:100%;border-collapse:collapse;}
.crm-sales-inv th{text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.03em;color:#8A9189;padding:0 8px 8px;border-bottom:1px solid #E2E6E0;}
.crm-sales-inv td{padding:9px 8px;border-bottom:1px solid #F1F4F1;vertical-align:top;}
.crm-sales-inv .num{text-align:right;font-variant-numeric:tabular-nums;font-weight:600;white-space:nowrap;}
.crm-sales-inv .neg{color:#D14343;}
.crm-sales-inv .badge{display:inline-block;font-size:11px;font-weight:700;border-radius:999px;padding:2px 8px;}
.crm-sales-inv .badge-venda{background:#E7F4EC;color:#12532F;}
.crm-sales-inv .badge-dev{background:#FBEAEA;color:#8A2323;}
.crm-sales-inv .doc{font-family:ui-monospace,Consolas,monospace;font-weight:700;}
.crm-sales-inv tfoot td{font-weight:700;border-top:1px solid #E2E6E0;padding-top:10px;}
.crm-sales-inv .muted{color:#55605A;font-size:12.5px;margin:0 0 12px;}
.crm-sales-inv .pager{display:flex;gap:8px;align-items:center;justify-content:flex-end;margin-top:12px;font-size:13px;}
.crm-sales-inv .pager a{color:#1B7A49;font-weight:600;text-decoration:none;}
</style>

<div class="container-fluid px-2 px-md-3">
  <?php include './app/adms/Views/partials/alerts.php'; ?>

  <div class="crm-sales-inv">
    <div class="csd-banner">
      <div>
        <h1><?= $title ?></h1>
        <p>Uma linha por nota (sem parcelas) · <?= htmlspecialchars($fromBr, ENT_QUOTES, 'UTF-8') ?> a <?= htmlspecialchars($toBr, ENT_QUOTES, 'UTF-8') ?>
          · <?= $qtdNfs ?> NF(s) líquidas (<?= $qtdVenda ?> venda<?= $qtdVenda === 1 ? '' : 's' ?> − <?= $qtdDev ?> devolução<?= $qtdDev === 1 ? '' : 'ões' ?>)</p>
      </div>
      <a class="csd-back" href="<?= $dashUrl ?>">← Voltar ao dashboard</a>
    </div>

    <?php if ($warning !== ''): ?>
      <div class="csd-warn"><?= htmlspecialchars($warning, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="csd-counts">
      <div class="csd-count">
        <p class="k">Notas de venda</p>
        <p class="v"><?= $qtdVenda ?></p>
        <p class="s">NS no recorte</p>
      </div>
      <div class="csd-count is-dev">
        <p class="k">Notas de devolução</p>
        <p class="v"><?= $qtdDev ?></p>
        <p class="s">NC no recorte</p>
      </div>
      <div class="csd-count">
        <p class="k">NFs líquidas</p>
        <p class="v"><?= $qtdNfs ?></p>
        <p class="s">vendas − devoluções</p>
      </div>
    </div>

    <div class="csd-panel">
      <p class="muted">
        <?= $totalRows ?> linha(s) na lista (venda e devolução).
        <?php if ($escopoItem): ?>
          Valor e quantidade são do <strong>item filtrado</strong> em cada nota, não o total da NF.
        <?php else: ?>
          Valor = total da nota no recorte do painel (grupos 104/106, natureza venda).
        <?php endif; ?>
      </p>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Documento</th>
              <th>Tipo</th>
              <th>Data</th>
              <th>Cliente</th>
              <th>Vendedor</th>
              <?php if ($escopoItem): ?><th class="num">Qtd</th><?php endif; ?>
              <th class="num">Valor</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($rows === []): ?>
              <tr>
                <td colspan="<?= $escopoItem ? 7 : 6 ?>" style="text-align:center;color:#8A9189;padding:24px;">
                  Nenhuma nota neste recorte.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($rows as $row): ?>
                <?php
                $tipo = $tipoLabel((string) ($row['tipo_documento'] ?? ''));
                $isDev = $tipo === 'Devolução';
                $valor = (float) ($row['valor'] ?? 0);
                $dataBr = !empty($row['doc_date']) ? date('d/m/Y', strtotime((string) $row['doc_date'])) : '—';
                $cliente = trim((string) ($row['card_code'] ?? '') . ' · ' . (string) ($row['cliente'] ?? ''), ' ·');
                ?>
                <tr>
                  <td class="doc"><?= htmlspecialchars($docLabel($row), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><span class="badge <?= $isDev ? 'badge-dev' : 'badge-venda' ?>"><?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td><?= htmlspecialchars($dataBr, ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars($cliente !== '' ? $cliente : '—', ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string) ($row['vendedor'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                  <?php if ($escopoItem): ?>
                    <td class="num"><?= $fmtQtd((float) ($row['quantidade'] ?? 0)) ?></td>
                  <?php endif; ?>
                  <td class="num<?= $valor < 0 ? ' neg' : '' ?>"><?= $fmtMoeda($valor) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
          <?php if ($rows !== []): ?>
            <tfoot>
              <tr>
                <td colspan="<?= $escopoItem ? 5 : 5 ?>">Total (<?= $totalRows ?> nota(s))</td>
                <?php if ($escopoItem): ?><td class="num"><?= $fmtQtd($totalQtd) ?></td><?php endif; ?>
                <td class="num<?= $totalValor < 0 ? ' neg' : '' ?>"><?= $fmtMoeda($totalValor) ?></td>
              </tr>
            </tfoot>
          <?php endif; ?>
        </table>
      </div>

      <?php if ($pages > 1): ?>
        <div class="pager">
          <?php if ($page > 1): ?>
            <a href="<?= $pageUrl($page - 1) ?>">Anterior</a>
          <?php endif; ?>
          <span>Página <?= $page ?> de <?= $pages ?></span>
          <?php if ($page < $pages): ?>
            <a href="<?= $pageUrl($page + 1) ?>">Próxima</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
