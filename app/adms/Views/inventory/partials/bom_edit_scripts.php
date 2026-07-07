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
$bomScriptSimulationMode = (bool)($bomScriptSimulationMode ?? ($bomSimulationMode ?? false));

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
    if (typeof window.invItemMarkDirty === 'function') {
        window.invItemMarkDirty();
    }
}

function isProjectCategorySelected() {
    if (window.bomSimulationMode === true) {
        return true;
    }
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
        const qty = parseBomDecimal(row.querySelector('.bom-qty')?.value);
        if (isManual) {
            const desc = (row.querySelector('.bom-manual-desc')?.value || '').trim();
            const cost = parseBomDecimal(row.querySelector('.bom-manual-cost')?.value);
            if (qty <= 0 && desc === '' && cost <= 0) row.remove();
            return;
        }
        const compId = parseInt(row.querySelector('.bom-catalog-select')?.value || '0', 10);
        if (qty <= 0 && compId <= 0) row.remove();
    });
    recalcBomGrandTotal();
}

window.bomScriptForceProjectItem = <?= $bomScriptIsProjectItem ? 'true' : 'false' ?>;
window.bomSimulationMode = <?= $bomScriptSimulationMode ? 'true' : 'false' ?>;
window.bomHideTipoColumn = <?= $bomScriptSimulationMode ? 'true' : 'false' ?>;
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

function bomCatalogCostCellHtml(initialCost) {
    const cost = Number(initialCost || 0);
    if (window.bomSimulationMode === true) {
        const value = cost > 0 ? cost.toFixed(6) : '0';
        return '<input type="number" step="0.000001" min="0" name="bom_catalog_unit_cost[]" class="form-control form-control-sm bom-catalog-cost-input bom-catalog-unit-cost" value="' + value + '"><input type="hidden" name="bom_manual_unit_cost[]" value="">';
    }
    return '<span class="bom-catalog-cost-display text-muted small">—</span><input type="hidden" name="bom_manual_unit_cost[]" value=""><input type="hidden" name="bom_catalog_unit_cost[]" value=""><input type="hidden" class="bom-catalog-unit-cost" value="0">';
}

function readBomCatalogUnitCost(row) {
    const input = row.querySelector('.bom-catalog-cost-input');
    if (input) {
        return parseBomDecimal(input.value);
    }
    return parseBomDecimal(row.querySelector('.bom-catalog-unit-cost')?.value);
}

