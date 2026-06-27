<?php
/**
 * Resumo do resultado da simulação (cards no topo do formulário).
 *
 * @var array<string, mixed> $breakdown
 * @var array<string, mixed>|null $selectedItem
 * @var float $batchSize
 */
if (!isset($this)) {
    exit;
}
if (!is_array($breakdown) || !is_array($selectedItem)) {
    return;
}
$baseUnit = (float)($breakdown['base_total'] ?? 0);
$baseBatch = (float)($breakdown['base_total_batch'] ?? 0);
$simUnit = (float)($breakdown['simulated_total'] ?? 0);
$simBatch = (float)($breakdown['simulated_total_batch'] ?? 0);
$diffUnit = $simUnit - $baseUnit;
$diffBatch = $simBatch - $baseBatch;
$pctUnit = $baseUnit > 0 ? (($diffUnit / $baseUnit) * 100) : 0;
$diffClass = $diffUnit >= 0 ? 'text-danger' : 'text-success';

$cvarMpUnit = (float)($breakdown['cvar_mp_cost'] ?? 0);
$cvarMaeUnit = (float)($breakdown['cvar_mae_cost'] ?? 0);
$cvarMaterialsUnit = $cvarMpUnit + $cvarMaeUnit;
$simCvarMp = (float)($breakdown['simulated_cvar_mp_cost'] ?? 0);
$simCvarMae = (float)($breakdown['simulated_cvar_mae_cost'] ?? 0);
$simCvarMaterials = $simCvarMp + $simCvarMae;

$laborHours = (float)($breakdown['labor_hours'] ?? 0);
$machineHours = (float)($breakdown['machine_hours'] ?? 0);
$routeOpsUnit = (float)($breakdown['operations_cost'] ?? 0);
$simRouteOpsUnit = (float)($breakdown['simulated_operations_cost'] ?? 0);

$currentItemProduction = $currentItemProduction ?? null;
$currentItemPeriodDrivers = $currentItemPeriodDrivers ?? null;
$cfixAllocation = $cfixAllocation ?? null;
$suggestedPrice = $suggestedPrice ?? null;
$productionEfficiency = $productionEfficiency ?? ($this->data['production_efficiency'] ?? null);
$batchesInPeriod = is_array($currentItemProduction) ? (int)($currentItemProduction['batches_count'] ?? 0) : 0;
$qtyInPeriod = is_array($currentItemProduction) ? (float)($currentItemProduction['qty_produced'] ?? 0) : 0.0;
$hhPeriodSim = $batchesInPeriod > 0 ? round($laborHours * $batchesInPeriod, 4) : null;
$hmPeriodSim = $batchesInPeriod > 0 ? round($machineHours * $batchesInPeriod, 4) : null;
$cfixPeriodTotal = is_array($cfixAllocation) ? (float)($cfixAllocation['cfix_total'] ?? 0) : 0.0;
$cfixPerSku = ($cfixPeriodTotal > 0 && $qtyInPeriod > 0) ? round($cfixPeriodTotal / $qtyInPeriod, 6) : 0.0;
$fullCostSim = $simUnit + $cfixPerSku;

