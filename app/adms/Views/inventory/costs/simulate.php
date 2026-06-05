<?php if (!isset($this)) { exit; } ?>
<?php
use App\adms\Helpers\CSRFHelper;

$breakdown = $this->data['breakdown'] ?? null;
$scenario = $this->data['scenario'] ?? ['material_adjust_pct' => 0, 'operations_adjust_pct' => 0, 'global_adjust_pct' => 0];
$selectedItem = $this->data['selected_item'] ?? null;
$savedSimulations = $this->data['saved_simulations'] ?? [];
$itemId = (int)($this->data['selected_item_id'] ?? 0);
$batchSize = (float)($scenario['standard_batch_size'] ?? ($breakdown['standard_batch_size'] ?? 1));
$fmtMoney = static fn(float $v): string => number_format($v, 4, ',', '.');
$fmtPct = static fn(float $v): string => number_format($v, 2, ',', '.');
$fmtHours = static fn(float $v): string => number_format($v, 2, ',', '.');
$fmtBatch = static fn(float $v): string => number_format($v, 4, ',', '.');
$baseUrl = $_ENV['URL_ADM'] . 'simulate-inventory-cost/' . $itemId;
$pdfQuery = http_build_query([
    'material_adjust_pct' => (float)($scenario['material_adjust_pct'] ?? 0),
    'operations_adjust_pct' => (float)($scenario['operations_adjust_pct'] ?? 0),
    'global_adjust_pct' => (float)($scenario['global_adjust_pct'] ?? 0),
    'standard_batch_size' => $batchSize,
]);
$pdfLiveUrl = $_ENV['URL_ADM'] . 'export-inventory-cost-simulation-pdf/' . $itemId . '?' . $pdfQuery;
$canSave = in_array('SaveInventoryCostSimulation', $this->data['buttonPermission'] ?? [], true);
$canPdf = in_array('ExportInventoryCostSimulationPdf', $this->data['buttonPermission'] ?? [], true);

$lineBatch = static function (array $line, string $key, float $bs): float {
    return (float)($line[$key . '_batch'] ?? ((float)($line[$key] ?? 0) * $bs));
};

