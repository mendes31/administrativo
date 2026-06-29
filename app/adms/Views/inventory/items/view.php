<?php if (!isset($this)) { exit; } ?>
<?php
$item = $this->data['item'] ?? [];
$itemId = (int)($item['id'] ?? 0);
$isProjectItem = !empty($this->data['is_project_item']);
$bom = $this->data['bom'] ?? [];
$operations = $this->data['operations'] ?? [];
$breakdown = $this->data['cost_breakdown'] ?? [];
$hasStructure = !empty($this->data['has_structure']);
$fmtMoney = static fn(float $v, int $dec = 4): string => number_format($v, $dec, ',', '.');
$canSimulate = !empty($this->data['buttonPermission']) && in_array('SimulateInventoryCost', $this->data['buttonPermission'], true);
$materialCost = (float)($breakdown['material_cost_batch'] ?? $breakdown['material_cost'] ?? 0);
$operationsCost = (float)($breakdown['operations_cost_batch'] ?? $breakdown['operations_cost'] ?? 0);
$structureTotal = (float)($breakdown['base_total_batch'] ?? $breakdown['base_total'] ?? 0);
require_once __DIR__ . '/../partials/operation_metrics.php';
$truncate = static function (string $text, int $max = 42): string {
    $text = trim($text);
    if ($text === '' || mb_strlen($text) <= $max) {
        return $text;
    }
    return mb_substr($text, 0, $max - 1) . '…';
};
?>
<?php include __DIR__ . '/../partials/cost_tables_compact_style.php'; ?>
<div class="container-fluid px-2 px-md-4 pb-4">

  <div class="mb-1 hstack gap-2 flex-wrap">
    <h2 class="mt-3 mb-0">Visualizar item</h2>
    <ol class="breadcrumb mb-3 mt-3 ms-auto">
      <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard" class="text-decoration-none">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>list-inventory-items" class="text-decoration-none">Itens</a></li>
      <li class="breadcrumb-item active">Visualizar</li>
    </ol>
  </div>

  <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
    <?php if (!empty($this->data['buttonPermission']) && in_array('ListInventoryItems', $this->data['buttonPermission'])): ?>
      <a href="<?= $_ENV['URL_ADM'] ?>list-inventory-items" class="btn btn-info btn-sm"><i class="fa-solid fa-list"></i> Listar</a>
    <?php endif; ?>
    <?php if (!empty($this->data['buttonPermission']) && in_array('UpdateInventoryItem', $this->data['buttonPermission'])): ?>
      <a href="<?= $_ENV['URL_ADM'] . 'update-inventory-item/' . $itemId ?>" class="btn btn-warning btn-sm"><i class="fa-solid fa-pen-to-square"></i> Editar</a>
    <?php endif; ?>
    <?php if (!empty($item['erp_code']) && empty($this->data['is_project_item'])): ?>
      <form action="" method="POST" class="d-inline">
        <input type="hidden" name="csrf_token" value="<?= \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_sync_inventory_structure') ?>">
        <input type="hidden" name="sync_sap_structure_item_id" value="<?= $itemId ?>">
        <button type="submit" class="btn btn-outline-secondary btn-sm" title="Sincronizar lista de materiais e rota no SAP">
          <i class="fa-solid fa-sitemap"></i> Estrutura
        </button>
      </form>
    <?php endif; ?>
    <?php if ($hasStructure): ?>
      <form action="" method="POST" class="d-inline">
        <input type="hidden" name="csrf_token" value="<?= \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_view_inventory_item_cost') ?>">
        <input type="hidden" name="recalculate_cost" value="1">
        <button type="submit" class="btn btn-outline-success btn-sm" title="Recalcula custo médio a partir da BOM e da rota">
          <i class="fa-solid fa-calculator"></i> Calcular custo
        </button>
      </form>
      <?php if ($canSimulate): ?>
        <a href="<?= $_ENV['URL_ADM'] ?>simulate-inventory-cost/<?= $itemId ?>" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-chart-line"></i> Simular cenários
        </a>
      <?php endif; ?>
    <?php endif; ?>
    <?php
    $log_resumo = $this->data['log_resumo'] ?? [];
    $log_btn_class = 'btn btn-outline-info btn-sm';
    include __DIR__ . '/../../partials/button_log_alteracoes.php';
    ?>
  </div>

  <?php include './app/adms/Views/partials/alerts.php'; ?>

  <!-- Dados gerais -->
  <div class="card mb-4 border-light shadow">
    <div class="card-header py-3">
      <span class="fw-semibold">Dados do item</span>
    </div>
    <div class="card-body p-4">
      <div class="rounded border bg-light p-3 mb-4">
        <div class="fw-semibold fs-6 mb-2">
          <?= htmlspecialchars(($item['code'] ?? '') . ' — ' . ($item['description'] ?? '')) ?>
          <?php if ($isProjectItem): ?>
            <span class="badge bg-warning text-dark ms-1">PA - PROJETO</span>
          <?php endif; ?>
        </div>
        <div class="small text-muted d-flex flex-wrap gap-3">
          <span><span class="text-secondary">ERP:</span> <?= htmlspecialchars($item['erp_code'] ?? '—') ?></span>
          <span><span class="text-secondary">Unidade:</span> <?= htmlspecialchars($item['unit_name'] ?? '—') ?></span>
          <span><span class="text-secondary">Grupo de Itens:</span> <?= htmlspecialchars($item['category_name'] ?? '—') ?></span>
          <span><span class="text-secondary">Forma farm.:</span> <?= htmlspecialchars($item['pharma_form_name'] ?? '—') ?></span>
          <span><span class="text-secondary">Linha:</span> <?= htmlspecialchars($item['production_line'] ?? '—') ?></span>
          <span><span class="text-secondary">Administração:</span> <?= htmlspecialchars(match ((string)($item['admin_type'] ?? 'none')) {
            'lot' => 'Lotes',
            'serial' => 'Números de série',
            default => 'Nenhum',
          }) ?></span>
          <span><span class="text-secondary">Ativo:</span> <?= !empty($item['active']) ? 'Sim' : 'Não' ?></span>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-12 col-md-4">
          <h6 class="text-muted text-uppercase small mb-3">Identificação</h6>
          <dl class="row mb-0 small">
            <dt class="col-5 text-muted">Código</dt>
            <dd class="col-7 fw-semibold mb-2"><?= htmlspecialchars($item['code'] ?? '') ?></dd>
            <dt class="col-5 text-muted">Código ERP</dt>
            <dd class="col-7 fw-semibold mb-2"><?= htmlspecialchars($item['erp_code'] ?? '—') ?></dd>
            <dt class="col-5 text-muted">Unidade</dt>
            <dd class="col-7 fw-semibold mb-0"><?= htmlspecialchars($item['unit_name'] ?? '—') ?></dd>
          </dl>
        </div>
        <div class="col-12 col-md-4">
          <h6 class="text-muted text-uppercase small mb-3">Custos</h6>
          <dl class="row mb-0 small">
            <dt class="col-5 text-muted">Custo médio</dt>
            <dd class="col-7 fw-semibold mb-2"><?= $fmtMoney((float)($this->data['header_average_cost'] ?? ($item['average_cost'] ?? 0))) ?> <span class="text-muted fw-normal">/ lote</span></dd>
            <dt class="col-5 text-muted">Último custo</dt>
            <dd class="col-7 fw-semibold mb-2"><?= $fmtMoney((float)($item['last_cost'] ?? 0)) ?></dd>
            <?php if ($hasStructure): ?>
              <dt class="col-5 text-muted">Custo estrutura</dt>
              <dd class="col-7 fw-semibold text-primary mb-0"><?= $fmtMoney($structureTotal) ?></dd>
            <?php endif; ?>
          </dl>
        </div>
        <div class="col-12 col-md-4">
          <h6 class="text-muted text-uppercase small mb-3">Produção / SAP</h6>
          <dl class="row mb-0 small">
            <dt class="col-5 text-muted">Forma farm.</dt>
            <dd class="col-7 fw-semibold mb-2"><?= htmlspecialchars($item['pharma_form_name'] ?? '—') ?></dd>
            <dt class="col-5 text-muted">Linha produção</dt>
            <dd class="col-7 fw-semibold mb-2"><?= htmlspecialchars($item['production_line'] ?? '—') ?></dd>
            <dt class="col-5 text-muted">Lote padrão</dt>
            <dd class="col-7 fw-semibold mb-2"><?= $fmtMoney((float)($item['standard_batch_size'] ?? 1), 6) ?></dd>
            <dt class="col-5 text-muted">Administrar por</dt>
            <dd class="col-7 fw-semibold mb-0"><?= htmlspecialchars(match ((string)($item['admin_type'] ?? 'none')) {
              'lot' => 'Lotes',
              'serial' => 'Números de série',
              default => 'Nenhum',
            }) ?></dd>
          </dl>
        </div>
        <div class="col-12 col-md-4">
          <h6 class="text-muted text-uppercase small mb-3">Estoque</h6>
          <dl class="row mb-0 small">
            <dt class="col-5 text-muted">Mínimo</dt>
            <dd class="col-7 fw-semibold mb-2"><?= $fmtMoney((float)($item['min_stock'] ?? 0)) ?></dd>
            <dt class="col-5 text-muted">Máximo</dt>
            <dd class="col-7 fw-semibold mb-0"><?= $fmtMoney((float)($item['max_stock'] ?? 0)) ?></dd>
          </dl>
        </div>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../partials/item_sap_sync_view.php'; ?>

  <!-- Estrutura BOM + Rota -->
  <?php if ($hasStructure): ?>
    <div class="card mb-4 border-light shadow">
      <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-semibold">
          <i class="fa-solid fa-sitemap text-primary me-1"></i> Estrutura do item
        </span>
        <div class="d-flex flex-wrap gap-2">
          <span class="badge bg-secondary">Materiais: R$ <?= $fmtMoney($materialCost) ?></span>
          <span class="badge bg-secondary">Rota: R$ <?= $fmtMoney($operationsCost) ?></span>
          <span class="badge bg-primary">Total: R$ <?= $fmtMoney($structureTotal) ?></span>
        </div>
      </div>
      <div class="card-body p-4 pt-2">
        <ul class="nav nav-tabs mb-4" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="view-tab-bom" data-bs-toggle="tab" data-bs-target="#view-pane-bom" type="button" role="tab">
              <i class="fa-solid fa-cubes text-primary"></i> Lista de materiais (<?= count($bom) ?>)
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="view-tab-route" data-bs-toggle="tab" data-bs-target="#view-pane-route" type="button" role="tab">
              <i class="fa-solid fa-gears text-success"></i> Rota / operações (<?= count($operations) ?>)
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="view-tab-tree" data-bs-toggle="tab" data-bs-target="#view-pane-tree" type="button" role="tab">
              <i class="fa-solid fa-list-tree"></i> Visão combinada
            </button>
          </li>
        </ul>

        <div class="tab-content inv-cost-compact-tables">
          <div class="tab-pane fade show active" id="view-pane-bom" role="tabpanel">
            <div class="table-wrap border rounded d-none d-md-block">
              <table class="table table-striped align-middle mb-0">
                <thead class="thead-green">
                  <tr>
                    <th class="ps-3" style="width:8%">Pos.</th>
                    <th style="width:14%">Código</th>
                    <th>Descrição</th>
                    <th class="text-end" style="width:12%">Quantidade</th>
                    <th style="width:8%">Un.</th>
                    <th class="text-end" style="width:10%">Perda %</th>
                    <th class="text-end" style="width:12%">Custo un.</th>
                    <th class="text-end pe-3" style="width:12%">Custo linha</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($bom)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Nenhum componente na lista de materiais.</td></tr>
                  <?php else: ?>
                    <?php foreach ($bom as $idx => $line):
                      $qty = (float)($line['quantity_per_batch'] ?? 0);
                      $scrap = (float)($line['scrap_percent'] ?? 0);
                      $isManual = (string)($line['line_source'] ?? 'catalog') === 'manual';
                      $cost = \App\adms\Models\Repository\inventory\InvItemBomRepository::resolveLineUnitCost($line);
                      $lineCost = \App\adms\Models\Repository\inventory\InvItemBomRepository::computeLineMaterialCost($line);
                      $pos = ($idx + 1) * 10;
                      $typeLabel = $isManual ? strtoupper((string)($line['manual_component_type'] ?? '')) : '';
                    ?>
                      <tr>
                        <td class="ps-3 text-muted"><?= $pos ?></td>
                        <td>
                          <code class="small"><?= htmlspecialchars($line['component_code'] ?? '') ?></code>
                          <?php if ($isManual): ?>
                            <span class="badge bg-warning text-dark ms-1">Manual</span>
                          <?php endif; ?>
                        </td>
                        <td class="cell-truncate" title="<?= htmlspecialchars($line['component_description'] ?? '') ?>"><?= htmlspecialchars($truncate((string)($line['component_description'] ?? ''), 50)) ?></td>
                        <td class="text-end"><?= $fmtMoney($qty, 6) ?></td>
                        <td><?= htmlspecialchars($line['unit_name'] ?? '') ?></td>
                        <td class="text-end"><?= $fmtMoney($scrap, 2) ?></td>
                        <td class="text-end"><?= $fmtMoney($cost, 6) ?></td>
                        <td class="text-end pe-3 fw-semibold"><?= $fmtMoney($lineCost, 6) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
                <?php if (!empty($bom)): ?>
                  <tfoot>
                    <tr>
                      <th colspan="7" class="text-end pe-2">Total materiais</th>
                      <th class="text-end pe-3"><?= $fmtMoney($materialCost, 6) ?></th>
                    </tr>
                  </tfoot>
                <?php endif; ?>
              </table>
            </div>

            <div class="d-block d-md-none list-mobile">
              <?php if (empty($bom)): ?>
                <p class="text-center text-muted py-3 mb-0 small">Nenhum componente na lista de materiais.</p>
              <?php else: ?>
                <?php foreach ($bom as $idx => $line):
                  $qty = (float)($line['quantity_per_batch'] ?? 0);
                  $scrap = (float)($line['scrap_percent'] ?? 0);
                  $cost = \App\adms\Models\Repository\inventory\InvItemBomRepository::resolveLineUnitCost($line);
                  $lineCost = \App\adms\Models\Repository\inventory\InvItemBomRepository::computeLineMaterialCost($line);
                  $isManual = (string)($line['line_source'] ?? 'catalog') === 'manual';
                  $pos = ($idx + 1) * 10;
                ?>
                <div class="card mb-2 shadow-sm inv-structure-mobile-card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                      <code class="small"><?= htmlspecialchars($line['component_code'] ?? '') ?></code>
                      <span class="text-muted small">Pos. <?= $pos ?><?php if ($isManual): ?> · <span class="badge bg-warning text-dark">Manual</span><?php endif; ?></span>
                    </div>
                    <div class="fw-semibold text-break mb-2"><?= htmlspecialchars($line['component_description'] ?? '') ?></div>
                    <div class="row g-2 small">
                      <div class="col-6"><span class="row-label">Quantidade</span><br><?= $fmtMoney($qty, 6) ?></div>
                      <div class="col-6"><span class="row-label">Unidade</span><br><?= htmlspecialchars($line['unit_name'] ?? '—') ?></div>
                      <div class="col-6"><span class="row-label">Perda %</span><br><?= $fmtMoney($scrap, 2) ?></div>
                      <div class="col-6"><span class="row-label">Custo un.</span><br><?= $fmtMoney($cost, 6) ?></div>
                      <div class="col-12"><span class="row-label">Custo linha</span><br><span class="fw-semibold"><?= $fmtMoney($lineCost, 6) ?></span></div>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
                <div class="inv-structure-mobile-total text-end fw-semibold">
                  Total materiais: <?= $fmtMoney($materialCost, 6) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="tab-pane fade" id="view-pane-route" role="tabpanel">
            <?php
            require_once __DIR__ . '/../partials/operation_metrics.php';
            include __DIR__ . '/../partials/inv_route_resource_type_labels.php';
            include __DIR__ . '/../partials/inv_route_operations_style.php';
            ?>
            <div class="d-none d-md-block">
              <?php if (empty($operations)): ?>
                <p class="text-center text-muted py-4 border rounded">Nenhuma operação na rota.</p>
              <?php else: ?>
                <div class="d-flex flex-wrap gap-2 mb-3">
                  <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllViewRouteSubs(true)"><i class="fa-solid fa-angles-down me-1"></i> Expandir subníveis</button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllViewRouteSubs(false)"><i class="fa-solid fa-angles-up me-1"></i> Recolher subníveis</button>
                </div>
                <?php foreach ($operations as $opIdx => $op):
                  $metrics = invOperationMetrics($op);
                  $resourceLines = $op['resource_lines'] ?? [];
                  $laborLines = $op['labor_lines'] ?? [];
                  $notes = (string)($op['notes'] ?? '');
                  $sapResource = '';
                  if (preg_match('/Recurso SAP:\s*(.+)$/i', $notes, $m)) {
                      $sapResource = trim($m[1]);
                  }
                  $timeValue = (float)($op['time_per_batch_hours'] ?? 0);
                  $timeUnit = strtoupper((string)($op['time_unit'] ?? 'MIN'));
                  if (!in_array($timeUnit, ['MIN', 'H'], true)) {
                      $timeUnit = 'MIN';
                  }
                  $timeUnitLabel = $timeUnit === 'H' ? 'Horas' : 'Minutos';
                  $timeMinutes = $metrics['time_minutes'];
                  $laborHH = ($timeMinutes / 60.0) * array_sum(array_map(static fn(array $l): int => max(1, (int)($l['qty'] ?? 1)), $laborLines));
                ?>
                <div class="card mb-3 border shadow-sm inv-route-op-card">
                  <div class="card-header bg-success-subtle py-2 px-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                      <div>
                        <span class="badge bg-secondary me-2">Pos. <?= (int)($op['sequence'] ?? 0) ?></span>
                        <strong><?= htmlspecialchars($op['operation_name'] ?? '—') ?></strong>
                        <?php if (!empty($op['operation_code'])): ?>
                          <code class="small ms-1 inv-op-code-display"><?= htmlspecialchars($op['operation_code']) ?></code>
                        <?php endif; ?>
                      </div>
                      <div class="text-end small">
                        <div><span class="text-muted">Σ R$/min:</span> <strong><?= $fmtMoney($metrics['cost_per_min'], 4) ?></strong></div>
                        <div><span class="text-muted">Custo linha:</span> <strong class="text-success"><?= $fmtMoney($metrics['line_cost'], 6) ?></strong></div>
                      </div>
                    </div>
                    <div class="d-flex flex-wrap gap-3 mt-2 small text-muted">
                      <span>Tempo/lote: <strong class="text-dark"><?= $fmtMoney($timeValue, 4) ?></strong> <?= htmlspecialchars($timeUnitLabel) ?></span>
                      <span>Tempo: <strong class="text-dark"><?= $fmtMoney($timeMinutes, 4) ?></strong> min</span>
                      <span>MO SAP: <strong class="text-dark"><?= $fmtMoney($metrics['sap_labor_per_min'], 4) ?></strong>/min</span>
                      <span>Equip.: <strong class="text-dark"><?= $fmtMoney($metrics['equipment_per_min'], 4) ?></strong>/min</span>
                      <span>MO cad.: <strong class="text-dark"><?= $fmtMoney($metrics['manual_labor_per_min'], 4) ?></strong>/min</span>
                      <span>Σ HH: <strong class="text-dark"><?= $fmtMoney($laborHH, 2) ?></strong></span>
                      <?php if ($metrics['time_minutes'] <= 0 && $metrics['cost_per_min'] > 0): ?>
                        <span class="badge bg-warning text-dark">Tempo não informado</span>
                      <?php endif; ?>
                      <?php if ($sapResource !== ''): ?>
                        <span class="text-muted">SAP: <code><?= htmlspecialchars($sapResource) ?></code></span>
                      <?php endif; ?>
                    </div>
                    <div class="mt-2 small"><span class="text-muted">Observações:</span> <?= $notes !== '' ? htmlspecialchars($notes) : '<span class="text-muted">—</span>' ?></div>
                  </div>
                  <div class="card-body py-2 inv-route-lines-align">
                    <?php if ($resourceLines !== []): ?>
                      <div class="ps-3 border-start border-3 border-secondary mb-2">
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none inv-view-sub-toggle mb-1"
                          data-bs-toggle="collapse" data-bs-target="#view-op-res-<?= (int)$opIdx ?>" aria-expanded="true">
                          <i class="fa-solid fa-chevron-down inv-chevron me-1"></i>
                          <i class="fa-solid fa-industry me-1"></i> Recursos SAP / equipamentos
                          <span class="badge bg-light text-dark border ms-1"><?= count($resourceLines) ?></span>
                        </button>
                        <div class="collapse show" id="view-op-res-<?= (int)$opIdx ?>">
                          <table class="table table-sm table-bordered mb-2 bg-white inv-route-sub-table">
                            <colgroup>
                              <col><col><col><col><col><col><col><col>
                            </colgroup>
                            <thead class="table-light">
                              <tr>
                                <th>Recurso</th>
                                <th class="inv-cell-num">Qtd</th>
                                <th class="inv-cell-num">Máq./min</th>
                                <th class="inv-cell-num">En./min</th>
                                <th class="inv-cell-num"></th>
                                <th class="inv-cell-num">Subtotal/lote</th>
                                <th class="inv-cell-tag">Tipo</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach ($resourceLines as $rl):
                                $rQty = max(1, (int)($rl['qty'] ?? 1));
                                $rMachine = (float)($rl['machine_cost_per_min'] ?? 0);
                                $rEnergy = (float)($rl['energy_cost_per_min'] ?? 0);
                                $rSub = $timeMinutes * $rQty * ($rMachine + $rEnergy);
                                $rType = strtoupper((string)($rl['resource_type'] ?? ''));
                              ?>
                                <tr>
                                  <td>
                                    <?php if (!empty($rl['resource_erp_code'])): ?>
                                      <code class="small inv-erp-code d-block"><?= htmlspecialchars($rl['resource_erp_code']) ?></code>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($rl['resource_name'] ?? '') ?>
                                  </td>
                                  <td class="inv-cell-num"><?= $rQty ?></td>
                                  <td class="inv-cell-num"><?= $fmtMoney($rMachine, 6) ?></td>
                                  <td class="inv-cell-num"><?= $fmtMoney($rEnergy, 6) ?></td>
                                  <td class="inv-cell-num text-muted">—</td>
                                  <td class="inv-cell-num"><?= $fmtMoney($rSub, 4) ?></td>
                                  <td class="inv-cell-tag"><span class="badge bg-secondary"><?= htmlspecialchars($invResourceTypeLabels[$rType] ?? ($rl['resource_type'] ?? '—')) ?></span></td>
                                </tr>
                              <?php endforeach; ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    <?php endif; ?>
                    <?php if ($laborLines !== []): ?>
                      <div class="ps-3 border-start border-3 border-secondary">
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none inv-view-sub-toggle mb-1"
                          data-bs-toggle="collapse" data-bs-target="#view-op-labor-<?= (int)$opIdx ?>" aria-expanded="true">
                          <i class="fa-solid fa-chevron-down inv-chevron me-1"></i>
                          <i class="fa-solid fa-users me-1"></i> MO cadastrada (papéis)
                          <span class="badge bg-light text-dark border ms-1"><?= count($laborLines) ?></span>
                        </button>
                        <div class="collapse show" id="view-op-labor-<?= (int)$opIdx ?>">
                          <table class="table table-sm table-bordered mb-0 bg-white inv-route-sub-table">
                            <colgroup>
                              <col><col><col><col><col><col><col><col>
                            </colgroup>
                            <thead class="table-light">
                              <tr>
                                <th>Papel</th>
                                <th class="inv-cell-num">Qtd</th>
                                <th class="inv-cell-num">HH</th>
                                <th class="inv-cell-num">R$/min</th>
                                <th class="inv-cell-num">Subtotal/min</th>
                                <th class="inv-cell-num">Subtotal/lote</th>
                                <th class="inv-cell-tag">Tipo</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach ($laborLines as $ll):
                                $lQty = max(1, (int)($ll['qty'] ?? 1));
                                $lCost = max(0, (float)($ll['cost_per_min'] ?? 0));
                                $lHH = ($timeMinutes / 60.0) * $lQty;
                                $lSubMin = $lQty * $lCost;
                                $lSubLote = $timeMinutes * $lQty * $lCost;
                              ?>
                                <tr>
                                  <td><?= htmlspecialchars($ll['role_name'] ?? '—') ?></td>
                                  <td class="inv-cell-num"><?= $lQty ?></td>
                                  <td class="inv-cell-num"><?= $fmtMoney($lHH, 2) ?></td>
                                  <td class="inv-cell-num"><?= $fmtMoney($lCost, 6) ?></td>
                                  <td class="inv-cell-num fw-semibold"><?= $fmtMoney($lSubMin, 4) ?></td>
                                  <td class="inv-cell-num"><?= $fmtMoney($lSubLote, 4) ?></td>
                                  <td class="inv-cell-tag"><span class="badge bg-light text-muted border">MO cad.</span></td>
                                </tr>
                              <?php endforeach; ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
                <div class="text-end fw-semibold border-top pt-2">Total rota (lote): <?= $fmtMoney($operationsCost, 6) ?></div>
              <?php endif; ?>
            </div>

            <div class="d-block d-md-none list-mobile">
              <?php if (empty($operations)): ?>
                <p class="text-center text-muted py-3 mb-0 small">Nenhuma operação na rota.</p>
              <?php else: ?>
                <?php foreach ($operations as $opIdx => $op):
                  $metrics = invOperationMetrics($op);
                  $resourceLines = $op['resource_lines'] ?? [];
                  $laborLines = $op['labor_lines'] ?? [];
                ?>
                <div class="card mb-2 shadow-sm inv-structure-mobile-card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                      <span class="fw-semibold"><?= htmlspecialchars($op['operation_name'] ?? '') ?></span>
                      <span class="text-muted small">Pos. <?= (int)($op['sequence'] ?? 0) ?></span>
                    </div>
                    <div class="row g-2 small mb-2">
                      <div class="col-6"><span class="row-label">Σ R$/min</span><br><?= $fmtMoney($metrics['cost_per_min'], 4) ?></div>
                      <div class="col-6"><span class="row-label">Tempo (min)</span><br><?= $fmtMoney($metrics['time_minutes'], 4) ?></div>
                      <div class="col-12"><span class="row-label">Custo linha</span><br><span class="fw-semibold text-success"><?= $fmtMoney($metrics['line_cost'], 6) ?></span></div>
                    </div>
                    <?php if ($resourceLines !== []): ?>
                      <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none inv-view-sub-toggle mb-1"
                        data-bs-toggle="collapse" data-bs-target="#view-mob-res-<?= (int)$opIdx ?>" aria-expanded="false">
                        <i class="fa-solid fa-chevron-down inv-chevron me-1"></i> Recursos (<?= count($resourceLines) ?>)
                      </button>
                      <div class="collapse" id="view-mob-res-<?= (int)$opIdx ?>">
                        <ul class="small ps-3 mb-2">
                          <?php foreach ($resourceLines as $rl): ?>
                            <li><?= htmlspecialchars(($rl['resource_erp_code'] ?? '') . ' ' . ($rl['resource_name'] ?? '')) ?> × <?= (int)($rl['qty'] ?? 1) ?></li>
                          <?php endforeach; ?>
                        </ul>
                      </div>
                    <?php endif; ?>
                    <?php if ($laborLines !== []): ?>
                      <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none inv-view-sub-toggle mb-1"
                        data-bs-toggle="collapse" data-bs-target="#view-mob-labor-<?= (int)$opIdx ?>" aria-expanded="false">
                        <i class="fa-solid fa-chevron-down inv-chevron me-1"></i> Pessoal (<?= count($laborLines) ?>)
                      </button>
                      <div class="collapse" id="view-mob-labor-<?= (int)$opIdx ?>">
                        <ul class="small ps-3 mb-0">
                          <?php foreach ($laborLines as $ll): ?>
                            <li><?= htmlspecialchars($ll['role_name'] ?? '—') ?> × <?= (int)($ll['qty'] ?? 1) ?> @ <?= $fmtMoney((float)($ll['cost_per_min'] ?? 0), 4) ?>/min</li>
                          <?php endforeach; ?>
                        </ul>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
                <div class="inv-structure-mobile-total text-end fw-semibold">
                  Total rota: <?= $fmtMoney($operationsCost, 6) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="tab-pane fade" id="view-pane-tree" role="tabpanel">
            <p class="text-muted mb-2" style="font-size:0.75rem">Componentes (material) e operações (rota) em sequência, no estilo SAP.</p>
            <div class="table-wrap border rounded d-none d-md-block">
              <table class="table align-middle mb-0">
                <thead class="thead-green">
                  <tr>
                    <th class="ps-3" style="width:8%">Pos.</th>
                    <th style="width:12%">Tipo</th>
                    <th style="width:14%">Código / Recurso</th>
                    <th>Descrição / atividade</th>
                    <th class="text-end" style="width:14%">Qtd / Tempo</th>
                    <th class="text-end pe-3" style="width:12%">Custo linha</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($bom) && empty($operations)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Estrutura vazia.</td></tr>
                  <?php endif; ?>
                  <?php foreach ($bom as $idx => $line):
                    $qty = (float)($line['quantity_per_batch'] ?? 0);
                    $cost = \App\adms\Models\Repository\inventory\InvItemBomRepository::resolveLineUnitCost($line);
                    $lineCost = \App\adms\Models\Repository\inventory\InvItemBomRepository::computeLineMaterialCost($line);
                  ?>
                    <tr>
                      <td class="ps-3"><?= ($idx + 1) * 10 ?></td>
                      <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="fa-solid fa-cube"></i> Material</span></td>
                      <td><code class="small"><?= htmlspecialchars($line['component_code'] ?? '') ?></code></td>
                      <td class="cell-truncate" title="<?= htmlspecialchars($line['component_description'] ?? '') ?>"><?= htmlspecialchars($truncate((string)($line['component_description'] ?? ''), 40)) ?></td>
                      <td class="text-end"><?= $fmtMoney($qty, 6) ?> <?= htmlspecialchars($line['unit_name'] ?? '') ?></td>
                      <td class="text-end pe-3 fw-semibold"><?= $fmtMoney($lineCost, 6) ?></td>
                    </tr>
                  <?php endforeach; ?>
                  <?php foreach ($operations as $op):
                    $metrics = invOperationMetrics($op);
                    $timeMinutes = $metrics['time_minutes'];
                    $rowTotal = $metrics['line_cost'];
                    $notes = (string)($op['notes'] ?? '');
                    $resource = '';
                    if (preg_match('/Recurso SAP:\s*(.+)$/i', $notes, $m)) {
                        $resource = trim($m[1]);
                    }
                    if ($resource === '' && !empty($op['resource_lines'])) {
                        $names = array_map(static fn(array $r): string => (string)($r['resource_erp_code'] ?? $r['resource_name'] ?? ''), $op['resource_lines']);
                        $resource = implode(' + ', array_filter($names));
                    }
                  ?>
                    <tr>
                      <td class="ps-3"><?= (int)($op['sequence'] ?? 0) ?></td>
                      <td><span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-gear"></i> Operação</span></td>
                      <td><code class="small"><?= htmlspecialchars($resource !== '' ? $resource : ($op['operation_code'] ?? '')) ?></code></td>
                      <td class="cell-truncate" title="<?= htmlspecialchars($op['operation_name'] ?? '') ?>"><?= htmlspecialchars($truncate((string)($op['operation_name'] ?? ''), 36)) ?></td>
                      <td class="text-end"><?= $fmtMoney($timeMinutes, 4) ?> min</td>
                      <td class="text-end pe-3 fw-semibold"><?= $fmtMoney($rowTotal, 6) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <div class="d-block d-md-none list-mobile">
              <?php if (empty($bom) && empty($operations)): ?>
                <p class="text-center text-muted py-3 mb-0 small">Estrutura vazia.</p>
              <?php else: ?>
                <?php foreach ($bom as $idx => $line):
                  $qty = (float)($line['quantity_per_batch'] ?? 0);
                  $cost = \App\adms\Models\Repository\inventory\InvItemBomRepository::resolveLineUnitCost($line);
                  $lineCost = \App\adms\Models\Repository\inventory\InvItemBomRepository::computeLineMaterialCost($line);
                ?>
                <div class="card mb-2 shadow-sm inv-structure-mobile-card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                      <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="fa-solid fa-cube"></i> Material</span>
                      <span class="text-muted small">Pos. <?= ($idx + 1) * 10 ?></span>
                    </div>
                    <code class="small d-block mb-1"><?= htmlspecialchars($line['component_code'] ?? '') ?></code>
                    <div class="text-break mb-2"><?= htmlspecialchars($line['component_description'] ?? '') ?></div>
                    <div class="d-flex justify-content-between small">
                      <span><span class="row-label">Qtd:</span> <?= $fmtMoney($qty, 6) ?> <?= htmlspecialchars($line['unit_name'] ?? '') ?></span>
                      <span class="fw-semibold"><?= $fmtMoney($lineCost, 6) ?></span>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
                <?php foreach ($operations as $op):
                  $timeValue = (float)($op['time_per_batch_hours'] ?? 0);
                  $timeUnit = strtoupper((string)($op['time_unit'] ?? 'MIN'));
                  $timeMinutes = ($timeUnit === 'H') ? $timeValue * 60.0 : $timeValue;
                  $operatorsQty = max(1, (int)($op['operators_qty'] ?? 1));
                  $labor = (float)($op['labor_cost_per_min'] ?? 0);
                  $machine = (float)($op['machine_cost_per_min'] ?? 0);
                  $energy = (float)($op['energy_cost_per_min'] ?? 0);
                  $costHour = (float)($op['operation_cost_per_hour'] ?? 0);
                  $cpm = ($labor * $operatorsQty) + $machine + $energy;
                  $rowTotal = $cpm > 0 ? $timeMinutes * $cpm : ($timeMinutes / 60.0) * $costHour;
                  $notes = (string)($op['notes'] ?? '');
                  $resource = '';
                  if (preg_match('/Recurso SAP:\s*(.+)$/i', $notes, $m)) {
                      $resource = trim($m[1]);
                  }
                ?>
                <div class="card mb-2 shadow-sm inv-structure-mobile-card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                      <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-gear"></i> Operação</span>
                      <span class="text-muted small">Pos. <?= (int)($op['sequence'] ?? 0) ?></span>
                    </div>
                    <code class="small d-block mb-1"><?= htmlspecialchars($resource !== '' ? $resource : ($op['operation_code'] ?? '')) ?></code>
                    <div class="text-break mb-2"><?= htmlspecialchars($op['operation_name'] ?? '') ?></div>
                    <div class="d-flex justify-content-between small">
                      <span><span class="row-label">Tempo:</span> <?= $fmtMoney($timeMinutes, 4) ?> min</span>
                      <span class="fw-semibold"><?= $fmtMoney($rowTotal, 6) ?></span>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="card mb-4 border-light shadow">
      <div class="card-body p-4">
        <div class="alert alert-light border mb-0">
          <i class="fa-solid fa-circle-info text-muted me-1"></i>
          Este item ainda não possui <strong>lista de materiais</strong> nem <strong>rota</strong> cadastradas.
          <?php if ($isProjectItem): ?>
            Para itens <strong>PA - PROJETO</strong>, monte a lista de materiais manualmente (incluindo linhas manuais) na edição do item.
          <?php elseif (!empty($item['erp_code'])): ?>
            Use o botão <strong>Estrutura</strong> acima para importar do SAP, ou edite o item nas abas correspondentes.
          <?php else: ?>
            Edite o item para informar a estrutura manualmente ou vincule um código ERP e sincronize pelo SAP.
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Saldos -->
  <div class="card border-light shadow">
    <div class="card-header py-3">
      <span class="fw-semibold">Saldos por depósito / posição</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive px-3 py-3 d-none d-md-block">
        <table class="table table-striped align-middle mb-0">
          <thead class="thead-green">
            <tr>
              <th class="ps-2">Depósito</th>
              <th>Posição</th>
              <th>Lote</th>
              <th>Validade</th>
              <th class="text-end">Em estoque</th>
              <th class="text-end">Custo médio</th>
              <th class="text-end pe-2">Valor</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $totalQty = 0;
            $totalVal = 0;
            $balances = $this->data['balances'] ?? [];
            if (empty($balances)):
            ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">Nenhum saldo registrado para este item.</td>
              </tr>
            <?php else:
              foreach ($balances as $b):
                $val = (float)($b['qty'] ?? 0) * (float)($b['average_cost'] ?? 0);
                $totalQty += (float)($b['qty'] ?? 0);
                $totalVal += $val;
            ?>
              <tr>
                <td class="ps-2"><?= htmlspecialchars($b['stock_name'] ?? '') ?></td>
                <td><?= htmlspecialchars(trim(($b['position_code'] ?? '') . ' ' . ($b['position_description'] ?? ''))) ?></td>
                <td><?= htmlspecialchars($b['batch_code'] ?? '') ?></td>
                <td><?= htmlspecialchars($b['expiration_date'] ?? '') ?></td>
                <td class="text-end"><?= number_format((float)($b['qty'] ?? 0), 4, ',', '.') ?></td>
                <td class="text-end"><?= number_format((float)($b['average_cost'] ?? 0), 4, ',', '.') ?></td>
                <td class="text-end pe-2"><?= number_format($val, 2, ',', '.') ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
          <?php if (!empty($balances)): ?>
            <tfoot>
              <tr>
                <th colspan="4" class="text-end pe-2">Totais</th>
                <th class="text-end"><?= number_format($totalQty, 4, ',', '.') ?></th>
                <th></th>
                <th class="text-end pe-2"><?= number_format($totalVal, 2, ',', '.') ?></th>
              </tr>
            </tfoot>
          <?php endif; ?>
        </table>
      </div>

      <div class="d-block d-md-none list-mobile px-3 py-3">
        <?php
        $balances = $this->data['balances'] ?? [];
        $totalQty = 0.0;
        $totalVal = 0.0;
        if (empty($balances)):
        ?>
          <p class="text-center text-muted py-3 mb-0 small">Nenhum saldo registrado para este item.</p>
        <?php else:
          foreach ($balances as $b):
            $val = (float)($b['qty'] ?? 0) * (float)($b['average_cost'] ?? 0);
            $totalQty += (float)($b['qty'] ?? 0);
            $totalVal += $val;
        ?>
          <div class="card mb-2 shadow-sm inv-structure-mobile-card">
            <div class="card-body">
              <div class="fw-semibold mb-2"><?= htmlspecialchars($b['stock_name'] ?? '—') ?></div>
              <div class="small mb-1"><span class="row-label">Posição:</span> <?= htmlspecialchars(trim(($b['position_code'] ?? '') . ' ' . ($b['position_description'] ?? ''))) ?: '—' ?></div>
              <div class="small mb-1"><span class="row-label">Lote:</span> <?= htmlspecialchars($b['batch_code'] ?? '—') ?></div>
              <div class="small mb-2"><span class="row-label">Validade:</span> <?= htmlspecialchars($b['expiration_date'] ?? '—') ?></div>
              <div class="row g-2 small">
                <div class="col-4"><span class="row-label">Em estoque</span><br><?= number_format((float)($b['qty'] ?? 0), 4, ',', '.') ?></div>
                <div class="col-4"><span class="row-label">Custo méd.</span><br><?= number_format((float)($b['average_cost'] ?? 0), 4, ',', '.') ?></div>
                <div class="col-4"><span class="row-label">Valor</span><br><span class="fw-semibold"><?= number_format($val, 2, ',', '.') ?></span></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
          <div class="inv-structure-mobile-total">
            <div class="d-flex justify-content-between small mb-1">
              <span class="text-muted">Total em estoque</span>
              <span class="fw-semibold"><?= number_format($totalQty, 4, ',', '.') ?></span>
            </div>
            <div class="d-flex justify-content-between small">
              <span class="text-muted">Valor total</span>
              <span class="fw-semibold"><?= number_format($totalVal, 2, ',', '.') ?></span>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<script>
function toggleAllViewRouteSubs(expand) {
    if (typeof bootstrap === 'undefined') return;
    document.querySelectorAll('#view-pane-route .collapse').forEach(function (el) {
        const instance = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
        expand ? instance.show() : instance.hide();
    });
}
</script>
