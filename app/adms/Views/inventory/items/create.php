<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">

	<div class="mb-1 hstack gap-2">
		<h2 class="mt-3">Item de Estoque</h2>

		<ol class="breadcrumb mb-3 mt-3 ms-auto">
			<li class="breadcrumb-item">
				<a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
			</li>
			<li class="breadcrumb-item">
				<a href="<?php echo $_ENV['URL_ADM']; ?>list-inventory-items" class="text-decoration-none">Itens</a>
			</li>
			<li class="breadcrumb-item">Cadastrar</li>
		</ol>
	</div>

	<div class="card mb-4 border-light shadow">
		<div class="card-header hstack gap-2">
			<span>Cadastrar</span>
			<span class="ms-auto d-sm-flex flex-row">
				<?php if (in_array('ListInventoryItems', $this->data['buttonPermission'])) { echo "<a href='{$_ENV['URL_ADM']}list-inventory-items' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> "; } ?>
			</span>
		</div>

		<div class="card-body">
			<?php include './app/adms/Views/partials/alerts.php'; ?>

			<form action="" method="POST" class="row g-3">
				<input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_inventory_item'); ?>">

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
					<label for="inv_category_id" class="form-label">Grupo de Itens</label>
					<select name="inv_category_id" id="inv_category_id" class="form-select">
						<option value="">Selecione</option>
						<?php foreach (($this->data['listCategories'] ?? []) as $c) { $sel = ((string)($this->data['form']['inv_category_id'] ?? '') === (string)$c['id']) ? 'selected' : ''; echo "<option value='{$c['id']}' $sel>{$c['name']}</option>"; } ?>
					</select>
				</div>

				<div class="col-12 col-md-3">
					<label for="admin_type" class="form-label">Administrar por</label>
					<select name="admin_type" id="admin_type" class="form-select">
						<option value="none" <?php echo (($this->data['form']['admin_type'] ?? 'none') === 'none') ? 'selected' : ''; ?>>Nenhum</option>
						<option value="serial" <?php echo (($this->data['form']['admin_type'] ?? '') === 'serial') ? 'selected' : ''; ?>>Números de série</option>
						<option value="lot" <?php echo (($this->data['form']['admin_type'] ?? '') === 'lot') ? 'selected' : ''; ?>>Lotes</option>
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
					<label for="average_cost" class="form-label">Custo médio (manual)</label>
					<input type="number" step="0.000001" min="0" name="average_cost" id="average_cost" class="form-control" value="<?php echo $this->data['form']['average_cost'] ?? '0'; ?>">
				</div>

				<div class="col-12 col-md-3">
					<label for="last_cost" class="form-label">Último custo</label>
					<input type="number" step="0.000001" min="0" name="last_cost" id="last_cost" class="form-control" value="<?php echo $this->data['form']['last_cost'] ?? '0'; ?>">
				</div>

				<div class="col-12">
					<hr class="my-1">
					<p class="small text-muted mb-0">Parâmetros de rateio CFIX e cadastro complementar (também preenchidos na sincronização SAP).</p>
				</div>

				<div class="col-12 col-md-3">
					<label for="inv_pharma_form_id" class="form-label">Forma farmacêutica</label>
					<select name="inv_pharma_form_id" id="inv_pharma_form_id" class="form-select">
						<option value="">— selecione —</option>
						<?php foreach (($this->data['listPharmaForms'] ?? []) as $pf) {
							$sel = ((string)($this->data['form']['inv_pharma_form_id'] ?? '') === (string)$pf['id']) ? 'selected' : '';
							echo "<option value='{$pf['id']}' $sel>" . htmlspecialchars((string)$pf['name']) . '</option>';
						} ?>
					</select>
					<div class="form-text">Lista importada do SAP (<code>U_FormaFarma</code>). Rode <em>Sincronizar itens</em> para atualizar opções.</div>
				</div>

				<div class="col-12 col-md-3">
					<label for="production_line" class="form-label">Linha de produção</label>
					<?php $productionLine = mb_strtoupper(trim((string)($this->data['form']['production_line'] ?? '')), 'UTF-8'); ?>
					<select name="production_line" id="production_line" class="form-select">
						<option value=""<?= $productionLine === '' ? ' selected' : '' ?>>— não informada</option>
						<option value="TERCEIRO"<?= $productionLine === 'TERCEIRO' ? ' selected' : '' ?>>TERCEIRO</option>
						<option value="TIARAJU"<?= $productionLine === 'TIARAJU' ? ' selected' : '' ?>>TIARAJU (Própria)</option>
					</select>
				</div>

				<div class="col-12 col-md-3">
					<label for="active" class="form-label">Ativo</label>
					<div class="form-check form-switch mt-2">
						<input class="form-check-input" type="checkbox" id="active" name="active" <?php echo !isset($this->data['form']['active']) || $this->data['form']['active'] ? 'checked' : ''; ?> />
						<label class="form-check-label" for="active">Sim</label>
					</div>
				</div>

				<div class="col-12">
					<button type="submit" class="btn btn-success">Salvar</button>
				</div>
			</form>
		</div>
	</div>

</div>


