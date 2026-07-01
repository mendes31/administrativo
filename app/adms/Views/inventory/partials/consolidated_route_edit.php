<?php

declare(strict_types=1);

/** @var array<int, array<string, mixed>> $consolidatedOperations */
/** @var array<int, array<string, mixed>> $listOperations */
/** @var array<int, array<string, mixed>> $listProductionResources */
/** @var array<int, array<string, mixed>> $listLaborRoles */

require_once __DIR__ . '/operation_metrics.php';
require_once __DIR__ . '/sap_pos_display.php';
include __DIR__ . '/inv_route_operations_style.php';
include __DIR__ . '/consolidated_route_style.php';

use App\adms\Helpers\InvRoutePiExplosionHelper;

$consolidatedOperations = $consolidatedOperations ?? [];
$listOperations = $listOperations ?? [];
$listProductionResources = $listProductionResources ?? [];
$listLaborRoles = $listLaborRoles ?? [];

$machineResources = array_values(array_filter(
	$listProductionResources,
	static fn(array $res): bool => strtoupper((string)($res['resource_type'] ?? 'MACHINE')) !== 'LABOR'
));

$consolidatedTotalHh = 0.0;
$consolidatedTotalHm = 0.0;
$consolidatedOpCount = count($consolidatedOperations);
foreach ($consolidatedOperations as $op) {
	$resourceLines = invNormalizeMachineResourceLinesForDrivers(array_values(array_filter(
		$op['resource_lines'] ?? [],
		static fn(array $line): bool => (int)($line['inv_production_resource_id'] ?? 0) > 0
	)));
	$laborLines = invNormalizeLaborLinesForDrivers(array_values(array_filter(
		$op['labor_lines'] ?? [],
		static fn(array $line): bool => (int)($line['inv_labor_role_id'] ?? 0) > 0
	)));
	$metrics = invOperationMetrics(array_merge($op, [
		'labor_lines' => $laborLines,
		'resource_lines' => $resourceLines,
		'operators_qty' => 1,
	]));
	$timeMinutes = (float)($metrics['time_minutes'] ?? 0);
	$drivers = invOperationDriverHours($laborLines, $resourceLines, $timeMinutes, 1);
	$consolidatedTotalHh += (float)($drivers['labor_hours'] ?? 0);
	$consolidatedTotalHm += (float)($drivers['machine_hours'] ?? 0);
}
$consolidatedBatchSize = (float)($consolidatedBatchSize ?? 1);
if ($consolidatedBatchSize <= 0) {
	$consolidatedBatchSize = 1.0;
}
$rateioTotals = invAggregateRouteRateioDrivers($consolidatedOperations, $consolidatedBatchSize, null);
$consolidatedRateioHh = (float)($rateioTotals['rateio_labor_hours'] ?? 0);
$consolidatedRateioHm = (float)($rateioTotals['rateio_machine_hours'] ?? 0);
?>
<div class="cons-route-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2">
	<div class="small">
		<strong class="text-primary"><i class="fa-solid fa-route me-1"></i> Rota de custeio</strong>
		<span class="text-muted ms-1">— tempo · papéis e equipamentos (drivers HH/HM para rateio CFIX)</span>
	</div>
	<div class="d-flex flex-wrap align-items-center gap-2">
		<?php if ($consolidatedOpCount > 0): ?>
			<span class="badge bg-light text-dark border">
				<?= $consolidatedOpCount ?> operação<?= $consolidatedOpCount === 1 ? '' : 'ões' ?>
			</span>
			<button type="button" class="btn btn-sm btn-outline-secondary" onclick="consToggleAllOps(false)">
				<i class="fa-solid fa-angles-up me-1"></i> Recolher ops.
			</button>
			<button type="button" class="btn btn-sm btn-outline-secondary" onclick="consToggleAllOps(true)">
				<i class="fa-solid fa-angles-down me-1"></i> Expandir ops.
			</button>
		<?php endif; ?>
		<button type="button" class="btn btn-sm btn-outline-primary" id="btn-regenerate-consolidated-route">
			<i class="fa-solid fa-wand-magic-sparkles me-1"></i> Gerar da rota SAP
		</button>
	</div>
	<input type="hidden" name="regenerate_consolidated_route" id="regenerate_consolidated_route" value="0">
</div>

<?php if ($consolidatedOperations === []): ?>
	<div class="cons-section-empty py-4">
		<div class="cons-empty-icon"><i class="fa-solid fa-diagram-project"></i></div>
		<p class="text-muted small mb-2">Nenhuma operação consolidada ainda.</p>
		<p class="small mb-3">Sincronize a <strong>Rota SAP</strong> e clique em <strong>Gerar da rota SAP</strong>.</p>
		<button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('btn-regenerate-consolidated-route')?.click()">
			<i class="fa-solid fa-wand-magic-sparkles me-1"></i> Gerar da rota SAP
		</button>
	</div>
