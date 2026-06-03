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
		<div class="card-header hstack gap-2 flex-wrap align-items-center">
			<span>Listar</span>
			<span class="ms-auto d-flex flex-wrap gap-1">
				<form action="" method="POST" class="d-inline">
					<input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_sync_inventory_items'); ?>">
					<input type="hidden" name="sync_sap_items" value="1">
					<button type="submit" class="btn btn-outline-primary btn-sm">
						<i class="fa-solid fa-rotate"></i> Sincronizar
					</button>
				</form>
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

			<?php
			$items = $this->data['items'] ?? [];
			$paginatorHtml = $this->data['paginator'] ?? '';
			$paginatorMobile = $paginatorHtml;
			if ($paginatorMobile !== '') {
				$paginatorMobile = str_replace(
					['>Primeiro<', '>Anterior<', '>Próximo<', '>Último<'],
					['>&laquo;<', '>&lsaquo;<', '>&rsaquo;<', '>&raquo;<'],
					$paginatorMobile
				);
				$paginatorMobile = preg_replace('/class="pagination(.*?)"/', 'class="pagination pagination-sm$1"', $paginatorMobile, 1);
			}
			?>

			<?php if (!empty($items)) { ?>

			<div class="table-responsive d-none d-md-block list-desktop">
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
						<?php foreach ($items as $item) { ?>
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
									<div class="d-inline-flex flex-wrap gap-1 justify-content-center">
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
									</div>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>

			<div class="d-block d-md-none list-mobile">
				<?php foreach ($items as $item) {
					$adminLabel = ($item['admin_type'] === 'none') ? 'Nenhum' : strtoupper($item['admin_type']);
					?>
				<div class="card mb-2 shadow-sm">
					<div class="card-body py-3">
						<div class="d-flex justify-content-between align-items-start gap-2 mb-2">
							<strong class="text-break"><?php echo htmlspecialchars($item['code']); ?></strong>
							<span class="text-muted small text-nowrap">ID: <?php echo (int)$item['id']; ?></span>
						</div>
						<div class="small mb-2 text-break"><?php echo htmlspecialchars($item['description']); ?></div>
						<?php if (!empty($item['erp_code'])) { ?>
							<div class="small"><span class="text-muted">Código ERP:</span> <?php echo htmlspecialchars($item['erp_code']); ?></div>
						<?php } ?>
						<div class="small"><span class="text-muted">Unidade:</span> <?php echo htmlspecialchars($item['unit_name'] ?? '—'); ?></div>
						<div class="small"><span class="text-muted">Categoria:</span> <?php echo htmlspecialchars($item['category_name'] ?? '—'); ?></div>
						<div class="small"><span class="text-muted">Admin.:</span> <?php echo htmlspecialchars($adminLabel); ?></div>
						<div class="small"><span class="text-muted">Em estoque:</span> <?php echo number_format((float)($item['total_qty'] ?? 0), 4, ',', '.'); ?></div>
						<div class="small mb-2"><span class="text-muted">Ativo:</span> <?php echo $item['active'] ? 'Sim' : 'Não'; ?></div>
						<div class="d-flex flex-wrap gap-1">
							<?php if (in_array('ViewInventoryItem', $this->data['buttonPermission'])) { echo "<a href='{$_ENV['URL_ADM']}view-inventory-item/{$item['id']}' class='btn btn-primary btn-sm'><i class='fa-regular fa-eye'></i> Ver</a> "; }
							if (in_array('UpdateInventoryItem', $this->data['buttonPermission'])) { echo "<a href='{$_ENV['URL_ADM']}update-inventory-item/{$item['id']}' class='btn btn-warning btn-sm'><i class='fa-solid fa-pen-to-square'></i> Editar</a> "; }
							if (in_array('DeleteInventoryItem', $this->data['buttonPermission'])) {
								$csrf_token = CSRFHelper::generateCSRFToken('form_delete_inventory_item');
								echo "<form action='{$_ENV['URL_ADM']}delete-inventory-item' method='POST' class='d-inline'>";
								echo "<input type='hidden' name='csrf_token' value='{$csrf_token}'>";
								echo "<input type='hidden' name='id' value='{$item['id']}'>";
								echo "<button type='submit' class='btn btn-danger btn-sm'><i class='fa-regular fa-trash-can'></i> Apagar</button>";
								echo "</form>";
							}
							?>
						</div>
					</div>
				</div>
				<?php } ?>

				<?php if ($paginatorMobile !== '') { ?>
				<div class="d-flex flex-column align-items-center w-100 mt-2">
					<div class="text-secondary small w-100 text-center mb-1">
						Exibindo <?php echo count($items); ?> registro(s) nesta página.
					</div>
					<div class="w-100 d-flex justify-content-center overflow-auto">
						<?php echo $paginatorMobile; ?>
					</div>
				</div>
				<?php } ?>
			</div>

			<?php if ($paginatorHtml !== '') { ?>
			<div class="d-none d-md-flex justify-content-end mt-3 list-desktop">
				<?php echo $paginatorHtml; ?>
			</div>
			<?php } ?>

			<?php } else { ?>
				<div class="alert alert-warning mb-0" role="alert">Nenhum item encontrado.</div>
			<?php } ?>
		</div>
	</div>

</div>




