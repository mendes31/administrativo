<?php if (!isset($this)) { exit; } ?>
<?php
use App\adms\Helpers\InvCostBatchAdoptedHelper;

$periodId = (int)($periodId ?? 0);
$skuResults = $skuResults ?? [];
$skuFilter = trim((string)($skuFilter ?? ''));
$skuResultsAllCount = (int)($skuResultsAllCount ?? count($skuResults));
$period = $period ?? [];
$fmtQty = static fn(?float $v): string => $v === null ? '—' : number_format($v, 0, ',', '.');
$fmtPct = static fn(?float $v): string => $v === null ? '—' : number_format($v, 2, ',', '.') . '%';
$fmtMoney = static fn(mixed $v): string => $v === null || $v === '' || !is_numeric($v) ? '—' : number_format((float)$v, 4, ',', '.');
$fmtMoney2 = static fn(mixed $v): string => $v === null || $v === '' || !is_numeric($v) ? '—' : number_format((float)$v, 2, ',', '.');
$hasTariff = (float)($period['kwh_tariff'] ?? 0) > 0;
?>
<div class="card border-light shadow mb-4">
  <div class="card-body">
    <p class="small text-muted mb-3">
      <em>Lote adotado</em> (Pasta 4): F24 = lote padrão (mín.) do <strong>cadastro do item</strong> quando &gt; 1
      (0 ou 1 = placeholder SAP, trata como não fixado); senão F25 = produzido ÷ <strong>nº de lotes</strong>
      (entradas na aba Lotes produzidos, mesma coluna <em>Lotes</em>); F26 = <code>SE(F24=0;F25;F24)</code>.
      <strong>CVAR/un.</strong> usa divisor 1 na BOM (unidade comercial). <strong>CFIX</strong> usa HH/HM com o lote adotado.
    </p>
    <?php if (!$hasTariff): ?>
      <div class="alert alert-warning py-2 small mb-3">
        Tarifa kWh não informada no período — coluna <em>Energia/un.</em> ficará zerada.
        <a href="<?= $_ENV['URL_ADM'] ?>update-inventory-cost-period/<?= $periodId ?>">Editar período</a>
      </div>
    <?php endif; ?>
    <?php if ($skuResults === []): ?>
      <?php if ($skuFilter !== ''): ?>
        <p class="text-muted mb-0">Nenhum resultado para o filtro <strong><?= htmlspecialchars($skuFilter) ?></strong>.</p>
      <?php else: ?>
        <p class="text-muted mb-0">Nenhuma produção no período.</p>
      <?php endif; ?>
    <?php else: ?>
      <form method="get" action="<?= $_ENV['URL_ADM'] ?>view-inventory-cost-period/<?= $periodId ?>" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="tab" value="resultados">
        <div class="col-12 col-md-4">
          <label class="form-label small mb-1" for="sku_filter_resultados">Filtrar SKUs</label>
          <input type="text" class="form-control form-control-sm" id="sku_filter_resultados" name="sku_filter"
            value="<?= htmlspecialchars($skuFilter) ?>" placeholder="PA, descrição ou código ERP">
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
        </div>
        <div class="col-auto">
          <a href="<?= $_ENV['URL_ADM'] ?>view-inventory-cost-period/<?= $periodId ?>?tab=resultados" class="btn btn-sm btn-outline-secondary">Limpar</a>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-sm btn-outline-secondary" name="sku_filter" value="PA">Somente PA</button>
        </div>
        <div class="col-auto ms-md-auto">
          <a href="<?= $_ENV['URL_ADM'] ?>export-inventory-cost-period-sku-results/<?= $periodId ?><?= $skuFilter !== '' ? '?sku_filter=' . rawurlencode($skuFilter) : '' ?>"
            class="btn btn-sm btn-outline-success">
            <i class="fa-solid fa-file-csv me-1"></i> Exportar CSV
          </a>
        </div>
        <div class="col-12">
          <span class="small text-muted"><?= count($skuResults) ?><?= $skuFilter !== '' ? ' de ' . $skuResultsAllCount : '' ?> SKU(s) · ordenado por descrição</span>
        </div>
      </form>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-3" rowspan="2">SKU</th>
              <th rowspan="2">Descrição</th>
              <th class="text-end" rowspan="2">Qtd</th>
              <th class="text-end" rowspan="2" title="Lote padrão SAP (MinOrdrQty) — exibido só quando &gt; 1">Lote SAP</th>
              <th class="text-end" rowspan="2" title="Lote adotado para custo/un. (Pasta 4)">Lote adotado</th>
              <th class="text-end" rowspan="2">Efic. %</th>
              <th class="text-center border-start" colspan="5">CVAR</th>
              <th class="text-center border-start" colspan="2">CFIX</th>
              <th class="text-center border-start" colspan="2">Custo pleno</th>
              <th class="text-center border-start" colspan="2">Pasta 11</th>
            </tr>
            <tr>
              <th class="text-end border-start small" title="Matéria-prima por unidade comercial (lote adotado)">MP/un.</th>
              <th class="text-end small" title="Embalagem por unidade comercial">MAE/un.</th>
              <th class="text-end small" title="CVAR/un. = MP + MAE + energia — linha verde planilha">CVAR/un.</th>
              <th class="text-end small" title="Energia direta /un.">EE/un.</th>
              <th class="text-end border-start small" title="CVAR/un. × qtd produzida">CVAR período</th>
              <th class="text-end border-start small" title="CFIX rateado no período para o SKU">Total período</th>
              <th class="text-end small" title="CFIX total ÷ qtd produzida — linha 10 planilha">/un.</th>
              <th class="text-end border-start small" title="CVAR/un. + CFIX/un.">/un.</th>
              <th class="text-end small" title="Custo pleno/un. × qtd">Total período</th>
              <th class="text-end border-start small" title="Preço líquido (cadastro por período)">Preço líq.</th>
              <th class="text-end pe-3 small" title="(preço − custo) ÷ custo — convenção planilha linha 14">Markup %</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($skuResults as $row): ?>
              <tr class="<?= !empty($row['linked']) ? '' : 'table-warning' ?>">
                <td class="ps-3 font-monospace small"><?= htmlspecialchars((string)($row['erp_code'] ?? '—')) ?></td>
                <td class="small"><?= htmlspecialchars((string)($row['item_description'] ?? '')) ?></td>
                <td class="text-end"><?= $fmtQty(isset($row['total_qty']) ? (float)$row['total_qty'] : null) ?></td>
                <td class="text-end"><?= $fmtQty(InvCostBatchAdoptedHelper::catalogBatchForDisplay($row['standard_batch_size'] ?? null)) ?></td>
                <td class="text-end"><?= $fmtQty(isset($row['batch_size_adopted']) ? (float)$row['batch_size_adopted'] : null) ?></td>
                <td class="text-end"><?= $fmtPct(isset($row['efficiency_pct']) ? (float)$row['efficiency_pct'] : null) ?></td>
                <td class="text-end border-start"><?= $fmtMoney2($row['cvar_mp_unit'] ?? null) ?></td>
                <td class="text-end"><?= $fmtMoney2($row['cvar_mae_unit'] ?? null) ?></td>
                <td class="text-end fw-semibold text-success"><?= $fmtMoney2($row['cvar_sim_unit'] ?? null) ?></td>
                <td class="text-end"><?= $fmtMoney2($row['cvar_energy_unit'] ?? null) ?></td>
                <td class="text-end border-start fw-semibold text-success"><?= $fmtMoney2($row['cvar_period_total'] ?? $row['cvar_batch'] ?? null) ?></td>
                <td class="text-end border-start"><?= $fmtMoney2($row['cfix_total'] ?? null) ?></td>
                <td class="text-end"><?= $fmtMoney2($row['cfix_unit'] ?? null) ?></td>
                <td class="text-end border-start"><?= $fmtMoney2($row['full_cost_unit'] ?? null) ?></td>
                <td class="text-end fw-semibold"><?= $fmtMoney2($row['full_cost_period_total'] ?? $row['full_cost_batch'] ?? null) ?></td>
                <td class="text-end border-start"><?= $fmtMoney2($row['sale_price_net'] ?? null) ?></td>
                <td class="text-end pe-3"><?= $fmtPct(isset($row['markup_pct']) ? (float)$row['markup_pct'] : null) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
