<?php

use App\adms\Helpers\CSRFHelper;

?>
<style>
  @media (max-width: 767.98px) {
    .inv-update-tabs .nav-tabs {
      flex-wrap: nowrap;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    .inv-update-tabs .nav-tabs .nav-item {
      flex-shrink: 0;
    }
    .inv-edit-responsive-table thead {
      display: none;
    }
    .inv-edit-responsive-table tbody tr {
      display: block;
      margin-bottom: 0.75rem;
      padding: 0.65rem 0.75rem;
      border: 1px solid #dee2e6;
      border-radius: 0.375rem;
      background: #fff;
      box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
    }
    .inv-edit-responsive-table tbody td {
      display: block;
      width: 100% !important;
      border: 0;
      padding: 0.35rem 0;
      text-align: left !important;
    }
    .inv-edit-responsive-table tbody td::before {
      content: attr(data-label);
      display: block;
      font-size: 0.72rem;
      font-weight: 600;
      color: #6c757d;
      margin-bottom: 0.2rem;
    }
    .inv-edit-responsive-table tbody td.text-end {
      text-align: left !important;
    }
    .inv-edit-responsive-table tbody td.text-end .btn {
      width: 100%;
    }
    .inv-edit-responsive-table tfoot tr {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 0.5rem;
      padding: 0.65rem 0.75rem;
      margin-top: 0.5rem;
      background: #f8f9fa;
      border-radius: 0.375rem;
      border: 1px solid #dee2e6;
    }
    .inv-edit-responsive-table tfoot td {
      display: block;
      width: auto !important;
      border: 0;
      padding: 0;
    }
  }
</style>
<div class="container-fluid px-2 px-md-4">

	<div class="mb-1 hstack gap-2">
		<h2 class="mt-3">Item de Estoque</h2>

		<ol class="breadcrumb mb-3 mt-3 ms-auto">
			<li class="breadcrumb-item">
				<a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
			</li>
			<li class="breadcrumb-item">
				<a href="<?php echo $_ENV['URL_ADM']; ?>list-inventory-items" class="text-decoration-none">Itens</a>
			</li>
			<li class="breadcrumb-item">Editar</li>
		</ol>
	</div>

	<div class="card mb-4 border-light shadow">
		<div class="card-header hstack gap-2 flex-wrap align-items-center">
			<span>Editar</span>
			<span class="ms-auto d-sm-flex flex-row flex-wrap gap-1 align-items-center">
				<?php if (in_array('ListInventoryItems', $this->data['buttonPermission'])) { echo "<a href='{$_ENV['URL_ADM']}list-inventory-items' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> "; }
				if (in_array('ViewInventoryItem', $this->data['buttonPermission']) && !empty($this->data['form']['id'])) { echo "<a href='{$_ENV['URL_ADM']}view-inventory-item/{$this->data['form']['id']}' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Ver</a> "; } ?>
			</span>
		</div>

		<div class="card-body">
			<?php include './app/adms/Views/partials/alerts.php'; ?>

			<div id="form-alerts" class="mb-2"></div>

			<form id="form-update-inventory-item" action="" method="POST" class="row g-3">
				<input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_inventory_item'); ?>">
				<?php
				$activeTabPane = (string)($_POST['active_tab'] ?? $_GET['tab'] ?? 'pane-dados-gerais');
				$allowedUpdateTabs = ['pane-dados-gerais', 'pane-bom', 'pane-operations'];
				if (!in_array($activeTabPane, $allowedUpdateTabs, true)) {
					$activeTabPane = 'pane-dados-gerais';
				}
				$tabIsActive = static fn(string $pane): string => $activeTabPane === $pane ? ' active' : '';
				$tabPaneShow = static fn(string $pane): string => $activeTabPane === $pane ? ' show active' : '';
				?>
				<input type="hidden" name="active_tab" id="active_tab" value="<?= htmlspecialchars($activeTabPane) ?>">

				<ul class="nav nav-tabs mb-3 inv-update-tabs" role="tablist">
					<li class="nav-item" role="presentation">
						<button class="nav-link<?= $tabIsActive('pane-dados-gerais') ?>" id="tab-dados-gerais" data-bs-toggle="tab" data-bs-target="#pane-dados-gerais" type="button" role="tab">Dados gerais</button>
					</li>
					<li class="nav-item" role="presentation">
						<button class="nav-link<?= $tabIsActive('pane-bom') ?>" id="tab-bom" data-bs-toggle="tab" data-bs-target="#pane-bom" type="button" role="tab">Lista de materiais</button>
					</li>
					<li class="nav-item" role="presentation">
						<button class="nav-link<?= $tabIsActive('pane-operations') ?>" id="tab-operations" data-bs-toggle="tab" data-bs-target="#pane-operations" type="button" role="tab">Rota</button>
					</li>
				</ul>

				<div class="tab-content">
					<div class="tab-pane fade<?= $tabPaneShow('pane-dados-gerais') ?>" id="pane-dados-gerais" role="tabpanel" aria-labelledby="tab-dados-gerais">
						<div class="row g-3">
							<div class="col-12 col-md-3">
								<label for="code" class="form-label">Código</label>
								<input type="text" name="code" id="code" class="form-control" value="<?php echo $this->data['form']['code'] ?? ''; ?>">
							</div>

							<div class="col-12 col-md-3">
								<label for="erp_code" class="form-label">Código ERP</label>
								<input type="text" name="erp_code" id="erp_code" class="form-control" value="<?php echo $this->data['form']['erp_code'] ?? ''; ?>" placeholder="Código do item no ERP (opcional)">
							</div>

							<div class="col-12 col-md-6">
								<label for="description" class="form-label">Descrição</label>
								<input type="text" name="description" id="description" class="form-control" value="<?php echo $this->data['form']['description'] ?? ''; ?>">
							</div>

							<div class="col-12 col-md-3">
								<label for="inv_unit_id" class="form-label">Unidade</label>
								<select name="inv_unit_id" id="inv_unit_id" class="form-select">
									<option value="">Selecione</option>
									<?php foreach (($this->data['listUnits'] ?? []) as $u) { $sel = ((string)($this->data['form']['inv_unit_id'] ?? '') === (string)$u['id']) ? 'selected' : ''; echo "<option value='{$u['id']}' $sel>{$u['name']}</option>"; } ?>
								</select>
							</div>

							<div class="col-12 col-md-3">
								<label for="inv_category_id" class="form-label">Categoria</label>
								<select name="inv_category_id" id="inv_category_id" class="form-select">
									<option value="">Selecione</option>
									<?php foreach (($this->data['listCategories'] ?? []) as $c) { $sel = ((string)($this->data['form']['inv_category_id'] ?? '') === (string)$c['id']) ? 'selected' : ''; echo "<option value='{$c['id']}' $sel>{$c['name']}</option>"; } ?>
								</select>
							</div>

							<div class="col-12 col-md-3">
								<label for="admin_type" class="form-label">Administrar por</label>
								<select name="admin_type" id="admin_type" class="form-select">
									<?php $adm = $this->data['form']['admin_type'] ?? 'none'; ?>
									<option value="none" <?php echo ($adm === 'none') ? 'selected' : ''; ?>>Nenhum</option>
									<option value="serial" <?php echo ($adm === 'serial') ? 'selected' : ''; ?>>Números de série</option>
									<option value="lot" <?php echo ($adm === 'lot') ? 'selected' : ''; ?>>Lotes</option>
								</select>
							</div>

							<div class="col-12 col-md-3">
								<label for="min_stock" class="form-label">Estoque mínimo</label>
								<input type="number" step="0.0001" min="0" name="min_stock" id="min_stock" class="form-control" value="<?php echo $this->data['form']['min_stock'] ?? ''; ?>">
							</div>

							<div class="col-12 col-md-3">
								<label for="max_stock" class="form-label">Estoque máximo</label>
								<input type="number" step="0.0001" min="0" name="max_stock" id="max_stock" class="form-control" value="<?php echo $this->data['form']['max_stock'] ?? ''; ?>">
							</div>

							<div class="col-12 col-md-3">
								<label for="average_cost" class="form-label">Custo médio</label>
								<input type="number" step="0.000001" min="0" name="average_cost" id="average_cost" class="form-control" value="<?php echo (string)($this->data['form']['average_cost'] ?? '0'); ?>" readonly title="Custo total do lote (materiais + rota). Rateio por SKU na simulação de custos.">
							</div>

							<div class="col-12 col-md-3">
								<label for="last_cost" class="form-label">Último custo</label>
								<input type="number" step="0.000001" min="0" name="last_cost" id="last_cost" class="form-control" value="<?php echo (string)($this->data['form']['last_cost'] ?? '0'); ?>" readonly title="Custo total do lote (materiais + rota). Rateio por SKU na simulação de custos.">
							</div>

							<div class="col-12 col-md-3">
								<label for="active" class="form-label">Ativo</label>
								<div class="form-check form-switch mt-2">
									<?php $active = (isset($this->data['form']['active']) ? (int)$this->data['form']['active'] : 1) ? 'checked' : ''; ?>
									<input class="form-check-input" type="checkbox" id="active" name="active" <?php echo $active; ?> />
									<label class="form-check-label" for="active">Sim</label>
								</div>
							</div>
						</div>
					</div>

					<div class="tab-pane fade<?= $tabPaneShow('pane-bom') ?>" id="pane-bom" role="tabpanel" aria-labelledby="tab-bom">
						<div class="table-responsive inv-edit-responsive-table">
							<table class="table table-sm align-middle" id="bom-table">
								<thead class="thead-green">
									<tr>
										<th style="width: 30%;">Componente</th>
										<th style="width: 15%;">Quantidade por lote</th>
										<th style="width: 10%;">Perda (%)</th>
										<th style="width: 10%;">Unidade</th>
										<th style="width: 15%;">Custo médio</th>
										<th style="width: 15%;">Total (Qtd x Custo)</th>
										<th style="width: 5%;" class="text-end">Ações</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$totalMaterialCost = 0.0;
									foreach (($this->data['bom'] ?? []) as $idx => $line):
										$qty = (float)($line['quantity_per_batch'] ?? 0);
										$cost = (float)($line['component_cost'] ?? 0);
										$rowTotal = $qty * $cost;
										$totalMaterialCost += $rowTotal;
										?>
										<tr>
											<td data-label="Componente">
												<select name="bom_component_item_id[]" class="form-select form-select-sm">
													<option value="">Selecione o componente</option>
													<?php
													$selectedComponentId = (string)($line['component_item_id'] ?? '');
													foreach (($this->data['listBomItems'] ?? []) as $bomItem) {
														$optVal = (string)$bomItem['id'];
														$sel = $optVal === $selectedComponentId ? 'selected' : '';
														$label = $bomItem['code'] . ' - ' . $bomItem['description'];
														echo "<option value=\"{$bomItem['id']}\" {$sel}>".htmlspecialchars($label)."</option>";
													}
													?>
												</select>
											</td>
											<td data-label="Quantidade por lote">
												<input type="number" step="0.000001" min="0" name="bom_quantity_per_batch[]" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($line['quantity_per_batch'] ?? '')) ?>">
											</td>
											<td data-label="Perda (%)">
												<input type="number" step="0.0001" min="0" name="bom_scrap_percent[]" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($line['scrap_percent'] ?? '0')) ?>">
											</td>
											<td data-label="Unidade"><?= htmlspecialchars($line['unit_name'] ?? '') ?></td>
											<td data-label="Custo médio"><?= number_format($cost, 6, ',', '.') ?></td>
											<td data-label="Total (Qtd x Custo)"><?= number_format($rowTotal, 6, ',', '.') ?></td>
											<td data-label="Ações" class="text-end">
												<button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeBomRow(this)">Remover</button>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
								<tfoot>
									<tr>
										<td colspan="5" class="text-end"><strong>Custo total dos componentes (lote):</strong></td>
										<td colspan="2"><strong id="bom-grand-total"><?= number_format($totalMaterialCost, 6, ',', '.') ?></strong></td>
									</tr>
								</tfoot>
							</table>
						</div>
						<div class="mt-2">
							<button type="button" class="btn btn-sm btn-outline-primary" onclick="addBomRow()">Adicionar componente</button>
							<p class="text-muted mt-2 mb-0">
								<small>
									Selecione um item de estoque já cadastrado para usar como componente.<br>
									Caso a matéria-prima ainda não exista, abra a tela de
									<a href="<?= $_ENV['URL_ADM'] ?>create-inventory-item" target="_blank">Cadastro de Item de Estoque</a>
									em uma nova aba, cadastre o item e recarregue esta página.
								</small>
							</p>
						</div>
					</div>

					<div class="tab-pane fade<?= $tabPaneShow('pane-operations') ?>" id="pane-operations" role="tabpanel" aria-labelledby="tab-operations">
						<?php
						$listResources = $this->data['listProductionResources'] ?? [];
						include __DIR__ . '/../partials/inv_route_resource_type_labels.php';
						$resourceTypeLabels = $invResourceTypeLabels;
						$resourcesByType = [];
						foreach ($listResources as $resItem) {
							$rtype = strtoupper((string)($resItem['resource_type'] ?? 'MACHINE'));
							$resourcesByType[$rtype][] = $resItem;
						}
						$canManageResources = in_array('ListInventoryProductionResources', $this->data['buttonPermission'] ?? [], true);
						require_once __DIR__ . '/../partials/operation_metrics.php';
						include __DIR__ . '/../partials/inv_route_operations_style.php';
						?>
						<div class="alert alert-light border small mb-3 py-2">
							<strong>Recursos</strong> são cadastrados em
							<?php if ($canManageResources): ?>
								<a href="<?= $_ENV['URL_ADM'] ?>list-inventory-production-resources" target="_blank">Estoque → Cadastros Bases → Recursos de Produção</a>
							<?php else: ?>
								<em>Recursos de Produção</em>
							<?php endif; ?>
							(máquinas, energia etc.). Cada operação pode usar <strong>vários recursos</strong>.
							<strong>Cálculo:</strong> Subtotal/lote = Tempo × Qtd × custo/min · <strong>Custo linha</strong> = Tempo × Σ custos/min (valores do lote).
							Rateio por SKU (unidade) na <a href="<?= $_ENV['URL_ADM'] ?>simulate-inventory-cost/<?= (int)($this->data['form']['id'] ?? 0) ?>">simulação de custos</a>.
							<strong>MO SAP</strong> = recursos tipo LABOR ·
							<strong>Equipamentos</strong> = Máq. + En. · <strong>MO cadastrada</strong> = papéis em Mão de obra.
						</div>
						<div class="d-flex flex-wrap gap-2 mb-2">
							<button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllRouteSubs(true)"><i class="fa-solid fa-angles-down me-1"></i> Expandir subníveis</button>
							<button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllRouteSubs(false)"><i class="fa-solid fa-angles-up me-1"></i> Recolher subníveis</button>
						</div>
						<div id="operations-list" class="inv-route-edit">
									<?php
									$totalOperationsCost = 0.0;
									foreach (($this->data['operations'] ?? []) as $lineSeq => $op):
										$timeValue = (float)($op['time_per_batch_hours'] ?? 0);
										$timeUnit = strtoupper((string)($op['time_unit'] ?? 'MIN'));
										if (!in_array($timeUnit, ['MIN', 'H'], true)) {
											$timeUnit = 'MIN';
										}
										$operatorsQty = max(1, (int)($op['operators_qty'] ?? 1));
										$laborCostPerMin = (float)($op['labor_cost_per_min'] ?? 0);
										$machineCostPerMin = (float)($op['machine_cost_per_min'] ?? 0);
										$energyCostPerMin = (float)($op['energy_cost_per_min'] ?? 0);
										$costHour = (float)($op['operation_cost_per_hour'] ?? 0);
										$resourceId = (int)($op['inv_production_resource_id'] ?? 0);
										$resourceLines = $op['resource_lines'] ?? [];
										if ($resourceLines === [] && $resourceId > 0) {
											$resourceLines = [[
												'inv_production_resource_id' => $resourceId,
												'qty' => 1,
												'machine_cost_per_min' => $machineCostPerMin,
												'energy_cost_per_min' => $energyCostPerMin,
												'resource_name' => $op['resource_name'] ?? '',
												'resource_erp_code' => $op['resource_erp_code'] ?? '',
												'resource_type' => $op['resource_type'] ?? '',
											]];
										}
										if ($resourceLines === [] && ($machineCostPerMin > 0 || $energyCostPerMin > 0)) {
											$resourceLines = [[
												'inv_production_resource_id' => '',
												'qty' => 1,
												'machine_cost_per_min' => $machineCostPerMin,
												'energy_cost_per_min' => $energyCostPerMin,
											]];
										}
										if ($resourceLines === []) {
											$resourceLines = [[
												'inv_production_resource_id' => '',
												'qty' => 1,
												'machine_cost_per_min' => 0,
												'energy_cost_per_min' => 0,
											]];
										}
										$laborLines = $op['labor_lines'] ?? [];
										if ($laborLines === [] && ($laborCostPerMin > 0 || $operatorsQty > 1)) {
											$laborLines = [[
												'inv_labor_role_id' => '',
												'qty' => $operatorsQty,
												'cost_per_min' => $laborCostPerMin,
											]];
										}
										if ($laborLines === []) {
											$laborLines = [['inv_labor_role_id' => '', 'qty' => 1, 'cost_per_min' => 0]];
										}
										$opForMetrics = array_merge($op, [
											'labor_lines' => $laborLines,
											'resource_lines' => $resourceLines,
										]);
										$metrics = invOperationMetrics($opForMetrics);
										$manualLaborPerMin = $metrics['manual_labor_per_min'];
										$sapLaborPerMin = $metrics['sap_labor_per_min'];
										$equipmentPerMin = $metrics['equipment_per_min'];
										$costPerMin = $metrics['cost_per_min'];
										$rowTotal = $metrics['line_cost'];
										$timeMinutes = $metrics['time_minutes'];
										$totalOperationsCost += $rowTotal;
										$notes = (string)($op['notes'] ?? '');
										$sapResource = '';
										if (preg_match('/Recurso SAP:\s*(.+)$/i', $notes, $m)) {
											$sapResource = trim($m[1]);
										}
										?>
										<div class="inv-route-op inv-route-op-card card mb-3 border shadow-sm" data-op-index="<?= (int)$lineSeq ?>">
											<div class="card-header bg-success-subtle py-2 px-3">
												<div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
													<div class="flex-grow-1">
														<span class="badge bg-secondary me-2">Pos.
															<input type="number" class="inv-seq-badge-input" name="op_sequence[]" value="<?= (int)($op['sequence'] ?? ($lineSeq + 1)) ?>" min="1" step="1" aria-label="Posição">
														</span>
														<select name="op_operation_id[]" class="form-select form-select-sm inv-op-select-inline op-operation-select" onchange="onOperationChange(this)">
															<option value="">Selecione a operação</option>
															<?php foreach (($this->data['listOperations'] ?? []) as $operation) {
																$sel = ((string)($op['inv_operation_id'] ?? '') === (string)$operation['id']) ? 'selected' : '';
																$opCode = htmlspecialchars((string)($operation['code'] ?? ''), ENT_QUOTES);
																echo "<option value='{$operation['id']}' data-code=\"{$opCode}\" {$sel}>".htmlspecialchars($operation['name'])."</option>";
															} ?>
														</select>
														<?php if (!empty($op['operation_code'])): ?>
															<code class="small ms-1 inv-op-code-display op-operation-code"><?= htmlspecialchars($op['operation_code']) ?></code>
														<?php else: ?>
															<code class="small ms-1 inv-op-code-display op-operation-code d-none"></code>
														<?php endif; ?>
														<button type="button" class="btn btn-link btn-sm text-danger inv-op-remove-btn ms-1" onclick="removeOperationRow(this)" title="Remover operação"><i class="fa-regular fa-trash-can"></i></button>
													</div>
													<div class="text-end small">
														<div><span class="text-muted">Σ R$/min:</span> <strong class="op-cost-per-min text-dark"><?= number_format($costPerMin, 4, ',', '.') ?></strong></div>
														<div><span class="text-muted">Custo linha:</span> <strong class="op-line-total text-success"><?= number_format($rowTotal, 6, ',', '.') ?></strong></div>
														<?php if ($timeMinutes <= 0 && $costPerMin > 0): ?>
															<div class="mt-1"><span class="badge bg-warning text-dark">Tempo não informado</span></div>
														<?php endif; ?>
													</div>
												</div>
												<?php
												$timeUnitLabel = $timeUnit === 'H' ? 'Horas' : 'Minutos';
												$laborHH = ($timeMinutes / 60.0) * array_sum(array_map(static fn(array $l): int => max(1, (int)($l['qty'] ?? 1)), $laborLines));
												?>
												<div class="d-flex flex-wrap gap-3 mt-2 small text-muted">
													<span>Tempo/lote: <input type="number" step="0.0001" min="0" name="op_time_per_batch_hours[]" class="inv-metric-inline op-time-input" value="<?= htmlspecialchars((string)($op['time_per_batch_hours'] ?? '0')) ?>" oninput="recalcOpLineTotal(<?= (int)$lineSeq ?>)" aria-label="Tempo por lote"> <select name="op_time_unit[]" class="inv-metric-inline-select op-time-unit" onchange="recalcOpLineTotal(<?= (int)$lineSeq ?>)" aria-label="Unidade de tempo">
															<?php
															foreach (['MIN' => 'Minutos', 'H' => 'Horas'] as $value => $label) {
																$sel = $value === $timeUnit ? 'selected' : '';
																echo "<option value=\"{$value}\" {$sel}>".htmlspecialchars($label)."</option>";
															}
															?>
														</select></span>
													<span>Tempo: <strong class="op-time-display text-dark"><?= number_format($timeMinutes, 4, ',', '.') ?></strong> min</span>
													<span>MO SAP: <strong class="op-sum-sap-labor text-dark"><?= number_format($sapLaborPerMin, 4, ',', '.') ?></strong>/min</span>
													<span>Equip.: <strong class="op-sum-equipment text-dark"><?= number_format($equipmentPerMin, 4, ',', '.') ?></strong>/min</span>
													<span>MO cad.: <strong class="op-sum-manual-labor text-dark"><?= number_format($manualLaborPerMin, 4, ',', '.') ?></strong>/min</span>
													<span>Σ HH: <strong class="op-sum-labor-hh text-dark"><?= number_format($laborHH, 2, ',', '.') ?></strong></span>
													<?php if ($sapResource !== ''): ?>
														<span class="text-muted">SAP: <code><?= htmlspecialchars($sapResource) ?></code></span>
													<?php endif; ?>
												</div>
												<input type="hidden" name="op_machine_cost_per_min[]" class="op-machine-cost" value="<?= htmlspecialchars((string)($metrics['machine_per_min'] ?? 0)) ?>">
												<input type="hidden" name="op_energy_cost_per_min[]" class="op-energy-cost" value="<?= htmlspecialchars((string)($metrics['energy_per_min'] ?? 0)) ?>">
												<div class="mt-2 small">
													<span class="text-muted">Observações:</span>
													<input type="text" name="op_notes[]" class="inv-notes-inline" value="<?= htmlspecialchars($notes) ?>" aria-label="Observações">
												</div>
											</div>
											<div class="card-body py-2 inv-route-lines-align">
												<div class="ps-3 border-start border-3 border-secondary inv-route-sub-block mb-2">
													<button type="button" class="btn btn-link btn-sm p-0 text-decoration-none inv-view-sub-toggle mb-1"
														data-bs-toggle="collapse" data-bs-target="#op-res-collapse-<?= (int)$lineSeq ?>" aria-expanded="true" aria-controls="op-res-collapse-<?= (int)$lineSeq ?>">
														<i class="fa-solid fa-chevron-down inv-chevron me-1"></i>
														<i class="fa-solid fa-industry me-1"></i> Recursos SAP / equipamentos
														<span class="badge bg-light text-dark border ms-1"><?= count($resourceLines) ?></span>
													</button>
													<div class="collapse show" id="op-res-collapse-<?= (int)$lineSeq ?>">
														<table class="table table-sm table-bordered inv-route-sub-table bg-white mb-2">
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
																	<th class="inv-cell-act" title="Remover linha"><span class="visually-hidden">Excluir</span><i class="fa-regular fa-trash-can text-muted"></i></th>
																</tr>
															</thead>
															<tbody class="op-resource-tbody" data-op-index="<?= (int)$lineSeq ?>">
																<?php foreach ($resourceLines as $resourceLine):
																	$selResId = (int)($resourceLine['inv_production_resource_id'] ?? 0);
																	$rType = strtoupper((string)($resourceLine['resource_type'] ?? ''));
																	$rQty = max(1, (int)($resourceLine['qty'] ?? 1));
																	$rMachine = max(0, (float)($resourceLine['machine_cost_per_min'] ?? 0));
																	$rEnergy = max(0, (float)($resourceLine['energy_cost_per_min'] ?? 0));
																	$rSub = ($timeMinutes * $rQty * ($rMachine + $rEnergy));
																	?>
																	<tr>
																		<td>
																			<?php if (!empty($resourceLine['resource_erp_code'])): ?>
																				<code class="small inv-erp-code d-block op-resource-erp-preview"><?= htmlspecialchars($resourceLine['resource_erp_code']) ?></code>
																			<?php else: ?>
																				<code class="small inv-erp-code d-block op-resource-erp-preview d-none"></code>
																			<?php endif; ?>
																			<select name="op_resource_id[<?= (int)$lineSeq ?>][]" class="form-select form-select-sm op-resource-select" onchange="onResourceLineChange(this)">
																				<option value="">Selecione o recurso</option>
																				<?php foreach ($resourceTypeLabels as $typeKey => $typeLabel):
																					if (empty($resourcesByType[$typeKey])) continue;
																					?>
																					<optgroup label="<?= htmlspecialchars($typeLabel) ?>">
																						<?php foreach ($resourcesByType[$typeKey] as $res):
																							$sel = $selResId > 0 && $selResId === (int)$res['id'] ? 'selected' : '';
																							$label = trim(($res['erp_code'] ?? '') . ' — ' . ($res['name'] ?? ''));
																							?>
																							<option value="<?= (int)$res['id'] ?>" data-type="<?= htmlspecialchars($typeKey) ?>"
																								data-erp="<?= htmlspecialchars((string)($res['erp_code'] ?? ''), ENT_QUOTES) ?>"
																								data-machine="<?= htmlspecialchars((string)($res['machine_cost_per_min'] ?? '0')) ?>"
																								data-energy="<?= htmlspecialchars((string)($res['energy_cost_per_min'] ?? '0')) ?>"
																								<?= $sel ?>><?= htmlspecialchars($label) ?></option>
																						<?php endforeach; ?>
																					</optgroup>
																				<?php endforeach; ?>
																			</select>
																		</td>
																		<td class="inv-cell-num"><input type="number" min="1" step="1" name="op_resource_qty[<?= (int)$lineSeq ?>][]" class="form-control form-control-sm op-resource-qty" value="<?= $rQty ?>" oninput="recalcOpLineTotal(<?= (int)$lineSeq ?>)"></td>
																		<td class="inv-cell-num"><input type="number" step="0.000001" min="0" name="op_resource_machine_cost[<?= (int)$lineSeq ?>][]" class="form-control form-control-sm op-resource-machine" value="<?= htmlspecialchars((string)($resourceLine['machine_cost_per_min'] ?? '0')) ?>" oninput="recalcOpLineTotal(<?= (int)$lineSeq ?>)"></td>
																		<td class="inv-cell-num"><input type="number" step="0.000001" min="0" name="op_resource_energy_cost[<?= (int)$lineSeq ?>][]" class="form-control form-control-sm op-resource-energy" value="<?= htmlspecialchars((string)($resourceLine['energy_cost_per_min'] ?? '0')) ?>" oninput="recalcOpLineTotal(<?= (int)$lineSeq ?>)"></td>
																		<td class="inv-cell-num text-muted"><span class="inv-cell-readonly">—</span></td>
																		<td class="inv-cell-num"><span class="inv-cell-readonly op-resource-subtotal"><?= number_format($rSub, 4, ',', '.') ?></span></td>
																		<td class="inv-cell-tag"><span class="badge bg-secondary op-resource-type-badge"><?= htmlspecialchars($resourceTypeLabels[$rType] ?? ($rType ?: '—')) ?></span></td>
																		<td class="inv-cell-act"><button type="button" class="btn btn-link inv-row-remove" title="Remover linha" aria-label="Remover linha" onclick="removeResourceRow(this, <?= (int)$lineSeq ?>)"><i class="fa-regular fa-trash-can"></i></button></td>
																	</tr>
																<?php endforeach; ?>
															</tbody>
														</table>
													</div>
													<button type="button" class="btn btn-link btn-sm p-0 inv-add-row-link mt-1" onclick="addResourceRow(<?= (int)$lineSeq ?>)"><i class="fa-solid fa-plus me-1"></i> Recurso</button>
												</div>
												<div class="ps-3 border-start border-3 border-secondary inv-route-sub-block">
													<button type="button" class="btn btn-link btn-sm p-0 text-decoration-none inv-view-sub-toggle mb-1"
														data-bs-toggle="collapse" data-bs-target="#op-labor-collapse-<?= (int)$lineSeq ?>" aria-expanded="true" aria-controls="op-labor-collapse-<?= (int)$lineSeq ?>">
														<i class="fa-solid fa-chevron-down inv-chevron me-1"></i>
														<i class="fa-solid fa-users me-1"></i> MO cadastrada (papéis)
														<span class="badge bg-light text-dark border ms-1"><?= count($laborLines) ?></span>
													</button>
													<div class="collapse show" id="op-labor-collapse-<?= (int)$lineSeq ?>">
														<table class="table table-sm table-bordered inv-route-sub-table bg-white mb-0">
															<colgroup>
																<col><col><col><col><col><col><col><col>
															</colgroup>
															<thead class="table-light">
																<tr>
																	<th>Papel</th>
																	<th class="inv-cell-num">Qtd</th>
																	<th class="inv-cell-num" title="Horas-homem = Tempo (h) × Qtd">HH</th>
																	<th class="inv-cell-num">R$/min</th>
																	<th class="inv-cell-num">Subtotal/min</th>
																	<th class="inv-cell-num">Subtotal/lote</th>
																	<th class="inv-cell-tag">Tipo</th>
																	<th class="inv-cell-act" title="Remover linha"><span class="visually-hidden">Excluir</span><i class="fa-regular fa-trash-can text-muted"></i></th>
																</tr>
															</thead>
															<tbody class="op-labor-tbody" data-op-index="<?= (int)$lineSeq ?>">
																<?php foreach ($laborLines as $laborLine):
																	$lQty = max(1, (int)($laborLine['qty'] ?? 1));
																	$lCost = max(0, (float)($laborLine['cost_per_min'] ?? 0));
																	$lSubLote = ($timeMinutes * $lQty * $lCost);
																	$lSubMin = $lQty * $lCost;
																	$lHH = ($timeMinutes / 60.0) * $lQty;
																	?>
																	<tr>
																		<td>
																			<select name="op_labor_role_id[<?= (int)$lineSeq ?>][]" class="form-select form-select-sm op-labor-role" onchange="onLaborRoleChange(this)">
																				<option value="">Selecione</option>
																				<?php foreach (($this->data['listLaborRoles'] ?? []) as $role) {
																					$roleSel = ((string)($laborLine['inv_labor_role_id'] ?? '') === (string)$role['id']) ? 'selected' : '';
																					echo "<option value='{$role['id']}' data-default-cost='{$role['default_cost_per_min']}' {$roleSel}>".htmlspecialchars($role['name'])."</option>";
																				} ?>
																			</select>
																		</td>
																		<td class="inv-cell-num"><input type="number" min="1" step="1" name="op_labor_qty[<?= (int)$lineSeq ?>][]" class="form-control form-control-sm op-labor-qty" value="<?= $lQty ?>" oninput="recalcOpLineTotal(<?= (int)$lineSeq ?>)"></td>
																		<td class="inv-cell-num"><span class="inv-cell-readonly op-labor-hh"><?= number_format($lHH, 2, ',', '.') ?></span></td>
																		<td class="inv-cell-num"><input type="number" step="0.000001" min="0" name="op_labor_cost_per_min[<?= (int)$lineSeq ?>][]" class="form-control form-control-sm op-labor-cost" value="<?= htmlspecialchars((string)$lCost) ?>" oninput="recalcOpLineTotal(<?= (int)$lineSeq ?>)"></td>
																		<td class="inv-cell-num"><span class="inv-cell-readonly op-labor-subtotal-min fw-semibold"><?= number_format($lSubMin, 4, ',', '.') ?></span></td>
																		<td class="inv-cell-num"><span class="inv-cell-readonly op-labor-subtotal"><?= number_format($lSubLote, 4, ',', '.') ?></span></td>
																		<td class="inv-cell-tag"><span class="badge bg-light text-muted border">MO cad.</span></td>
																		<td class="inv-cell-act"><button type="button" class="btn btn-link inv-row-remove" title="Remover linha" aria-label="Remover linha" onclick="removeLaborRow(this, <?= (int)$lineSeq ?>)"><i class="fa-regular fa-trash-can"></i></button></td>
																	</tr>
																<?php endforeach; ?>
															</tbody>
														</table>
													</div>
													<button type="button" class="btn btn-link btn-sm p-0 inv-add-row-link mt-1" onclick="addLaborRow(<?= (int)$lineSeq ?>)"><i class="fa-solid fa-plus me-1"></i> Pessoa</button>
												</div>
											</div>
										</div>
									<?php endforeach; ?>
						</div>
						<div class="text-end fw-semibold border-top pt-2 mt-2">
							Total rota (lote): <strong id="operations-grand-total"><?= number_format($totalOperationsCost, 6, ',', '.') ?></strong>
						</div>
						<div class="mt-2">
							<button type="button" class="btn btn-sm btn-outline-primary" onclick="addOperationRow()">Adicionar operação</button>
						</div>
					</div>
				</div>

				<div class="col-12 mt-3">
					<button type="submit" class="btn btn-success">Salvar</button>
				</div>
			</form>
		</div>
	</div>

