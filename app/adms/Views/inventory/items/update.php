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
			<li class="breadcrumb-item">Editar</li>
		</ol>
	</div>

	<div class="card mb-4 border-light shadow">
		<div class="card-header hstack gap-2">
			<span>Editar</span>
			<span class="ms-auto d-sm-flex flex-row">
				<?php if (in_array('ListInventoryItems', $this->data['buttonPermission'])) { echo "<a href='{$_ENV['URL_ADM']}list-inventory-items' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> "; }
				if (in_array('ViewInventoryItem', $this->data['buttonPermission']) && !empty($this->data['form']['id'])) { echo "<a href='{$_ENV['URL_ADM']}view-inventory-item/{$this->data['form']['id']}' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Ver</a> "; } ?>
			</span>
		</div>

		<div class="card-body">
			<?php include './app/adms/Views/partials/alerts.php'; ?>

			<form action="" method="POST" class="row g-3">
				<input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_inventory_item'); ?>">

				<div class="col-12 col-md-3">
					<label for="code" class="form-label">Código</label>
					<input type="text" name="code" id="code" class="form-control" value="<?php echo $this->data['form']['code'] ?? ''; ?>">
				</div>

				<div class="col-12 col-md-9">
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
					<label for="active" class="form-label">Ativo</label>
					<div class="form-check form-switch mt-2">
						<?php $active = (isset($this->data['form']['active']) ? (int)$this->data['form']['active'] : 1) ? 'checked' : ''; ?>
						<input class="form-check-input" type="checkbox" id="active" name="active" <?php echo $active; ?> />
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




