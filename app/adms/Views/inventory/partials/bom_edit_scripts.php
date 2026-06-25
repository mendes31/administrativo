<?php
/**
 * Scripts da tabela BOM (edição de item e simulação).
 *
 * @var list<array<string, mixed>> $bomScriptListBomItems
 * @var list<array<string, mixed>> $bomScriptListUnits
 * @var bool $bomScriptIsProjectItem
 */
if (!isset($this)) {
    exit;
}
$bomScriptListBomItems = $bomScriptListBomItems ?? ($listBomItems ?? []);
$bomScriptListUnits = $bomScriptListUnits ?? ($listUnits ?? []);
$bomScriptIsProjectItem = (bool)($bomScriptIsProjectItem ?? ($isProjectItem ?? false));

$unitCodes = [];
$unitLabels = ['UN' => 'Unidade', 'KG' => 'Quilograma', 'G' => 'Grama', 'ML' => 'Mililitro', 'L' => 'Litro', 'CX' => 'Caixa'];
foreach ($bomScriptListUnits as $u) {
    $c = strtoupper(trim((string)($u['code'] ?? '')));
    if ($c !== '') {
        $unitCodes[] = $c;
        $n = trim((string)($u['name'] ?? ''));
        if ($n !== '') {
            $unitLabels[$c] = $n;
        }
    }
}
foreach (['UN', 'KG', 'G', 'ML', 'L', 'CX'] as $d) {
    if (!in_array($d, $unitCodes, true)) {
        $unitCodes[] = $d;
    }
}
sort($unitCodes);
?>
<script>
function removeBomRow(btn) {
    const row = btn.closest('tr');
    if (row) row.remove();
    recalcBomGrandTotal();
}

function isProjectCategorySelected() {
    if (typeof window.bomScriptForceProjectItem === 'boolean') {
        return window.bomScriptForceProjectItem;
    }
    const sel = document.getElementById('inv_category_id');
    if (!sel || !sel.value) return false;
    return (window.projectCategoryIds || []).includes(parseInt(sel.value, 10));
}

function removeEmptyBomRowsBeforeSubmit() {
    document.querySelectorAll('#bom-table tbody .bom-row').forEach(function (row) {
        const isManual = row.classList.contains('bom-row-manual');
        const qty = parseFloat(row.querySelector('.bom-qty')?.value || '0') || 0;
        if (isManual) {
            const desc = (row.querySelector('.bom-manual-desc')?.value || '').trim();
            const cost = parseFloat(row.querySelector('.bom-manual-cost')?.value || '0') || 0;
            if (qty <= 0 && desc === '' && cost <= 0) row.remove();
            return;
        }
        const compId = parseInt(row.querySelector('.bom-catalog-select')?.value || '0', 10);
        if (qty <= 0 && compId <= 0) row.remove();
    });
    recalcBomGrandTotal();
}

window.bomScriptForceProjectItem = <?= $bomScriptIsProjectItem ? 'true' : 'false' ?>;
const bomItemsData = <?php echo json_encode($bomScriptListBomItems, JSON_UNESCAPED_UNICODE); ?>;
const bomUnitOptions = <?php echo json_encode(['codes' => $unitCodes, 'labels' => $unitLabels], JSON_UNESCAPED_UNICODE); ?>;

function bomShowAlert(message, type) {
    const box = document.getElementById('form-alerts');
    if (box) {
        const safeType = ['success', 'danger', 'warning', 'info'].includes(type) ? type : 'info';
        box.innerHTML = '<div class="alert alert-' + safeType + ' py-2 mb-0" role="alert">' + message + '</div>';
        return;
    }
    alert(message);
}