</div>

<script>
function removeBomRow(btn) {
    const row = btn.closest('tr');
    if (row) row.remove();
}
const productionResources = <?php echo json_encode($this->data['listProductionResources'] ?? [], JSON_UNESCAPED_UNICODE); ?>;
const laborRoles = <?php echo json_encode($this->data['listLaborRoles'] ?? [], JSON_UNESCAPED_UNICODE); ?>;
const operationOptions = <?php echo json_encode($this->data['listOperations'] ?? [], JSON_UNESCAPED_UNICODE); ?>;
const resourceTypeLabels = <?php echo json_encode($resourceTypeLabels ?? [], JSON_UNESCAPED_UNICODE); ?>;

function getOpCard(opIndex) {
    return document.querySelector('.inv-route-op[data-op-index="' + opIndex + '"]');
}

function nextOpIndex() {
    const list = document.getElementById('operations-list');
    if (!list) return 0;
    let max = -1;
    list.querySelectorAll('.inv-route-op').forEach(function (card) {
        const idx = parseInt(card.getAttribute('data-op-index') || '0', 10);
        if (idx > max) max = idx;
    });
    return max + 1;
}

function removeOperationRow(btn) {
    const card = btn.closest('.inv-route-op');
    if (!card) return;
    card.remove();
    recalcGrandTotal();
}