<?php else: ?>
	<div id="consolidated-operations-list" class="inv-route-edit">
		<?php foreach ($consolidatedOperations as $lineSeq => $op):
			$timeUnit = strtoupper((string)($op['time_unit'] ?? 'MIN'));
			if (!in_array($timeUnit, ['MIN', 'H'], true)) {
				$timeUnit = 'MIN';
			}

			$resourceLines = invNormalizeMachineResourceLinesForDrivers(array_values(array_filter(
				$op['resource_lines'] ?? [],
				static fn(array $line): bool => (int)($line['inv_production_resource_id'] ?? 0) > 0
			)));
			$laborLines = invNormalizeLaborLinesForDrivers(array_values(array_filter(
				$op['labor_lines'] ?? [],
				static fn(array $line): bool => (int)($line['inv_labor_role_id'] ?? 0) > 0
			)));

			$opForMetrics = array_merge($op, [
				'labor_lines' => $laborLines,
				'resource_lines' => $resourceLines,
				'operators_qty' => 1,
			]);
			$metrics = invOperationMetrics($opForMetrics);
			$timeMinutes = $metrics['time_minutes'];
			$sapGroupPos = (int)($op['sap_group_pos_id'] ?? 0);
			$sapGroupPosDisplay = (int)($op['sap_group_pos_text'] ?? 0);
			if ($sapGroupPosDisplay <= 0 && $sapGroupPos > 0) {
				$sapGroupPosDisplay = $sapGroupPos;
			}
			$posBadgeLabel = $sapGroupPosDisplay > 0 ? $sapGroupPosDisplay : (int)($op['sequence'] ?? ($lineSeq + 1));
			$resourceCount = count($resourceLines);
			$laborCount = count($laborLines);
			$timeMissing = $timeMinutes <= 0 && ($laborCount > 0 || $resourceCount > 0);
			$drivers = invOperationDriverHours($laborLines, $resourceLines, $timeMinutes, 1);
			$opHh = (float)($drivers['labor_hours'] ?? 0);
			$opHm = (float)($drivers['machine_hours'] ?? 0);
			$opToneClass = ((int)$lineSeq % 2) === 1 ? ' cons-route-op--alt' : '';
			$opCode = (string)($op['operation_code'] ?? '');
			$sourcePiCode = InvRoutePiExplosionHelper::parseSourcePiCode((string)($op['notes'] ?? ''));
			?>
			<div class="card mb-4 border-0 shadow-sm inv-route-op-card cons-route-op<?= $opToneClass ?>" data-cons-op-index="<?= (int)$lineSeq ?>">
				<div class="cons-route-op-header card-header py-2 px-3"
					data-bs-toggle="collapse" data-bs-target="#cons-op-body-<?= (int)$lineSeq ?>" aria-expanded="true"
					role="button" tabindex="0">
					<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
						<div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
							<i class="fa-solid fa-chevron-down cons-op-chevron text-muted"></i>
							<span class="badge bg-secondary" title="POS_TEXT do grupo SAP<?= $sapGroupPos > 0 ? ' (POS_ID mestre ' . $sapGroupPos . ')' : '' ?>">Pos. <?= $posBadgeLabel ?></span>
							<input type="hidden" name="cons_op_sequence[]" value="<?= (int)($op['sequence'] ?? ($lineSeq + 1)) ?>">
							<select name="cons_op_operation_id[]" class="form-select form-select-sm cons-op-select-inline" aria-label="Operação" onclick="event.stopPropagation()">
								<option value="">Selecione a operação</option>
								<?php foreach ($listOperations as $operation):
									$sel = ((string)($op['inv_operation_id'] ?? '') === (string)$operation['id']) ? 'selected' : '';
									echo '<option value="' . (int)$operation['id'] . '" ' . $sel . '>' . htmlspecialchars((string)$operation['name']) . '</option>';
								endforeach; ?>
							</select>
							<?php if ($opCode !== ''): ?>
								<code class="small ms-1 text-danger"><?= htmlspecialchars($opCode) ?></code>
							<?php endif; ?>
							<?php if ($sourcePiCode !== ''): ?>
								<span class="badge bg-primary ms-1" title="Operação do produto intermediário">PI <?= htmlspecialchars($sourcePiCode) ?></span>
							<?php endif; ?>
							<?php if ($sapGroupPos > 0): ?>
								<span class="badge bg-info text-dark ms-1" title="POS_TEXT do grupo SAP (POS_ID mestre <?= $sapGroupPos ?>)">Grp <?= $sapGroupPosDisplay ?></span>
							<?php endif; ?>
						</div>
						<div class="text-end small cons-op-header-metrics">
							<span class="me-3"><span class="text-muted">HH</span> <strong class="cons-op-hh"><?= number_format($opHh, 4, ',', '.') ?></strong> h</span>
							<span><span class="text-muted">HM</span> <strong class="cons-op-hm"><?= number_format($opHm, 4, ',', '.') ?></strong> h</span>
							<?php if ($timeMissing): ?>
								<div class="mt-1"><span class="badge bg-warning text-dark cons-time-warning">Tempo não informado</span></div>
							<?php else: ?>
								<div class="mt-1 cons-time-warning d-none"><span class="badge bg-warning text-dark">Tempo não informado</span></div>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="collapse show cons-route-op-body" id="cons-op-body-<?= (int)$lineSeq ?>">
				<div class="card-body py-2 px-3 inv-route-lines-align cons-route-op-inner">
					<div class="cons-op-time-bar mb-3">
						<div class="cons-op-time-group">
							<label class="cons-op-time-label" for="cons-op-time-<?= (int)$lineSeq ?>">Tempo da operação (lote)</label>
							<div class="cons-op-time-controls">
								<input type="number" step="0.0001" min="0" id="cons-op-time-<?= (int)$lineSeq ?>"
									name="cons_op_time_per_batch_hours[]"
									class="form-control form-control-sm cons-op-time-input"
									value="<?= htmlspecialchars((string)($op['time_per_batch_hours'] ?? '0')) ?>"
									oninput="consRecalcOpLine(<?= (int)$lineSeq ?>)" aria-label="Tempo por lote">
								<select name="cons_op_time_unit[]" class="form-select form-select-sm cons-op-time-unit"
									onchange="consRecalcOpLine(<?= (int)$lineSeq ?>)" aria-label="Unidade de tempo">
									<?php foreach (['MIN' => 'Minutos', 'H' => 'Horas'] as $value => $label):
										$sel = $value === $timeUnit ? 'selected' : '';
										echo '<option value="' . $value . '" ' . $sel . '>' . htmlspecialchars($label) . '</option>';
									endforeach; ?>
								</select>
								<span class="cons-op-time-result text-muted">
									≈ <strong class="text-dark cons-op-time-min"><?= number_format($timeMinutes, 2, ',', '.') ?></strong> min
								</span>
							</div>
						</div>
						<div class="cons-op-notes-group">
							<label class="cons-op-notes-label" for="cons-op-notes-<?= (int)$lineSeq ?>">Obs.</label>
							<input type="text" id="cons-op-notes-<?= (int)$lineSeq ?>" name="cons_op_notes[]"
								class="form-control form-control-sm cons-op-notes-input"
								placeholder="—" value="<?= htmlspecialchars((string)($op['notes'] ?? '')) ?>" aria-label="Observações">
						</div>
					</div>
					<input type="hidden" name="cons_op_sap_group_pos_id[]" value="<?= $sapGroupPos > 0 ? $sapGroupPos : '' ?>">
					<input type="hidden" name="cons_op_sap_group_pos_text[]" value="<?= $sapGroupPosDisplay > 0 ? $sapGroupPosDisplay : '' ?>">

					<div class="cons-route-op-lines">
					<div class="cons-section-panel mb-3">
						<button type="button" class="btn btn-link btn-sm p-0 text-decoration-none inv-view-sub-toggle mb-1 cons-sub-toggle"
							data-bs-toggle="collapse" data-bs-target="#cons-res-<?= (int)$lineSeq ?>" aria-expanded="true">
							<i class="fa-solid fa-chevron-down inv-chevron me-1"></i>
							<i class="fa-solid fa-industry me-1"></i>
							<span class="text-uppercase">Equipamentos</span>
							<span class="badge bg-light text-dark border ms-1 cons-resource-count"><?= $resourceCount ?></span>
						</button>
						<p class="cons-cost-hint small text-muted mb-2">
							Vincule equipamento e <strong>quantidade</strong>. Tempo parcial (min) só se menor que o da operação; vazio usa o tempo da operação. <strong>R$/min cad.</strong> é referência — CFIX via rateio (crit. 3 HM); energia CVAR usa <strong>kW</strong>.
						</p>
						<div class="collapse show" id="cons-res-<?= (int)$lineSeq ?>">
							<div class="cons-resource-empty cons-section-empty mb-0<?= $resourceCount > 0 ? ' d-none' : '' ?>">
								<div class="cons-empty-icon"><i class="fa-solid fa-industry"></i></div>
								<p class="text-muted small mb-2">Nenhum equipamento nesta operação</p>
								<button type="button" class="btn btn-sm btn-outline-primary" onclick="consAddResourceRow(<?= (int)$lineSeq ?>)">
									<i class="fa-solid fa-plus me-1"></i> Adicionar recurso
								</button>
							</div>
							<div class="cons-resource-block<?= $resourceCount > 0 ? '' : ' d-none' ?>">
								<table class="table table-sm table-bordered inv-route-sub-table cons-route-sub-table cons-route-driver-table bg-white mb-2">
									<colgroup>
										<col class="cons-col-item"><col class="cons-col-qty"><col class="cons-col-time"><col class="cons-col-ref"><col class="cons-col-act">
									</colgroup>
									<thead class="table-light">
										<tr>
											<th>Recurso</th>
											<th class="inv-cell-num">Qtd</th>
											<th class="inv-cell-num" title="Vazio = tempo da operação; deve ser ≤ operação">Tempo (min)</th>
											<th class="inv-cell-num" title="Referência do cadastro (uso futuro)">R$/min cad.</th>
											<th class="inv-cell-act"></th>
										</tr>
									</thead>
									<tbody class="cons-resource-tbody" data-cons-op-index="<?= (int)$lineSeq ?>">
										<?php foreach ($resourceLines as $resourceLine):
											$selResId = (int)($resourceLine['inv_production_resource_id'] ?? 0);
											$rQty = max(1, (int)($resourceLine['qty'] ?? 1));
											$rLineTime = isset($resourceLine['line_time_minutes']) && $resourceLine['line_time_minutes'] !== null && $resourceLine['line_time_minutes'] !== ''
												? (float)$resourceLine['line_time_minutes'] : null;
											$rCadMachine = (float)($resourceLine['resource_machine_cost_per_min'] ?? $resourceLine['machine_cost_per_min'] ?? 0);
											$rCadEnergy = (float)($resourceLine['resource_energy_cost_per_min'] ?? $resourceLine['energy_cost_per_min'] ?? 0);
											$rCadTotal = $rCadMachine + $rCadEnergy;
											?>
											<tr>
												<td>
													<select name="cons_op_resource_id[<?= (int)$lineSeq ?>][]" class="form-select form-select-sm cons-resource-select" onchange="consOnResourceSelect(<?= (int)$lineSeq ?>, this)">
														<option value="">Selecione</option>
														<?php foreach ($machineResources as $res):
															$rsel = ((string)$selResId === (string)$res['id']) ? 'selected' : '';
															$label = trim((string)($res['erp_code'] ?? '') . ' — ' . (string)($res['name'] ?? ''));
															$mc = (float)($res['machine_cost_per_min'] ?? 0);
															$ec = (float)($res['energy_cost_per_min'] ?? 0);
															echo '<option value="' . (int)$res['id'] . '"'
																. ' data-type="' . htmlspecialchars(strtoupper((string)($res['resource_type'] ?? 'MACHINE'))) . '"'
																. ' data-cad-cost="' . htmlspecialchars((string)($mc + $ec)) . '"'
																. ' ' . $rsel . '>' . htmlspecialchars($label) . '</option>';
														endforeach; ?>
													</select>
													<input type="hidden" name="cons_op_resource_machine_cost[<?= (int)$lineSeq ?>][]" value="0">
													<input type="hidden" name="cons_op_resource_energy_cost[<?= (int)$lineSeq ?>][]" value="0">
												</td>
												<td class="inv-cell-num"><input type="number" min="1" step="1" name="cons_op_resource_qty[<?= (int)$lineSeq ?>][]" class="form-control form-control-sm cons-resource-qty" value="<?= $rQty ?>" oninput="consRecalcOpLine(<?= (int)$lineSeq ?>)"></td>
												<td class="inv-cell-num"><input type="number" step="0.0001" min="0" name="cons_op_resource_line_time_min[<?= (int)$lineSeq ?>][]" class="form-control form-control-sm cons-resource-line-time" placeholder="op." value="<?= $rLineTime !== null ? htmlspecialchars((string)$rLineTime) : '' ?>" oninput="consRecalcOpLine(<?= (int)$lineSeq ?>)" title="Deixe vazio para usar o tempo da operação"></td>
												<td class="inv-cell-num"><span class="inv-cell-readonly cons-resource-cad-cost text-muted"><?= number_format($rCadTotal, 4, ',', '.') ?></span></td>
												<td class="inv-cell-act">
													<button type="button" class="btn btn-link inv-row-remove" title="Remover" onclick="consRemoveResourceRow(this, <?= (int)$lineSeq ?>)"><i class="fa-regular fa-trash-can"></i></button>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
								<button type="button" class="btn btn-link btn-sm p-0 inv-add-row-link" onclick="consAddResourceRow(<?= (int)$lineSeq ?>)">
									<i class="fa-solid fa-plus me-1"></i> Recurso
								</button>
							</div>
						</div>
					</div>

					<div class="cons-section-panel">
						<button type="button" class="btn btn-link btn-sm p-0 text-decoration-none inv-view-sub-toggle mb-1 cons-sub-toggle"
							data-bs-toggle="collapse" data-bs-target="#cons-lab-<?= (int)$lineSeq ?>" aria-expanded="true">
							<i class="fa-solid fa-chevron-down inv-chevron me-1"></i>
							<i class="fa-solid fa-user-gear me-1"></i>
							<span class="text-uppercase">MO — Papéis</span>
							<span class="badge bg-light text-dark border ms-1 cons-labor-count"><?= $laborCount ?></span>
						</button>
						<p class="cons-cost-hint small text-muted mb-2">
							Papel, <strong>quantidade</strong> e tempo parcial (se &lt; operação) para HH (crit. 2). <strong>R$/min cad.</strong> é referência do cadastro — rateio CFIX no período.
						</p>
						<div class="collapse show" id="cons-lab-<?= (int)$lineSeq ?>">
							<div class="cons-labor-empty cons-section-empty mb-0<?= $laborCount > 0 ? ' d-none' : '' ?>">
								<div class="cons-empty-icon"><i class="fa-solid fa-user-gear"></i></div>
								<p class="text-muted small mb-2">Nenhum papel de MO nesta operação</p>
								<button type="button" class="btn btn-sm btn-outline-primary" onclick="consAddLaborRow(<?= (int)$lineSeq ?>)">
									<i class="fa-solid fa-plus me-1"></i> Adicionar papel
								</button>
							</div>
							<div class="cons-labor-block<?= $laborCount > 0 ? '' : ' d-none' ?>">
								<table class="table table-sm table-bordered inv-route-sub-table cons-route-sub-table cons-route-driver-table bg-white mb-2">
									<colgroup>
										<col class="cons-col-item"><col class="cons-col-qty"><col class="cons-col-time"><col class="cons-col-ref"><col class="cons-col-act">
									</colgroup>
									<thead class="table-light">
										<tr>
											<th>Papel</th>
											<th class="inv-cell-num">Qtd</th>
											<th class="inv-cell-num" title="Vazio = tempo da operação">Tempo (min)</th>
											<th class="inv-cell-num" title="Referência do cadastro">R$/min cad.</th>
											<th class="inv-cell-act"></th>
										</tr>
									</thead>
									<tbody class="cons-labor-tbody" data-cons-op-index="<?= (int)$lineSeq ?>">
										<?php foreach ($laborLines as $laborLine):
											$selRoleId = (int)($laborLine['inv_labor_role_id'] ?? 0);
											$lQty = max(1, (int)($laborLine['qty'] ?? 1));
											$lLineTime = isset($laborLine['line_time_minutes']) && $laborLine['line_time_minutes'] !== null && $laborLine['line_time_minutes'] !== ''
												? (float)$laborLine['line_time_minutes'] : null;
											$lCadCost = (float)($laborLine['default_cost_per_min'] ?? $laborLine['cost_per_min'] ?? 0);
											?>
											<tr>
												<td>
													<select name="cons_op_labor_role_id[<?= (int)$lineSeq ?>][]" class="form-select form-select-sm cons-labor-select" onchange="consOnLaborSelect(<?= (int)$lineSeq ?>, this)">
														<option value="">Selecione</option>
														<?php foreach ($listLaborRoles as $role):
															$rsel = ((string)$selRoleId === (string)$role['id']) ? 'selected' : '';
															$label = trim((string)($role['code'] ?? '') . ' — ' . (string)($role['name'] ?? ''));
															echo '<option value="' . (int)$role['id'] . '" data-cad-cost="' . htmlspecialchars((string)($role['default_cost_per_min'] ?? '0')) . '" ' . $rsel . '>' . htmlspecialchars($label) . '</option>';
														endforeach; ?>
													</select>
													<input type="hidden" name="cons_op_labor_cost_per_min[<?= (int)$lineSeq ?>][]" value="0">
												</td>
												<td class="inv-cell-num"><input type="number" min="1" step="1" name="cons_op_labor_qty[<?= (int)$lineSeq ?>][]" class="form-control form-control-sm cons-labor-qty" value="<?= $lQty ?>" oninput="consRecalcOpLine(<?= (int)$lineSeq ?>)"></td>
												<td class="inv-cell-num"><input type="number" step="0.0001" min="0" name="cons_op_labor_line_time_min[<?= (int)$lineSeq ?>][]" class="form-control form-control-sm cons-labor-line-time" placeholder="op." value="<?= $lLineTime !== null ? htmlspecialchars((string)$lLineTime) : '' ?>" oninput="consRecalcOpLine(<?= (int)$lineSeq ?>)" title="Deixe vazio para usar o tempo da operação"></td>
												<td class="inv-cell-num"><span class="inv-cell-readonly cons-labor-cad-cost text-muted"><?= number_format($lCadCost, 4, ',', '.') ?></span></td>
												<td class="inv-cell-act">
													<button type="button" class="btn btn-link inv-row-remove" title="Remover" onclick="consRemoveLaborRow(this, <?= (int)$lineSeq ?>)"><i class="fa-regular fa-trash-can"></i></button>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
								<button type="button" class="btn btn-link btn-sm p-0 inv-add-row-link" onclick="consAddLaborRow(<?= (int)$lineSeq ?>)">
									<i class="fa-solid fa-plus me-1"></i> Papel
								</button>
							</div>
						</div>
					</div>
					</div>
				</div>
			</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="cons-route-grand-total text-end fw-semibold">
		HH rateio (lote): <strong class="text-info" id="consolidated-route-rateio-hh"><?= number_format($consolidatedRateioHh, 4, ',', '.') ?></strong> h
		<span class="text-muted mx-2">·</span>
		HM rateio (lote): <strong class="text-warning-emphasis" id="consolidated-route-rateio-hm"><?= number_format($consolidatedRateioHm, 4, ',', '.') ?></strong> h
		<div class="small text-muted fw-normal mt-1">
			Σ rota MO×tempo: <?= number_format($consolidatedTotalHh, 4, ',', '.') ?> h HH · <?= number_format($consolidatedTotalHm, 4, ',', '.') ?> h HM
			— drivers CFIX crit. 2 / 3 usam tempo de etapa (planilha Tiaraju)
		</div>
	</div>
