<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\InvInventoryItemListNavHelper;

$csrf_token_delete = CSRFHelper::generateCSRFToken('form_delete_inventory_item');
$listNavQuery = (string)($this->data['list_nav_query'] ?? '');
$inventoryItemNavUrl = static function (int $itemId, string $route) use ($listNavQuery): string {
    return InvInventoryItemListNavHelper::appendQueryToUrl(
        ($_ENV['URL_ADM'] ?? '') . $route . '/' . $itemId,
        $listNavQuery
    );
};

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
			<span class="ms-auto d-flex flex-wrap gap-1 align-items-center inv-sap-sync-toolbar">
				<input type="text" id="sync-filter-code" class="form-control form-control-sm" style="width:7.5rem"
					placeholder="Cód. ERP" title="Opcional: limitar sincronização a este item"
					value="<?php echo htmlspecialchars((string)($this->data['filtros']['code'] ?? ''), ENT_QUOTES); ?>">
				<select id="sync-filter-group" class="form-select form-select-sm" style="width:auto" title="Opcional: limitar a itens deste grupo SAP">
					<option value="">Grupo SAP</option>
					<?php foreach (['100', '200', '300', '400', '600', '700', '1000'] as $grp) {
						echo '<option value="' . $grp . '">' . $grp . '</option>';
					} ?>
				</select>
				<form action="" method="POST" class="d-inline" id="form-sync-inventory-all"
					data-sync-title="Sincronizando com o SAP"
					data-sync-type="all"
					onsubmit="return confirm('Sincronizar itens (incremental) e estruturas pendentes com o SAP?\n\nCom código ERP informado, sincroniza catálogo + BOM/rota + PIs dependentes do item.\n\nPode levar vários minutos. Uma barra de progresso será exibida.');">
					<input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_sync_inventory_all'); ?>">
					<input type="hidden" name="sync_sap_all" value="1">
					<button type="submit" class="btn btn-primary btn-sm" id="btn-sync-inventory-all">
						<i class="fa-solid fa-rotate"></i> Sincronizar SAP
					</button>
				</form>
				<details class="d-inline">
					<summary class="btn btn-outline-secondary btn-sm mb-0" title="Carga completa, só itens ou varredura total de BOM/rota">Avançado</summary>
					<div class="d-inline-flex flex-wrap gap-1 ms-1 mt-1 align-items-center">
				<label class="btn btn-outline-secondary btn-sm mb-0" title="Catálogo inteiro + remoção de itens fora dos grupos SAP. Ignorado se informar código ou grupo acima.">
					<input type="checkbox" id="sync-opt-full" value="1" class="form-check-input me-1">
					Completa
				</label>
				<form action="" method="POST" class="d-inline" id="form-sync-inventory-items"
					data-sync-title="Sincronizando itens com o SAP"
					data-sync-type="items"
					onsubmit="return confirm('Sincronizar somente itens (sem BOM/rota)?\n\nUse para carga completa do catálogo ou quando não quiser tocar em estruturas.');">
					<input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_sync_inventory_items'); ?>">
					<input type="hidden" name="sync_sap_items" value="1">
					<button type="submit" class="btn btn-outline-primary btn-sm" id="btn-sync-inventory-items" title="Apenas cadastro de itens; não atualiza lista de materiais nem rota">
						<i class="fa-solid fa-box"></i> Só itens
					</button>
				</form>
				<form action="" method="POST" class="d-inline" id="form-sync-inventory-structures"
					data-sync-title="Sincronizando estruturas (BOM/rota)"
					data-sync-type="structures"
					onsubmit="return confirm('Varredura completa de BOM e rota (BEAS) para todos os itens elegíveis?\n\nMais lento que o botão principal; use após migração ou se estruturas estiverem desatualizadas.');">
					<input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_sync_inventory_structures'); ?>">
					<input type="hidden" name="sync_sap_structures" value="1">
					<button type="submit" class="btn btn-outline-warning btn-sm" id="btn-sync-inventory-structures" title="Reconsulta BOM/rota de todos os itens elegíveis (não só pendentes)">
						<i class="fa-solid fa-sitemap"></i> Estruturas (completo)
					</button>
				</form>
					</div>
				</details>
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
						<label for="categoria_id" class="form-label inventory-items-list-filters-label">Grupo de Itens</label>
						<select name="categoria_id" id="categoria_id" class="form-select inventory-items-list-filters-control">
							<option value="">Todos</option>
							<?php foreach ($this->data['categories'] ?? [] as $cat): ?>
								<option value="<?= (int) $cat['id'] ?>" <?= ($this->data['filtros']['categoria_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
									<?= htmlspecialchars($cat['name']) ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-6 col-sm-4 col-md-2 col-xl-2">
						<label for="production_line" class="form-label inventory-items-list-filters-label">Linha produção</label>
						<select name="production_line" id="production_line" class="form-select inventory-items-list-filters-control">
							<option value="">Todas</option>
							<option value="TIARAJU" <?= ($this->data['filtros']['production_line'] ?? '') === 'TIARAJU' ? 'selected' : '' ?>>TIARAJU</option>
							<option value="TERCEIRO" <?= ($this->data['filtros']['production_line'] ?? '') === 'TERCEIRO' ? 'selected' : '' ?>>TERCEIRO</option>
							<option value="__empty__" <?= ($this->data['filtros']['production_line'] ?? '') === '__empty__' ? 'selected' : '' ?>>— não informada</option>
						</select>
					</div>
					<div class="col-6 col-sm-4 col-md-2 col-xl-2">
						<label for="inv_pharma_form_id" class="form-label inventory-items-list-filters-label">Forma farmacêutica</label>
						<select name="inv_pharma_form_id" id="inv_pharma_form_id" class="form-select inventory-items-list-filters-control">
							<option value="">Todas</option>
							<?php foreach ($this->data['pharmaForms'] ?? [] as $pf): ?>
								<option value="<?= (int) $pf['id'] ?>" <?= ($this->data['filtros']['inv_pharma_form_id'] ?? '') == $pf['id'] ? 'selected' : '' ?>>
									<?= htmlspecialchars($pf['name']) ?>
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
							<th scope="col" style="width: 8%;">Código</th>
							<th scope="col" style="width: 8%;" class="d-none d-lg-table-cell">Cód. ERP</th>
							<th scope="col" style="width: 18%;">Descrição</th>
							<th scope="col" style="width: 7%;" class="d-none d-md-table-cell">Unidade</th>
							<th scope="col" style="width: 11%;" class="d-none d-md-table-cell">Grupo de Itens</th>
							<th scope="col" style="width: 9%;" class="d-none d-lg-table-cell">Forma farm.</th>
							<th scope="col" style="width: 7%;" class="d-none d-lg-table-cell">Linha</th>
							<th scope="col" style="width: 6%;" class="d-none d-xl-table-cell">Admin.</th>
							<th scope="col" style="width: 8%;" class="text-end">Em estoque</th>
							<th scope="col" style="width: 6%;" class="text-center">Ativo</th>
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
							<td class="text-truncate" title="<?= htmlspecialchars($item['code']) ?>"><?= htmlspecialchars($item['code']) ?></td>
							<td class="d-none d-lg-table-cell text-truncate" title="<?= htmlspecialchars($item['erp_code'] ?? '') ?>"><?= htmlspecialchars($item['erp_code'] ?? '—') ?></td>
							<td class="text-truncate" title="<?= htmlspecialchars($item['description']) ?>">
								<?= htmlspecialchars($item['description']) ?>
								<?php if (mb_strtoupper(trim((string)($item['category_name'] ?? '')), 'UTF-8') === 'PA - PROJETO'): ?>
									<span class="badge bg-warning text-dark ms-1">Projeto</span>
								<?php endif; ?>
							</td>
							<td class="d-none d-md-table-cell"><?= htmlspecialchars($item['unit_name'] ?? '—') ?></td>
							<td class="d-none d-md-table-cell text-truncate" title="<?= htmlspecialchars($item['category_name'] ?? '') ?>"><?= htmlspecialchars($item['category_name'] ?? '—') ?></td>
							<td class="d-none d-lg-table-cell text-truncate" title="<?= htmlspecialchars($item['pharma_form_name'] ?? '') ?>"><?= htmlspecialchars($item['pharma_form_name'] ?? '—') ?></td>
							<td class="d-none d-lg-table-cell"><?= htmlspecialchars($item['production_line'] ?? '—') ?></td>
							<td class="d-none d-xl-table-cell"><?= htmlspecialchars($adminLabel) ?></td>
							<td class="text-end"><?= number_format($totalQty, 4, ',', '.') ?></td>
							<td class="text-center">
								<span class="badge <?= $item['active'] ? 'bg-success' : 'bg-danger' ?>"><?= $item['active'] ? 'Sim' : 'Não' ?></span>
							</td>
							<td class="text-center table-inventory-items-desktop-actions">
								<div class="btn-group btn-group-sm" role="group">
									<?php
									if (in_array('ViewInventoryItem', $this->data['buttonPermission'])) {
										echo "<a href='" . htmlspecialchars($inventoryItemNavUrl((int)$item['id'], 'view-inventory-item')) . "' class='btn btn-info btn-sm' title='Visualizar'><i class='fa-regular fa-eye'></i></a>";
									}
									if (in_array('UpdateInventoryItem', $this->data['buttonPermission'])) {
										echo "<a href='" . htmlspecialchars($inventoryItemNavUrl((int)$item['id'], 'update-inventory-item')) . "' class='btn btn-warning btn-sm' title='Editar'><i class='fa-regular fa-pen-to-square'></i></a>";
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
							<span class="badge <?= $item['active'] ? 'bg-success' : 'bg-danger' ?>"><?= $item['active'] ? 'Ativo' : 'Inativo' ?></span>
						</div>
						<div class="small mb-2 text-break">
							<?= htmlspecialchars($item['description']) ?>
							<?php if (mb_strtoupper(trim((string)($item['category_name'] ?? '')), 'UTF-8') === 'PA - PROJETO'): ?>
								<span class="badge bg-warning text-dark ms-1">Projeto</span>
							<?php endif; ?>
						</div>
						<div class="mb-1 small"><b>Forma farm.:</b> <?= htmlspecialchars($item['pharma_form_name'] ?? '—') ?></div>
						<div class="mb-1 small"><b>Linha:</b> <?= htmlspecialchars($item['production_line'] ?? '—') ?></div>
						<div class="mb-1"><b>Em estoque:</b> <?= number_format($totalQty, 4, ',', '.') ?></div>
						<button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#cardItemDetails<?= $i ?>" aria-expanded="false" aria-controls="cardItemDetails<?= $i ?>">Ver mais</button>
						<div class="collapse mt-2" id="cardItemDetails<?= $i ?>">
							<?php if (!empty($item['erp_code'])) { ?>
								<div><b>Código ERP:</b> <?= htmlspecialchars($item['erp_code']) ?></div>
							<?php } ?>
							<div><b>Unidade:</b> <?= htmlspecialchars($item['unit_name'] ?? '—') ?></div>
							<div><b>Grupo de Itens:</b> <?= htmlspecialchars($item['category_name'] ?? '—') ?></div>
							<div><b>Admin.:</b> <?= htmlspecialchars($adminLabel) ?></div>
							<div class="mt-2 d-flex flex-wrap gap-1">
								<?php
								if (in_array('ViewInventoryItem', $this->data['buttonPermission'])) {
									echo "<a href='" . htmlspecialchars($inventoryItemNavUrl((int)$item['id'], 'view-inventory-item')) . "' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a> ";
								}
								if (in_array('UpdateInventoryItem', $this->data['buttonPermission'])) {
									echo "<a href='" . htmlspecialchars($inventoryItemNavUrl((int)$item['id'], 'update-inventory-item')) . "' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-regular fa-pen-to-square'></i> Editar</a> ";
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

<iframe name="sap-sync-worker" id="sap-sync-worker-frame" title="Worker SAP" class="visually-hidden" aria-hidden="true" tabindex="-1"></iframe>

<div class="modal fade" id="sapSyncProgressModal" tabindex="-1" aria-labelledby="sapSyncProgressModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="sapSyncProgressModalLabel">Sincronizando com o SAP</h5>
			</div>
			<div class="modal-body">
				<p class="mb-2 small text-secondary" id="sap-sync-progress-label">Preparando…</p>
				<div class="progress mb-2" style="height: 1.35rem;" role="progressbar" aria-valuemin="0" aria-valuemax="100">
					<div class="progress-bar progress-bar-striped progress-bar-animated" id="sap-sync-progress-bar" style="width: 0%;">0%</div>
				</div>
				<div class="d-flex justify-content-between small text-secondary">
					<span id="sap-sync-progress-count">—</span>
					<span id="sap-sync-progress-elapsed">Tempo: —</span>
					<span id="sap-sync-progress-eta">Restante: calculando…</span>
				</div>
				<div class="small text-muted mt-2" id="sap-sync-progress-stats"></div>
				<div class="small mt-2 d-none" id="sap-sync-progress-failures-wrap">
					<div class="fw-semibold text-danger mb-1">Itens com falha</div>
					<div class="sap-sync-progress-failures-scroll border rounded bg-light-subtle" id="sap-sync-progress-failures-scroll">
						<ul class="mb-0 ps-3 pe-2 py-2 text-danger" id="sap-sync-progress-failures"></ul>
					</div>
					<div class="text-muted mt-1 small" id="sap-sync-progress-failure-log"></div>
				</div>
			</div>
			<div class="modal-footer justify-content-between" id="sap-sync-progress-footer">
				<button type="button" class="btn btn-outline-danger btn-sm" id="sap-sync-progress-cancel">Interromper</button>
				<button type="button" class="btn btn-primary btn-sm d-none" id="sap-sync-progress-reload">Atualizar listagem</button>
			</div>
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

.sap-sync-progress-failures-scroll {
	max-height: 10.5rem;
	overflow-y: auto;
	overflow-x: hidden;
}

.sap-sync-progress-failures-scroll ul li {
	margin-bottom: 0.35rem;
	line-height: 1.35;
}

.sap-sync-progress-failures-scroll ul li:last-child {
	margin-bottom: 0;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
	var baseUrl = <?php echo json_encode(rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/list-inventory-items'); ?>;
	var pollTimer = null;
	var titleTimer = null;
	var progressModal = null;
	var activeRunId = null;
	var activeSyncType = 'items';
	var activeForm = null;
	var originalTitle = document.title;
	var syncStartedAt = 0;

	function startTitlePulse() {
		stopTitlePulse();
		titleTimer = setInterval(function () {
			document.title = document.title.indexOf('⟳ ') === 0 ? originalTitle : '⟳ ' + originalTitle;
		}, 900);
	}

	function stopTitlePulse() {
		if (titleTimer) {
			clearInterval(titleTimer);
			titleTimer = null;
		}
		document.title = originalTitle;
	}

	function getProgressModal() {
		if (!progressModal) {
			var el = document.getElementById('sapSyncProgressModal');
			if (el && window.bootstrap && bootstrap.Modal) {
				progressModal = new bootstrap.Modal(el);
			}
		}
		return progressModal;
	}

	function setProgressUi(data) {
		var percent = Math.max(0, Math.min(100, parseInt(data.percent, 10) || 0));
		var bar = document.getElementById('sap-sync-progress-bar');
		var label = document.getElementById('sap-sync-progress-label');
		var count = document.getElementById('sap-sync-progress-count');
		var elapsed = document.getElementById('sap-sync-progress-elapsed');
		var eta = document.getElementById('sap-sync-progress-eta');
		var stats = document.getElementById('sap-sync-progress-stats');
		var failuresWrap = document.getElementById('sap-sync-progress-failures-wrap');
		var failuresList = document.getElementById('sap-sync-progress-failures');
		var failureLogHint = document.getElementById('sap-sync-progress-failure-log');
		var cancelBtn = document.getElementById('sap-sync-progress-cancel');
		var reloadBtn = document.getElementById('sap-sync-progress-reload');

		if (bar) {
			bar.style.width = percent + '%';
			bar.textContent = percent + '%';
			bar.setAttribute('aria-valuenow', String(percent));
			bar.classList.toggle('bg-success', !!data.finished && data.success);
			bar.classList.toggle('bg-danger', !!data.finished && data.success === false);
		}
		if (label) {
			var phasePrefix = (data.current_phase === 'structures') ? 'Estruturas — ' : ((data.current_phase === 'items' || data.sync_type === 'all') && !data.finished ? 'Itens — ' : '');
			label.textContent = phasePrefix + (data.message || data.label || (data.finished ? 'Concluído' : 'Processando…'));
		}
		if (count) {
			var unit = (data.sync_type === 'structures' || data.current_phase === 'structures') ? 'estruturas' : 'itens';
			var passPrefix = (data.sync_pass && data.sync_pass > 1) ? ('Passagem ' + data.sync_pass + ' — ') : '';
			if (data.total) {
				count.textContent = passPrefix + (data.examined || 0) + ' / ' + data.total + ' ' + unit;
			} else if (data.examined) {
				count.textContent = passPrefix + (data.examined || 0) + ' ' + unit + ' processados';
			} else {
				count.textContent = '—';
			}
		}
		if (elapsed) {
			elapsed.textContent = 'Tempo: ' + (data.elapsed_label || '—');
		}
		if (eta) {
			if (data.finished) {
				eta.textContent = data.status === 'cancelled' ? 'Interrompido' : 'Concluído';
			} else if (data.eta_label) {
				eta.textContent = 'Restante: ' + data.eta_label;
			} else {
				eta.textContent = 'Restante: calculando…';
			}
		}
		if (stats) {
			var parts = [];
			if (data.sync_type === 'structures') {
				if (typeof data.updated === 'number' && data.updated > 0) {
					parts.push('Alteradas: ' + data.updated);
				}
				if (typeof data.unchanged === 'number' && data.unchanged > 0) {
					parts.push('Sem alteração: ' + data.unchanged);
				}
				if (typeof data.created === 'number' && data.created > 0) {
					parts.push('Sem BOM/rota: ' + data.created);
				}
				if (typeof data.failed === 'number' && data.failed > 0) {
					parts.push('Falhas: ' + data.failed);
				}
			} else {
				if (typeof data.created === 'number') {
					parts.push('Novos: ' + data.created);
				}
				if (typeof data.updated === 'number') {
					parts.push('Alterados: ' + data.updated);
				}
				if (typeof data.unchanged === 'number') {
					parts.push('Sem mudança: ' + data.unchanged);
				}
				if (typeof data.failed === 'number' && data.failed > 0) {
					parts.push('Falhas: ' + data.failed);
				}
			}
			stats.textContent = parts.join(' · ');
		}
		if (failuresWrap && failuresList) {
			var failedItems = Array.isArray(data.failed_items) ? data.failed_items : [];
			if (failedItems.length > 0) {
				failuresWrap.classList.remove('d-none');
				failuresList.innerHTML = failedItems.map(function (item) {
					var code = (item && item.erp_code) ? String(item.erp_code) : '?';
					var reason = (item && item.reason) ? String(item.reason) : 'Falha na sincronização SAP.';
					var phase = (item && item.phase) ? String(item.phase) : 'items';
					return '<li><code>' + code + '</code> (' + phase + ') — ' + reason + '</li>';
				}).join('');
			} else {
				failuresWrap.classList.add('d-none');
				failuresList.innerHTML = '';
			}
		}
		if (failureLogHint) {
			if (data.failure_log_file) {
				var totalFailed = Array.isArray(data.failed_items) ? data.failed_items.length : (typeof data.failed === 'number' ? data.failed : 0);
				failureLogHint.textContent = 'Lista completa (' + totalFailed + '): logs/' + data.failure_log_file;
			} else {
				failureLogHint.textContent = '';
			}
		}
		if (cancelBtn) {
			cancelBtn.classList.toggle('d-none', !!data.finished);
		}
		if (reloadBtn) {
			reloadBtn.classList.toggle('d-none', !data.finished);
		}
	}

	function stopPolling() {
		if (pollTimer) {
			clearInterval(pollTimer);
			pollTimer = null;
		}
	}

	function fetchSyncStatus(runId) {
		return fetch(baseUrl + '?sap_sync_status=1&run_id=' + encodeURIComponent(runId), {
			headers: { 'Accept': 'application/json' },
			credentials: 'same-origin'
		}).then(function (response) { return response.json(); });
	}

	function pollSyncStatus(runId, onFinished) {
		stopPolling();
		fetchSyncStatus(runId).then(function (data) {
			if (data && data.ok) {
				setProgressUi(data);
				if (data.finished && typeof onFinished === 'function') {
					onFinished(data);
				}
			}
		}).catch(function () {});

		pollTimer = setInterval(function () {
			fetchSyncStatus(runId)
				.then(function (data) {
					if (!data || !data.ok) {
						return;
					}
					setProgressUi(data);
					if (!data.finished && syncStartedAt > 0 && Date.now() - syncStartedAt > 20000 && (data.examined || 0) === 0) {
						document.getElementById('sap-sync-progress-label').textContent =
							(data.label || 'Processando…') + ' — aguarde, conectando ao SAP…';
					}
					if (data.finished) {
						stopPolling();
						if (typeof onFinished === 'function') {
							onFinished(data);
						}
					}
				})
				.catch(function () {});
		}, 1500);
	}

	function resetActiveForm() {
		if (!activeForm) {
			return;
		}
		activeForm.dataset.syncSubmitting = '0';
		var btn = activeForm.querySelector('button[type="submit"]');
		if (btn) {
			btn.disabled = false;
		}
		activeForm = null;
	}

	var executeRequested = {};

	function finishSyncUi(data) {
		stopTitlePulse();
		var reloadBtn = document.getElementById('sap-sync-progress-reload');
		if (reloadBtn) {
			reloadBtn.onclick = function () {
				window.location.reload();
			};
		}
		resetActiveForm();
		activeRunId = null;
		syncStartedAt = 0;

		if (!data || !data.finished) {
			return;
		}

		var nothingToSync = data.success && (data.examined || 0) === 0 && (data.created || 0) === 0
			&& (data.updated || 0) === 0
			&& (data.message || '').match(/Nenhum item (pendente|elegível)/i);
		var noStructureChanges = data.success && (data.sync_type === 'structures' || data.current_phase === 'structures')
			&& (data.updated || 0) === 0 && (data.failed || 0) === 0
			&& (data.examined || 0) > 0
			&& (data.message || '').match(/Nenhuma estrutura (pendente|alterada)/i);
		if (nothingToSync || noStructureChanges) {
			var modal = getProgressModal();
			setTimeout(function () {
				if (modal) {
					modal.hide();
				}
			}, 2800);
		}
	}

	function triggerSyncExecute(form, runId) {
		if (!form || !runId || executeRequested[runId]) {
			return;
		}
		executeRequested[runId] = true;

		var body = new FormData(form);
		appendSharedSyncOptions(body, form);
		body.append('sync_sap_ajax', '1');
		body.append('sync_sap_phase', 'execute');
		body.append('run_id', String(runId));

		fetch(form.getAttribute('action') || baseUrl, {
			method: 'POST',
			body: body,
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json' }
		})
			.then(function (response) { return response.json(); })
			.then(function (payload) {
				if (!payload || !payload.ok) {
					executeRequested[runId] = false;
					stopPolling();
					stopTitlePulse();
					var modal = getProgressModal();
					if (modal) {
						modal.hide();
					}
					resetActiveForm();
					alert((payload && payload.message) ? payload.message : 'Não foi possível executar a sincronização.');
				}
			})
			.catch(function () {
				executeRequested[runId] = false;
			});
	}

	function appendSharedSyncOptions(body, form) {
		var codeEl = document.getElementById('sync-filter-code');
		var groupEl = document.getElementById('sync-filter-group');
		if (codeEl) {
			body.set('sync_sap_item_code', codeEl.value.trim());
		}
		if (groupEl) {
			body.set('sync_sap_group_prefix', groupEl.value);
		}
		var fullEl = document.getElementById('sync-opt-full');
		if (fullEl && fullEl.checked) {
			body.set('sync_sap_items_full', '1');
		}
	}

	function bindSyncForm(formId) {
		var form = document.getElementById(formId);
		if (!form) {
			return;
		}

		form.addEventListener('submit', function (event) {
			if (form.dataset.syncSubmitting === '1') {
				event.preventDefault();
				return;
			}

			var modal = getProgressModal();
			if (!modal) {
				return;
			}

			event.preventDefault();
			form.dataset.syncSubmitting = '1';
			activeForm = form;
			activeSyncType = form.getAttribute('data-sync-type') || 'items';

			var btn = form.querySelector('button[type="submit"]');
			var btnHtml = btn ? btn.innerHTML : '';
			if (btn) {
				btn.disabled = true;
				btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Iniciando…';
			}

			var titleEl = document.getElementById('sapSyncProgressModalLabel');
			if (titleEl) {
				titleEl.textContent = form.getAttribute('data-sync-title') || 'Sincronizando com o SAP';
			}
			document.getElementById('sap-sync-progress-reload').classList.add('d-none');
			document.getElementById('sap-sync-progress-cancel').classList.remove('d-none');
			setProgressUi({ percent: 0, label: 'Preparando sincronização…', elapsed_label: '—', eta_label: null });
			modal.show();

			var body = new FormData(form);
			appendSharedSyncOptions(body, form);
			body.append('sync_sap_ajax', '1');
			body.append('sync_sap_phase', 'start');

			fetch(form.getAttribute('action') || baseUrl, {
				method: 'POST',
				body: body,
				credentials: 'same-origin',
				headers: { 'Accept': 'application/json' }
			})
				.then(function (response) { return response.json(); })
				.then(function (payload) {
					if (!payload || !payload.ok || !payload.run_id) {
						throw new Error((payload && payload.message) ? payload.message : 'Não foi possível iniciar a sincronização.');
					}

					activeRunId = payload.run_id;
					syncStartedAt = Date.now();
					startTitlePulse();
					if (btn) {
						btn.disabled = false;
						btn.innerHTML = btnHtml;
					}

					setProgressUi({ percent: 0, label: 'Sincronização iniciada…', elapsed_label: '—' });
					pollSyncStatus(payload.run_id, finishSyncUi);
					triggerSyncExecute(form, payload.run_id);
				})
				.catch(function (error) {
					stopPolling();
					stopTitlePulse();
					modal.hide();
					resetActiveForm();
					alert(error.message || 'Erro ao iniciar sincronização.');
				});
		});
	}

	document.getElementById('sap-sync-progress-cancel').addEventListener('click', function () {
		if (!activeRunId || !activeForm) {
			return;
		}
		if (!window.confirm('Interromper a sincronização em andamento?\n\nOs itens já processados permanecem gravados.')) {
			return;
		}

		var cancelBtn = this;
		cancelBtn.disabled = true;

		var body = new FormData();
		body.append('sap_sync_cancel', '1');
		body.append('run_id', String(activeRunId));
		body.append('sync_type', activeSyncType);
		body.append('csrf_token', activeForm.querySelector('input[name="csrf_token"]').value);

		fetch(baseUrl, {
			method: 'POST',
			body: body,
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json' }
		})
			.then(function (response) { return response.json(); })
			.then(function (data) {
				cancelBtn.disabled = false;
				if (data && data.ok && activeRunId) {
					fetchSyncStatus(activeRunId).then(function (status) {
						if (status && status.ok) {
							setProgressUi(status);
							if (status.finished) {
								stopPolling();
								finishSyncUi(status);
							}
						}
					});
				} else if (data && data.message) {
					alert(data.message);
				}
			})
			.catch(function () {
				cancelBtn.disabled = false;
			});
	});

	bindSyncForm('form-sync-inventory-all');
	bindSyncForm('form-sync-inventory-items');
	bindSyncForm('form-sync-inventory-structures');
});
</script>