function removeResourceRow(btn, opIndex) {
    const row = btn.closest('tr');
    const tbody = row ? row.closest('.op-resource-tbody') : null;
    if (!row || !tbody) return;
    if (tbody.querySelectorAll('tr').length <= 1) return;
    row.remove();
    updateSublevelBadge(opIndex, 'resource');
    recalcOpLineTotal(opIndex);
}

function formatBrNumber(value, decimals) {
    return Number(value || 0).toFixed(decimals).replace('.', ',');
}

function recalcAllOperations() {
    document.querySelectorAll('.inv-route-op').forEach(function (card) {
        const opIndex = parseInt(card.getAttribute('data-op-index') || '0', 10);
        recalcOpLineTotal(opIndex);
    });
}

function getOpTimeMinutes(card) {
    const timeVal = parseFloat(card.querySelector('.op-time-input')?.value || '0');
    const unit = (card.querySelector('.op-time-unit')?.value || 'MIN').toUpperCase();
    return unit === 'H' ? timeVal * 60.0 : timeVal;
}

function recalcOpLineTotal(opIndex) {
    const card = getOpCard(opIndex);
    if (!card) return;

    const timeMin = getOpTimeMinutes(card);

    let sumSapLabor = 0;
    let sumEquipment = 0;
    let sumMachineHidden = 0;
    let sumEnergyHidden = 0;
    card.querySelectorAll('.op-resource-tbody tr').forEach(function (row) {
        const qty = parseFloat(row.querySelector('.op-resource-qty')?.value || '0');
        const machine = parseFloat(row.querySelector('.op-resource-machine')?.value || '0');
        const energy = parseFloat(row.querySelector('.op-resource-energy')?.value || '0');
        const select = row.querySelector('.op-resource-select');
        const opt = select && select.options[select.selectedIndex];
        const resType = (opt ? (opt.getAttribute('data-type') || '') : '').toUpperCase();
        const costPerMin = machine + energy;
        const subtotal = timeMin > 0 && qty > 0 ? timeMin * qty * costPerMin : 0;
        const subEl = row.querySelector('.op-resource-subtotal');
        if (subEl) subEl.textContent = formatBrNumber(subtotal, 4);
        if (qty > 0) {
            const linePerMin = qty * costPerMin;
            if (resType === 'LABOR') {
                sumSapLabor += linePerMin;
            } else {
                sumEquipment += linePerMin;
            }
            sumMachineHidden += qty * machine;
            sumEnergyHidden += qty * energy;
        }
    });

    let sumManualLabor = 0;
    let sumLaborHH = 0;
    card.querySelectorAll('.op-labor-tbody tr').forEach(function (row) {
        const qty = parseFloat(row.querySelector('.op-labor-qty')?.value || '0');
        const cost = parseFloat(row.querySelector('.op-labor-cost')?.value || '0');
        const hh = timeMin > 0 && qty > 0 ? (timeMin / 60.0) * qty : 0;
        const subtotalMin = qty > 0 && cost > 0 ? qty * cost : 0;
        const subtotal = timeMin > 0 && qty > 0 ? timeMin * qty * cost : 0;
        const hhEl = row.querySelector('.op-labor-hh');
        if (hhEl) hhEl.textContent = formatBrNumber(hh, 2);
        const subMinEl = row.querySelector('.op-labor-subtotal-min');
        if (subMinEl) subMinEl.textContent = formatBrNumber(subtotalMin, 4);
        const subEl = row.querySelector('.op-labor-subtotal');
        if (subEl) subEl.textContent = formatBrNumber(subtotal, 4);
        if (qty > 0 && cost > 0) sumManualLabor += qty * cost;
        sumLaborHH += hh;
    });
    const costPerMin = sumSapLabor + sumEquipment + sumManualLabor;
    const lineTotal = timeMin > 0 ? timeMin * costPerMin : 0;

    const machineHidden = card.querySelector('.op-machine-cost');
    const energyHidden = card.querySelector('.op-energy-cost');
    if (machineHidden) machineHidden.value = sumMachineHidden.toFixed(6);
    if (energyHidden) energyHidden.value = sumEnergyHidden.toFixed(6);

    const setText = function (sel, val, dec) {
        const el = card.querySelector(sel);
        if (el) el.textContent = formatBrNumber(val, dec);
    };
    setText('.op-sum-sap-labor', sumSapLabor, 4);
    setText('.op-sum-equipment', sumEquipment, 4);
    setText('.op-sum-manual-labor', sumManualLabor, 4);
    setText('.op-sum-labor-hh', sumLaborHH, 2);
    setText('.op-time-display', timeMin, 4);
    setText('.op-cost-per-min', costPerMin, 4);
    setText('.op-line-total', lineTotal, 6);

    recalcGrandTotal();
}