<?php endif; ?>

<script>
const consMachineResources = <?php echo json_encode($machineResources, JSON_UNESCAPED_UNICODE); ?>;
const consLaborRoles = <?php echo json_encode($listLaborRoles, JSON_UNESCAPED_UNICODE); ?>;

function consToggleAllSubs(expand) {
	document.querySelectorAll('#consolidated-operations-list .collapse').forEach(function (el) {
		if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
			bootstrap.Collapse.getOrCreateInstance(el, { toggle: false })[expand ? 'show' : 'hide']();
		} else {
			el.classList.toggle('show', expand);
		}
	});
	document.querySelectorAll('#consolidated-operations-list .cons-sub-toggle').forEach(function (btn) {
		btn.classList.toggle('collapsed', !expand);
	});
}

function consGetOpCard(opIndex) {
	return document.querySelector('.cons-route-op[data-cons-op-index="' + opIndex + '"]');
}

function consFormatBr(value, decimals) {
	return Number(value || 0).toFixed(decimals).replace('.', ',');
}

function consGetTimeMinutes(card) {
	const raw = String(card.querySelector('.cons-op-time-input')?.value || '0').replace(',', '.');
	const timeVal = parseFloat(raw) || 0;
	const unit = (card.querySelector('.cons-op-time-unit')?.value || 'MIN').toUpperCase();
	return unit === 'H' ? timeVal * 60.0 : timeVal;
}

