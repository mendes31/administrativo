<?php

use App\adms\Helpers\CSRFHelper;

?>

<div class="container-fluid px-4">

	<div class="mb-1 hstack gap-2">
		<h2 class="mt-3">Itens de Estoque</h2>

		<ol class="breadcrumb mb-3 mt-3 ms-auto">
			<li class="breadcrumb-item">
				<a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
			</li>
			<li class="breadcrumb-item">Itens</li>
		</ol>
	</div>

	<div class="card mb-4 border-light shadow">
		<div class="card-header hstack gap-2">
			<span>Listar</span>
			<span class="ms-auto">
				<?php if (in_array('CreateInventoryItem', $this->data['buttonPermission'])) {
					echo "<a href='{$_ENV['URL_ADM']}create-inventory-item' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Cadastrar</a> ";
				} ?>
			</span>
		</div>

		<div class="card-body">
			<?php include './app/adms/Views/partials/alerts.php'; ?>

			<form method="get" class="row g-2 mb-3 align-items-end">
				<div class="col-12 col-md-3">
					<label for="code" class="form-label">Código</label>
					<input type="text" name="code" id="code" class="form-control" value="<?php echo htmlspecialchars($_GET['code'] ?? '', ENT_QUOTES); ?>">
				</div>
				<div class="col-12 col-md-5">
					<label for="description" class="form-label">Descrição</label>
					<input type="text" name="description" id="description" class="form-control" value="<?php echo htmlspecialchars($_GET['description'] ?? '', ENT_QUOTES); ?>">
				</div>
				<div class="col-12 col-md-2">
					<label for="active" class="form-label">Ativo</label>
					<select name="active" id="active" class="form-select">
						<option value="">Todos</option>
						<option value="1" <?php echo (($_GET['active'] ?? '') === '1') ? 'selected' : ''; ?>>Ativo</option>
						<option value="0" <?php echo (($_GET['active'] ?? '') === '0') ? 'selected' : ''; ?>>Inativo</option>
					</select>
				</div>
				<div class="col-12 col-md-2">
					<label class="form-label d-none d-md-block">&nbsp;</label>
					<button type="submit" class="btn btn-primary w-100">Filtrar</button>
				</div>
			</form>

			<div class="table-responsive">
				<table class="table table-striped table-hover" id="tabela">
					<thead>
						<tr>
							<th>ID</th>
							<th>Código</th>
							<th>Código ERP</th>
							<th>Descrição</th>
							<th>Unidade</th>
							<th>Categoria</th>
							<th>Admin.</th>
							<th class="text-end">Em estoque</th>
							<th>Ativo</th>
							<th class="text-center">Ações</th>
						</tr>
					</thead>
					<tbody>
						<?php if (!empty($this->data['items'])) { foreach ($this->data['items'] as $item) { ?>
							<tr>
								<td><?php echo $item['id']; ?></td>
								<td><?php echo htmlspecialchars($item['code']); ?></td>
								<td><?php echo htmlspecialchars($item['erp_code'] ?? ''); ?></td>
								<td><?php echo htmlspecialchars($item['description']); ?></td>
								<td><?php echo htmlspecialchars($item['unit_name'] ?? ''); ?></td>
								<td><?php echo htmlspecialchars($item['category_name'] ?? ''); ?></td>
								<td><?php echo ($item['admin_type'] === 'none') ? 'Nenhum' : strtoupper($item['admin_type']); ?></td>
								<td class="text-end"><?php echo number_format((float)($item['total_qty'] ?? 0), 4, ',', '.'); ?></td>
								<td><?php echo $item['active'] ? 'Sim' : 'Não'; ?></td>
								<td class="text-center">
									<?php if (in_array('ViewInventoryItem', $this->data['buttonPermission'])) { echo "<a href='{$_ENV['URL_ADM']}view-inventory-item/{$item['id']}' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Ver</a> "; }
									if (in_array('UpdateInventoryItem', $this->data['buttonPermission'])) { echo "<a href='{$_ENV['URL_ADM']}update-inventory-item/{$item['id']}' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a> "; }
									if (in_array('DeleteInventoryItem', $this->data['buttonPermission'])) {
										$csrf_token = CSRFHelper::generateCSRFToken('form_delete_inventory_item');
										echo "<form action='{$_ENV['URL_ADM']}delete-inventory-item' method='POST' class='d-inline'>";
										echo "<input type='hidden' name='csrf_token' value='{$csrf_token}'>";
										echo "<input type='hidden' name='id' value='{$item['id']}'>";
										echo "<button type='submit' class='btn btn-danger btn-sm me-1 mb-1'><i class='fa-regular fa-trash-can'></i> Apagar</button>";
										echo "</form>";
									}
									?>
							</td>
							</tr>
						<?php } } ?>
					</tbody>
				</table>
				<?php echo $this->data['paginator'] ?? ''; ?>
			</div>
		</div>
	</div>

</div>