function recalcGrandTotal() {
    let grand = 0;
    document.querySelectorAll('.inv-route-op .op-line-total').forEach(function (el) {
        grand += parseFloat(String(el.textContent || '0').replace(',', '.')) || 0;
    });
    const footer = document.getElementById('operations-grand-total');
    if (footer) footer.textContent = formatBrNumber(grand, 6);
}

function onOperationChange(select) {
    const card = select.closest('.inv-route-op');
    if (!card) return;
    const opt = select.options[select.selectedIndex];
    const code = opt ? (opt.getAttribute('data-code') || '') : '';
    const codeEl = card.querySelector('.op-operation-code');
    if (codeEl) {
        codeEl.textContent = code;
        codeEl.classList.toggle('d-none', code === '');
    }
}

function onResourceLineChange(select) {
    const row = select.closest('tr');
    if (!row) return;
    const opt = select.options[select.selectedIndex];
    const machine = opt ? parseFloat(opt.getAttribute('data-machine') || '0') : 0;
    const energy = opt ? parseFloat(opt.getAttribute('data-energy') || '0') : 0;
    const typeKey = opt ? (opt.getAttribute('data-type') || '') : '';
    const erpCode = opt ? (opt.getAttribute('data-erp') || '') : '';
    const machineInput = row.querySelector('.op-resource-machine');
    const energyInput = row.querySelector('.op-resource-energy');
    const badge = row.querySelector('.op-resource-type-badge');
    const erpPreview = row.querySelector('.op-resource-erp-preview');
    if (machineInput && machine > 0) machineInput.value = machine;
    if (energyInput && energy > 0) energyInput.value = energy;
    if (badge) badge.textContent = resourceTypeLabels[typeKey] || typeKey || '—';
    if (erpPreview) {
        erpPreview.textContent = erpCode;
        erpPreview.classList.toggle('d-none', erpCode === '');
    }
    const opIndex = parseInt(row.closest('.op-resource-tbody')?.getAttribute('data-op-index') || '0', 10);
    recalcOpLineTotal(opIndex);
}

