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

			<form action="" method="POST" class="row g-3">
				<input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_inventory_item'); ?>">

				<ul class="nav nav-tabs mb-3 inv-update-tabs" role="tablist">
					<li class="nav-item" role="presentation">
						<button class="nav-link active" id="tab-dados-gerais" data-bs-toggle="tab" data-bs-target="#pane-dados-gerais" type="button" role="tab">Dados gerais</button>
					</li>
					<li class="nav-item" role="presentation">
						<button class="nav-link" id="tab-bom" data-bs-toggle="tab" data-bs-target="#pane-bom" type="button" role="tab">Lista de materiais</button>
					</li>
					<li class="nav-item" role="presentation">
						<button class="nav-link" id="tab-operations" data-bs-toggle="tab" data-bs-target="#pane-operations" type="button" role="tab">Rota</button>
					</li>
				</ul>

				<div class="tab-content">
					<div class="tab-pane fade show active" id="pane-dados-gerais" role="tabpanel" aria-labelledby="tab-dados-gerais">
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
								<input type="number" step="0.000001" min="0" name="average_cost" id="average_cost" class="form-control" value="<?php echo (string)($this->data['form']['average_cost'] ?? '0'); ?>" readonly title="Calculado pela soma da Lista de materiais e da Rota">
							</div>

							<div class="col-12 col-md-3">
								<label for="last_cost" class="form-label">Último custo</label>
								<input type="number" step="0.000001" min="0" name="last_cost" id="last_cost" class="form-control" value="<?php echo (string)($this->data['form']['last_cost'] ?? '0'); ?>" readonly title="Calculado pela soma da Lista de materiais e da Rota">
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

					<div class="tab-pane fade" id="pane-bom" role="tabpanel" aria-labelledby="tab-bom">
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
										<td colspan="5" class="text-end"><strong>Custo total dos componentes:</strong></td>
										<td colspan="2"><strong><?= number_format($totalMaterialCost, 6, ',', '.') ?></strong></td>
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

					<div class="tab-pane fade" id="pane-operations" role="tabpanel" aria-labelledby="tab-operations">
						<div class="table-responsive inv-edit-responsive-table">
							<table class="table table-sm align-middle" id="operations-table">
								<thead class="thead-green">
									<tr>
										<th style="width: 8%;">Seq.</th>
										<th style="width: 28%;">Operação</th>
										<th style="width: 12%;">Tempo por lote</th>
										<th style="width: 8%;">Unid.</th>
										<th style="width: 8%;">Oper.</th>
										<th style="width: 10%;">MO/min</th>
										<th style="width: 10%;">Máq./min</th>
										<th style="width: 10%;">Energia/min</th>
										<th style="width: 12%;">Total (Tempo x Custo)</th>
										<th style="width: 10%;">Observações</th>
										<th style="width: 5%;" class="text-end">Ações</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$totalOperationsCost = 0.0;
									foreach (($this->data['operations'] ?? []) as $idx => $op):
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
										$timeMinutes = $timeUnit === 'H' ? $timeValue * 60.0 : $timeValue;
										$costPerMinuteFromRoute = ($laborCostPerMin * $operatorsQty) + $machineCostPerMin + $energyCostPerMin;
										if ($costPerMinuteFromRoute > 0) {
											$rowTotal = $timeMinutes * $costPerMinuteFromRoute;
										} else {
											$rowTotal = ($timeMinutes / 60.0) * $costHour;
										}
										$totalOperationsCost += $rowTotal;
										?>
										<tr>
											<td data-label="Seq.">
												<input type="number" class="form-control form-control-sm" name="op_sequence[]" value="<?= (int)($op['sequence'] ?? ($idx + 1)) ?>">
											</td>
											<td data-label="Operação">
												<select name="op_operation_id[]" class="form-select form-select-sm">
													<option value="">Selecione</option>
													<?php foreach (($this->data['listOperations'] ?? []) as $operation) {
														$sel = ((string)($op['inv_operation_id'] ?? '') === (string)$operation['id']) ? 'selected' : '';
														echo "<option value='{$operation['id']}' {$sel}>".htmlspecialchars($operation['name'])."</option>";
													} ?>
												</select>
											</td>
											<td data-label="Tempo por lote">
												<input type="number" step="0.0001" min="0" name="op_time_per_batch_hours[]" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($op['time_per_batch_hours'] ?? '0')) ?>">
											</td>
											<td data-label="Unid.">
												<select name="op_time_unit[]" class="form-select form-select-sm">
													<?php
													$unit = $timeUnit;
													$options = [
														'MIN' => 'Minutos',
														'H' => 'Horas',
													];
													foreach ($options as $value => $label) {
														$sel = $value === $unit ? 'selected' : '';
														echo "<option value=\"{$value}\" {$sel}>".htmlspecialchars($label)."</option>";
													}
													?>
												</select>
											</td>
											<td data-label="Oper.">
												<input type="number" min="1" step="1" name="op_operators_qty[]" class="form-control form-control-sm" value="<?= (int)$operatorsQty ?>">
											</td>
											<td data-label="MO/min">
												<input type="number" step="0.000001" min="0" name="op_labor_cost_per_min[]" class="form-control form-control-sm" value="<?= htmlspecialchars((string)$laborCostPerMin) ?>" title="Custo de mão de obra por minuto (por operador)">
											</td>
											<td data-label="Máq./min">
												<input type="number" step="0.000001" min="0" name="op_machine_cost_per_min[]" class="form-control form-control-sm" value="<?= htmlspecialchars((string)$machineCostPerMin) ?>" title="Custo de máquina por minuto">
											</td>
											<td data-label="Energia/min">
												<input type="number" step="0.000001" min="0" name="op_energy_cost_per_min[]" class="form-control form-control-sm" value="<?= htmlspecialchars((string)$energyCostPerMin) ?>" title="Custo de energia por minuto">
											</td>
											<td data-label="Total (Tempo x Custo)"><?= number_format($rowTotal, 6, ',', '.') ?></td>
											<td data-label="Observações">
												<input type="text" name="op_notes[]" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($op['notes'] ?? '')) ?>">
											</td>
											<td data-label="Ações" class="text-end">
												<button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeOperationRow(this)">Remover</button>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
								<tfoot>
									<tr>
										<td colspan="8" class="text-end"><strong>Custo total das operações:</strong></td>
										<td colspan="3"><strong><?= number_format($totalOperationsCost, 6, ',', '.') ?></strong></td>
									</tr>
								</tfoot>
							</table>
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
function removeOperationRow(btn) {
    const row = btn.closest('tr');
    if (row) row.remove();
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
    const tbody = document.querySelector('#operations-table tbody');
    if (!tbody) return;
    const index = tbody.querySelectorAll('tr').length;
    const tr = document.createElement('tr');
    const operations = <?php echo json_encode($this->data['listOperations'] ?? []); ?>;
    let optionsHtml = '<option value=\"\">Selecione</option>';
    (operations || []).forEach(function (op) {
        optionsHtml += '<option value=\"' + op.id + '\">' + op.name.replace(/"/g, '&quot;') + '</option>';
    });
    tr.innerHTML = `
        <td data-label="Seq."><input type="number" class="form-control form-control-sm" name="op_sequence[]" value="${index + 1}"></td>
        <td data-label="Operação">
            <select name="op_operation_id[]" class="form-select form-select-sm">
                ${optionsHtml}
            </select>
        </td>
        <td data-label="Tempo por lote"><input type="number" step="0.0001" min="0" name="op_time_per_batch_hours[]" class="form-control form-control-sm" value="0"></td>
        <td data-label="Unid.">
            <select name="op_time_unit[]" class="form-select form-select-sm">
                <option value="MIN" selected>Minutos</option>
                <option value="H">Horas</option>
            </select>
        </td>
        <td data-label="Oper."><input type="number" min="1" step="1" name="op_operators_qty[]" class="form-control form-control-sm" value="1"></td>
        <td data-label="MO/min"><input type="number" step="0.000001" min="0" name="op_labor_cost_per_min[]" class="form-control form-control-sm" value="0"></td>
        <td data-label="Máq./min"><input type="number" step="0.000001" min="0" name="op_machine_cost_per_min[]" class="form-control form-control-sm" value="0"></td>
        <td data-label="Energia/min"><input type="number" step="0.000001" min="0" name="op_energy_cost_per_min[]" class="form-control form-control-sm" value="0"></td>
        <td data-label="Total (Tempo x Custo)">0,000000</td>
        <td data-label="Observações"><input type="text" name="op_notes[]" class="form-control form-control-sm" value=""></td>
        <td data-label="Ações" class="text-end">
            <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeOperationRow(this)">Remover</button>
        </td>
    `;
    tbody.appendChild(tr);
}
</script>




