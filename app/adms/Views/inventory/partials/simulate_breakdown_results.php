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

$fmtMoney = static fn(float $v): string => number_format($v, 4, ',', '.');
$fmtPct = static fn(float $v): string => number_format($v, 2, ',', '.');
$fmtHours = static fn(float $v): string => number_format($v, 4, ',', '.');
$renderCostBreakdown = $renderCostBreakdown ?? null;
?>
<div class="mb-4 sim-breakdown-results">
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <span class="fw-semibold fs-6">Resultado da simulação</span>
    <span class="badge bg-light text-dark border">lote <?= $fmtMoney($batchSize) ?> un.</span>
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
          <div class="small text-muted mt-1">tempo × qtd MO (ou operadores)</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card border-0 bg-warning bg-opacity-10 h-100">
        <div class="card-body py-3 px-3">
          <div class="text-muted text-uppercase small mb-1">HM (lote)</div>
          <div class="fs-5 fw-semibold mb-0"><?= $fmtHours($machineHours) ?> h</div>
          <div class="small text-muted mt-1">etapas com equipamento/recurso</div>
        </div>
      </div>
    </div>
  </div>

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
      <p class="mb-2 mt-2"><strong>HH</strong> = Σ (tempo da operação em h × qtd MO cadastrada; se não houver linhas de MO, usa <em>qtd. operadores</em> da operação).</p>
      <p class="mb-2"><strong>HM</strong> = Σ tempo em h das etapas que têm equipamento/recurso (máq., energia ou linhas de recurso).</p>
      <p class="mb-2"><strong>CVAR materiais</strong> no card = MP + MAE (detalhe por grupo nos cards abaixo).</p>
      <p class="mb-1"><strong>Ainda fora do total:</strong> CFIX e rateios 1–8 (folha adm., energia HVAC, área comum) — Fase 2 do custeio fabril.</p>
    </div>
  </details>
</div>
