<?php
/**
 * Campos de controle da sincronização SAP (somente leitura).
 *
 * @var array<string, mixed> $item
 */
$item = $item ?? [];
$hasErp = trim((string)($item['erp_code'] ?? '')) !== '';
if (!$hasErp) {
    return;
}

$fmtDate = static function (?string $value): string {
    $value = trim((string)($value ?? ''));
    if ($value === '') {
        return '—';
    }
    $ts = strtotime($value);

    return $ts !== false ? date('d/m/Y H:i', $ts) : $value;
};

$shortHash = static function (?string $hash): string {
    $hash = trim((string)($hash ?? ''));
    if ($hash === '') {
        return '—';
    }

    return strlen($hash) > 16 ? substr($hash, 0, 16) . '…' : $hash;
};

$pendingBom = !empty($item['sap_structure_pending']);
$pendingRoute = !empty($item['sap_route_pending']);
?>
<div class="col-12">
    <hr class="my-1">
    <p class="small text-muted mb-2">Controle da sincronização SAP (preenchido automaticamente; não editável).</p>
</div>

<div class="col-12 col-md-3">
    <label class="form-label">Versão BEAS (SAP)</label>
    <input type="text" class="form-control form-control-sm bg-light" readonly
        value="<?= htmlspecialchars((string)($item['sap_beas_version'] ?? ''), ENT_QUOTES) ?>"
        placeholder="—">
</div>

<div class="col-12 col-md-3">
    <label class="form-label">Última sync SAP</label>
    <input type="text" class="form-control form-control-sm bg-light" readonly
        value="<?= htmlspecialchars($fmtDate($item['sap_last_synced_at'] ?? null), ENT_QUOTES) ?>">
</div>

<div class="col-12 col-md-3">
    <label class="form-label">UpdateDate SAP</label>
    <input type="text" class="form-control form-control-sm bg-light" readonly
        value="<?= htmlspecialchars((string)($item['sap_update_date'] ?? '—'), ENT_QUOTES) ?>">
</div>

<div class="col-12 col-md-3">
    <label class="form-label">Pendências</label>
    <div class="d-flex flex-wrap gap-1 mt-1">
        <?php if ($pendingBom): ?>
            <span class="badge bg-warning text-dark">BOM pendente</span>
        <?php endif; ?>
        <?php if ($pendingRoute): ?>
            <span class="badge bg-warning text-dark">Rota pendente</span>
        <?php endif; ?>
        <?php if (!$pendingBom && !$pendingRoute): ?>
            <span class="badge bg-success-subtle text-success border">Nenhuma</span>
        <?php endif; ?>
    </div>
</div>

<div class="col-12 col-md-4">
    <label class="form-label" title="<?= htmlspecialchars((string)($item['sap_item_hash'] ?? ''), ENT_QUOTES) ?>">Hash do item</label>
    <input type="text" class="form-control form-control-sm bg-light font-monospace" readonly
        value="<?= htmlspecialchars($shortHash($item['sap_item_hash'] ?? null), ENT_QUOTES) ?>"
        title="<?= htmlspecialchars((string)($item['sap_item_hash'] ?? ''), ENT_QUOTES) ?>">
</div>

<div class="col-12 col-md-4">
    <label class="form-label" title="<?= htmlspecialchars((string)($item['sap_bom_hash'] ?? ''), ENT_QUOTES) ?>">Hash BOM</label>
    <input type="text" class="form-control form-control-sm bg-light font-monospace" readonly
        value="<?= htmlspecialchars($shortHash($item['sap_bom_hash'] ?? null), ENT_QUOTES) ?>"
        title="<?= htmlspecialchars((string)($item['sap_bom_hash'] ?? ''), ENT_QUOTES) ?>">
</div>

<div class="col-12 col-md-4">
    <label class="form-label" title="<?= htmlspecialchars((string)($item['sap_route_hash'] ?? ''), ENT_QUOTES) ?>">Hash rota</label>
    <input type="text" class="form-control form-control-sm bg-light font-monospace" readonly
        value="<?= htmlspecialchars($shortHash($item['sap_route_hash'] ?? null), ENT_QUOTES) ?>"
        title="<?= htmlspecialchars((string)($item['sap_route_hash'] ?? ''), ENT_QUOTES) ?>">
</div>
