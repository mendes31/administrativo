<?php
/**
 * Tabelas de resultado editáveis (materiais + rota) na simulação.
 *
 * @var array<string, mixed> $breakdown
 * @var float $batchSize
 * @var list<array<string, mixed>> $baseline_bom
 * @var list<array<string, mixed>> $baseline_operations
 */
if (!isset($this)) {
    exit;
}
$breakdown = $breakdown ?? [];
$batchSize = (float)($batchSize ?? 1);
if ($batchSize <= 0) {
    $batchSize = 1.0;
}
$baselineBom = $baseline_bom ?? [];
$baselineOps = $baseline_operations ?? [];
$fmtMoney = static fn(float $v): string => number_format($v, 4, ',', '.');
$fmtHours = static fn(float $v): string => number_format($v, 2, ',', '.');
$lineBatch = static function (array $line, string $key, float $bs): float {
    return (float)($line[$key . '_batch'] ?? ((float)($line[$key] ?? 0) * $bs));
};

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
            'manual_unit_cost' => (float)($line['manual_unit_cost'] ?? $line['component_cost'] ?? 0),
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
        $minutes = $unit === 'H' ? $rawTime * 60.0 : $rawTime;
        $out[] = [
            'id' => (int)($line['id'] ?? 0),
            'inv_operation_id' => (int)($line['inv_operation_id'] ?? 0),
            'sequence' => (int)($line['sequence'] ?? 1),
            'time_per_batch_hours' => $rawTime,
            'time_unit' => $unit,
            'time_minutes' => $minutes,
            'override_sap_sku' => '',
            'override_equip_sku' => '',
            'override_manual_sku' => '',
        ];
    }

    return $out;
};
?>
<div class="alert alert-light border small py-2 mb-3">
  <i class="fa-solid fa-pen-to-square me-1"></i>
  Edite <strong>diretamente</strong> quantidades, custos e tempos nas tabelas abaixo. As alterações sincronizam com a estrutura what-if.
  Use <i class="fa-solid fa-rotate-left"></i> em cada linha para voltar ao estado inicial do cadastro. Clique em <strong>Simular</strong> para recalcular os totais.
</div>