function consUpdateCountBadge(card, selector, count) {
	const badge = card.querySelector(selector);
	if (badge) badge.textContent = String(count);
}

function consUpdateResourceVisibility(opIndex) {
	const card = consGetOpCard(opIndex);
	if (!card) return;
	const tbody = card.querySelector('.cons-resource-tbody');
	const count = tbody ? tbody.querySelectorAll('tr').length : 0;
	const hasRows = count > 0;
	card.querySelector('.cons-resource-block')?.classList.toggle('d-none', !hasRows);
	card.querySelector('.cons-resource-empty')?.classList.toggle('d-none', hasRows);
	consUpdateCountBadge(card, '.cons-resource-count', count);
}

function consUpdateLaborVisibility(opIndex) {
	const card = consGetOpCard(opIndex);
	if (!card) return;
	const tbody = card.querySelector('.cons-labor-tbody');
	const count = tbody ? tbody.querySelectorAll('tr').length : 0;
	const hasRows = count > 0;
	card.querySelector('.cons-labor-block')?.classList.toggle('d-none', !hasRows);
	card.querySelector('.cons-labor-empty')?.classList.toggle('d-none', hasRows);
	consUpdateCountBadge(card, '.cons-labor-count', count);
}

function consRecalcGrandTotal() {
	let totalHh = 0;
	let totalHm = 0;
	document.querySelectorAll('.cons-route-op').forEach(function (card) {
		const hh = parseFloat(String(card.querySelector('.cons-op-hh')?.textContent || '0').replace(',', '.')) || 0;
		const hm = parseFloat(String(card.querySelector('.cons-op-hm')?.textContent || '0').replace(',', '.')) || 0;
		totalHh += hh;
		totalHm += hm;
	});
	const hhEl = document.getElementById('consolidated-route-total-hh');
	const hmEl = document.getElementById('consolidated-route-total-hm');
	if (hhEl) hhEl.textContent = consFormatBr(totalHh, 4);
	if (hmEl) hmEl.textContent = consFormatBr(totalHm, 4);
}