function removeLaborRow(btn, opIndex) {
    const row = btn.closest('tr');
    const tbody = row ? row.closest('.op-labor-tbody') : null;
    if (!row || !tbody) return;
    if (tbody.querySelectorAll('tr').length <= 1) return;
    row.remove();
    updateSublevelBadge(opIndex, 'labor');
    recalcOpLineTotal(opIndex);
}

function onLaborRoleChange(select) {
    const row = select.closest('tr');
    if (!row) return;
    const opt = select.options[select.selectedIndex];
    const defaultCost = opt ? parseFloat(opt.getAttribute('data-default-cost') || '0') : 0;
    const costInput = row.querySelector('.op-labor-cost');
    if (costInput && defaultCost > 0 && parseFloat(costInput.value || '0') <= 0) {
        costInput.value = defaultCost;
    }
    const opIndex = parseInt(row.closest('.op-labor-tbody')?.getAttribute('data-op-index') || '0', 10);
    recalcOpLineTotal(opIndex);
}

function buildLaborRoleOptionsHtml() {
    let html = '<option value="">Selecione</option>';
    (laborRoles || []).forEach(function (role) {
        const name = String(role.name || '').replace(/"/g, '&quot;');
        html += '<option value="' + role.id + '" data-default-cost="' + (role.default_cost_per_min || 0) + '">' + name + '</option>';
    });
    return html;
}

function buildResourceOptionsHtml() {
    let html = '<option value="">Selecione o recurso</option>';
    const byType = {};
    (productionResources || []).forEach(function (res) {
        const type = String(res.resource_type || 'MACHINE').toUpperCase();
        if (!byType[type]) byType[type] = [];
        byType[type].push(res);
    });
    Object.keys(resourceTypeLabels || {}).forEach(function (typeKey) {
        const items = byType[typeKey] || [];
        if (!items.length) return;
        const groupLabel = (resourceTypeLabels[typeKey] || typeKey).replace(/"/g, '&quot;');
        html += '<optgroup label="' + groupLabel + '">';
        items.forEach(function (res) {
            const label = ((res.erp_code || '') + ' — ' + (res.name || '')).replace(/"/g, '&quot;');
            html += '<option value="' + res.id + '" data-type="' + typeKey + '"'
                + ' data-erp="' + String(res.erp_code || '').replace(/"/g, '&quot;') + '"'
                + ' data-machine="' + (res.machine_cost_per_min || 0) + '"'
                + ' data-energy="' + (res.energy_cost_per_min || 0) + '">'
                + label + '</option>';
        });
        html += '</optgroup>';
    });
    return html;
}

function addResourceRow(opIndex) {
    const tbody = document.querySelector('.op-resource-tbody[data-op-index="' + opIndex + '"]');
    if (!tbody) return;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <code class="small inv-erp-code d-block op-resource-erp-preview d-none"></code>
            <select name="op_resource_id[${opIndex}][]" class="form-select form-select-sm op-resource-select" onchange="onResourceLineChange(this)">${buildResourceOptionsHtml()}</select>
        </td>
        <td class="inv-cell-num"><input type="number" min="1" step="1" name="op_resource_qty[${opIndex}][]" class="form-control form-control-sm op-resource-qty" value="1" oninput="recalcOpLineTotal(${opIndex})"></td>
        <td class="inv-cell-num"><input type="number" step="0.000001" min="0" name="op_resource_machine_cost[${opIndex}][]" class="form-control form-control-sm op-resource-machine" value="0" oninput="recalcOpLineTotal(${opIndex})"></td>
        <td class="inv-cell-num"><input type="number" step="0.000001" min="0" name="op_resource_energy_cost[${opIndex}][]" class="form-control form-control-sm op-resource-energy" value="0" oninput="recalcOpLineTotal(${opIndex})"></td>
        <td class="inv-cell-num text-muted"><span class="inv-cell-readonly">—</span></td>
        <td class="inv-cell-num"><span class="inv-cell-readonly op-resource-subtotal">0,0000</span></td>
        <td class="inv-cell-tag"><span class="badge bg-secondary op-resource-type-badge">—</span></td>
        <td class="inv-cell-act"><button type="button" class="btn btn-link inv-row-remove" title="Remover linha" aria-label="Remover linha" onclick="removeResourceRow(this, ${opIndex})"><i class="fa-regular fa-trash-can"></i></button></td>
    `;
    tbody.appendChild(tr);
    updateSublevelBadge(opIndex, 'resource');
    recalcOpLineTotal(opIndex);
    syncInvRouteTableColumns(getOpCard(opIndex));
}

function buildOperationOptionsHtml() {
    let html = '<option value="">Selecione a operação</option>';
    (operationOptions || []).forEach(function (op) {
        const code = String(op.code || '').replace(/"/g, '&quot;');
        html += '<option value="' + op.id + '" data-code="' + code + '">' + String(op.name || '').replace(/"/g, '&quot;') + '</option>';
    });
    return html;
}

function addLaborRow(opIndex) {
    const tbody = document.querySelector('.op-labor-tbody[data-op-index="' + opIndex + '"]');
    if (!tbody) return;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><select name="op_labor_role_id[${opIndex}][]" class="form-select form-select-sm op-labor-role" onchange="onLaborRoleChange(this)">${buildLaborRoleOptionsHtml()}</select></td>
        <td class="inv-cell-num"><input type="number" min="1" step="1" name="op_labor_qty[${opIndex}][]" class="form-control form-control-sm op-labor-qty" value="1" oninput="recalcOpLineTotal(${opIndex})"></td>
        <td class="inv-cell-num"><span class="inv-cell-readonly op-labor-hh">0,00</span></td>
        <td class="inv-cell-num"><input type="number" step="0.000001" min="0" name="op_labor_cost_per_min[${opIndex}][]" class="form-control form-control-sm op-labor-cost" value="0" oninput="recalcOpLineTotal(${opIndex})"></td>
        <td class="inv-cell-num"><span class="inv-cell-readonly op-labor-subtotal-min fw-semibold">0,0000</span></td>
        <td class="inv-cell-num"><span class="inv-cell-readonly op-labor-subtotal">0,0000</span></td>
        <td class="inv-cell-tag"><span class="badge bg-light text-muted border">MO cad.</span></td>
        <td class="inv-cell-act"><button type="button" class="btn btn-link inv-row-remove" title="Remover linha" aria-label="Remover linha" onclick="removeLaborRow(this, ${opIndex})"><i class="fa-regular fa-trash-can"></i></button></td>
    `;
    tbody.appendChild(tr);
    updateSublevelBadge(opIndex, 'labor');
    recalcOpLineTotal(opIndex);
    syncInvRouteTableColumns(getOpCard(opIndex));
}
function addBomRow() {
    const tbody = document.querySelector('#bom-table tbody');
    if (!tbody) return;
    const tr = document.createElement('tr');
    const bomItems = <?php echo json_encode($this->data['listBomItems'] ?? []); ?>;
    let optionsHtml = '<option value=\"\">Selecione o componente</option>';
    (bomItems || []).forEach(function (item) {
        const label = (item.code + ' - ' + item.description).replace(/"/g, '&quot;');
        optionsHtml += '<option value=\"' + item.id + '\">' + label + '</option>';
    });
    tr.innerHTML = `
        <td data-label="Componente">
            <select name="bom_component_item_id[]" class="form-select form-select-sm">
                ${optionsHtml}
            </select>
        </td>
        <td data-label="Quantidade por lote">
            <input type="number" step="0.000001" min="0" name="bom_quantity_per_batch[]" class="form-control form-control-sm" value="0">
        </td>
        <td data-label="Perda (%)">
            <input type="number" step="0.0001" min="0" name="bom_scrap_percent[]" class="form-control form-control-sm" value="0">
        </td>
        <td data-label="Unidade"><span class="text-muted">Unidade será exibida após salvar</span></td>
        <td data-label="Custo médio">0,000000</td>
        <td data-label="Total (Qtd x Custo)">0,000000</td>
        <td data-label="Ações" class="text-end">
            <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeBomRow(this)">Remover</button>
        </td>
    `;
    tbody.appendChild(tr);
}
function addOperationRow() {
    const list = document.getElementById('operations-list');
    if (!list) return;
    const opIndex = nextOpIndex();
    const seq = list.querySelectorAll('.inv-route-op').length + 1;
    const card = document.createElement('div');
    card.className = 'inv-route-op inv-route-op-card card mb-3 border shadow-sm';
    card.setAttribute('data-op-index', String(opIndex));
    card.innerHTML = `
        <div class="card-header bg-success-subtle py-2 px-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div class="flex-grow-1">
                    <span class="badge bg-secondary me-2">Pos.
                        <input type="number" class="inv-seq-badge-input" name="op_sequence[]" value="${seq}" min="1" step="1" aria-label="Posição">
                    </span>
                    <select name="op_operation_id[]" class="form-select form-select-sm inv-op-select-inline op-operation-select" onchange="onOperationChange(this)">${buildOperationOptionsHtml()}</select>
                    <code class="small ms-1 inv-op-code-display op-operation-code d-none"></code>
                    <button type="button" class="btn btn-link btn-sm text-danger inv-op-remove-btn ms-1" onclick="removeOperationRow(this)" title="Remover operação"><i class="fa-regular fa-trash-can"></i></button>
                </div>
                <div class="text-end small">
                    <div><span class="text-muted">Σ R$/min:</span> <strong class="op-cost-per-min text-dark">0,0000</strong></div>
                    <div><span class="text-muted">Custo linha:</span> <strong class="op-line-total text-success">0,000000</strong></div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-3 mt-2 small text-muted">
                <span>Tempo/lote: <input type="number" step="0.0001" min="0" name="op_time_per_batch_hours[]" class="inv-metric-inline op-time-input" value="0" oninput="recalcOpLineTotal(${opIndex})" aria-label="Tempo por lote"> <select name="op_time_unit[]" class="inv-metric-inline-select op-time-unit" onchange="recalcOpLineTotal(${opIndex})" aria-label="Unidade de tempo"><option value="MIN" selected>Minutos</option><option value="H">Horas</option></select></span>
                <span>Tempo: <strong class="op-time-display text-dark">0,0000</strong> min</span>
                <span>MO SAP: <strong class="op-sum-sap-labor text-dark">0,0000</strong>/min</span>
                <span>Equip.: <strong class="op-sum-equipment text-dark">0,0000</strong>/min</span>
                <span>MO cad.: <strong class="op-sum-manual-labor text-dark">0,0000</strong>/min</span>
                <span>Σ HH: <strong class="op-sum-labor-hh text-dark">0,00</strong></span>
            </div>
            <input type="hidden" name="op_machine_cost_per_min[]" class="op-machine-cost" value="0">
            <input type="hidden" name="op_energy_cost_per_min[]" class="op-energy-cost" value="0">
            <div class="mt-2 small">
                <span class="text-muted">Observações:</span>
                <input type="text" name="op_notes[]" class="inv-notes-inline" value="" aria-label="Observações">
            </div>
        </div>
        <div class="card-body py-2 inv-route-lines-align">
            <div class="ps-3 border-start border-3 border-secondary inv-route-sub-block mb-2">
                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none inv-view-sub-toggle mb-1"
                    data-bs-toggle="collapse" data-bs-target="#op-res-collapse-${opIndex}" aria-expanded="true">
                    <i class="fa-solid fa-chevron-down inv-chevron me-1"></i>
                    <i class="fa-solid fa-industry me-1"></i> Recursos SAP / equipamentos
                    <span class="badge bg-light text-dark border ms-1">1</span>
                </button>
                <div class="collapse show" id="op-res-collapse-${opIndex}">
                    <table class="table table-sm table-bordered inv-route-sub-table bg-white mb-2">
                        <colgroup><col><col><col><col><col><col><col><col></colgroup>
                        <thead class="table-light"><tr><th>Recurso</th><th class="inv-cell-num">Qtd</th><th class="inv-cell-num">Máq./min</th><th class="inv-cell-num">En./min</th><th class="inv-cell-num"></th><th class="inv-cell-num">Subtotal/lote</th><th class="inv-cell-tag">Tipo</th><th class="inv-cell-act" title="Remover linha"><span class="visually-hidden">Excluir</span><i class="fa-regular fa-trash-can text-muted"></i></th></tr></thead>
                        <tbody class="op-resource-tbody" data-op-index="${opIndex}">
                            <tr>
                                <td>
                                    <code class="small inv-erp-code d-block op-resource-erp-preview d-none"></code>
                                    <select name="op_resource_id[${opIndex}][]" class="form-select form-select-sm op-resource-select" onchange="onResourceLineChange(this)">${buildResourceOptionsHtml()}</select>
                                </td>
                                <td class="inv-cell-num"><input type="number" min="1" step="1" name="op_resource_qty[${opIndex}][]" class="form-control form-control-sm op-resource-qty" value="1" oninput="recalcOpLineTotal(${opIndex})"></td>
                                <td class="inv-cell-num"><input type="number" step="0.000001" min="0" name="op_resource_machine_cost[${opIndex}][]" class="form-control form-control-sm op-resource-machine" value="0" oninput="recalcOpLineTotal(${opIndex})"></td>
                                <td class="inv-cell-num"><input type="number" step="0.000001" min="0" name="op_resource_energy_cost[${opIndex}][]" class="form-control form-control-sm op-resource-energy" value="0" oninput="recalcOpLineTotal(${opIndex})"></td>
                                <td class="inv-cell-num text-muted"><span class="inv-cell-readonly">—</span></td>
                                <td class="inv-cell-num"><span class="inv-cell-readonly op-resource-subtotal">0,0000</span></td>
                                <td class="inv-cell-tag"><span class="badge bg-secondary op-resource-type-badge">—</span></td>
                                <td class="inv-cell-act"><button type="button" class="btn btn-link inv-row-remove" title="Remover linha" aria-label="Remover linha" onclick="removeResourceRow(this, ${opIndex})"><i class="fa-regular fa-trash-can"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-link btn-sm p-0 inv-add-row-link mt-1" onclick="addResourceRow(${opIndex})"><i class="fa-solid fa-plus me-1"></i> Recurso</button>
            </div>
            <div class="ps-3 border-start border-3 border-secondary inv-route-sub-block">
                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none inv-view-sub-toggle mb-1"
                    data-bs-toggle="collapse" data-bs-target="#op-labor-collapse-${opIndex}" aria-expanded="true">
                    <i class="fa-solid fa-chevron-down inv-chevron me-1"></i>
                    <i class="fa-solid fa-users me-1"></i> MO cadastrada (papéis)
                    <span class="badge bg-light text-dark border ms-1">1</span>
                </button>
                <div class="collapse show" id="op-labor-collapse-${opIndex}">
                    <table class="table table-sm table-bordered inv-route-sub-table bg-white mb-0">
                        <colgroup><col><col><col><col><col><col><col><col></colgroup>
                        <thead class="table-light"><tr><th>Papel</th><th class="inv-cell-num">Qtd</th><th class="inv-cell-num">HH</th><th class="inv-cell-num">R$/min</th><th class="inv-cell-num">Subtotal/min</th><th class="inv-cell-num">Subtotal/lote</th><th class="inv-cell-tag">Tipo</th><th class="inv-cell-act" title="Remover linha"><span class="visually-hidden">Excluir</span><i class="fa-regular fa-trash-can text-muted"></i></th></tr></thead>
                        <tbody class="op-labor-tbody" data-op-index="${opIndex}">
                            <tr>
                                <td><select name="op_labor_role_id[${opIndex}][]" class="form-select form-select-sm op-labor-role" onchange="onLaborRoleChange(this)">${buildLaborRoleOptionsHtml()}</select></td>
                                <td class="inv-cell-num"><input type="number" min="1" step="1" name="op_labor_qty[${opIndex}][]" class="form-control form-control-sm op-labor-qty" value="1" oninput="recalcOpLineTotal(${opIndex})"></td>
                                <td class="inv-cell-num"><span class="inv-cell-readonly op-labor-hh">0,00</span></td>
                                <td class="inv-cell-num"><input type="number" step="0.000001" min="0" name="op_labor_cost_per_min[${opIndex}][]" class="form-control form-control-sm op-labor-cost" value="0" oninput="recalcOpLineTotal(${opIndex})"></td>
                                <td class="inv-cell-num"><span class="inv-cell-readonly op-labor-subtotal-min fw-semibold">0,0000</span></td>
                                <td class="inv-cell-num"><span class="inv-cell-readonly op-labor-subtotal">0,0000</span></td>
                                <td class="inv-cell-tag"><span class="badge bg-light text-muted border">MO cad.</span></td>
                                <td class="inv-cell-act"><button type="button" class="btn btn-link inv-row-remove" title="Remover linha" aria-label="Remover linha" onclick="removeLaborRow(this, ${opIndex})"><i class="fa-regular fa-trash-can"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-link btn-sm p-0 inv-add-row-link mt-1" onclick="addLaborRow(${opIndex})"><i class="fa-solid fa-plus me-1"></i> Pessoa</button>
            </div>
        </div>
    `;
    list.appendChild(card);
    recalcGrandTotal();
    syncInvRouteTableColumns(card);
}

function updateSublevelBadge(opIndex, type) {
    const card = getOpCard(opIndex);
    if (!card) return;
    const selector = type === 'resource' ? '.op-resource-tbody tr' : '.op-labor-tbody tr';
    const count = card.querySelectorAll(selector).length;
    const targetId = type === 'resource' ? '#op-res-collapse-' + opIndex : '#op-labor-collapse-' + opIndex;
    const toggle = card.querySelector('[data-bs-target="' + targetId + '"] .badge');
    if (toggle) toggle.textContent = String(count);
}

function toggleAllRouteSubs(expand) {
    if (typeof bootstrap === 'undefined') return;
    document.querySelectorAll('#operations-list .collapse').forEach(function (el) {
        const instance = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
        expand ? instance.show() : instance.hide();
    });
}

var INV_ROUTE_COL_PCT = [0.28, 0.07, 0.09, 0.10, 0.10, 0.11, 0.11, 0.07];

function syncInvRouteTableColumns(scope) {
    (scope || document).querySelectorAll('.inv-route-lines-align').forEach(function (wrap) {
        const w = wrap.getBoundingClientRect().width;
        if (w <= 0) return;
        wrap.querySelectorAll('.inv-route-sub-table').forEach(function (table) {
            table.style.width = w + 'px';
            table.style.maxWidth = w + 'px';
            table.style.tableLayout = 'fixed';
            const cols = table.querySelectorAll('colgroup col');
            INV_ROUTE_COL_PCT.forEach(function (pct, i) {
                if (cols[i]) cols[i].style.width = Math.round(w * pct) + 'px';
            });
        });
    });
}

function setupInvRouteColumnSync() {
    const run = function () {
        requestAnimationFrame(function () { syncInvRouteTableColumns(); });
    };
    run();
    window.addEventListener('resize', run);
    const list = document.getElementById('operations-list');
    if (list && typeof ResizeObserver !== 'undefined') {
        new ResizeObserver(run).observe(list);
    }
    document.addEventListener('shown.bs.collapse', function (e) {
        if (e.target.closest && e.target.closest('.inv-route-lines-align')) {
            syncInvRouteTableColumns(e.target.closest('.inv-route-op') || undefined);
        }
    });
    document.addEventListener('hidden.bs.collapse', function (e) {
        if (e.target.closest && e.target.closest('.inv-route-lines-align')) {
            syncInvRouteTableColumns(e.target.closest('.inv-route-op') || undefined);
        }
    });
}

function reindexOperationCards() {
    const list = document.getElementById('operations-list');
    if (!list) return;
    list.querySelectorAll('.inv-route-op').forEach(function (card, newIndex) {
        card.setAttribute('data-op-index', String(newIndex));

        card.querySelectorAll('.op-resource-tbody, .op-labor-tbody').forEach(function (tbody) {
            tbody.setAttribute('data-op-index', String(newIndex));
        });

        ['op-res-collapse-', 'op-labor-collapse-'].forEach(function (prefix) {
            const collapse = card.querySelector('[id^="' + prefix + '"]');
            if (collapse) collapse.id = prefix + newIndex;
            const toggle = card.querySelector('[data-bs-target^="#' + prefix + '"]');
            if (toggle) {
                toggle.setAttribute('data-bs-target', '#' + prefix + newIndex);
                toggle.setAttribute('aria-controls', prefix + newIndex);
            }
        });

        card.querySelectorAll('[name^="op_resource_"], [name^="op_labor_"]').forEach(function (el) {
            const name = el.getAttribute('name') || '';
            el.setAttribute('name', name.replace(/\[\d+\]/, '[' + newIndex + ']'));
        });

        ['oninput', 'onchange', 'onclick'].forEach(function (attr) {
            card.querySelectorAll('[' + attr + '*="recalcOpLineTotal"], [' + attr + '*="removeResourceRow"], [' + attr + '*="removeLaborRow"], [' + attr + '*="addResourceRow"], [' + attr + '*="addLaborRow"], [' + attr + '*="onOperationChange"]').forEach(function (el) {
                const val = el.getAttribute(attr);
                if (!val) return;
                el.setAttribute(attr, val.replace(/recalcOpLineTotal\(\d+\)/g, 'recalcOpLineTotal(' + newIndex + ')')
                    .replace(/removeResourceRow\(this,\s*\d+\)/g, 'removeResourceRow(this, ' + newIndex + ')')
                    .replace(/removeLaborRow\(this,\s*\d+\)/g, 'removeLaborRow(this, ' + newIndex + ')')
                    .replace(/addResourceRow\(\d+\)/g, 'addResourceRow(' + newIndex + ')')
                    .replace(/addLaborRow\(\d+\)/g, 'addLaborRow(' + newIndex + ')'));
            });
        });
    });
}

function getActiveTabPaneId() {
    const active = document.querySelector('.inv-update-tabs .nav-link.active');
    return active ? (active.getAttribute('data-bs-target') || '').replace('#', '') : 'pane-dados-gerais';
}

function showFormAlert(message, type) {
    const box = document.getElementById('form-alerts');
    if (!box) return;
    const safeType = ['success', 'danger', 'warning', 'info'].includes(type) ? type : 'info';
    box.innerHTML = '<div class="alert alert-' + safeType + ' adms-inline-alert" role="alert">' + message + '</div>';
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function activateTabById(paneId) {
    if (!paneId || typeof bootstrap === 'undefined') return;
    const tabBtn = document.querySelector('.inv-update-tabs [data-bs-target="#' + paneId + '"]');
    if (!tabBtn) return;
    bootstrap.Tab.getOrCreateInstance(tabBtn).show();
}

document.addEventListener('DOMContentLoaded', function () {
    const activeTab = document.getElementById('active_tab')?.value || 'pane-dados-gerais';
    activateTabById(activeTab);
    setupInvRouteColumnSync();

    document.querySelectorAll('.inv-route-op').forEach(function (card) {
        const opIndex = parseInt(card.getAttribute('data-op-index') || '0', 10);
        recalcOpLineTotal(opIndex);
    });

    document.querySelectorAll('.inv-update-tabs [data-bs-toggle="tab"]').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function () {
            const paneId = getActiveTabPaneId();
            const hidden = document.getElementById('active_tab');
            if (hidden) hidden.value = paneId;
            if (paneId === 'pane-operations') {
                syncInvRouteTableColumns();
            }
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', paneId);
                history.replaceState(null, '', url.toString());
            } catch (e) { /* ignore */ }
        });
    });

    const form = document.getElementById('form-update-inventory-item');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        reindexOperationCards();
        const activeTabInput = document.getElementById('active_tab');
        if (activeTabInput) activeTabInput.value = getActiveTabPaneId();

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';
        }

        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            const data = await response.json();
            if (data.csrf_token) {
                const csrfInput = form.querySelector('[name="csrf_token"]');
                if (csrfInput) csrfInput.value = data.csrf_token;
            }
            showFormAlert(data.message || (data.success ? 'Salvo com sucesso.' : 'Erro ao salvar.'), data.success ? 'success' : 'danger');
            if (data.success && activeTabInput) {
                const tabToKeep = data.active_tab || activeTabInput.value;
                activeTabInput.value = tabToKeep;
                activateTabById(tabToKeep);
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', tabToKeep);
                    history.replaceState(null, '', url.toString());
                } catch (e) { /* ignore */ }
            }
        } catch (err) {
            showFormAlert('Falha de comunicação ao salvar. Tente novamente.', 'danger');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    });
});
</script>