$fmtMoney = static fn(float $v): string => number_format($v, 4, ',', '.');
$fmtPct = static fn(float $v): string => number_format($v, 2, ',', '.');
$fmtHours = static fn(float $v): string => number_format($v, 4, ',', '.');
$renderCostBreakdown = $renderCostBreakdown ?? null;
?>
<div class="mb-4 sim-breakdown-results">
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <span class="fw-semibold fs-6">Resultado da simulação</span>
    <span class="badge bg-light text-dark border">lote <?= $fmtMoney($batchSize) ?> un.</span>
    <?php if (!empty($breakdown['production_efficiency_pct'])): ?>
      <span class="badge bg-light text-dark border" title="Eficiência agregada no período (lotes SAP)">
        Efic. <?= number_format((float)$breakdown['production_efficiency_pct'], 2, ',', '.') ?>%
      </span>
    <?php endif; ?>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
      <div class="card border-0 bg-primary bg-opacity-10 h-100">
        <div class="card-body py-3 px-3">
          <div class="text-muted text-uppercase small mb-1">CVAR materiais</div>
          <div class="fs-5 fw-semibold text-primary mb-0">R$ <?= $fmtMoney($cvarMaterialsUnit) ?></div>
          <div class="small text-muted mt-1">Simulado: R$ <?= $fmtMoney($simCvarMaterials) ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card border-0 bg-secondary bg-opacity-10 h-100">
        <div class="card-body py-3 px-3">
          <div class="text-muted text-uppercase small mb-1">Rota (SKU)</div>
          <div class="fs-5 fw-semibold mb-0">R$ <?= $fmtMoney($routeOpsUnit) ?></div>
          <div class="small text-muted mt-1">Simulado: R$ <?= $fmtMoney($simRouteOpsUnit) ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card border-0 bg-info bg-opacity-10 h-100">
        <div class="card-body py-3 px-3">
          <div class="text-muted text-uppercase small mb-1">HH (lote)</div>
          <div class="fs-5 fw-semibold text-info-emphasis mb-0"><?= $fmtHours($laborHours) ?> h</div>
          <?php if ($hhPeriodSim !== null): ?>
            <div class="small text-muted mt-1">Período: <strong><?= $fmtHours($hhPeriodSim) ?> h</strong> (<?= $batchesInPeriod ?> lote(s))</div>
            <?php if (is_array($currentItemPeriodDrivers)): ?>
              <div class="small text-muted">Rateio 2 (cad.): <?= $fmtPct((float)($currentItemPeriodDrivers['share_criterion_2'] ?? 0)) ?>%</div>
            <?php endif; ?>
          <?php else: ?>
            <div class="small text-muted mt-1">tempo × qtd MO (ou operadores)</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card border-0 bg-warning bg-opacity-10 h-100">
        <div class="card-body py-3 px-3">
          <div class="text-muted text-uppercase small mb-1">HM (lote)</div>
          <div class="fs-5 fw-semibold mb-0"><?= $fmtHours($machineHours) ?> h</div>
          <?php if ($hmPeriodSim !== null): ?>
            <div class="small text-muted mt-1">Período: <strong><?= $fmtHours($hmPeriodSim) ?> h</strong> (<?= $batchesInPeriod ?> lote(s))</div>
            <?php if (is_array($currentItemPeriodDrivers)): ?>
              <div class="small text-muted">Rateio 3 (cad.): <?= $fmtPct((float)($currentItemPeriodDrivers['share_criterion_3'] ?? 0)) ?>%</div>
            <?php endif; ?>
          <?php else: ?>
            <div class="small text-muted mt-1">etapas com equipamento/recurso</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <?php if ($cfixPeriodTotal > 0 || $suggestedPrice !== null): ?>
  <div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
      <div class="card border-0 bg-dark bg-opacity-10 h-100">
        <div class="card-body py-3 px-3">
          <div class="text-muted text-uppercase small mb-1">CFIX (período)</div>
          <div class="fs-5 fw-semibold mb-0">R$ <?= $fmtMoney($cfixPeriodTotal) ?></div>
          <?php if ($cfixPerSku > 0): ?>
            <div class="small text-muted mt-1">≈ R$ <?= $fmtMoney($cfixPerSku) ?> / SKU (÷ <?= number_format($qtyInPeriod, 2, ',', '.') ?> un.)</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card border-0 bg-secondary bg-opacity-10 h-100">
        <div class="card-body py-3 px-3">
          <div class="text-muted text-uppercase small mb-1">Custo pleno (sim. + CFIX/SKU)</div>
          <div class="fs-5 fw-semibold mb-0">R$ <?= $fmtMoney($fullCostSim) ?></div>
          <div class="small text-muted mt-1">CVAR sim. R$ <?= $fmtMoney($simUnit) ?> + CFIX rateado</div>
        </div>
      </div>
    </div>
    <?php if ($suggestedPrice !== null): ?>
    <div class="col-12 col-md-4">
      <div class="card border-0 bg-success bg-opacity-10 h-100">
        <div class="card-body py-3 px-3">
          <div class="text-muted text-uppercase small mb-1">Preço sugerido</div>
          <div class="fs-5 fw-semibold text-success mb-0">R$ <?= $fmtMoney((float)$suggestedPrice) ?></div>
          <div class="small text-muted mt-1">Margem alvo cadastrada no período/SKU</div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="row g-3 mb-3">
    <div class="col-12 col-lg-4">
      <div class="card h-100 border shadow-sm">
        <div class="card-header bg-white py-2 border-bottom">
          <span class="text-muted text-uppercase small fw-semibold">Custo base</span>
        </div>
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-baseline mb-2">
            <span class="small text-muted">SKU</span>
            <span class="fs-4 fw-semibold">R$ <?= $fmtMoney($baseUnit) ?></span>
          </div>
          <div class="d-flex justify-content-between align-items-baseline mb-3 pb-3 border-bottom">
            <span class="small text-muted">Lote</span>
            <span class="fs-5 fw-semibold text-secondary">R$ <?= $fmtMoney($baseBatch) ?></span>
          </div>
          <?php if (is_callable($renderCostBreakdown)): ?>
            <div class="small text-muted lh-sm">
              <?php $renderCostBreakdown(
                  $breakdown['material_groups'] ?? [],
                  (float)($breakdown['route_sap_labor_cost'] ?? 0),
                  (float)($breakdown['route_equipment_cost'] ?? 0),
                  (float)($breakdown['route_manual_labor_cost'] ?? 0),
                  (float)($breakdown['route_sap_labor_cost_batch'] ?? 0),
                  (float)($breakdown['route_equipment_cost_batch'] ?? 0),
                  (float)($breakdown['route_manual_labor_cost_batch'] ?? 0),
                  $laborHours
              ); ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-4">
      <div class="card h-100 border border-success border-opacity-25 shadow-sm">
        <div class="card-header bg-success bg-opacity-10 py-2 border-bottom border-success border-opacity-25">
          <span class="text-success text-uppercase small fw-semibold">Custo simulado</span>
        </div>
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-baseline mb-2">
            <span class="small text-muted">SKU</span>
            <span class="fs-4 fw-semibold text-success">R$ <?= $fmtMoney($simUnit) ?></span>
          </div>
          <div class="d-flex justify-content-between align-items-baseline mb-3 pb-3 border-bottom">
            <span class="small text-muted">Lote</span>
            <span class="fs-5 fw-semibold text-success">R$ <?= $fmtMoney($simBatch) ?></span>
          </div>
          <?php if (is_callable($renderCostBreakdown)): ?>
            <div class="small text-muted lh-sm">
              <?php $renderCostBreakdown(
                  $breakdown['simulated_material_groups'] ?? [],
                  (float)($breakdown['simulated_route_sap_labor_cost'] ?? 0),
                  (float)($breakdown['simulated_route_equipment_cost'] ?? 0),
                  (float)($breakdown['simulated_route_manual_labor_cost'] ?? 0),
                  (float)($breakdown['simulated_route_sap_labor_cost_batch'] ?? 0),
                  (float)($breakdown['simulated_route_equipment_cost_batch'] ?? 0),
                  (float)($breakdown['simulated_route_manual_labor_cost_batch'] ?? 0),
                  $laborHours
              ); ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-4">
      <div class="card h-100 border shadow-sm">
        <div class="card-header bg-white py-2 border-bottom">
          <span class="text-muted text-uppercase small fw-semibold">Variação</span>
        </div>
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-baseline mb-2">
            <span class="small text-muted">SKU</span>
            <span class="fs-4 fw-semibold <?= $diffClass ?>">R$ <?= $fmtMoney($diffUnit) ?></span>
          </div>
          <div class="d-flex justify-content-between align-items-baseline mb-2">
            <span class="small text-muted">Lote</span>
            <span class="fs-5 fw-semibold <?= $diffClass ?>">R$ <?= $fmtMoney($diffBatch) ?></span>
          </div>
          <div class="pt-2 border-top">
            <span class="badge <?= $diffUnit >= 0 ? 'bg-danger' : 'bg-success' ?> bg-opacity-10 text-<?= $diffUnit >= 0 ? 'danger' : 'success' ?> border border-<?= $diffUnit >= 0 ? 'danger' : 'success' ?> border-opacity-25">
              <?= $fmtPct($pctUnit) ?>% sobre o base (SKU)
            </span>
          </div>
          <?php if ($laborHours <= 0 && $machineHours <= 0): ?>
            <p class="small text-muted mt-3 mb-0">
              HH/HM zerados: confira <strong>tempo / lote</strong> na rota e MO ou equipamentos no cadastro do item.
            </p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <details class="border rounded bg-light small">
    <summary class="px-3 py-2 fw-semibold user-select-none" style="cursor:pointer">Como são calculados HH, HM e o que ainda falta</summary>
    <div class="px-3 pb-3 text-muted border-top">
      <p class="mb-2 mt-2"><strong>HH (lote)</strong> = Σ (tempo da operação em h × qtd MO cadastrada; se não houver linhas de MO, usa <em>qtd. operadores</em> da operação).</p>
      <p class="mb-2"><strong>HH (período)</strong> = HH do lote simulado × quantidade de lotes produzidos no período (filtro por <em>data prod.</em> em Lotes produzidos).</p>
      <p class="mb-2"><strong>HM (lote)</strong> = Σ tempo em h das etapas que têm equipamento/recurso (máq., energia ou linhas de recurso).</p>
      <p class="mb-2"><strong>HM (período)</strong> = HM do lote × lotes produzidos no período. <strong>Rateio 2/3</strong> usa a rota cadastrada de todos os SKUs do período.</p>
      <p class="mb-2"><strong>CVAR materiais</strong> no card = MP + MAE (detalhe por grupo nos cards abaixo).</p>
      <?php if (!empty($breakdown['production_efficiency_pct'])): ?>
      <p class="mb-2"><strong>Eficiência (período)</strong> = Σ qty produzida ÷ (N lotes × lote mínimo).
        Valor: <strong><?= number_format((float)$breakdown['production_efficiency_pct'], 2, ',', '.') ?>%</strong>
        — CVAR MP simulado ÷ eficiência (MAE não é ajustada).</p>
      <?php elseif (is_array($productionEfficiency) && !empty($productionEfficiency['efficiency_pct'])): ?>
      <p class="mb-2"><strong>Eficiência (período):</strong> <?= number_format((float)$productionEfficiency['efficiency_pct'], 2, ',', '.') ?>%
        (<?= number_format((float)($productionEfficiency['qty_produced'] ?? 0), 0, ',', '.') ?> ÷
        <?= number_format((float)($productionEfficiency['qty_theoretical'] ?? 0), 0, ',', '.') ?> un.)</p>
      <?php endif; ?>
      <p class="mb-1"><strong>CFIX:</strong> importe o DRE no período, vincule cada conta a um critério (1–8) e o sistema rateia sobre os drivers do período. O valor/SKU divide o CFIX do item pela qty produzida.</p>
      <p class="mb-1"><strong>Ainda em evolução:</strong> redistribuições Pasta 9, HVAC anual, critérios 4–8 sem cadastro de complexidade no SKU/período.</p>
    </div>
  </details>
</div>
