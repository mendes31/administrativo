<?php if (!isset($this)) { exit; } ?>
<?php
$periodId = (int)($periodId ?? 0);
$productionItems = $productionItems ?? [];
$skuFilter = trim((string)($skuFilter ?? ''));
$productionItemsAllCount = (int)($productionItemsAllCount ?? count($productionItems));
$isClosed = (bool)($isClosed ?? false);
$fmtQty = static fn(?float $v): string => $v === null ? '—' : number_format($v, 0, ',', '.');
$fmtQtyRatio = static function (?float $produced, ?float $planned) use ($fmtQty): string {
    if ($produced === null || $produced <= 0) {
        return '—';
    }
    if ($planned === null || $planned <= 0) {
        return $fmtQty($produced);
    }

    return $fmtQty($produced) . ' / ' . $fmtQty($planned);
};
$fmtDec = static fn(?float $v, int $dec = 1): string => $v === null || $v <= 0 ? '—' : number_format($v, $dec, ',', '.');
$fmtPct = static fn(?float $v): string => $v === null || $v <= 0 ? '—' : number_format($v, 2, ',', '.') . '%';
$energyLabels = ['CM' => 'CM', 'PROB' => 'PROB', 'OTHER' => 'OTHER'];
$complexityLabels = ['baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta'];
$productionLineLabels = ['TERCEIRO' => 'TERCEIRO', 'TIARAJU' => 'TIARAJU'];
?>
<div class="card border-light shadow mb-4">
  <div class="card-body">
    <p class="small text-muted mb-3">
      Resultados da produção no intervalo do período (lotes SAP). Classe energia, complexidade e linha vêm do
      <strong>cadastro do item</strong> — use o link <em>Editar item</em> para ajustar.
      <strong>Análises</strong> (critério 6) = (MP + MAE na BOM) × lotes × eficiência do período.
      <strong>Fator compl.</strong> alimenta o critério 4 (complexidade); <strong>Compl×Anál</strong> alimenta o critério 6 (complexidade × análises).
      As colunas <strong>% Crit. 4</strong> e <strong>% Crit. 6</strong> mostram a participação percentual no rateio CQ/P&D/DA.
    </p>
    <?php if ($productionItems === []): ?>
      <?php if ($skuFilter !== ''): ?>
        <p class="text-muted mb-0">Nenhum SKU encontrado para o filtro <strong><?= htmlspecialchars($skuFilter) ?></strong>.
          <a href="<?= $_ENV['URL_ADM'] ?>view-inventory-cost-period/<?= $periodId ?>?tab=skus">Limpar filtro</a></p>
      <?php else: ?>
      <p class="text-muted mb-0">Nenhuma produção registrada neste período. Importe lotes em <a href="<?= $_ENV['URL_ADM'] ?>list-inventory-cost-production-batches">Lotes produzidos</a>.</p>
      <?php endif; ?>
    <?php else: ?>
      <form method="get" action="<?= $_ENV['URL_ADM'] ?>view-inventory-cost-period/<?= $periodId ?>" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="tab" value="skus">
        <div class="col-12 col-md-4">
          <label class="form-label small mb-1" for="sku_filter">Filtrar SKUs</label>
          <input type="text" class="form-control form-control-sm" id="sku_filter" name="sku_filter"
            value="<?= htmlspecialchars($skuFilter) ?>"
            placeholder="PA, descrição ou código ERP">
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
        </div>
        <div class="col-auto">
          <a href="<?= $_ENV['URL_ADM'] ?>view-inventory-cost-period/<?= $periodId ?>?tab=skus" class="btn btn-sm btn-outline-secondary">Limpar</a>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-sm btn-outline-secondary" name="sku_filter" value="PA">Somente PA</button>
        </div>
        <?php if ($skuFilter !== ''): ?>
          <div class="col-12">
            <span class="small text-muted">Exibindo <?= count($productionItems) ?> de <?= $productionItemsAllCount ?> SKU(s) · ordenado por descrição</span>
          </div>
        <?php else: ?>
          <div class="col-12">
            <span class="small text-muted"><?= count($productionItems) ?> SKU(s) · ordenado por descrição · digite <strong>PA</strong> ou use o botão para produto acabado</span>
          </div>
        <?php endif; ?>
      </form>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-3">SKU</th>
              <th>Descrição</th>
              <th class="text-end">Lotes</th>
              <th class="text-end" title="Produzido / planejado (lotes × lote mín. do cadastro)">Qtd</th>
              <th class="text-end">Efic. %</th>
              <th>Classe energia</th>
              <th>Complexidade</th>
              <th class="text-end" title="Fator 2 / 5 / 8 — critério 4">Fator compl.</th>
              <th class="text-end" title="Σ (MP+MAE por lote) × lotes">Análises</th>
              <th class="text-end" title="Fator × análises — critério 6">Compl×Anál</th>
              <th class="text-end" title="Critério 4 — CQ/P&amp;D/DA: participação % pela complexidade (fator 2/5/8)">% Crit. 4</th>
              <th class="text-end" title="Critério 6 — CQ/P&amp;D/DA: participação % por complexidade × nº análises">% Crit. 6</th>
              <th title="Linha de produção: TIARAJU entra no crit. 7 (EE direta); TERCEIRO não">Linha</th>
              <th class="pe-3"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($productionItems as $row):
              $itemId = (int)($row['inv_item_id'] ?? 0);
              $linked = !empty($row['linked']);
              $energyClass = trim((string)($row['energy_class'] ?? ''));
              $suggested = trim((string)($row['suggested_energy_class'] ?? ''));
              $fromItem = !empty($row['energy_class_from_item']);
              $complexity = trim((string)($row['complexity_level'] ?? 'media'));
              if ($complexity === '' || !isset($complexityLabels[$complexity])) {
                  $complexity = 'media';
              }
              ?>
              <tr class="<?= $linked ? '' : 'table-warning' ?><?= !empty($row['is_scenario']) ? ' table-info' : '' ?>">
                <td class="ps-3 font-monospace small">
                  <?= htmlspecialchars((string)($row['erp_code'] ?? '—')) ?>
                  <?php if (!empty($row['is_scenario'])): ?>
                    <span class="badge text-bg-info ms-1">Sim</span>
                  <?php elseif (!empty($row['has_scenario'])): ?>
                    <span class="badge text-bg-warning ms-1">+Sim</span>
                  <?php endif; ?>
                </td>
                <td class="small"><?= htmlspecialchars((string)($row['item_description'] ?? '')) ?></td>
                <td class="text-end"><?= (int)($row['batches_count'] ?? 0) ?></td>
                <td class="text-end"><?= $fmtQtyRatio(
                    isset($row['total_qty']) ? (float)$row['total_qty'] : null,
                    isset($row['qty_planned']) ? (float)$row['qty_planned'] : null
                ) ?></td>
                <td class="text-end"><?= $fmtPct(isset($row['efficiency_pct']) ? (float)$row['efficiency_pct'] : null) ?></td>
                <td class="small">
                  <?php if ($energyClass !== ''): ?>
                    <?= htmlspecialchars($energyLabels[$energyClass] ?? $energyClass) ?>
                    <?php if (!$fromItem && $suggested !== ''): ?>
                      <span class="text-muted">(sug.)</span>
                    <?php endif; ?>
                  <?php elseif ($suggested !== ''): ?>
                    <span class="text-muted">sug. <?= htmlspecialchars($suggested) ?></span>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
                <td class="small"><?= htmlspecialchars($complexityLabels[$complexity] ?? ucfirst($complexity)) ?></td>
                <td class="text-end"><?= $fmtDec(isset($row['complexity_factor']) ? (float)$row['complexity_factor'] : null, 0) ?></td>
                <td class="text-end"><?= $fmtDec(isset($row['analysis_count_total']) ? (float)$row['analysis_count_total'] : null, 1) ?></td>
                <td class="text-end"><?= $fmtDec(isset($row['driver_6']) ? (float)$row['driver_6'] : null, 1) ?></td>
                <td class="text-end"><?= $fmtPct(isset($row['share_criterion_4']) ? (float)$row['share_criterion_4'] : null) ?></td>
                <td class="text-end"><?= $fmtPct(isset($row['share_criterion_6']) ? (float)$row['share_criterion_6'] : null) ?></td>
                <td class="small"><?php
                  $line = mb_strtoupper(trim((string)($row['production_line'] ?? '')), 'UTF-8');
                  echo $line !== '' ? htmlspecialchars($productionLineLabels[$line] ?? $line) : '—';
                ?></td>
                <td class="pe-3 text-nowrap">
                  <?php if ($linked && $itemId > 0): ?>
                    <a href="<?= $_ENV['URL_ADM'] ?>update-inventory-item/<?= $itemId ?>" class="btn btn-sm btn-outline-secondary">Editar item</a>
                  <?php else: ?>
                    <span class="small text-muted">Sem cadastro</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($isClosed): ?>
        <p class="small text-muted mt-3 mb-0">Período fechado: dados somente leitura.</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