function addBomCatalogRow() {
    const tbody = document.querySelector('#bom-table tbody');
    if (!tbody) return;
    const tr = document.createElement('tr');
    tr.className = 'bom-row bom-row-catalog';
    let optionsHtml = '<option value="">Selecione o componente</option>';
    (bomItemsData || []).forEach(function (item) {
        const label = (item.code + ' - ' + item.description).replace(/"/g, '&quot;');
        const avgCost = parseFloat(item.average_cost || 0) || 0;
        const unit = String(item.unit_name || '').replace(/"/g, '&quot;');
        optionsHtml += '<option value="' + item.id + '" data-average-cost="' + avgCost.toFixed(6) + '" data-unit="' + unit + '">' + label + '</option>';
    });
    tr.innerHTML = `
        <td data-label="Origem"><input type="hidden" name="bom_line_source[]" value="catalog"><span class="badge bg-secondary">Catálogo</span></td>
        <td data-label="Componente"><select name="bom_component_item_id[]" class="form-select form-select-sm bom-catalog-select" onchange="onBomCatalogSelectChange(this)">${optionsHtml}</select><input type="hidden" name="bom_manual_description[]" value=""></td>
        <td data-label="Tipo"><span class="text-muted small">—</span><input type="hidden" name="bom_manual_component_type[]" value=""></td>
        <td data-label="Qtd"><input type="number" step="0.000001" min="0" name="bom_quantity_per_batch[]" class="form-control form-control-sm bom-qty" value="0"></td>
        <td data-label="Perda"><input type="number" step="0.0001" min="0" name="bom_scrap_percent[]" class="form-control form-control-sm bom-scrap" value="0"></td>
        <td data-label="Un."><span class="bom-catalog-unit text-muted small">—</span><input type="hidden" name="bom_manual_unit[]" value=""></td>
        <td data-label="Custo"><span class="bom-catalog-cost-display text-muted small">—</span><input type="hidden" name="bom_manual_unit_cost[]" value=""><input type="hidden" class="bom-catalog-unit-cost" value="0"></td>
        <td data-label="Total" class="bom-line-total">0,000000</td>
        <td data-label="Ações" class="text-end"><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeBomRow(this)">Remover</button></td>
    `;
    tbody.appendChild(tr);
    bindBomRowInputs(tr);
    recalcBomGrandTotal();
}

function addBomManualRow() {
    if (!isProjectCategorySelected()) {
        bomShowAlert('Linhas manuais são permitidas apenas para itens da categoria PA - PROJETO.', 'warning');
        return;
    }
    const tbody = document.querySelector('#bom-table tbody');
    if (!tbody) return;
    let unitHtml = '';
    (bomUnitOptions.codes || []).forEach(function (code) {
        const sel = code === 'UN' ? ' selected' : '';
        const name = (bomUnitOptions.labels && bomUnitOptions.labels[code]) ? bomUnitOptions.labels[code] : code;
        unitHtml += '<option value="' + code + '"' + sel + '>' + name + ' (' + code + ')</option>';
    });
    const tr = document.createElement('tr');
    tr.className = 'bom-row bom-row-manual';
    tr.innerHTML = `
        <td data-label="Origem"><input type="hidden" name="bom_line_source[]" value="manual"><span class="badge bg-warning text-dark">Manual</span></td>
        <td data-label="Componente"><input type="text" name="bom_manual_description[]" class="form-control form-control-sm bom-manual-desc" maxlength="255" placeholder="Descrição do insumo"><input type="hidden" name="bom_component_item_id[]" value=""></td>
        <td data-label="Tipo"><select name="bom_manual_component_type[]" class="form-select form-select-sm bom-manual-type"><option value="MP" selected>MP</option><option value="EMB">MAE</option><option value="OTHER">Outro</option></select></td>
        <td data-label="Qtd"><input type="number" step="0.000001" min="0" name="bom_quantity_per_batch[]" class="form-control form-control-sm bom-qty" value="0"></td>
        <td data-label="Perda"><input type="number" step="0.0001" min="0" name="bom_scrap_percent[]" class="form-control form-control-sm bom-scrap" value="0"></td>
        <td data-label="Un."><select name="bom_manual_unit[]" class="form-select form-select-sm bom-manual-unit" data-prev-unit="UN">${unitHtml}</select></td>
        <td data-label="Custo"><input type="number" step="0.000001" min="0" name="bom_manual_unit_cost[]" class="form-control form-control-sm bom-manual-cost" value="0"></td>
        <td data-label="Total" class="bom-line-total">0,000000</td>
        <td data-label="Ações" class="text-end"><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeBomRow(this)">Remover</button></td>
    `;
    tbody.appendChild(tr);
    bindBomRowInputs(tr);
    recalcBomGrandTotal();
}

function parseBomDecimal(value) {
    const s = String(value ?? '').trim();
    if (s === '') return 0;
    if (/^\d{1,3}(\.\d{3})*(,\d+)?$/.test(s)) return parseFloat(s.replace(/\./g, '').replace(',', '.')) || 0;
    if (s.includes(',') && !s.includes('.')) return parseFloat(s.replace(',', '.')) || 0;
    return parseFloat(s) || 0;
}

function formatBomMoney(value) {
    return Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 6, maximumFractionDigits: 6 });
}