function consToggleAllOps(expand) {
	document.querySelectorAll('#consolidated-operations-list .cons-route-op-body').forEach(function (el) {
		if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
			bootstrap.Collapse.getOrCreateInstance(el, { toggle: false })[expand ? 'show' : 'hide']();
		} else {
			el.classList.toggle('show', expand);
		}
	});
	document.querySelectorAll('#consolidated-operations-list .cons-route-op-header').forEach(function (hdr) {
		hdr.classList.toggle('collapsed', !expand);
		hdr.setAttribute('aria-expanded', expand ? 'true' : 'false');
	});
}

function consResolveLineTimeMinutes(lineInput, opTimeMin) {
	const raw = String(lineInput?.value ?? '').trim();
	if (raw === '') return opTimeMin;
	const v = parseFloat(raw.replace(',', '.'));
	if (!Number.isFinite(v) || v <= 0) return opTimeMin;
	if (opTimeMin > 0) return Math.min(v, opTimeMin);
	return v;
}

function consOnResourceSelect(opIndex, selectEl) {
	const row = selectEl?.closest('tr');
	const opt = selectEl?.selectedOptions?.[0];
	const cad = parseFloat(opt?.getAttribute('data-cad-cost') || '0') || 0;
	const cadEl = row?.querySelector('.cons-resource-cad-cost');
	if (cadEl) cadEl.textContent = consFormatBr(cad, 4);
	consRecalcOpLine(opIndex);
}