<div class="row g-3 inv-cost-compact-tables" id="sim-breakdown-inline">
  <div class="col-12 col-xl-6">
    <div class="card border-light shadow h-100">
      <div class="card-header fw-semibold">Lista de materiais</div>
      <div class="card-body p-0">
        <div class="table-wrap">
          <table class="table table-striped align-middle mb-0" id="sim-bd-materials-table">
            <thead class="thead-green">
              <tr>
                <th class="ps-1 col-w-component">Componente</th>
                <th>Grupo</th>
                <th class="text-end col-w-qty">Qtd/lote</th>
                <th class="text-end col-w-cost">C. comp.</th>
                <th class="text-end">C. SKU</th>
                <th class="text-end pe-1 col-w-line">C. lote</th>
                <th class="text-end" style="width:2.5rem;"></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($breakdown['materials'])): ?>
                <tr><td colspan="7" class="text-center text-muted py-2">Sem componentes na BOM.</td></tr>
              <?php else: ?>
                <?php foreach ($breakdown['materials'] as $lineIndex => $line):
                  $compCode = trim((string)($line['component_code'] ?? ''));
                  $compDesc = trim((string)($line['component_description'] ?? ''));
                  $qty = (float)($line['quantity'] ?? 0);
                  $scrap = (float)($line['scrap_percent'] ?? 0);
                  $unitCost = (float)($line['unit_cost'] ?? 0);
                  $lineCostSku = (float)($line['line_cost'] ?? 0);
                  $lineCostBatch = $lineBatch($line, 'line_cost', $batchSize);
                  ?>
                  <tr class="sim-bd-mat-row" data-line-index="<?= (int)$lineIndex ?>"
                    data-scrap="<?= htmlspecialchars((string)$scrap) ?>">
                    <td class="ps-1" title="<?= htmlspecialchars(trim($compCode . ' ' . $compDesc)) ?>">
                      <span class="cell-component-name"><?= htmlspecialchars($compDesc !== '' ? $compDesc : $compCode) ?></span>
                      <?php if ($compCode !== '' && $compDesc !== ''): ?>
                        <span class="cell-desc-sub"><?= htmlspecialchars($compCode) ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="text-nowrap small text-muted"><?= htmlspecialchars((string)($line['group_name'] ?? 'Outros')) ?></td>
                    <td class="text-end col-num">
                      <input type="text" inputmode="decimal" class="form-control form-control-sm text-end sim-bd-mat-qty"
                        value="<?= htmlspecialchars(number_format($qty, 6, '.', '')) ?>" aria-label="Quantidade por lote">
                    </td>
                    <td class="text-end col-num">
                      <input type="text" inputmode="decimal" class="form-control form-control-sm text-end sim-bd-mat-cost"
                        value="<?= htmlspecialchars(number_format($unitCost, 6, '.', '')) ?>" aria-label="Custo unitário">
                    </td>
                    <td class="text-end text-nowrap col-num sim-bd-mat-line-sku"><?= $fmtMoney($lineCostSku) ?></td>
                    <td class="text-end pe-1 text-nowrap col-num fw-semibold sim-bd-mat-line-batch"><?= $fmtMoney($lineCostBatch) ?></td>
                    <td class="text-end pe-1">
                      <button type="button" class="btn btn-link btn-sm p-0 text-secondary sim-bd-reset-mat" title="Voltar ao estado inicial">
                        <i class="fa-solid fa-rotate-left"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-xl-6">
    <div class="card border-light shadow h-100">
      <div class="card-header fw-semibold">Rota de produção</div>
      <div class="card-body p-0">
        <div class="table-wrap">
          <table class="table table-striped align-middle mb-0" id="sim-bd-operations-table">
            <thead class="thead-green">
              <tr>
                <th class="ps-1 col-w-component">Operação</th>
                <th class="text-end col-w-min">Min</th>
                <th class="text-end">HH</th>
                <th class="text-end">MO SAP<br><span class="small fw-normal">SKU</span></th>
                <th class="text-end">Equip.<br><span class="small fw-normal">SKU</span></th>
                <th class="text-end">MO cad.<br><span class="small fw-normal">SKU</span></th>
                <th class="text-end">C. SKU</th>
                <th class="text-end pe-1 col-w-line">C. lote</th>
                <th class="text-end" style="width:2.5rem;"></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($breakdown['operations'])): ?>
                <tr><td colspan="9" class="text-center text-muted py-2">Sem operações na rota.</td></tr>
              <?php else: ?>
                <?php foreach ($breakdown['operations'] as $lineIndex => $line):
                  $opCode = trim((string)($line['operation_code'] ?? ''));
                  $opName = trim((string)($line['operation_name'] ?? ''));
                  $notes = trim((string)($line['notes'] ?? ''));
                  $opTitle = $opName !== '' ? $opName : $opCode;
                  $timeMin = (float)($line['time_minutes'] ?? 0);
                  $laborHours = (float)($line['labor_hours'] ?? 0);
                  $sapSku = (float)($line['sap_labor_line_cost'] ?? 0);
                  $equipSku = (float)($line['equipment_line_cost'] ?? 0);
                  $manualSku = (float)($line['manual_labor_line_cost'] ?? 0);
                  $lineCostSku = (float)($line['line_cost'] ?? 0);
                  $lineCostBatch = $lineBatch($line, 'line_cost', $batchSize);
                  ?>
                  <tr class="sim-bd-op-row" data-line-index="<?= (int)$lineIndex ?>">
                    <td class="ps-1" title="<?= htmlspecialchars($opTitle) ?>">
                      <span class="cell-op-title"><?= htmlspecialchars($opTitle) ?></span>
                    </td>
                    <td class="text-end col-num">
                      <input type="text" inputmode="decimal" class="form-control form-control-sm text-end sim-bd-op-min"
                        value="<?= htmlspecialchars(number_format($timeMin, 4, '.', '')) ?>" aria-label="Minutos">
                    </td>
                    <td class="text-end text-nowrap col-num sim-bd-op-hh"><?= $fmtHours($laborHours) ?></td>
                    <td class="text-end col-num">
                      <input type="text" inputmode="decimal" class="form-control form-control-sm text-end sim-bd-op-sap"
                        value="<?= htmlspecialchars(number_format($sapSku, 4, '.', '')) ?>" aria-label="MO SAP SKU">
                    </td>
                    <td class="text-end col-num">
                      <input type="text" inputmode="decimal" class="form-control form-control-sm text-end sim-bd-op-equip"
                        value="<?= htmlspecialchars(number_format($equipSku, 4, '.', '')) ?>" aria-label="Equipamento SKU">
                    </td>
                    <td class="text-end col-num">
                      <input type="text" inputmode="decimal" class="form-control form-control-sm text-end sim-bd-op-manual"
                        value="<?= htmlspecialchars(number_format($manualSku, 4, '.', '')) ?>" aria-label="MO cadastrada SKU">
                    </td>
                    <td class="text-end text-nowrap col-num sim-bd-op-line-sku"><?= $fmtMoney($lineCostSku) ?></td>
                    <td class="text-end pe-1 text-nowrap col-num fw-semibold sim-bd-op-line-batch"><?= $fmtMoney($lineCostBatch) ?></td>
                    <td class="text-end pe-1">
                      <button type="button" class="btn btn-link btn-sm p-0 text-secondary sim-bd-reset-op" title="Voltar ao estado inicial">
                        <i class="fa-solid fa-rotate-left"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
window.simBreakdownBatchSize = <?= json_encode($batchSize) ?>;
window.simBaselineBom = <?= json_encode($normalizeBaselineBom($baselineBom), JSON_UNESCAPED_UNICODE) ?>;
window.simBaselineOps = <?= json_encode($normalizeBaselineOps($baselineOps), JSON_UNESCAPED_UNICODE) ?>;
</script>
<?php include __DIR__ . '/simulate_breakdown_inline_scripts.php'; ?>
