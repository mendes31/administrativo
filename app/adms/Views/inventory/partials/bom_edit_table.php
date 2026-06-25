<?php
/**
 * Linhas da aba Lista de materiais (edição de item).
 *
 * @var list<array<string, mixed>> $bomLines
 * @var list<array<string, mixed>> $listBomItems
 * @var list<array<string, mixed>> $listUnits
 * @var bool $isProjectItem
 */
if (!isset($this)) {
    exit;
}
$bomLines = $bomLines ?? [];
$listBomItems = $listBomItems ?? [];
$listUnits = $listUnits ?? [];
$isProjectItem = (bool)($isProjectItem ?? false);

$buildComponentOptions = static function (?int $selectedId) use ($listBomItems): string {
    $html = '<option value="">Selecione o componente</option>';
    foreach ($listBomItems as $bomItem) {
        $optVal = (int)($bomItem['id'] ?? 0);
        $sel = $optVal === (int)$selectedId ? ' selected' : '';
        $avgCost = number_format((float)($bomItem['average_cost'] ?? 0), 6, '.', '');
        $unitName = htmlspecialchars((string)($bomItem['unit_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars((string)($bomItem['code'] ?? '') . ' - ' . (string)($bomItem['description'] ?? ''));
        $html .= '<option value="' . $optVal . '"' . $sel
            . ' data-average-cost="' . $avgCost . '" data-unit="' . $unitName . '">'
            . $label . '</option>';
    }

    return $html;
};

$buildUnitOptions = static function (?string $selected) use ($listUnits): string {
    $selected = strtoupper(trim((string)$selected));
    $defaults = ['UN', 'KG', 'G', 'ML', 'L', 'CX'];
    $codes = [];
    foreach ($listUnits as $u) {
        $code = strtoupper(trim((string)($u['code'] ?? '')));
        if ($code !== '') {
            $codes[] = $code;
        }
    }
    foreach ($defaults as $d) {
        if (!in_array($d, $codes, true)) {
            $codes[] = $d;
        }
    }
    sort($codes);
    $html = '';
    foreach ($codes as $code) {
        $sel = $code === $selected ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($code) . '"' . $sel . '>' . htmlspecialchars($code) . '</option>';
    }

    return $html;
};

$totalMaterialCost = 0.0;
foreach ($bomLines as $line) {
    $totalMaterialCost += \App\adms\Models\Repository\inventory\InvItemBomRepository::computeLineMaterialCost($line);
}
?>
<?php if ($isProjectItem): ?>
<div class="alert alert-info border small py-2 mb-3">
  <i class="fa-solid fa-flask me-1"></i>
  <strong>PA - PROJETO:</strong> a mesma lista pode misturar <em>componentes de catálogo</em> (itens já cadastrados) e <em>linhas manuais</em> (MPs/MAEs ainda sem cadastro), com descrição, tipo e custo unitário informados.
  Este item não entra na sincronização SAP nem no custeio oficial fechado.
</div>
<?php endif; ?>
<div class="table-responsive inv-edit-responsive-table">
  <table class="table table-sm align-middle" id="bom-table">
    <thead class="thead-green">
      <tr>
        <th style="width: 8%;">Origem</th>
        <th style="width: 28%;">Componente / descrição</th>
        <th style="width: 8%;">Tipo</th>
        <th style="width: 12%;">Qtd / lote</th>
        <th style="width: 8%;">Perda %</th>
        <th style="width: 8%;">Un.</th>
        <th style="width: 12%;">Custo un.</th>
        <th style="width: 12%;">Total linha</th>
        <th style="width: 4%;" class="text-end">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($bomLines as $line):
        $isManual = (string)($line['line_source'] ?? 'catalog') === 'manual';
        $qty = (float)($line['quantity_per_batch'] ?? 0);
        $rowTotal = \App\adms\Models\Repository\inventory\InvItemBomRepository::computeLineMaterialCost($line);
        $unitCost = \App\adms\Models\Repository\inventory\InvItemBomRepository::resolveLineUnitCost($line);
        $manualType = strtoupper((string)($line['manual_component_type'] ?? 'MP'));
        ?>
        <tr class="bom-row<?= $isManual ? ' bom-row-manual' : ' bom-row-catalog' ?>">
          <td data-label="Origem">
            <input type="hidden" name="bom_line_source[]" value="<?= $isManual ? 'manual' : 'catalog' ?>">
            <span class="badge <?= $isManual ? 'bg-warning text-dark' : 'bg-secondary' ?>"><?= $isManual ? 'Manual' : 'Catálogo' ?></span>
          </td>
          <td data-label="Componente / descrição">
            <?php if ($isManual): ?>
              <input type="text" name="bom_manual_description[]" class="form-control form-control-sm bom-manual-desc" maxlength="255"
                value="<?= htmlspecialchars((string)($line['manual_description'] ?? '')) ?>" placeholder="Descrição do insumo">
              <input type="hidden" name="bom_component_item_id[]" value="">
            <?php else: ?>
              <select name="bom_component_item_id[]" class="form-select form-select-sm bom-catalog-select" onchange="onBomCatalogSelectChange(this)">
                <?= $buildComponentOptions((int)($line['component_item_id'] ?? 0)) ?>
              </select>
              <input type="hidden" name="bom_manual_description[]" value="">
            <?php endif; ?>
          </td>
          <td data-label="Tipo">
            <?php if ($isManual): ?>
              <select name="bom_manual_component_type[]" class="form-select form-select-sm bom-manual-type">
                <option value="MP" <?= $manualType === 'MP' ? 'selected' : '' ?>>MP</option>
                <option value="EMB" <?= $manualType === 'EMB' ? 'selected' : '' ?>>MAE</option>
                <option value="OTHER" <?= $manualType === 'OTHER' ? 'selected' : '' ?>>Outro</option>
              </select>
            <?php else: ?>
              <span class="text-muted small">—</span>
              <input type="hidden" name="bom_manual_component_type[]" value="">
            <?php endif; ?>
          </td>
          <td data-label="Qtd / lote">
            <input type="number" step="0.000001" min="0" name="bom_quantity_per_batch[]" class="form-control form-control-sm bom-qty" value="<?= htmlspecialchars((string)$qty) ?>">
          </td>
          <td data-label="Perda (%)">
            <input type="number" step="0.0001" min="0" name="bom_scrap_percent[]" class="form-control form-control-sm bom-scrap" value="<?= htmlspecialchars((string)($line['scrap_percent'] ?? '0')) ?>">
          </td>
          <td data-label="Unidade">
            <?php if ($isManual): ?>
              <?php $manualUnitCode = strtoupper(trim((string)($line['manual_unit'] ?? 'UN'))); ?>
              <select name="bom_manual_unit[]" class="form-select form-select-sm bom-manual-unit" data-prev-unit="<?= htmlspecialchars($manualUnitCode) ?>"><?= $buildUnitOptions($manualUnitCode) ?></select>
            <?php else: ?>
              <span class="bom-catalog-unit text-muted small"><?= htmlspecialchars((string)($line['unit_name'] ?? '')) ?></span>
              <input type="hidden" name="bom_manual_unit[]" value="">
            <?php endif; ?>
          </td>
          <td data-label="Custo unitário">
            <?php if ($isManual): ?>
              <input type="number" step="0.000001" min="0" name="bom_manual_unit_cost[]" class="form-control form-control-sm bom-manual-cost" value="<?= htmlspecialchars(number_format($unitCost, 6, '.', '')) ?>">
            <?php else: ?>
              <span class="bom-catalog-cost-display"><?= number_format($unitCost, 6, ',', '.') ?></span>
              <input type="hidden" name="bom_manual_unit_cost[]" value="">
              <input type="hidden" class="bom-catalog-unit-cost" value="<?= htmlspecialchars(number_format($unitCost, 6, '.', '')) ?>">
            <?php endif; ?>
          </td>
          <td data-label="Total linha" class="bom-line-total"><?= number_format($rowTotal, 6, ',', '.') ?></td>
          <td data-label="Ações" class="text-end">
            <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeBomRow(this)">Remover</button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="7" class="text-end"><strong>Custo total dos componentes (lote):</strong></td>
        <td colspan="2"><strong id="bom-grand-total"><?= number_format($totalMaterialCost, 6, ',', '.') ?></strong></td>
      </tr>
    </tfoot>
  </table>
</div>
<div class="mt-2 d-flex flex-wrap gap-2">
  <button type="button" class="btn btn-sm btn-outline-primary" onclick="addBomCatalogRow()">
    <i class="fa-solid fa-plus"></i> Adicionar componente
  </button>
  <?php if ($isProjectItem): ?>
    <button type="button" class="btn btn-sm btn-outline-warning" id="btn-add-bom-manual" onclick="addBomManualRow()">
      <i class="fa-solid fa-pen-ruler"></i> Adicionar linha manual
    </button>
  <?php else: ?>
    <button type="button" class="btn btn-sm btn-outline-warning d-none" id="btn-add-bom-manual" onclick="addBomManualRow()">
      <i class="fa-solid fa-pen-ruler"></i> Adicionar linha manual
    </button>
  <?php endif; ?>
</div>
<p class="text-muted mt-2 mb-0">
  <small>
    <?php if ($isProjectItem): ?>
      Linhas de <strong>catálogo</strong> usam itens já cadastrados; linhas <strong>manuais</strong> permitem simular MPs/MAEs hipotéticas com custo informado.
      Na linha manual, quantidade e <em>custo unitário</em> referem-se à unidade selecionada (ex.: R$/kg); ao trocar a unidade, quantidade e custo são convertidos quando compatíveis (kg↔g, L↔ml).
    <?php else: ?>
      Selecione um item de estoque já cadastrado para usar como componente.
      Para novos projetos, cadastre o PA na categoria <strong>PA - PROJETO</strong>.
    <?php endif; ?>
    <?php if (!$isProjectItem): ?>
      <br>Caso a matéria-prima ainda não exista, abra
      <a href="<?= $_ENV['URL_ADM'] ?>create-inventory-item" target="_blank" rel="noopener">Cadastro de Item de Estoque</a>
      em nova aba, cadastre o item e recarregue esta página.
    <?php endif; ?>
  </small>
</p>
