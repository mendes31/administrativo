<?php
/**
 * Estrutura editável na simulação (BOM + rota) — não altera o cadastro oficial do item.
 *
 * @var list<array<string, mixed>> $edit_bom
 * @var list<array<string, mixed>> $edit_operations
 * @var list<array<string, mixed>> $listBomItems
 * @var list<array<string, mixed>> $listUnits
 * @var list<array<string, mixed>> $listOperations
 * @var bool $is_project_item
 * @var bool $structure_customized
 */
if (!isset($this)) {
    exit;
}
$bomLines = $edit_bom ?? [];
$listBomItems = $listBomItems ?? [];
$listUnits = $listUnits ?? [];
$isProjectItem = (bool)($is_project_item ?? false);
$editOperations = $edit_operations ?? [];
$listOperations = $listOperations ?? [];
$structureCustomized = (bool)($structure_customized ?? false);
?>
<div class="border rounded p-3 p-md-4 bg-white mb-3">
  <input type="hidden" name="sim_structure_enabled" value="1">
  <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
    <div class="fw-semibold">Estrutura da simulação (what-if)</div>
    <?php if ($structureCustomized): ?>
      <span class="badge bg-info text-dark">estrutura ajustada</span>
    <?php endif; ?>
  </div>
  <p class="text-muted small mb-3">
    Adicione, remova ou altere componentes e operações <strong>só para este cenário</strong>.
    O cadastro oficial do item (abas Lista de materiais e Rota) não é alterado.
  </p>

  <h6 class="fw-semibold mb-2">Lista de materiais</h6>
  <?php include __DIR__ . '/bom_edit_table.php'; ?>

  <h6 class="fw-semibold mt-4 mb-2">Rota de produção</h6>
  <div class="table-responsive">
    <table class="table table-sm align-middle" id="sim-ops-table">
      <thead class="thead-green">
        <tr>
          <th style="width: 6%;">Seq.</th>
          <th style="width: 40%;">Operação</th>
          <th style="width: 18%;">Tempo / lote</th>
          <th style="width: 12%;">Un.</th>
          <th style="width: 14%;">Origem</th>
          <th style="width: 10%;" class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($editOperations as $opLine):
          $opRowId = (int)($opLine['id'] ?? 0);
          $opMasterId = (int)($opLine['inv_operation_id'] ?? 0);
          $seq = (int)($opLine['sequence'] ?? 1);
          $time = (float)($opLine['time_per_batch_hours'] ?? 0);
          $timeUnit = strtoupper((string)($opLine['time_unit'] ?? 'MIN'));
          $hasResources = !empty($opLine['labor_lines']) || !empty($opLine['resource_lines']);
          ?>
          <tr class="sim-op-row">
            <td>
              <input type="hidden" name="sim_op_row_id[]" value="<?= $opRowId ?>">
              <input type="number" min="1" step="1" name="sim_op_sequence[]" class="form-control form-control-sm" value="<?= $seq ?>">
            </td>
            <td>
              <select name="sim_op_operation_id[]" class="form-select form-select-sm sim-op-select">
                <option value="">Selecione</option>
                <?php foreach ($listOperations as $opOpt):
                  $oid = (int)($opOpt['id'] ?? 0);
                  $sel = $oid === $opMasterId ? ' selected' : '';
                  ?>
                  <option value="<?= $oid ?>"<?= $sel ?>><?= htmlspecialchars((string)($opOpt['name'] ?? '')) ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td>
              <input type="number" step="0.000001" min="0" name="sim_op_time_per_batch_hours[]" class="form-control form-control-sm" value="<?= htmlspecialchars(number_format($time, 6, '.', '')) ?>">
            </td>
            <td>
              <select name="sim_op_time_unit[]" class="form-select form-select-sm">
                <option value="MIN" <?= $timeUnit === 'MIN' ? 'selected' : '' ?>>MIN</option>
                <option value="H" <?= $timeUnit === 'H' ? 'selected' : '' ?>>H</option>
              </select>
            </td>
            <td>
              <?php if ($opRowId > 0): ?>
                <span class="badge bg-secondary">Item</span>
                <?php if ($hasResources): ?>
                  <span class="badge bg-light text-muted border" title="MO/recursos do cadastro do item">+ MO/rec.</span>
                <?php endif; ?>
              <?php else: ?>
                <span class="badge bg-warning text-dark">Nova</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeSimOpRow(this)">Remover</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSimOpRow()">
    <i class="fa-solid fa-plus"></i> Adicionar operação
  </button>
  <p class="text-muted small mt-2 mb-0">
    Operações vindas do <strong>cadastro do item</strong> mantêm MO e recursos já configurados; linhas <strong>Nova</strong> usam apenas o custo padrão da operação.
  </p>
</div>

<?php
$bomScriptListBomItems = $listBomItems;
$bomScriptListUnits = $listUnits;
$bomScriptIsProjectItem = $isProjectItem;
include __DIR__ . '/bom_edit_scripts.php';
?>

<script>
const simListOperations = <?php echo json_encode($listOperations ?? [], JSON_UNESCAPED_UNICODE); ?>;
function removeSimOpRow(btn) {
    const row = btn.closest('tr');
    if (row) row.remove();
}
function addSimOpRow() {
    const tbody = document.querySelector('#sim-ops-table tbody');
    if (!tbody) return;
    const seq = tbody.querySelectorAll('.sim-op-row').length + 1;
    let optionsHtml = '<option value="">Selecione</option>';
    (simListOperations || []).forEach(function (op) {
        optionsHtml += '<option value="' + op.id + '">' + String(op.name || '').replace(/"/g, '&quot;') + '</option>';
    });
    const tr = document.createElement('tr');
    tr.className = 'sim-op-row';
    tr.innerHTML = `
        <td><input type="hidden" name="sim_op_row_id[]" value="0"><input type="number" min="1" step="1" name="sim_op_sequence[]" class="form-control form-control-sm" value="${seq}"></td>
        <td><select name="sim_op_operation_id[]" class="form-select form-select-sm sim-op-select">${optionsHtml}</select></td>
        <td><input type="number" step="0.000001" min="0" name="sim_op_time_per_batch_hours[]" class="form-control form-control-sm" value="0"></td>
        <td><select name="sim_op_time_unit[]" class="form-select form-select-sm"><option value="MIN" selected>MIN</option><option value="H">H</option></select></td>
        <td><span class="badge bg-warning text-dark">Nova</span></td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeSimOpRow(this)">Remover</button></td>
    `;
    tbody.appendChild(tr);
}
</script>