function initBomManualUnitSelect(select) {
    if (!select || select.dataset.bomBound) return;
    select.dataset.bomBound = '1';
    select.addEventListener('change', recalcBomGrandTotal);
}

function onBomCatalogSelectChange(select) {
    const row = select ? select.closest('tr') : null;
    if (!row) return;
    const opt = select.options[select.selectedIndex];
    const cost = parseBomDecimal(opt ? opt.getAttribute('data-average-cost') : '0');
    const unit = opt ? (opt.getAttribute('data-unit') || '—') : '—';
    const costHidden = row.querySelector('.bom-catalog-unit-cost');
    const costDisplay = row.querySelector('.bom-catalog-cost-display');
    const unitEl = row.querySelector('.bom-catalog-unit');
    if (costHidden) costHidden.value = cost > 0 ? cost.toFixed(6) : '0';
    if (costDisplay) costDisplay.textContent = cost > 0 ? formatBomMoney(cost) : '—';
    if (unitEl) unitEl.textContent = unit || '—';
    recalcBomGrandTotal();
}

function bindBomRowInputs(row) {
    if (!row) return;
    row.querySelectorAll('.bom-qty, .bom-scrap, .bom-manual-cost').forEach(function (el) {
        if (el.dataset.bomQtyBound) return;
        el.dataset.bomQtyBound = '1';
        el.addEventListener('input', recalcBomGrandTotal);
        el.addEventListener('change', recalcBomGrandTotal);
    });
    const catalogSelect = row.querySelector('.bom-catalog-select');
    if (catalogSelect && !catalogSelect.dataset.bomBound) {
        catalogSelect.dataset.bomBound = '1';
        catalogSelect.addEventListener('change', function () { onBomCatalogSelectChange(catalogSelect); });
    }
    const manualUnit = row.querySelector('.bom-manual-unit');
    if (manualUnit) initBomManualUnitSelect(manualUnit);
}

function recalcBomLineTotal(row) {
    const qty = parseBomDecimal(row.querySelector('.bom-qty')?.value);
    const scrap = parseBomDecimal(row.querySelector('.bom-scrap')?.value);
    let cost = 0;
    if (row.classList.contains('bom-row-manual')) {
        cost = parseBomDecimal(row.querySelector('.bom-manual-cost')?.value);
    } else {
        cost = parseBomDecimal(row.querySelector('.bom-catalog-unit-cost')?.value);
    }
    const effectiveQty = qty * (1 + scrap / 100);
    const total = (qty > 0 && cost > 0) ? effectiveQty * cost : 0;
    const cell = row.querySelector('.bom-line-total');
    if (cell) cell.textContent = formatBomMoney(total);
    return total;
}

function recalcBomGrandTotal() {
    let sum = 0;
    document.querySelectorAll('#bom-table tbody .bom-row').forEach(function (row) {
        sum += recalcBomLineTotal(row);
    });
    const el = document.getElementById('bom-grand-total');
    if (el) el.textContent = formatBomMoney(sum);
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#bom-table tbody .bom-row').forEach(bindBomRowInputs);
    document.querySelectorAll('#bom-table tbody .bom-row-catalog .bom-catalog-select').forEach(function (sel) {
        if (sel.value) onBomCatalogSelectChange(sel);
    });
    recalcBomGrandTotal();
    const simForm = document.getElementById('form-simulate-cost');
    if (simForm) {
        simForm.addEventListener('submit', function () {
            removeEmptyBomRowsBeforeSubmit();
        });
    }
});
</script>
