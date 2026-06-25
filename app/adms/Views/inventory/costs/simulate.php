<?php if (!isset($this)) { exit; } ?>
<?php
use App\adms\Helpers\CSRFHelper;

$breakdown = $this->data['breakdown'] ?? null;
$scenario = $this->data['scenario'] ?? ['material_adjust_pct' => 0, 'operations_adjust_pct' => 0, 'global_adjust_pct' => 0];
$selectedItem = $this->data['selected_item'] ?? null;
$savedSimulations = $this->data['saved_simulations'] ?? [];
$costPeriods = $this->data['cost_periods'] ?? [];
$productionWarehouses = $this->data['production_warehouses'] ?? [];
$selectedPeriodId = (int)($this->data['selected_period_id'] ?? 0);
$warehouseScope = (string)($this->data['warehouse_scope'] ?? 'all');
$selectedWarehouseCodes = $this->data['selected_warehouse_codes'] ?? [];
$productionAggregation = $this->data['production_aggregation'] ?? null;
$currentItemProduction = $this->data['current_item_production'] ?? null;
$isProjectItem = !empty($this->data['is_project_item']);
$itemId = (int)($this->data['selected_item_id'] ?? 0);
$batchSize = (float)($scenario['standard_batch_size'] ?? ($breakdown['standard_batch_size'] ?? 1));
$fmtMoney = static fn(float $v): string => number_format($v, 4, ',', '.');
$fmtPct = static fn(float $v): string => number_format($v, 2, ',', '.');
$fmtHours = static fn(float $v): string => number_format($v, 2, ',', '.');
$fmtBatch = static fn(float $v): string => number_format($v, 4, ',', '.');
$baseUrl = $_ENV['URL_ADM'] . 'simulate-inventory-cost/' . $itemId;
$pdfQueryParams = [
    'material_adjust_pct' => (float)($scenario['material_adjust_pct'] ?? 0),
    'operations_adjust_pct' => (float)($scenario['operations_adjust_pct'] ?? 0),
    'global_adjust_pct' => (float)($scenario['global_adjust_pct'] ?? 0),
    'standard_batch_size' => $batchSize,
];
if ($selectedPeriodId > 0) {
    $pdfQueryParams['inv_cost_period_id'] = $selectedPeriodId;
    $pdfQueryParams['warehouse_scope'] = $warehouseScope;
    if ($warehouseScope === 'selected' && $selectedWarehouseCodes !== []) {
        $pdfQueryParams['warehouse_codes'] = $selectedWarehouseCodes;
    }
}
$pdfQuery = http_build_query($pdfQueryParams);
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
    <?php if ($isProjectItem): ?>
      <span class="badge bg-warning text-dark mt-3">PA - PROJETO · what-if</span>
    <?php endif; ?>
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
        <a class="btn btn-sm btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-cost-production-batches">
          <i class="fa-solid fa-boxes-stacked"></i> Lotes
        </a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-cost-periods">
          <i class="fa-regular fa-calendar"></i> Períodos
        </a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-items">
          <i class="fa-solid fa-list"></i> Listar itens
        </a>
      </span>
    </div>

    <div class="card-body p-4">
      <div id="form-alerts" class="mb-2"></div>
      <form method="post" id="form-simulate-cost" action="<?= htmlspecialchars($baseUrl) ?>" class="mb-0">

      <?php if (is_array($selectedItem)): ?>
        <div class="rounded border bg-light p-3 mb-4">
          <div class="row g-3 align-items-center">
            <div class="col-12 col-lg">
              <div class="fw-semibold fs-6">
                <?= htmlspecialchars(($selectedItem['code'] ?? '') . ' — ' . ($selectedItem['description'] ?? '')) ?>
                <?php if ($isProjectItem): ?>
                  <span class="badge bg-warning text-dark ms-1">PA - PROJETO</span>
                <?php endif; ?>
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
        <?php if ($isProjectItem): ?>
          <span class="d-block mt-1"><strong>Projeto:</strong> simulação independente do custeio oficial fechado; linhas manuais na BOM são consideradas no CVAR.</span>
        <?php endif; ?>
      </p>

      <?php
      $edit_bom = $this->data['edit_bom'] ?? [];
      $edit_operations = $this->data['edit_operations'] ?? [];
      $listBomItems = $this->data['listBomItems'] ?? [];
      $listUnits = $this->data['listUnits'] ?? [];
      $listOperations = $this->data['listOperations'] ?? [];
      $structure_customized = !empty($this->data['structure_customized']);
      include __DIR__ . '/../partials/simulate_structure_edit.php';
      ?>

      <div class="border rounded p-3 p-md-4 bg-light mb-3">
        <div class="fw-semibold mb-3">Produção no período (critério rateio 1)</div>
        <div class="row g-3">
          <div class="col-12 col-lg-4">
            <label class="form-label mb-1" for="inv_cost_period_id">Período de custeio</label>
            <select class="form-select form-select-sm" name="inv_cost_period_id" id="inv_cost_period_id">
              <option value="">— Sem período —</option>
              <?php foreach ($costPeriods as $period): ?>
                <?php
                  $pid = (int)($period['id'] ?? 0);
                  $label = ($period['name'] ?? '') . ' (' . date('d/m/Y', strtotime((string)$period['date_from'])) . ' – ' . date('d/m/Y', strtotime((string)$period['date_to'])) . ')';
                ?>
                <option value="<?= $pid ?>" <?= $selectedPeriodId === $pid ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-lg-8">
            <label class="form-label mb-1">Depósitos</label>
            <div class="d-flex flex-wrap gap-3 align-items-center">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="warehouse_scope" id="warehouse_scope_all" value="all"
                  <?= $warehouseScope !== 'selected' ? 'checked' : '' ?>>
                <label class="form-check-label" for="warehouse_scope_all">Todos (TJQP + APQP)</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="warehouse_scope" id="warehouse_scope_selected" value="selected"
                  <?= $warehouseScope === 'selected' ? 'checked' : '' ?>>
                <label class="form-check-label" for="warehouse_scope_selected">Selecionar:</label>
              </div>
              <?php foreach ($productionWarehouses as $wh): ?>
                <?php $whCode = (string)($wh['code'] ?? ''); ?>
                <div class="form-check">
                  <input class="form-check-input warehouse-code-check" type="checkbox" name="warehouse_codes[]" value="<?= htmlspecialchars($whCode) ?>"
                    id="wh_<?= htmlspecialchars($whCode) ?>"
                    <?= in_array($whCode, $selectedWarehouseCodes, true) ? 'checked' : '' ?>>
                  <label class="form-check-label" for="wh_<?= htmlspecialchars($whCode) ?>"><?= htmlspecialchars($whCode) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php if (is_array($productionAggregation) && is_array($productionAggregation['period'] ?? null)): ?>
          <?php
            $periodRow = $productionAggregation['period'];
            $whLabel = ($productionAggregation['warehouse_codes'] ?? null) === null
              ? 'Todos'
              : implode(', ', $productionAggregation['warehouse_codes']);
          ?>
          <div class="mt-3 small text-muted">
            Filtro ativo: <strong><?= htmlspecialchars((string)($periodRow['name'] ?? '')) ?></strong>
            (<?= date('d/m/Y', strtotime((string)$periodRow['date_from'])) ?> – <?= date('d/m/Y', strtotime((string)$periodRow['date_to'])) ?>)
            · Depósitos: <?= htmlspecialchars($whLabel) ?>
            · Total período: <strong><?= number_format((float)($productionAggregation['total_qty'] ?? 0), 2, ',', '.') ?> un.</strong>
          </div>
          <?php if (is_array($currentItemProduction)): ?>
            <div class="alert alert-info py-2 px-3 mt-3 mb-0 small">
              <strong>Este item:</strong>
              <?= number_format((float)($currentItemProduction['qty_produced'] ?? 0), 2, ',', '.') ?> un. produzidas
              · <?= (int)($currentItemProduction['batches_count'] ?? 0) ?> lote(s)
              · <strong><?= $fmtPct((float)($currentItemProduction['share_criterion_1'] ?? 0)) ?>%</strong> do rateio 1
            </div>
          <?php elseif ($selectedPeriodId > 0): ?>
            <div class="alert alert-warning py-2 px-3 mt-3 mb-0 small">
              Nenhuma produção encontrada para este item no período e depósitos selecionados.
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>

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
        <form method="post" action="<?= $_ENV['URL_ADM'] ?>save-inventory-cost-simulation/<?= $itemId ?>" class="border rounded p-3 bg-white mt-3" id="form-save-simulation">
          <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_save_inventory_cost_simulation') ?>">
          <input type="hidden" name="material_adjust_pct" value="<?= htmlspecialchars((string)($scenario['material_adjust_pct'] ?? 0)) ?>">
          <input type="hidden" name="operations_adjust_pct" value="<?= htmlspecialchars((string)($scenario['operations_adjust_pct'] ?? 0)) ?>">
          <input type="hidden" name="global_adjust_pct" value="<?= htmlspecialchars((string)($scenario['global_adjust_pct'] ?? 0)) ?>">
          <input type="hidden" name="standard_batch_size" value="<?= htmlspecialchars((string)$batchSize) ?>">
          <input type="hidden" name="inv_cost_period_id" value="<?= $selectedPeriodId ?>">
          <input type="hidden" name="warehouse_scope" value="<?= htmlspecialchars($warehouseScope) ?>">
          <?php foreach ($selectedWarehouseCodes as $whCode): ?>
            <input type="hidden" name="warehouse_codes[]" value="<?= htmlspecialchars((string)$whCode) ?>">
          <?php endforeach; ?>
          <div id="save-simulation-structure-fields"></div>
          <div class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
              <label class="form-label mb-1">Nome da simulação (ao salvar)</label>
              <input type="text" class="form-control form-control-sm" name="simulation_title" maxlength="150"
                placeholder="Ex.: Cenário +10% materiais — <?= date('d/m/Y') ?>">
            </div>
            <div class="col-12 col-md-auto">
              <button type="submit" class="btn btn-success btn-sm" onclick="return copySimStructureToSaveForm()">
                <i class="fa-solid fa-floppy-disk me-1"></i> Salvar simulação
              </button>
            </div>
          </div>
        </form>
        <script>
        function copySimStructureToSaveForm() {
            const main = document.getElementById('form-simulate-cost');
            const target = document.getElementById('save-simulation-structure-fields');
            if (!main || !target) return true;
            target.innerHTML = '';
            const skip = new Set(['csrf_token', 'simulation_title']);
            main.querySelectorAll('input, select, textarea').forEach(function (el) {
                const name = el.getAttribute('name');
                if (!name || skip.has(name)) return;
                if ((el.type === 'radio' || el.type === 'checkbox') && !el.checked) return;
                if (el.tagName === 'SELECT' && el.multiple) {
                    Array.from(el.selectedOptions).forEach(function (opt) {
                        const h = document.createElement('input');
                        h.type = 'hidden';
                        h.name = name;
                        h.value = opt.value;
                        target.appendChild(h);
                    });
                    return;
                }
                const h = document.createElement('input');
                h.type = 'hidden';
                h.name = name;
                h.value = el.value;
                target.appendChild(h);
            });
            return true;
        }
        </script>
      <?php endif; ?>
    </div>
  </div>

  <?php if (is_array($productionAggregation) && !empty($productionAggregation['items'])): ?>
    <div class="card mb-4 border-light shadow">
      <div class="card-header fw-semibold">Produção agregada no período (todos os produtos)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th class="ps-3">Item</th>
                <th>Descrição</th>
                <th class="text-end">Qtd produzida</th>
                <th class="text-end">Lotes</th>
                <th class="text-end pe-3">% rateio 1</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($productionAggregation['items'] as $prodRow): ?>
                <?php
                  $isCurrent = is_array($currentItemProduction)
                    && (string)($prodRow['erp_code'] ?? '') === (string)($currentItemProduction['erp_code'] ?? '');
                ?>
                <tr class="<?= $isCurrent ? 'table-info' : '' ?>">
                  <td class="ps-3 text-nowrap"><?= htmlspecialchars((string)($prodRow['erp_code'] ?? '')) ?></td>
                  <td><?= htmlspecialchars((string)($prodRow['description'] ?? '')) ?></td>
                  <td class="text-end text-nowrap"><?= number_format((float)($prodRow['qty_produced'] ?? 0), 2, ',', '.') ?></td>
                  <td class="text-end"><?= (int)($prodRow['batches_count'] ?? 0) ?></td>
                  <td class="text-end pe-3"><?= $fmtPct((float)($prodRow['share_criterion_1'] ?? 0)) ?>%</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

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
    $cvarMpUnit = (float)($breakdown['cvar_mp_cost'] ?? 0);
    $cvarMaeUnit = (float)($breakdown['cvar_mae_cost'] ?? 0);
    $simCvarMp = (float)($breakdown['simulated_cvar_mp_cost'] ?? 0);
    $simCvarMae = (float)($breakdown['simulated_cvar_mae_cost'] ?? 0);
  ?>
    <div class="row g-3 mb-4">
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card border-light shadow-sm h-100">
          <div class="card-body py-3">
            <div class="text-muted text-uppercase small mb-1">CVAR MP (SKU)</div>
            <div class="fw-semibold">R$ <?= $fmtMoney($cvarMpUnit) ?></div>
            <div class="small text-muted">Simulado: R$ <?= $fmtMoney($simCvarMp) ?></div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card border-light shadow-sm h-100">
          <div class="card-body py-3">
            <div class="text-muted text-uppercase small mb-1">CVAR MAE (SKU)</div>
            <div class="fw-semibold">R$ <?= $fmtMoney($cvarMaeUnit) ?></div>
            <div class="small text-muted">Simulado: R$ <?= $fmtMoney($simCvarMae) ?></div>
          </div>
        </div>
      </div>
    </div>
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
