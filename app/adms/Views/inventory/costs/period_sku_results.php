<?php if (!isset($this)) { exit; } ?>
<?php
$periodId = (int)($periodId ?? 0);
$skuResults = $skuResults ?? [];
$skuFilter = trim((string)($skuFilter ?? ''));
$skuResultsAllCount = (int)($skuResultsAllCount ?? count($skuResults));
$period = $period ?? [];
$fmtQty = static fn(?float $v): string => $v === null ? '—' : number_format($v, 0, ',', '.');
$fmtPct = static fn(?float $v): string => $v === null ? '—' : number_format($v, 2, ',', '.') . '%';
$fmtMoney = static fn(mixed $v): string => $v === null || $v === '' || !is_numeric($v) ? '—' : number_format((float)$v, 4, ',', '.');
$hasTariff = (float)($period['kwh_tariff'] ?? 0) > 0;
?>
<div class="card border-light shadow mb-4">
  <div class="card-body">
    <p class="small text-muted mb-3">
      Consolidação por SKU: produção, eficiência, <strong>CVAR simulado</strong> (cadastro BOM/rota + eficiência MP),
      <strong>CFIX rateado</strong> no período, <strong>energia</strong> (HM×kW×tarifa) e <strong>custo pleno/SKU</strong>.
      SKUs sem vínculo no cadastro exibem apenas produção.
    </p>
    <?php if (!$hasTariff): ?>
      <div class="alert alert-warning py-2 small mb-3">
        Tarifa kWh não informada no período — coluna <em>CVAR energia</em> ficará zerada.
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
              <th class="ps-3">SKU</th>
              <th>Descrição</th>
              <th class="text-end">Qtd</th>
              <th class="text-end">Lotes</th>
              <th class="text-end">Efic. %</th>
              <th class="text-end">CVAR sim./SKU</th>
              <th class="text-end">Energia/SKU</th>
              <th class="text-end">CFIX período</th>
              <th class="text-end">CFIX/SKU</th>
              <th class="text-end pe-3">Custo pleno/SKU</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($skuResults as $row): ?>
              <tr class="<?= !empty($row['linked']) ? '' : 'table-warning' ?>">
                <td class="ps-3 font-monospace small"><?= htmlspecialchars((string)($row['erp_code'] ?? '—')) ?></td>
                <td class="small"><?= htmlspecialchars((string)($row['item_description'] ?? '')) ?></td>
                <td class="text-end"><?= $fmtQty(isset($row['total_qty']) ? (float)$row['total_qty'] : null) ?></td>
                <td class="text-end"><?= (int)($row['batches_count'] ?? 0) ?></td>
                <td class="text-end"><?= $fmtPct(isset($row['efficiency_pct']) ? (float)$row['efficiency_pct'] : null) ?></td>
                <td class="text-end"><?= $fmtMoney($row['cvar_sim_unit'] ?? null) ?></td>
                <td class="text-end"><?= $fmtMoney($row['cvar_energy_unit'] ?? null) ?></td>
                <td class="text-end"><?= $fmtMoney($row['cfix_total'] ?? null) ?></td>
                <td class="text-end"><?= $fmtMoney($row['cfix_unit'] ?? null) ?></td>
                <td class="text-end pe-3 fw-semibold"><?= $fmtMoney($row['full_cost_unit'] ?? null) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