function consOnLaborSelect(opIndex, selectEl) {
	const row = selectEl?.closest('tr');
	const opt = selectEl?.selectedOptions?.[0];
	const cad = parseFloat(opt?.getAttribute('data-cad-cost') || '0') || 0;
	const cadEl = row?.querySelector('.cons-labor-cad-cost');
	if (cadEl) cadEl.textContent = consFormatBr(cad, 4);
	consRecalcOpLine(opIndex);
}

function consRecalcOpLine(opIndex) {
	const card = consGetOpCard(opIndex);
	if (!card) return;
	const timeMin = consGetTimeMinutes(card);

	let opHh = 0;
	let opHm = 0;
	let laborRows = 0;
	let resourceRows = 0;

	const laborBody = card.querySelector('.cons-labor-tbody[data-cons-op-index="' + opIndex + '"]');
	(laborBody ? laborBody.querySelectorAll('tr') : []).forEach(function (row) {
		const roleId = parseInt(row.querySelector('.cons-labor-select')?.value || '0', 10);
		if (roleId <= 0) return;
		laborRows++;
		const qty = Math.max(1, parseInt(String(row.querySelector('.cons-labor-qty')?.value || '1').replace(',', '.'), 10) || 1);
		const lineMin = consResolveLineTimeMinutes(row.querySelector('.cons-labor-line-time'), timeMin);
		opHh += (lineMin / 60.0) * qty;
	});

	const resourceBody = card.querySelector('.cons-resource-tbody[data-cons-op-index="' + opIndex + '"]');
	(resourceBody ? resourceBody.querySelectorAll('tr') : []).forEach(function (row) {
		const select = row.querySelector('.cons-resource-select');
		const resId = parseInt(select?.value || '0', 10);
		if (resId <= 0) return;
		const opt = select?.selectedOptions?.[0];
		const resType = String(opt?.getAttribute('data-type') || 'MACHINE').toUpperCase();
		if (resType === 'LABOR' || resType === 'ENERGY') return;
		resourceRows++;
		const qty = Math.max(1, parseInt(String(row.querySelector('.cons-resource-qty')?.value || '1').replace(',', '.'), 10) || 1);
		const lineMin = consResolveLineTimeMinutes(row.querySelector('.cons-resource-line-time'), timeMin);
		if (lineMin > 0) {
			opHm += (lineMin / 60.0) * qty;
		}
	});

	const timeMinEl = card.querySelector('.cons-op-time-min');
	if (timeMinEl) timeMinEl.textContent = consFormatBr(timeMin, 2);
	const hhEl = card.querySelector('.cons-op-hh');
	if (hhEl) hhEl.textContent = consFormatBr(opHh, 4);
	const hmEl = card.querySelector('.cons-op-hm');
	if (hmEl) hmEl.textContent = consFormatBr(opHm, 4);

	const warnWrap = card.querySelector('.cons-time-warning');
	if (warnWrap) {
		warnWrap.classList.toggle('d-none', !(timeMin <= 0 && (laborRows > 0 || resourceRows > 0)));
	}

	consRecalcGrandTotal();
}