function addBomCatalogRow() {
    const tbody = document.querySelector('#bom-table tbody');
    if (!tbody) return;
    let nextIndex = 0;
    tbody.querySelectorAll('.bom-row[data-line-index]').forEach(function (row) {
        const idx = parseInt(row.getAttribute('data-line-index') || '-1', 10);
        if (idx >= nextIndex) nextIndex = idx + 1;
    });
    if (tbody.querySelectorAll('.bom-row').length > nextIndex) {
        nextIndex = tbody.querySelectorAll('.bom-row').length;
    }
    const tr = document.createElement('tr');
    tr.className = 'bom-row bom-row-catalog';
    if (window.bomSimulationMode === true) {
        tr.setAttribute('data-line-index', String(nextIndex));
    }
    let optionsHtml = '<option value="">Selecione o componente</option>';
    (bomItemsData || []).forEach(function (item) {
        const label = (item.code + ' - ' + item.description).replace(/"/g, '&quot;');
        const avgCost = parseFloat(item.average_cost || 0) || 0;
        const unit = String(item.unit_name || '').replace(/"/g, '&quot;');
        optionsHtml += '<option value="' + item.id + '" data-average-cost="' + avgCost.toFixed(6) + '" data-unit="' + unit + '">' + label + '</option>';
    });
    const simMode = window.bomSimulationMode === true;
    const hideTipo = window.bomHideTipoColumn === true;
    const tipoCell = hideTipo
        ? (simMode ? '<input type="hidden" name="bom_manual_component_type[]" value="">' : '')
        : '<td data-label="Tipo"><span class="text-muted small">—</span><input type="hidden" name="bom_manual_component_type[]" value=""></td>';
    const catalogSelectClass = simMode ? 'form-select bom-catalog-select-sim bom-catalog-select' : 'form-select form-select-sm bom-catalog-select';
    const qtyTd = hideTipo ? '<td data-label="Qtd" class="bom-qty-col">' : '<td data-label="Qtd">';
    tr.innerHTML = `
        <td data-label="Origem"><input type="hidden" name="bom_line_source[]" value="catalog"><span class="badge bg-secondary">Catálogo</span></td>
        <td data-label="Componente"><select name="bom_component_item_id[]" class="${catalogSelectClass}">${optionsHtml}</select><input type="hidden" name="bom_manual_description[]" value=""></td>
        ${tipoCell}
        ${qtyTd}<input type="text" inputmode="decimal" autocomplete="off" name="bom_quantity_per_batch[]" class="form-control form-control-sm bom-qty" value="0"></td>
        <td data-label="Perda"><input type="number" step="0.0001" min="0" name="bom_scrap_percent[]" class="form-control form-control-sm bom-scrap" value="0"></td>
        <td data-label="Un."><span class="bom-catalog-unit text-muted small">—</span><input type="hidden" name="bom_manual_unit[]" value=""></td>
        <td data-label="Custo">${bomCatalogCostCellHtml(0)}</td>
        <td data-label="Total" class="bom-line-total">0,000000</td>
        <td data-label="Ações" class="text-end"><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeBomRow(this)">Remover</button></td>
    `;
    if (window.bomSimulationMode === true) {
        tr.innerHTML = tr.innerHTML.replace(
            '<td data-label="Ações" class="text-end"><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeBomRow(this)">Remover</button></td>',
            '<td data-label="Ações" class="text-end"><div class="d-flex gap-1 justify-content-end">'
            + '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="simResetBomRow(' + nextIndex + ')" title="Voltar ao cadastro original"><i class="fa-solid fa-rotate-left"></i></button>'
            + '<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeBomRow(this)">Remover</button></div></td>'
        );
    }
    tbody.appendChild(tr);
    bindBomRowInputs(tr);
    recalcBomGrandTotal();
    if (typeof window.invItemMarkDirty === 'function') {
        window.invItemMarkDirty();
    }
}

function addBomManualRow() {
    if (!isProjectCategorySelected()) {
        bomShowAlert('Linhas manuais são permitidas apenas para itens da categoria PA - PROJETO.', 'warning');
        return;
    }
    const tbody = document.querySelector('#bom-table tbody');
    if (!tbody) return;
    let nextIndex = 0;
    tbody.querySelectorAll('.bom-row[data-line-index]').forEach(function (row) {
        const idx = parseInt(row.getAttribute('data-line-index') || '-1', 10);
        if (idx >= nextIndex) nextIndex = idx + 1;
    });
    if (tbody.querySelectorAll('.bom-row').length > nextIndex) {
        nextIndex = tbody.querySelectorAll('.bom-row').length;
    }
    let unitHtml = '';
    (bomUnitOptions.codes || []).forEach(function (code) {
        const sel = code === 'UN' ? ' selected' : '';
        const name = (bomUnitOptions.labels && bomUnitOptions.labels[code]) ? bomUnitOptions.labels[code] : code;
        unitHtml += '<option value="' + code + '"' + sel + '>' + name + ' (' + code + ')</option>';
    });
    const tr = document.createElement('tr');
    tr.className = 'bom-row bom-row-manual';
    if (window.bomSimulationMode === true) {
        tr.setAttribute('data-line-index', String(nextIndex));
    }
    const simMode = window.bomSimulationMode === true;
    const hideTipo = window.bomHideTipoColumn === true;
    const manualTypeInline = hideTipo
        ? '<div class="d-flex align-items-center gap-2 mt-2"><span class="small text-muted text-nowrap">Classificação CVAR:</span>'
          + '<select name="bom_manual_component_type[]" class="form-select form-select-sm bom-manual-type" style="max-width:8rem">'
          + '<option value="MP" selected>MP</option><option value="EMB">MAE</option><option value="OTHER">Outro</option></select></div>'
        : '';
    const tipoCell = hideTipo ? '' : '<td data-label="Tipo"><select name="bom_manual_component_type[]" class="form-select form-select-sm bom-manual-type"><option value="MP" selected>MP</option><option value="EMB">MAE</option><option value="OTHER">Outro</option></select></td>';
    const descClass = hideTipo ? 'form-control bom-manual-desc' : 'form-control form-control-sm bom-manual-desc';
    const qtyTd = hideTipo ? '<td data-label="Qtd" class="bom-qty-col">' : '<td data-label="Qtd">';
    tr.innerHTML = `
        <td data-label="Origem"><input type="hidden" name="bom_line_source[]" value="manual"><span class="badge bg-warning text-dark">Manual</span></td>
        <td data-label="Componente"><input type="text" name="bom_manual_description[]" class="${descClass}" maxlength="255" placeholder="Descrição do insumo">${manualTypeInline}<input type="hidden" name="bom_component_item_id[]" value=""></td>
        ${tipoCell}
        ${qtyTd}<input type="text" inputmode="decimal" autocomplete="off" name="bom_quantity_per_batch[]" class="form-control form-control-sm bom-qty" value="0"></td>
        <td data-label="Perda"><input type="number" step="0.0001" min="0" name="bom_scrap_percent[]" class="form-control form-control-sm bom-scrap" value="0"></td>
        <td data-label="Un."><select name="bom_manual_unit[]" class="form-select form-select-sm bom-manual-unit">${unitHtml}</select></td>
        <td data-label="Custo"><input type="hidden" name="bom_catalog_unit_cost[]" value=""><input type="number" step="0.000001" min="0" name="bom_manual_unit_cost[]" class="form-control form-control-sm bom-manual-cost" value="0"></td>
        <td data-label="Total" class="bom-line-total">0,000000</td>
        <td data-label="Ações" class="text-end"><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeBomRow(this)">Remover</button></td>
    `;
    if (window.bomSimulationMode === true) {
        tr.innerHTML = tr.innerHTML.replace(
            '<td data-label="Ações" class="text-end"><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeBomRow(this)">Remover</button></td>',
            '<td data-label="Ações" class="text-end"><div class="d-flex gap-1 justify-content-end">'
            + '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="simResetBomRow(' + nextIndex + ')" title="Voltar ao cadastro original"><i class="fa-solid fa-rotate-left"></i></button>'
            + '<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeBomRow(this)">Remover</button></div></td>'
        );
    }
    tbody.appendChild(tr);
    bindBomRowInputs(tr);
    recalcBomGrandTotal();
    if (typeof window.invItemMarkDirty === 'function') {
        window.invItemMarkDirty();
    }
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

function formatBomQty(value) {
    const n = parseBomDecimal(value);
    if (!Number.isFinite(n) || Math.abs(n) < 1e-12) {
        return '0';
    }
    let s = n.toFixed(6);
    s = s.replace(/\.?0+$/, '');
    return s === '' ? '0' : s;
}

function onBomCatalogSelectChange(select) {
    const row = select ? select.closest('tr') : null;
    if (!row) return;
    const opt = select.options[select.selectedIndex];
    if (select) {
        select.title = opt && opt.value ? String(opt.textContent || '').trim() : '';
    }
    const cost = parseBomDecimal(opt ? opt.getAttribute('data-average-cost') : '0');
    const unit = opt ? (opt.getAttribute('data-unit') || '—') : '—';
    const costHidden = row.querySelector('.bom-catalog-unit-cost');
    const costInput = row.querySelector('.bom-catalog-cost-input');
    const costDisplay = row.querySelector('.bom-catalog-cost-display');
    const unitEl = row.querySelector('.bom-catalog-unit');
    if (costInput) {
        costInput.value = cost > 0 ? cost.toFixed(6) : '0';
    } else if (costHidden) {
        costHidden.value = cost > 0 ? cost.toFixed(6) : '0';
    }
    if (costDisplay) costDisplay.textContent = cost > 0 ? formatBomMoney(cost) : '—';
    if (unitEl) unitEl.textContent = unit || '—';
    recalcBomGrandTotal();
}

function initBomTableLiveRecalc() {
    const table = document.getElementById('bom-table');
    if (!table || table.dataset.bomLiveRecalc === '1') {
        return;
    }
    table.dataset.bomLiveRecalc = '1';
    table.addEventListener('input', function (event) {
        if (event.target.closest('tbody .bom-row')) {
            recalcBomGrandTotal();
        }
    });
    table.addEventListener('change', function (event) {
        const target = event.target;
        if (!target.closest('tbody .bom-row')) {
            return;
        }
        if (target.classList.contains('bom-catalog-select')) {
            onBomCatalogSelectChange(target);
            return;
        }
        recalcBomGrandTotal();
    });
}

function bindBomRowInputs(row) {
    if (!row) return;
    const catalogSelect = row.querySelector('.bom-catalog-select');
    if (catalogSelect && catalogSelect.value) {
        onBomCatalogSelectChange(catalogSelect);
    }
}

function recalcBomLineTotal(row) {
    const qty = parseBomDecimal(row.querySelector('.bom-qty')?.value);
    const scrap = parseBomDecimal(row.querySelector('.bom-scrap')?.value);
    let cost = 0;
    if (row.classList.contains('bom-row-manual')) {
        cost = parseBomDecimal(row.querySelector('.bom-manual-cost')?.value);
    } else {
        cost = readBomCatalogUnitCost(row);
    }
    const effectiveQty = qty * (1 + scrap / 100);
    const total = qty > 0 ? effectiveQty * cost : 0;
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

function initBomTableUi() {
    initBomTableLiveRecalc();
    document.querySelectorAll('#bom-table tbody .bom-row').forEach(bindBomRowInputs);
    document.querySelectorAll('#bom-table tbody .bom-row-catalog .bom-catalog-select').forEach(function (sel) {
        if (sel.value) onBomCatalogSelectChange(sel);
    });
    recalcBomGrandTotal();

    const bomTab = document.getElementById('tab-bom');
    if (bomTab) {
        bomTab.addEventListener('shown.bs.tab', recalcBomGrandTotal);
    }

    const simForm = document.getElementById('form-simulate-cost');
    if (simForm) {
        simForm.addEventListener('submit', function () {
            removeEmptyBomRowsBeforeSubmit();
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBomTableUi);
} else {
    initBomTableUi();
}
</script>
