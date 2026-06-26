<script>
(function () {
    function parseSimDecimal(value) {
        const s = String(value ?? '').trim();
        if (s === '') return 0;
        if (/^\d{1,3}(\.\d{3})*(,\d+)?$/.test(s)) {
            return parseFloat(s.replace(/\./g, '').replace(',', '.')) || 0;
        }
        if (s.includes(',') && !s.includes('.')) {
            return parseFloat(s.replace(',', '.')) || 0;
        }
        return parseFloat(s) || 0;
    }

    function formatSimMoney(value) {
        return Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 4, maximumFractionDigits: 4 });
    }

    function formatSimHours(value) {
        return Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function getBatchSize() {
        const el = document.getElementById('standard_batch_size');
        const fromWindow = parseSimDecimal(window.simBreakdownBatchSize);
        if (!el) return fromWindow > 0 ? fromWindow : 1;
        const parsed = parseSimDecimal(el.value);
        return parsed > 0 ? parsed : (fromWindow > 0 ? fromWindow : 1);
    }

    function getBomRow(index) {
        return document.querySelector('#bom-table tbody .bom-row[data-line-index="' + index + '"]');
    }

    function getOpRow(index) {
        return document.querySelector('#sim-ops-table tbody .sim-op-row[data-line-index="' + index + '"]');
    }

    function recalcMaterialBreakdownRow(row) {
        if (!row) return;
        const qty = parseSimDecimal(row.querySelector('.sim-bd-mat-qty')?.value);
        const cost = parseSimDecimal(row.querySelector('.sim-bd-mat-cost')?.value);
        const scrap = parseSimDecimal(row.dataset.scrap || '0');
        const batchSize = getBatchSize();
        const effectiveQty = qty * (1 + scrap / 100);
        const batchTotal = qty > 0 ? effectiveQty * cost : 0;
        const skuTotal = batchSize > 0 ? batchTotal / batchSize : 0;
        const skuCell = row.querySelector('.sim-bd-mat-line-sku');
        const batchCell = row.querySelector('.sim-bd-mat-line-batch');
        if (skuCell) skuCell.textContent = formatSimMoney(skuTotal);
        if (batchCell) batchCell.textContent = formatSimMoney(batchTotal);
    }

    function recalcOperationBreakdownRow(row) {
        if (!row) return;
        const sap = parseSimDecimal(row.querySelector('.sim-bd-op-sap')?.value);
        const equip = parseSimDecimal(row.querySelector('.sim-bd-op-equip')?.value);
        const manual = parseSimDecimal(row.querySelector('.sim-bd-op-manual')?.value);
        const batchSize = getBatchSize();
        const skuTotal = sap + equip + manual;
        const batchTotal = skuTotal * batchSize;
        const skuCell = row.querySelector('.sim-bd-op-line-sku');
        const batchCell = row.querySelector('.sim-bd-op-line-batch');
        if (skuCell) skuCell.textContent = formatSimMoney(skuTotal);
        if (batchCell) batchCell.textContent = formatSimMoney(batchTotal);
    }

    function syncMaterialRowToStructure(index) {
        const bdRow = document.querySelector('#sim-bd-materials-table .sim-bd-mat-row[data-line-index="' + index + '"]');
        const bomRow = getBomRow(index);
        if (!bdRow || !bomRow) return;
        const qty = parseSimDecimal(bdRow.querySelector('.sim-bd-mat-qty')?.value);
        const cost = parseSimDecimal(bdRow.querySelector('.sim-bd-mat-cost')?.value);
        const qtyInput = bomRow.querySelector('.bom-qty');
        if (qtyInput) qtyInput.value = qty > 0 ? String(parseFloat(qty.toFixed(6))) : '0';
        if (bomRow.classList.contains('bom-row-manual')) {
            const costInput = bomRow.querySelector('.bom-manual-cost');
            if (costInput) costInput.value = cost > 0 ? String(parseFloat(cost.toFixed(6))) : '0';
        } else {
            const costInput = bomRow.querySelector('.bom-catalog-cost-input, .bom-catalog-unit-cost');
            if (costInput) costInput.value = cost > 0 ? String(parseFloat(cost.toFixed(6))) : '0';
        }
        if (typeof recalcBomGrandTotal === 'function') {
            recalcBomGrandTotal();
        }
        recalcMaterialBreakdownRow(bdRow);
    }

    function syncOperationRowToStructure(index) {
        const bdRow = document.querySelector('#sim-bd-operations-table .sim-bd-op-row[data-line-index="' + index + '"]');
        const opRow = getOpRow(index);
        if (!bdRow || !opRow) return;
        const minutes = parseSimDecimal(bdRow.querySelector('.sim-bd-op-min')?.value);
        const timeInput = opRow.querySelector('input[name="sim_op_time_per_batch_hours[]"]');
        const unitSelect = opRow.querySelector('select[name="sim_op_time_unit[]"]');
        if (timeInput) timeInput.value = minutes > 0 ? String(parseFloat(minutes.toFixed(6))) : '0';
        if (unitSelect) unitSelect.value = 'MIN';
        const sap = bdRow.querySelector('.sim-bd-op-sap')?.value ?? '';
        const equip = bdRow.querySelector('.sim-bd-op-equip')?.value ?? '';
        const manual = bdRow.querySelector('.sim-bd-op-manual')?.value ?? '';
        let sapHidden = opRow.querySelector('.sim-op-override-sap');
        let equipHidden = opRow.querySelector('.sim-op-override-equip');
        let manualHidden = opRow.querySelector('.sim-op-override-manual');
        if (!sapHidden) {
            sapHidden = document.createElement('input');
            sapHidden.type = 'hidden';
            sapHidden.name = 'sim_op_override_sap_sku[]';
            sapHidden.className = 'sim-op-override-sap';
            opRow.querySelector('td')?.appendChild(sapHidden);
        }
        if (!equipHidden) {
            equipHidden = document.createElement('input');
            equipHidden.type = 'hidden';
            equipHidden.name = 'sim_op_override_equip_sku[]';
            equipHidden.className = 'sim-op-override-equip';
            opRow.querySelector('td')?.appendChild(equipHidden);
        }
        if (!manualHidden) {
            manualHidden = document.createElement('input');
            manualHidden.type = 'hidden';
            manualHidden.name = 'sim_op_override_manual_sku[]';
            manualHidden.className = 'sim-op-override-manual';
            opRow.querySelector('td')?.appendChild(manualHidden);
        }
        sapHidden.value = sap;
        equipHidden.value = equip;
        manualHidden.value = manual;
        recalcOperationBreakdownRow(bdRow);
    }

    function applyBaselineMaterialToStructure(index, baseline) {
        const bomRow = getBomRow(index);
        if (!baseline) {
            if (bomRow) bomRow.remove();
            const bdRow = document.querySelector('#sim-bd-materials-table .sim-bd-mat-row[data-line-index="' + index + '"]');
            if (bdRow) bdRow.remove();
            if (typeof recalcBomGrandTotal === 'function') recalcBomGrandTotal();
            return;
        }
        if (!bomRow) return;
        const isManual = baseline.line_source === 'manual';
        if (isManual) {
            const desc = bomRow.querySelector('.bom-manual-desc');
            const type = bomRow.querySelector('.bom-manual-type');
            const unit = bomRow.querySelector('.bom-manual-unit');
            const cost = bomRow.querySelector('.bom-manual-cost');
            if (desc) desc.value = baseline.manual_description || '';
            if (type) type.value = baseline.manual_component_type || 'MP';
            if (unit) unit.value = baseline.manual_unit || 'UN';
            if (cost) cost.value = baseline.manual_unit_cost > 0 ? String(baseline.manual_unit_cost) : '0';
        } else {
            const select = bomRow.querySelector('.bom-catalog-select');
            const cost = bomRow.querySelector('.bom-catalog-cost-input, .bom-catalog-unit-cost');
            if (select && baseline.component_item_id > 0) select.value = String(baseline.component_item_id);
            if (cost) cost.value = baseline.component_cost > 0 ? String(baseline.component_cost) : '0';
            if (typeof onBomCatalogSelectChange === 'function' && select) onBomCatalogSelectChange(select);
        }
        const qty = bomRow.querySelector('.bom-qty');
        const scrap = bomRow.querySelector('.bom-scrap');
        if (qty) qty.value = baseline.quantity_per_batch > 0 ? String(baseline.quantity_per_batch) : '0';
        if (scrap) scrap.value = baseline.scrap_percent > 0 ? String(baseline.scrap_percent) : '0';
        if (typeof recalcBomGrandTotal === 'function') recalcBomGrandTotal();
        syncBreakdownMaterialFromStructure(index, baseline);
    }

    function syncBreakdownMaterialFromStructure(index, baseline) {
        const bdRow = document.querySelector('#sim-bd-materials-table .sim-bd-mat-row[data-line-index="' + index + '"]');
        const bomRow = getBomRow(index);
        if (!bdRow || !bomRow) return;
        const qtyInput = bdRow.querySelector('.sim-bd-mat-qty');
        const costInput = bdRow.querySelector('.sim-bd-mat-cost');
        if (baseline) {
            if (qtyInput) qtyInput.value = baseline.quantity_per_batch > 0 ? String(parseFloat(Number(baseline.quantity_per_batch).toFixed(6))) : '0';
            const cost = isManualBaseline(baseline)
                ? baseline.manual_unit_cost
                : baseline.component_cost;
            if (costInput) costInput.value = cost > 0 ? String(parseFloat(Number(cost).toFixed(6))) : '0';
            bdRow.dataset.scrap = String(baseline.scrap_percent || 0);
        } else if (bomRow) {
            if (qtyInput) qtyInput.value = bomRow.querySelector('.bom-qty')?.value || '0';
            if (costInput) {
                if (bomRow.classList.contains('bom-row-manual')) {
                    costInput.value = bomRow.querySelector('.bom-manual-cost')?.value || '0';
                } else {
                    costInput.value = bomRow.querySelector('.bom-catalog-cost-input, .bom-catalog-unit-cost')?.value || '0';
                }
            }
            bdRow.dataset.scrap = bomRow.querySelector('.bom-scrap')?.value || '0';
        }
        recalcMaterialBreakdownRow(bdRow);
    }

    function isManualBaseline(baseline) {
        return baseline && baseline.line_source === 'manual';
    }

    function applyBaselineOperationToStructure(index, baseline) {
        const opRow = getOpRow(index);
        if (!baseline) {
            if (opRow) opRow.remove();
            const bdRow = document.querySelector('#sim-bd-operations-table .sim-bd-op-row[data-line-index="' + index + '"]');
            if (bdRow) bdRow.remove();
            return;
        }
        if (!opRow) return;
        const opSelect = opRow.querySelector('select[name="sim_op_operation_id[]"]');
        const seqInput = opRow.querySelector('input[name="sim_op_sequence[]"]');
        const timeInput = opRow.querySelector('input[name="sim_op_time_per_batch_hours[]"]');
        const unitSelect = opRow.querySelector('select[name="sim_op_time_unit[]"]');
        if (opSelect && baseline.inv_operation_id > 0) opSelect.value = String(baseline.inv_operation_id);
        if (seqInput) seqInput.value = String(baseline.sequence || (index + 1));
        if (timeInput) timeInput.value = baseline.time_per_batch_hours > 0 ? String(baseline.time_per_batch_hours) : '0';
        if (unitSelect) unitSelect.value = baseline.time_unit || 'MIN';
        const sapHidden = opRow.querySelector('.sim-op-override-sap');
        const equipHidden = opRow.querySelector('.sim-op-override-equip');
        const manualHidden = opRow.querySelector('.sim-op-override-manual');
        if (sapHidden) sapHidden.value = '';
        if (equipHidden) equipHidden.value = '';
        if (manualHidden) manualHidden.value = '';
        syncBreakdownOperationFromStructure(index, baseline);
    }

    function syncBreakdownOperationFromStructure(index, baseline) {
        const bdRow = document.querySelector('#sim-bd-operations-table .sim-bd-op-row[data-line-index="' + index + '"]');
        if (!bdRow) return;
        const minInput = bdRow.querySelector('.sim-bd-op-min');
        const sapInput = bdRow.querySelector('.sim-bd-op-sap');
        const equipInput = bdRow.querySelector('.sim-bd-op-equip');
        const manualInput = bdRow.querySelector('.sim-bd-op-manual');
        if (baseline) {
            if (minInput) minInput.value = baseline.time_minutes > 0 ? String(parseFloat(Number(baseline.time_minutes).toFixed(4))) : '0';
            if (sapInput) sapInput.value = '0';
            if (equipInput) equipInput.value = '0';
            if (manualInput) manualInput.value = '0';
        }
        syncOperationRowToStructure(index);
    }

    function initSimBreakdownInline() {
        const matTable = document.getElementById('sim-bd-materials-table');
        const opTable = document.getElementById('sim-bd-operations-table');
        if (!matTable && !opTable) return;

        if (matTable) {
            matTable.addEventListener('input', function (event) {
                const row = event.target.closest('.sim-bd-mat-row');
                if (!row) return;
                const index = row.getAttribute('data-line-index');
                syncMaterialRowToStructure(index);
            });
            matTable.addEventListener('click', function (event) {
                const btn = event.target.closest('.sim-bd-reset-mat');
                if (!btn) return;
                const row = btn.closest('.sim-bd-mat-row');
                if (!row) return;
                const index = parseInt(row.getAttribute('data-line-index') || '-1', 10);
                const baseline = (window.simBaselineBom || [])[index] || null;
                applyBaselineMaterialToStructure(index, baseline);
            });
        }

        if (opTable) {
            opTable.addEventListener('input', function (event) {
                const row = event.target.closest('.sim-bd-op-row');
                if (!row) return;
                const index = row.getAttribute('data-line-index');
                syncOperationRowToStructure(index);
            });
            opTable.addEventListener('click', function (event) {
                const btn = event.target.closest('.sim-bd-reset-op');
                if (!btn) return;
                const row = btn.closest('.sim-bd-op-row');
                if (!row) return;
                const index = parseInt(row.getAttribute('data-line-index') || '-1', 10);
                const baseline = (window.simBaselineOps || [])[index] || null;
                applyBaselineOperationToStructure(index, baseline);
            });
        }

        document.querySelectorAll('#sim-bd-materials-table .sim-bd-mat-row').forEach(function (row) {
            recalcMaterialBreakdownRow(row);
        });
        document.querySelectorAll('#sim-bd-operations-table .sim-bd-op-row').forEach(function (row) {
            recalcOperationBreakdownRow(row);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSimBreakdownInline);
    } else {
        initSimBreakdownInline();
    }
})();
</script>
