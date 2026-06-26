<?php
/**
 * Reset linha a linha ao cadastro original (simulação).
 *
 * @var list<array<string, mixed>> $baseline_bom
 * @var list<array<string, mixed>> $baseline_operations
 */
if (!isset($this)) {
    exit;
}
$normalizeBaselineBom = static function (array $lines): array {
    $out = [];
    foreach ($lines as $line) {
        $isManual = (string)($line['line_source'] ?? 'catalog') === 'manual';
        $out[] = [
            'line_source' => $isManual ? 'manual' : 'catalog',
            'component_item_id' => (int)($line['component_item_id'] ?? 0),
            'quantity_per_batch' => (float)($line['quantity_per_batch'] ?? 0),
            'scrap_percent' => (float)($line['scrap_percent'] ?? 0),
            'manual_description' => (string)($line['manual_description'] ?? ''),
            'manual_component_type' => (string)($line['manual_component_type'] ?? 'MP'),
            'manual_unit' => (string)($line['manual_unit'] ?? 'UN'),
            'manual_unit_cost' => (float)($line['manual_unit_cost'] ?? 0),
            'component_cost' => (float)($line['component_cost'] ?? $line['average_cost'] ?? 0),
        ];
    }

    return $out;
};
$normalizeBaselineOps = static function (array $lines): array {
    $out = [];
    foreach ($lines as $line) {
        $rawTime = (float)($line['time_per_batch_hours'] ?? 0);
        $unit = strtoupper((string)($line['time_unit'] ?? 'MIN'));
        $out[] = [
            'id' => (int)($line['id'] ?? 0),
            'inv_operation_id' => (int)($line['inv_operation_id'] ?? 0),
            'sequence' => (int)($line['sequence'] ?? 1),
            'time_per_batch_hours' => $rawTime,
            'time_unit' => $unit,
        ];
    }

    return $out;
};
$baselineBom = $baseline_bom ?? ($this->data['baseline_bom'] ?? []);
$baselineOps = $baseline_operations ?? ($this->data['baseline_operations'] ?? []);
?>
<script>
window.simBaselineBom = <?= json_encode($normalizeBaselineBom($baselineBom), JSON_UNESCAPED_UNICODE) ?>;
window.simBaselineOps = <?= json_encode($normalizeBaselineOps($baselineOps), JSON_UNESCAPED_UNICODE) ?>;

function simResetBomRow(index) {
    const baseline = (window.simBaselineBom || [])[index] || null;
    const bomRow = document.querySelector('#bom-table tbody .bom-row[data-line-index="' + index + '"]');
    if (!baseline) {
        if (bomRow) bomRow.remove();
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
    if (qty) qty.value = typeof formatBomQty === 'function' ? formatBomQty(baseline.quantity_per_batch) : String(baseline.quantity_per_batch || 0);
    if (scrap) scrap.value = baseline.scrap_percent > 0 ? String(baseline.scrap_percent) : '0';
    if (typeof recalcBomGrandTotal === 'function') recalcBomGrandTotal();
}

function simResetOpRow(index) {
    const baseline = (window.simBaselineOps || [])[index] || null;
    const opRow = document.querySelector('#sim-ops-table tbody .sim-op-row[data-line-index="' + index + '"]');
    if (!baseline) {
        if (opRow) opRow.remove();
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
    const sap = opRow.querySelector('.sim-op-override-sap');
    const equip = opRow.querySelector('.sim-op-override-equip');
    const manual = opRow.querySelector('.sim-op-override-manual');
    if (sap) sap.value = '';
    if (equip) equip.value = '';
    if (manual) manual.value = '';
}
</script>
