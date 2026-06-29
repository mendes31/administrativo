<?php
/**
 * Bloco de leitura — sincronização SAP (tela de visualização).
 *
 * @var array<string, mixed> $item
 */
$item = $item ?? [];
if (trim((string)($item['erp_code'] ?? '')) === '') {
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

    return strlen($hash) > 20 ? substr($hash, 0, 20) . '…' : $hash;
};

$pendingBom = !empty($item['sap_structure_pending']);
$pendingRoute = !empty($item['sap_route_pending']);
?>
<div class="card mb-4 border-light shadow">
  <div class="card-header py-3">
    <span class="fw-semibold"><i class="fa-solid fa-rotate text-primary me-1"></i> Sincronização SAP</span>
  </div>
  <div class="card-body p-4">
    <div class="row g-4">
      <div class="col-12 col-md-4">
        <dl class="row mb-0 small">
          <dt class="col-5 text-muted">Versão BEAS</dt>
          <dd class="col-7 fw-semibold mb-2"><?= htmlspecialchars((string)($item['sap_beas_version'] ?? '—'), ENT_QUOTES) ?></dd>
          <dt class="col-5 text-muted">Última sync</dt>
          <dd class="col-7 fw-semibold mb-2"><?= htmlspecialchars($fmtDate($item['sap_last_synced_at'] ?? null), ENT_QUOTES) ?></dd>
          <dt class="col-5 text-muted">UpdateDate SAP</dt>
          <dd class="col-7 fw-semibold mb-0"><?= htmlspecialchars((string)($item['sap_update_date'] ?? '—'), ENT_QUOTES) ?></dd>
        </dl>
      </div>
      <div class="col-12 col-md-4">
        <dl class="row mb-0 small">
          <dt class="col-5 text-muted">Hash item</dt>
          <dd class="col-7 font-monospace mb-2" title="<?= htmlspecialchars((string)($item['sap_item_hash'] ?? ''), ENT_QUOTES) ?>"><?= htmlspecialchars($shortHash($item['sap_item_hash'] ?? null), ENT_QUOTES) ?></dd>
          <dt class="col-5 text-muted">Hash BOM</dt>
          <dd class="col-7 font-monospace mb-2" title="<?= htmlspecialchars((string)($item['sap_bom_hash'] ?? ''), ENT_QUOTES) ?>"><?= htmlspecialchars($shortHash($item['sap_bom_hash'] ?? null), ENT_QUOTES) ?></dd>
          <dt class="col-5 text-muted">Hash rota</dt>
          <dd class="col-7 font-monospace mb-0" title="<?= htmlspecialchars((string)($item['sap_route_hash'] ?? ''), ENT_QUOTES) ?>"><?= htmlspecialchars($shortHash($item['sap_route_hash'] ?? null), ENT_QUOTES) ?></dd>
        </dl>
      </div>
      <div class="col-12 col-md-4">
        <h6 class="text-muted text-uppercase small mb-3">Pendências</h6>
        <div class="d-flex flex-wrap gap-2">
          <?php if ($pendingBom): ?>
            <span class="badge bg-warning text-dark">Lista de materiais pendente</span>
          <?php endif; ?>
          <?php if ($pendingRoute): ?>
            <span class="badge bg-warning text-dark">Rota pendente</span>
          <?php endif; ?>
          <?php if (!$pendingBom && !$pendingRoute): ?>
            <span class="badge bg-success-subtle text-success border">Nenhuma pendência</span>
          <?php endif; ?>
        </div>
        <p class="small text-muted mt-3 mb-0">Atualizado pela rotina <strong>Sincronizar SAP</strong>. Itens <em>inativos no SAP</em> também podem importar BOM/rota. Se as pendências persistirem, use o botão <strong>Estrutura</strong> ou <em>Avançado → Estruturas (completo)</em>.</p>
      </div>
    </div>
  </div>
</div>