function consBuildMachineResourceOptionsHtml() {
	let html = '<option value="">Selecione</option>';
	(consMachineResources || []).forEach(function (res) {
		const label = String((res.erp_code || '') + ' — ' + (res.name || '')).replace(/"/g, '&quot;');
		const mc = parseFloat(res.machine_cost_per_min || 0) || 0;
		const ec = parseFloat(res.energy_cost_per_min || 0) || 0;
		html += '<option value="' + res.id + '" data-type="' + String(res.resource_type || 'MACHINE').toUpperCase() + '" data-cad-cost="' + (mc + ec) + '">' + label + '</option>';
	});
	return html;
}

function consBuildLaborOptionsHtml() {
	let html = '<option value="">Selecione</option>';
	(consLaborRoles || []).forEach(function (role) {
		const label = String((role.code || '') + ' — ' + (role.name || '')).replace(/"/g, '&quot;');
		const cad = parseFloat(role.default_cost_per_min || 0) || 0;
		html += '<option value="' + role.id + '" data-cad-cost="' + cad + '">' + label + '</option>';
	});
	return html;
}

function consAddResourceRow(opIndex) {
	const card = consGetOpCard(opIndex);
	const tbody = card?.querySelector('.cons-resource-tbody[data-cons-op-index="' + opIndex + '"]');
	if (!tbody) return;
	const tr = document.createElement('tr');
	tr.innerHTML = ''
		+ '<td><select name="cons_op_resource_id[' + opIndex + '][]" class="form-select form-select-sm cons-resource-select" onchange="consOnResourceSelect(' + opIndex + ', this)">' + consBuildMachineResourceOptionsHtml() + '</select>'
		+ '<input type="hidden" name="cons_op_resource_machine_cost[' + opIndex + '][]" value="0">'
		+ '<input type="hidden" name="cons_op_resource_energy_cost[' + opIndex + '][]" value="0"></td>'
		+ '<td class="inv-cell-num"><input type="number" min="1" step="1" name="cons_op_resource_qty[' + opIndex + '][]" class="form-control form-control-sm cons-resource-qty" value="1" oninput="consRecalcOpLine(' + opIndex + ')"></td>'
		+ '<td class="inv-cell-num"><input type="number" step="0.0001" min="0" name="cons_op_resource_line_time_min[' + opIndex + '][]" class="form-control form-control-sm cons-resource-line-time" placeholder="op." oninput="consRecalcOpLine(' + opIndex + ')" title="Deixe vazio para usar o tempo da operação"></td>'
		+ '<td class="inv-cell-num"><span class="inv-cell-readonly cons-resource-cad-cost text-muted">0,0000</span></td>'
		+ '<td class="inv-cell-act"><button type="button" class="btn btn-link inv-row-remove" title="Remover" onclick="consRemoveResourceRow(this, ' + opIndex + ')"><i class="fa-regular fa-trash-can"></i></button></td>';
	tbody.appendChild(tr);
	consUpdateResourceVisibility(opIndex);
	consRecalcOpLine(opIndex);
}

function consRemoveResourceRow(btn, opIndex) {
	btn.closest('tr')?.remove();
	consUpdateResourceVisibility(opIndex);
	consRecalcOpLine(opIndex);
}

function consAddLaborRow(opIndex) {
	const card = consGetOpCard(opIndex);
	const tbody = card?.querySelector('.cons-labor-tbody[data-cons-op-index="' + opIndex + '"]');
	if (!tbody) return;
	const tr = document.createElement('tr');
	tr.innerHTML = ''
		+ '<td><select name="cons_op_labor_role_id[' + opIndex + '][]" class="form-select form-select-sm cons-labor-select" onchange="consOnLaborSelect(' + opIndex + ', this)">' + consBuildLaborOptionsHtml() + '</select>'
		+ '<input type="hidden" name="cons_op_labor_cost_per_min[' + opIndex + '][]" value="0"></td>'
		+ '<td class="inv-cell-num"><input type="number" min="1" step="1" name="cons_op_labor_qty[' + opIndex + '][]" class="form-control form-control-sm cons-labor-qty" value="1" oninput="consRecalcOpLine(' + opIndex + ')"></td>'
		+ '<td class="inv-cell-num"><input type="number" step="0.0001" min="0" name="cons_op_labor_line_time_min[' + opIndex + '][]" class="form-control form-control-sm cons-labor-line-time" placeholder="op." oninput="consRecalcOpLine(' + opIndex + ')" title="Deixe vazio para usar o tempo da operação"></td>'
		+ '<td class="inv-cell-num"><span class="inv-cell-readonly cons-labor-cad-cost text-muted">0,0000</span></td>'
		+ '<td class="inv-cell-act"><button type="button" class="btn btn-link inv-row-remove" title="Remover" onclick="consRemoveLaborRow(this, ' + opIndex + ')"><i class="fa-regular fa-trash-can"></i></button></td>';
	tbody.appendChild(tr);
	consUpdateLaborVisibility(opIndex);
	consRecalcOpLine(opIndex);
}

function consRemoveLaborRow(btn, opIndex) {
	btn.closest('tr')?.remove();
	consUpdateLaborVisibility(opIndex);
	consRecalcOpLine(opIndex);
}

document.querySelectorAll('#consolidated-operations-list .cons-sub-toggle').forEach(function (btn) {
	btn.addEventListener('click', function () {
		setTimeout(function () {
			btn.classList.toggle('collapsed', !document.querySelector(btn.getAttribute('data-bs-target'))?.classList.contains('show'));
		}, 350);
	});
});

document.querySelectorAll('#consolidated-operations-list .cons-route-op-header').forEach(function (hdr) {
	const target = hdr.getAttribute('data-bs-target');
	if (!target) return;
	const body = document.querySelector(target);
	if (!body) return;
	body.addEventListener('shown.bs.collapse', function () {
		hdr.classList.remove('collapsed');
		hdr.setAttribute('aria-expanded', 'true');
	});
	body.addEventListener('hidden.bs.collapse', function () {
		hdr.classList.add('collapsed');
		hdr.setAttribute('aria-expanded', 'false');
	});
});

document.querySelectorAll('.cons-route-op').forEach(function (card) {
	const idx = card.getAttribute('data-cons-op-index');
	if (idx !== null) consRecalcOpLine(parseInt(idx, 10));
});

document.getElementById('btn-regenerate-consolidated-route')?.addEventListener('click', function () {
	if (!confirm('Regenerar a consolidação a partir da rota SAP? Tempos informados manualmente serão substituídos nos grupos regerados.')) {
		return;
	}
	const hidden = document.getElementById('regenerate_consolidated_route');
	const tab = document.getElementById('active_tab');
	if (hidden) hidden.value = '1';
	if (tab) tab.value = 'pane-route-consolidated';
	document.getElementById('form-update-inventory-item')?.requestSubmit();
});
</script>