$renderCostBreakdown = static function (
    array $materialGroups,
    float $routeSapLaborCost,
    float $routeEquipmentCost,
    float $routeManualLaborCost,
    float $routeSapLaborBatch,
    float $routeEquipmentBatch,
    float $routeManualLaborBatch,
    float $laborHours
) use ($fmtMoney, $fmtHours, $batchSize): void {
    if ($materialGroups !== []) {
        foreach ($materialGroups as $group) {
            $label = htmlspecialchars((string)($group['group_name'] ?? 'Outros'));
            $unit = (float)($group['line_cost'] ?? 0);
            $batch = (float)($group['line_cost_batch'] ?? ($unit * $batchSize));
            echo $label . ': SKU R$ ' . $fmtMoney($unit) . ' · Lote R$ ' . $fmtMoney($batch) . '<br>';
        }
    } else {
        echo 'Materiais: R$ 0,0000<br>';
    }
    echo 'MO SAP: SKU R$ ' . $fmtMoney($routeSapLaborCost) . ' · Lote R$ ' . $fmtMoney($routeSapLaborBatch) . '<br>';
    echo 'Equipamentos: SKU R$ ' . $fmtMoney($routeEquipmentCost) . ' · Lote R$ ' . $fmtMoney($routeEquipmentBatch) . '<br>';
    echo 'MO cadastrada: SKU R$ ' . $fmtMoney($routeManualLaborCost) . ' · Lote R$ ' . $fmtMoney($routeManualLaborBatch);
    if ($laborHours > 0) {
        echo ' <span class="text-secondary">(' . $fmtHours($laborHours) . ' HH/lote)</span>';
    }
};
?>
<?php include __DIR__ . '/../partials/cost_tables_compact_style.php'; ?>
<div class="container-fluid px-4 pb-4">

  <div class="mb-1 hstack gap-2 flex-wrap">
    <h2 class="mt-3 mb-0">Simulação de Custos</h2>
    <ol class="breadcrumb mb-3 mt-3 ms-auto">
      <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard" class="text-decoration-none">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>list-inventory-items" class="text-decoration-none">Itens</a></li>
      <?php if ($itemId > 0): ?>
        <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>view-inventory-item/<?= $itemId ?>" class="text-decoration-none">Visualizar</a></li>
      <?php endif; ?>
      <li class="breadcrumb-item active">Simulação</li>
    </ol>
  </div>

  <?php include './app/adms/Views/partials/alerts.php'; ?>

  <div class="card mb-4 border-light shadow">
    <div class="card-header hstack gap-2 flex-wrap align-items-center py-3">
      <span class="fw-semibold">Cenários de ajuste</span>
      <span class="ms-auto d-flex flex-wrap gap-2">
        <?php if ($itemId > 0 && is_array($breakdown) && $canPdf): ?>
          <a class="btn btn-sm btn-danger" href="<?= htmlspecialchars($pdfLiveUrl) ?>" target="_blank" rel="noopener">
            <i class="fa-solid fa-file-pdf"></i> PDF (atual)
          </a>
        <?php endif; ?>
        <?php if ($itemId > 0): ?>
          <a class="btn btn-sm btn-outline-primary" href="<?= $_ENV['URL_ADM'] ?>view-inventory-item/<?= $itemId ?>">
            <i class="fa-regular fa-eye"></i> Voltar ao item
          </a>
        <?php endif; ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-items">
          <i class="fa-solid fa-list"></i> Listar itens
        </a>
      </span>
    </div>

    <div class="card-body p-4">
      <form method="get" id="form-simulate-cost" class="mb-0">
        <input type="hidden" name="url" value="simulate-inventory-cost/<?= $itemId ?>">

      <?php if (is_array($selectedItem)): ?>
        <div class="rounded border bg-light p-3 mb-4">
          <div class="row g-3 align-items-center">
            <div class="col-12 col-lg">
              <div class="fw-semibold fs-6">
                <?= htmlspecialchars(($selectedItem['code'] ?? '') . ' — ' . ($selectedItem['description'] ?? '')) ?>
              </div>
              <div class="small text-muted mt-2 d-flex flex-wrap gap-3">
                <?php if (!empty($selectedItem['erp_code'])): ?>
                  <span><span class="text-secondary">ERP:</span> <?= htmlspecialchars($selectedItem['erp_code']) ?></span>
                <?php endif; ?>
                <span><span class="text-secondary">Categoria:</span> <?= htmlspecialchars($selectedItem['category_name'] ?? '—') ?></span>
                <span><span class="text-secondary">Unidade (SKU):</span> <?= htmlspecialchars($selectedItem['unit_name'] ?? '—') ?></span>
              </div>
            </div>
            <div class="col-12 col-sm-auto col-lg-3">
              <label for="standard_batch_size" class="form-label mb-1 fw-semibold small">Tamanho do lote</label>
              <div class="input-group">
                <input type="text" class="form-control" name="standard_batch_size" id="standard_batch_size"
                  value="<?= $fmtBatch($batchSize) ?>" placeholder="1" required
                  title="Quantidade de unidades (SKU) produzidas por lote">
                <span class="input-group-text small">un.</span>
              </div>
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="row g-3 mb-4">
          <div class="col-12 col-sm-6 col-lg-3">
            <label for="standard_batch_size" class="form-label mb-1 fw-semibold">Tamanho do lote</label>
            <div class="input-group">
              <input type="text" class="form-control" name="standard_batch_size" id="standard_batch_size"
                value="<?= $fmtBatch($batchSize) ?>" placeholder="1" required>
              <span class="input-group-text small">un.</span>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <p class="text-muted mb-4">
        Compare o custo base com cenários de ajuste percentual. O tamanho do lote no cabeçalho define o rateio entre
        <strong>SKU (unidade)</strong> e <strong>lote</strong>. Tempos da rota e quantidades da BOM referem-se ao lote completo.
      </p>

      <div class="border rounded p-3 p-md-4 bg-white mb-3">
        <div class="row g-3 align-items-end">
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label mb-1">% Materiais</label>
            <input type="text" class="form-control" name="material_adjust_pct" value="<?= $fmtPct((float)($scenario['material_adjust_pct'] ?? 0)) ?>" placeholder="0">
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label mb-1">% Rota</label>
            <input type="text" class="form-control" name="operations_adjust_pct" value="<?= $fmtPct((float)($scenario['operations_adjust_pct'] ?? 0)) ?>" placeholder="0">
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label mb-1">% Global</label>
            <input type="text" class="form-control" name="global_adjust_pct" value="<?= $fmtPct((float)($scenario['global_adjust_pct'] ?? 0)) ?>" placeholder="0">
          </div>
          <div class="col-12 col-lg-3">
            <div class="d-flex flex-wrap gap-2">
              <button type="submit" class="btn btn-primary flex-grow-1 flex-sm-grow-0">
                <i class="fa-solid fa-calculator me-1"></i> Simular
              </button>
              <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($baseUrl) ?>">Limpar</a>
            </div>
          </div>
        </div>
      </div>
      </form>

      <?php if (is_array($breakdown) && $canSave): ?>
        <form method="post" action="<?= $_ENV['URL_ADM'] ?>save-inventory-cost-simulation/<?= $itemId ?>" class="border rounded p-3 bg-white">
          <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_save_inventory_cost_simulation') ?>">
          <input type="hidden" name="material_adjust_pct" value="<?= htmlspecialchars((string)($scenario['material_adjust_pct'] ?? 0)) ?>">
          <input type="hidden" name="operations_adjust_pct" value="<?= htmlspecialchars((string)($scenario['operations_adjust_pct'] ?? 0)) ?>">
          <input type="hidden" name="global_adjust_pct" value="<?= htmlspecialchars((string)($scenario['global_adjust_pct'] ?? 0)) ?>">
          <input type="hidden" name="standard_batch_size" value="<?= htmlspecialchars((string)$batchSize) ?>">
          <div class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
              <label class="form-label mb-1">Nome da simulação (ao salvar)</label>
              <input type="text" class="form-control form-control-sm" name="simulation_title" maxlength="150"
                placeholder="Ex.: Cenário +10% materiais — <?= date('d/m/Y') ?>">
            </div>
            <div class="col-12 col-md-auto">
              <button type="submit" class="btn btn-success btn-sm">
                <i class="fa-solid fa-floppy-disk me-1"></i> Salvar simulação
              </button>
            </div>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($savedSimulations !== []): ?>
    <div class="card mb-4 border-light shadow">
      <div class="card-header fw-semibold">Simulações salvas</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th class="ps-3">Título</th>
                <th class="text-end">SKU base</th>
                <th class="text-end">SKU sim.</th>
                <th class="text-end">Lote sim.</th>
                <th>Salvo em</th>
                <th class="text-end pe-3">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($savedSimulations as $sim): ?>
                <tr>
                  <td class="ps-3"><?= htmlspecialchars((string)($sim['title'] ?? '')) ?></td>
                  <td class="text-end text-nowrap">R$ <?= $fmtMoney((float)($sim['base_total_unit'] ?? 0)) ?></td>
                  <td class="text-end text-nowrap">R$ <?= $fmtMoney((float)($sim['simulated_total_unit'] ?? 0)) ?></td>
                  <td class="text-end text-nowrap">R$ <?= $fmtMoney((float)($sim['simulated_total_batch'] ?? 0)) ?></td>
                  <td class="text-nowrap small"><?= !empty($sim['created_at']) ? date('d/m/Y H:i', strtotime((string)$sim['created_at'])) : '—' ?></td>
                  <td class="text-end pe-3 text-nowrap">
                    <a class="btn btn-sm btn-outline-primary" href="<?= $_ENV['URL_ADM'] ?>simulate-inventory-cost/<?= $itemId ?>?<?= http_build_query([
                      'material_adjust_pct' => (float)($sim['material_adjust_pct'] ?? 0),
                      'operations_adjust_pct' => (float)($sim['operations_adjust_pct'] ?? 0),
                      'global_adjust_pct' => (float)($sim['global_adjust_pct'] ?? 0),
                      'standard_batch_size' => (float)($sim['standard_batch_size'] ?? 1),
                    ]) ?>">Abrir</a>
                    <?php if ($canPdf): ?>
                      <a class="btn btn-sm btn-outline-danger" href="<?= $_ENV['URL_ADM'] ?>export-inventory-cost-simulation-pdf/<?= (int)($sim['id'] ?? 0) ?>" target="_blank" rel="noopener">PDF</a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if (is_array($breakdown) && is_array($selectedItem)):
    $baseUnit = (float)($breakdown['base_total'] ?? 0);
    $baseBatch = (float)($breakdown['base_total_batch'] ?? 0);
    $simUnit = (float)($breakdown['simulated_total'] ?? 0);
    $simBatch = (float)($breakdown['simulated_total_batch'] ?? 0);
    $diffUnit = $simUnit - $baseUnit;
    $diffBatch = $simBatch - $baseBatch;
    $pctUnit = $baseUnit > 0 ? (($diffUnit / $baseUnit) * 100) : 0;
    $diffClass = $diffUnit >= 0 ? 'text-danger' : 'text-success';
  ?>
    <div class="row g-4 mb-4">
      <div class="col-12 col-md-4">
        <div class="card h-100 border-primary border-light shadow-sm">
          <div class="card-body p-4">
            <h6 class="text-muted text-uppercase small mb-2">Custo base</h6>
            <div class="small text-muted mb-1">Por SKU (unidade)</div>
            <div class="fs-3 fw-semibold mb-1">R$ <?= $fmtMoney($baseUnit) ?></div>
            <div class="small text-muted mb-1">Por lote (<?= $fmtMoney($batchSize) ?> un.)</div>
            <div class="fs-5 fw-semibold text-secondary mb-3">R$ <?= $fmtMoney($baseBatch) ?></div>
            <small class="text-muted d-block">
              <?php $renderCostBreakdown(
                  $breakdown['material_groups'] ?? [],
                  (float)($breakdown['route_sap_labor_cost'] ?? 0),
                  (float)($breakdown['route_equipment_cost'] ?? 0),
                  (float)($breakdown['route_manual_labor_cost'] ?? 0),
                  (float)($breakdown['route_sap_labor_cost_batch'] ?? 0),
                  (float)($breakdown['route_equipment_cost_batch'] ?? 0),
                  (float)($breakdown['route_manual_labor_cost_batch'] ?? 0),
                  (float)($breakdown['labor_hours'] ?? 0)
              ); ?>
            </small>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="card h-100 border-success border-light shadow-sm">
          <div class="card-body p-4">
            <h6 class="text-muted text-uppercase small mb-2">Custo simulado</h6>
            <div class="small text-muted mb-1">Por SKU (unidade)</div>
            <div class="fs-3 fw-semibold text-success mb-1">R$ <?= $fmtMoney($simUnit) ?></div>
            <div class="small text-muted mb-1">Por lote</div>
            <div class="fs-5 fw-semibold text-success mb-3">R$ <?= $fmtMoney($simBatch) ?></div>
            <small class="text-muted d-block">
              <?php $renderCostBreakdown(
                  $breakdown['simulated_material_groups'] ?? [],
                  (float)($breakdown['simulated_route_sap_labor_cost'] ?? 0),
                  (float)($breakdown['simulated_route_equipment_cost'] ?? 0),
                  (float)($breakdown['simulated_route_manual_labor_cost'] ?? 0),
                  (float)($breakdown['simulated_route_sap_labor_cost_batch'] ?? 0),
                  (float)($breakdown['simulated_route_equipment_cost_batch'] ?? 0),
                  (float)($breakdown['simulated_route_manual_labor_cost_batch'] ?? 0),
                  (float)($breakdown['labor_hours'] ?? 0)
              ); ?>
            </small>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="card h-100 border-light shadow-sm">
          <div class="card-body p-4">
            <h6 class="text-muted text-uppercase small mb-2">Variação</h6>
            <div class="small text-muted mb-1">SKU</div>
            <div class="fs-3 fw-semibold <?= $diffClass ?> mb-1">R$ <?= $fmtMoney($diffUnit) ?></div>
            <div class="small text-muted mb-1">Lote</div>
            <div class="fs-5 fw-semibold <?= $diffClass ?> mb-2">R$ <?= $fmtMoney($diffBatch) ?></div>
            <small class="<?= $diffClass ?>"><?= $fmtPct($pctUnit) ?>% (SKU) em relação ao base</small>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 inv-cost-compact-tables">
      <div class="col-12 col-xl-6">
        <div class="card border-light shadow h-100">
          <div class="card-header fw-semibold">Lista de materiais</div>
          <div class="card-body p-0">
            <div class="table-wrap">
              <table class="table table-striped align-middle">
                <thead class="thead-green">
                  <tr>
                    <th class="ps-1 col-w-component">Componente</th>
                    <th>Grupo</th>
                    <th class="text-end col-w-qty">Qtd/lote</th>
                    <th class="text-end col-w-cost">C. comp.</th>
                    <th class="text-end">C. SKU</th>
                    <th class="text-end pe-1 col-w-line">C. lote</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($breakdown['materials'])): ?>
                    <tr><td colspan="6" class="text-center text-muted py-2">Sem componentes na BOM.</td></tr>
                  <?php else: ?>
                    <?php foreach ($breakdown['materials'] as $line):
                      $compCode = trim((string)($line['component_code'] ?? ''));
                      $compDesc = trim((string)($line['component_description'] ?? ''));
                      $compFull = trim($compCode . ' ' . $compDesc);
                    ?>
                      <tr>
                        <td class="ps-1" title="<?= htmlspecialchars($compFull) ?>">
                          <span class="cell-component-name"><?= htmlspecialchars($compDesc !== '' ? $compDesc : $compCode) ?></span>
                          <?php if ($compCode !== '' && $compDesc !== ''): ?>
                            <span class="cell-desc-sub"><?= htmlspecialchars($compCode) ?></span>
                          <?php endif; ?>
                        </td>
                        <td class="text-nowrap small text-muted"><?= htmlspecialchars((string)($line['group_name'] ?? 'Outros')) ?></td>
                        <td class="text-end text-nowrap col-num"><?= $fmtMoney((float)($line['effective_qty'] ?? 0)) ?></td>
                        <td class="text-end text-nowrap col-num"><?= $fmtMoney((float)($line['unit_cost'] ?? 0)) ?></td>
                        <td class="text-end text-nowrap col-num"><?= $fmtMoney((float)($line['line_cost'] ?? 0)) ?></td>
                        <td class="text-end pe-1 text-nowrap col-num fw-semibold"><?= $fmtMoney($lineBatch($line, 'line_cost', $batchSize)) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-xl-6">
        <div class="card border-light shadow h-100">
          <div class="card-header fw-semibold">Rota de produção</div>
          <div class="card-body p-0">
            <div class="table-wrap">
              <table class="table table-striped align-middle">
                <thead class="thead-green">
                  <tr>
                    <th class="ps-1 col-w-component">Operação</th>
                    <th class="text-end col-w-min">Min</th>
                    <th class="text-end">HH</th>
                    <th class="text-end">MO SAP<br><span class="small fw-normal">SKU</span></th>
                    <th class="text-end">Equip.<br><span class="small fw-normal">SKU</span></th>
                    <th class="text-end">MO cad.<br><span class="small fw-normal">SKU</span></th>
                    <th class="text-end">C. SKU</th>
                    <th class="text-end pe-1 col-w-line">C. lote</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($breakdown['operations'])): ?>
                    <tr><td colspan="8" class="text-center text-muted py-2">Sem operações na rota.</td></tr>
                  <?php else: ?>
                    <?php foreach ($breakdown['operations'] as $line):
                      $opCode = trim((string)($line['operation_code'] ?? ''));
                      $opName = trim((string)($line['operation_name'] ?? ''));
                      $notes = trim((string)($line['notes'] ?? ''));
                      $opTitle = $opName !== '' ? $opName : $opCode;
                      $resource = '';
                      if (preg_match('/Recurso SAP:\s*(.+)$/i', $notes, $m)) {
                          $resource = trim($m[1]);
                      }
                      $opFull = trim($opTitle . ($resource !== '' ? ' — ' . $resource : ''));
                    ?>
                      <tr>
                        <td class="ps-1" title="<?= htmlspecialchars($opFull) ?>">
                          <span class="cell-op-title"><?= htmlspecialchars($opTitle) ?></span>
                          <?php if ($resource !== ''): ?>
                            <span class="cell-desc-sub"><?= htmlspecialchars($resource) ?></span>
                          <?php elseif ($notes !== ''): ?>
                            <span class="cell-desc-sub"><?= htmlspecialchars($notes) ?></span>
                          <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap col-num"><?= $fmtMoney((float)($line['time_minutes'] ?? 0)) ?></td>
                        <td class="text-end text-nowrap col-num"><?= $fmtHours((float)($line['labor_hours'] ?? 0)) ?></td>
                        <td class="text-end text-nowrap col-num"><?= $fmtMoney((float)($line['sap_labor_line_cost'] ?? 0)) ?></td>
                        <td class="text-end text-nowrap col-num"><?= $fmtMoney((float)($line['equipment_line_cost'] ?? 0)) ?></td>
                        <td class="text-end text-nowrap col-num"><?= $fmtMoney((float)($line['manual_labor_line_cost'] ?? 0)) ?></td>
                        <td class="text-end text-nowrap col-num"><?= $fmtMoney((float)($line['line_cost'] ?? 0)) ?></td>
                        <td class="text-end pe-1 text-nowrap col-num fw-semibold"><?= $fmtMoney($lineBatch($line, 'line_cost', $batchSize)) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

</div>
