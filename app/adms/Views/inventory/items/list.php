<?php

use App\adms\Helpers\CSRFHelper;

$csrf_token_delete = CSRFHelper::generateCSRFToken('form_delete_inventory_item');

?>

<div class="container-fluid px-4">

	<div class="mb-1 hstack gap-2">
		<h2 class="mt-3">Itens de Estoque</h2>

		<ol class="breadcrumb mb-3 ms-auto">
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

			<div class="d-md-none mb-2">
				<button
					class="btn btn-outline-primary btn-sm"
					type="button"
					data-bs-toggle="collapse"
					data-bs-target="#inventoryItemsFiltersCollapse"
					aria-expanded="false"
					aria-controls="inventoryItemsFiltersCollapse"
				>
					<i class="fa fa-filter me-1"></i> Abrir filtros
				</button>
			</div>

			<div class="collapse d-md-block" id="inventoryItemsFiltersCollapse">
				<form method="get" class="row g-2 mb-2 align-items-end inventory-items-list-filters">
					<div class="col-6 col-sm-4 col-md-2 col-xl-2">
						<label for="code" class="form-label inventory-items-list-filters-label">Código</label>
						<input type="text" name="code" id="code" class="form-control inventory-items-list-filters-control" value="<?= htmlspecialchars($this->data['filtros']['code'] ?? '') ?>">
					</div>
					<div class="col-12 col-sm-8 col-md-4 col-xl-3">
						<label for="description" class="form-label inventory-items-list-filters-label">Descrição</label>
						<input type="text" name="description" id="description" class="form-control inventory-items-list-filters-control" value="<?= htmlspecialchars($this->data['filtros']['description'] ?? '') ?>">
					</div>
					<div class="col-6 col-sm-4 col-md-2 col-xl-2">
						<label for="categoria_id" class="form-label inventory-items-list-filters-label">Categoria</label>
						<select name="categoria_id" id="categoria_id" class="form-select inventory-items-list-filters-control">
							<option value="">Todas</option>
							<?php foreach ($this->data['categories'] ?? [] as $cat): ?>
								<option value="<?= (int) $cat['id'] ?>" <?= ($this->data['filtros']['categoria_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
									<?= htmlspecialchars($cat['name']) ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-6 col-sm-4 col-md-2 col-xl-2">
						<label for="active" class="form-label inventory-items-list-filters-label">Ativo</label>
						<select name="active" id="active" class="form-select inventory-items-list-filters-control">
							<option value="">Todos</option>
							<option value="1" <?= ($this->data['filtros']['active'] ?? '') === '1' ? 'selected' : '' ?>>Ativo</option>
							<option value="0" <?= ($this->data['filtros']['active'] ?? '') === '0' ? 'selected' : '' ?>>Inativo</option>
						</select>
					</div>
					<div class="col-6 col-sm-4 col-md-2 col-xl-2">
						<label for="per_page" class="form-label inventory-items-list-filters-label">Mostrar</label>
						<div class="d-flex align-items-center">
							<select name="per_page" id="per_page" class="form-select inventory-items-list-filters-control me-2" onchange="this.form.submit()">
								<?php foreach ([10, 20, 50, 100] as $opt): ?>
									<option value="<?= $opt ?>" <?= ($this->data['per_page'] ?? 10) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
								<?php endforeach; ?>
							</select>
							<span class="form-label mb-0 inventory-items-list-filters-hint">registros</span>
						</div>
					</div>
					<div class="col-12 col-sm-auto d-flex gap-2 flex-wrap align-items-end inventory-items-list-filters-actions">
						<button type="submit" class="btn btn-primary btn-sm inventory-items-list-filters-btn"><i class="fa fa-search"></i> Filtrar</button>
						<a href="?limpar_filtros=1" class="btn btn-secondary btn-sm inventory-items-list-filters-btn"><i class="fa fa-times"></i> Limpar</a>
					</div>
				</form>
			</div>

			<?php if ($this->data['items'] ?? false) { ?>

			<div class="table-responsive d-none d-md-block list-desktop">
				<table class="table table-striped table-hover table-inventory-items-desktop table-inventory-items-desktop-header">
					<thead>
						<tr>
							<th scope="col" style="width: 4%;">ID</th>
							<th scope="col" style="width: 9%;">Código</th>
							<th scope="col" style="width: 9%;" class="d-none d-lg-table-cell">Cód. ERP</th>
							<th scope="col" style="width: 22%;">Descrição</th>
							<th scope="col" style="width: 7%;" class="d-none d-md-table-cell">Unidade</th>
							<th scope="col" style="width: 12%;" class="d-none d-md-table-cell">Categoria</th>
							<th scope="col" style="width: 7%;" class="d-none d-lg-table-cell">Admin.</th>
							<th scope="col" style="width: 9%;" class="text-end">Em estoque</th>
							<th scope="col" style="width: 7%;" class="text-center">Ativo</th>
							<th scope="col" class="text-center text-nowrap table-inventory-items-desktop-actions">Ações</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($this->data['items'] as $item) {
							$isInactive = empty($item['active']);
							$totalQty = (float) ($item['total_qty'] ?? 0);
							$isZeroStock = $totalQty <= 0;
							$rowClass = $isInactive ? 'table-danger' : ($isZeroStock ? 'table-warning' : '');
							$adminLabel = ($item['admin_type'] === 'none') ? 'Nenhum' : strtoupper($item['admin_type']);
						?>
						<tr class="<?= $rowClass ?>">
							<th class="text-center"><?= (int) $item['id'] ?></th>
							<td class="text-truncate" title="<?= htmlspecialchars($item['code']) ?>"><?= htmlspecialchars($item['code']) ?></td>
							<td class="d-none d-lg-table-cell text-truncate" title="<?= htmlspecialchars($item['erp_code'] ?? '') ?>"><?= htmlspecialchars($item['erp_code'] ?? '—') ?></td>
							<td class="text-truncate" title="<?= htmlspecialchars($item['description']) ?>"><?= htmlspecialchars($item['description']) ?></td>
							<td class="d-none d-md-table-cell"><?= htmlspecialchars($item['unit_name'] ?? '—') ?></td>
							<td class="d-none d-md-table-cell text-truncate" title="<?= htmlspecialchars($item['category_name'] ?? '') ?>"><?= htmlspecialchars($item['category_name'] ?? '—') ?></td>
							<td class="d-none d-lg-table-cell"><?= htmlspecialchars($adminLabel) ?></td>
							<td class="text-end"><?= number_format($totalQty, 4, ',', '.') ?></td>
							<td class="text-center">
								<span class="badge <?= $item['active'] ? 'bg-success' : 'bg-danger' ?>"><?= $item['active'] ? 'Sim' : 'Não' ?></span>
							</td>
							<td class="text-center table-inventory-items-desktop-actions">
								<div class="btn-group btn-group-sm" role="group">
									<?php
									if (in_array('ViewInventoryItem', $this->data['buttonPermission'])) {
										echo "<a href='{$_ENV['URL_ADM']}view-inventory-item/{$item['id']}' class='btn btn-info btn-sm' title='Visualizar'><i class='fa-regular fa-eye'></i></a>";
									}
									if (in_array('UpdateInventoryItem', $this->data['buttonPermission'])) {
										echo "<a href='{$_ENV['URL_ADM']}update-inventory-item/{$item['id']}' class='btn btn-warning btn-sm' title='Editar'><i class='fa-regular fa-pen-to-square'></i></a>";
									}
									if (in_array('DeleteInventoryItem', $this->data['buttonPermission'])) {
									?>
									<form action="<?= $_ENV['URL_ADM'] ?>delete-inventory-item" method="POST" class="d-inline">
										<input type="hidden" name="csrf_token" value="<?= $csrf_token_delete ?>">
										<input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
										<button type="submit" class="btn btn-danger btn-sm" title="Apagar" onclick="return confirm('Deseja realmente apagar este item?')"><i class="fa-regular fa-trash-can"></i></button>
									</form>
									<?php } ?>
								</div>
							</td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>

			<div class="d-block d-md-none list-mobile">
				<?php foreach ($this->data['items'] as $i => $item) {
					$isInactive = empty($item['active']);
					$totalQty = (float) ($item['total_qty'] ?? 0);
					$isZeroStock = $totalQty <= 0;
					$adminLabel = ($item['admin_type'] === 'none') ? 'Nenhum' : strtoupper($item['admin_type']);
					$cardBorder = $isInactive ? 'border-danger' : ($isZeroStock ? 'border-warning' : '');
				?>
				<div class="card mb-3 shadow-sm <?= $cardBorder ?>">
					<div class="card-body">
						<div class="d-flex justify-content-between align-items-start gap-2 mb-2">
							<h5 class="card-title mb-0 text-break">
								<b><?= htmlspecialchars($item['code']) ?></b>
							</h5>
							<span class="text-muted small text-nowrap">ID: <?= (int) $item['id'] ?></span>
						</div>
						<div class="small mb-2 text-break"><?= htmlspecialchars($item['description']) ?></div>
						<div class="mb-1"><b>Ativo:</b>
							<span class="badge <?= $item['active'] ? 'bg-success' : 'bg-danger' ?>"><?= $item['active'] ? 'Sim' : 'Não' ?></span>
						</div>
						<div class="mb-1"><b>Em estoque:</b> <?= number_format($totalQty, 4, ',', '.') ?></div>
						<button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#cardItemDetails<?= $i ?>" aria-expanded="false" aria-controls="cardItemDetails<?= $i ?>">Ver mais</button>
						<div class="collapse mt-2" id="cardItemDetails<?= $i ?>">
							<?php if (!empty($item['erp_code'])) { ?>
								<div><b>Código ERP:</b> <?= htmlspecialchars($item['erp_code']) ?></div>
							<?php } ?>
							<div><b>Unidade:</b> <?= htmlspecialchars($item['unit_name'] ?? '—') ?></div>
							<div><b>Categoria:</b> <?= htmlspecialchars($item['category_name'] ?? '—') ?></div>
							<div><b>Admin.:</b> <?= htmlspecialchars($adminLabel) ?></div>
							<div class="mt-2 d-flex flex-wrap gap-1">
								<?php
								if (in_array('ViewInventoryItem', $this->data['buttonPermission'])) {
									echo "<a href='{$_ENV['URL_ADM']}view-inventory-item/{$item['id']}' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a> ";
								}
								if (in_array('UpdateInventoryItem', $this->data['buttonPermission'])) {
									echo "<a href='{$_ENV['URL_ADM']}update-inventory-item/{$item['id']}' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-regular fa-pen-to-square'></i> Editar</a> ";
								}
								if (in_array('DeleteInventoryItem', $this->data['buttonPermission'])) {
								?>
								<form action="<?= $_ENV['URL_ADM'] ?>delete-inventory-item" method="POST" class="d-inline">
									<input type="hidden" name="csrf_token" value="<?= $csrf_token_delete ?>">
									<input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
									<button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="return confirm('Deseja realmente apagar este item?')"><i class="fa-regular fa-trash-can"></i> Apagar</button>
								</form>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
				<?php } ?>

				<div class="d-flex d-md-none flex-column align-items-center w-100 mt-2">
					<div class="text-secondary small w-100 text-center mb-1">
						<?php if (!empty($this->data['pagination']['total'])): ?>
							Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> de <?= $this->data['pagination']['total'] ?> registro(s)
						<?php else: ?>
							Exibindo <?= count($this->data['items']); ?> registro(s) nesta página.
						<?php endif; ?>
					</div>
					<div class="w-100 d-flex justify-content-center">
						<?php
						$paginationHtml = $this->data['pagination']['html'] ?? '';
						if ($paginationHtml) {
							$paginationHtml = str_replace(
								['>Primeiro<', '>Anterior<', '>Próximo<', '>Último<'],
								['>&laquo;<', '>&lsaquo;<', '>&rsaquo;<', '>&raquo;<'],
								$paginationHtml
							);
							$paginationHtml = preg_replace('/class="pagination(.*?)"/', 'class="pagination pagination-sm$1"', $paginationHtml, 1);
							echo $paginationHtml;
						}
						?>
					</div>
				</div>
			</div>

			<div class="w-100 mt-2 d-none d-md-flex justify-content-between align-items-center">
				<div class="text-secondary small">
					<?php if (!empty($this->data['pagination']['total'])): ?>
						Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> de <?= $this->data['pagination']['total'] ?> registro(s)
					<?php else: ?>
						Exibindo <?= count($this->data['items']); ?> registro(s) nesta página.
					<?php endif; ?>
				</div>
				<div>
					<?= $this->data['pagination']['html'] ?? '' ?>
				</div>
			</div>

			<?php } else {
				echo "<div class='alert alert-danger' role='alert'>Nenhum item encontrado.</div>";
			} ?>
		</div>
	</div>
</div>

<style>
.inventory-items-list-filters .inventory-items-list-filters-label {
	font-size: 0.6875rem;
	font-weight: 600;
	color: var(--bs-secondary-color, #6c757d);
	margin-bottom: 0.125rem;
	line-height: 1.2;
}

.inventory-items-list-filters .inventory-items-list-filters-control {
	font-size: 0.75rem;
	line-height: 1.25;
	padding: 0.15rem 0.4rem;
	min-height: calc(1.25em + 0.3rem + 2px);
}

.inventory-items-list-filters .inventory-items-list-filters-hint {
	font-size: 0.6875rem;
	color: var(--bs-secondary-color, #6c757d);
}

.inventory-items-list-filters .inventory-items-list-filters-btn {
	font-size: 0.75rem;
	padding: 0.2rem 0.55rem;
}

.inventory-items-list-filters.row > div {
	display: flex;
	flex-direction: column;
}

.inventory-items-list-filters.row > .inventory-items-list-filters-actions {
	flex-direction: row;
	justify-content: flex-start;
	align-items: flex-end;
}

@media (min-width: 768px) {
	.inventory-items-list-filters.row > .inventory-items-list-filters-actions {
		margin-left: auto;
		justify-content: flex-end;
	}
}

@media (max-width: 767.98px) {
	.inventory-items-list-filters.row > div {
		margin-bottom: 0.35rem;
	}
}

.table-inventory-items-desktop-header > thead > tr > th {
	background: linear-gradient(135deg, #2E9263 0%, #2C844B 55%, #236D3D 100%) !important;
	color: #ffffff !important;
	border-color: rgba(255, 255, 255, 0.2) !important;
	font-weight: 600;
	font-size: 0.8125rem;
	padding-top: 0.45rem;
	padding-bottom: 0.45rem;
}

.table-inventory-items-desktop {
	font-size: 0.9rem;
	table-layout: fixed;
}

.table-inventory-items-desktop tbody th,
.table-inventory-items-desktop tbody td {
	padding: 0.5rem 0.25rem;
	vertical-align: middle;
}

.table-inventory-items-desktop .text-truncate {
	max-width: 100%;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.btn-group .btn {
	width: 32px;
	height: 32px;
	padding: 0.25rem;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 0;
}

.btn-group .btn:first-child {
	border-top-left-radius: 0.375rem;
	border-bottom-left-radius: 0.375rem;
}

.btn-group .btn:last-child {
	border-top-right-radius: 0.375rem;
	border-bottom-right-radius: 0.375rem;
}

.btn-group .btn i {
	font-size: 0.875rem;
}

.list-desktop.table-responsive {
	overflow-x: auto;
	-webkit-overflow-scrolling: touch;
}

.table-inventory-items-desktop thead th.table-inventory-items-desktop-actions {
	width: 1%;
	min-width: 108px;
	white-space: nowrap;
	position: sticky;
	right: 0;
	z-index: 3;
	box-shadow: -6px 0 10px -6px rgba(0, 0, 0, 0.2);
}

.table-inventory-items-desktop tbody td.table-inventory-items-desktop-actions {
	width: 1%;
	min-width: 108px;
	white-space: nowrap;
	position: sticky;
	right: 0;
	z-index: 2;
	background-color: var(--bs-body-bg, #fff);
	box-shadow: -6px 0 8px -6px rgba(0, 0, 0, 0.12);
}

.table-inventory-items-desktop.table-striped > tbody > tr:nth-of-type(odd) > td.table-inventory-items-desktop-actions {
	background-color: var(--bs-table-striped-bg, rgba(0, 0, 0, 0.05));
}

.table-inventory-items-desktop.table-hover > tbody > tr:hover > td.table-inventory-items-desktop-actions {
	background-color: var(--bs-table-hover-bg, rgba(0, 0, 0, 0.075));
}

.table-inventory-items-desktop tbody tr.table-danger > td.table-inventory-items-desktop-actions {
	background-color: var(--bs-danger-bg-subtle, #f8d7da);
}

.table-inventory-items-desktop tbody tr.table-warning > td.table-inventory-items-desktop-actions {
	background-color: var(--bs-warning-bg-subtle, #fff3cd);
}

@media (min-width: 768px) and (max-width: 1199px) {
	.table-inventory-items-desktop {
		font-size: 0.85rem;
	}

	.table-inventory-items-desktop tbody th,
	.table-inventory-items-desktop tbody td {
		padding: 0.375rem 0.125rem;
	}

	.btn-group .btn {
		width: 28px;
		height: 28px;
	}
}

@media (min-width: 1200px) {
	.table-inventory-items-desktop tbody th,
	.table-inventory-items-desktop tbody td {
		padding: 0.625rem 0.375rem;
	}
}
</style>
