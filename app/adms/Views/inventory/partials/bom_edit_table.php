<?php
/**
 * Linhas da aba Lista de materiais (edição de item).
 *
 * @var list<array<string, mixed>> $bomLines
 * @var list<array<string, mixed>> $listBomItems
 * @var list<array<string, mixed>> $listUnits
 * @var bool $isProjectItem
 * @var bool $bomSimulationMode
 */
if (!isset($this)) {
    exit;
}
$bomLines = $bomLines ?? [];
$listBomItems = $listBomItems ?? [];
$listUnits = $listUnits ?? [];
$isProjectItem = (bool)($isProjectItem ?? false);
$bomSimulationMode = (bool)($bomSimulationMode ?? false);
$allowManualLines = $isProjectItem || $bomSimulationMode;
$allowCatalogCostEdit = $bomSimulationMode;
$hideTipoColumn = $bomSimulationMode;

$renderManualTypeField = static function (string $manualType, bool $inline): string {
    $mp = $manualType === 'MP' ? ' selected' : '';
    $emb = $manualType === 'EMB' ? ' selected' : '';
    $other = $manualType === 'OTHER' ? ' selected' : '';
    $select = '<select name="bom_manual_component_type[]" class="form-select form-select-sm bom-manual-type" style="max-width:8rem">'
        . '<option value="MP"' . $mp . '>MP</option>'
        . '<option value="EMB"' . $emb . '>MAE</option>'
        . '<option value="OTHER"' . $other . '>Outro</option>'
        . '</select>';
    if ($inline) {
        return '<div class="d-flex align-items-center gap-2 mt-2">'
            . '<span class="small text-muted text-nowrap">Classificação CVAR:</span>' . $select . '</div>';
    }

    return $select;
};

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
    $labels = [
        'UN' => 'Unidade',
        'KG' => 'Quilograma',
        'G' => 'Grama',
        'ML' => 'Mililitro',
        'L' => 'Litro',
        'CX' => 'Caixa',
    ];
    $codes = [];
    foreach ($listUnits as $u) {
        $code = strtoupper(trim((string)($u['code'] ?? '')));
        if ($code !== '') {
            $codes[] = $code;
            $name = trim((string)($u['name'] ?? ''));
            if ($name !== '') {
                $labels[$code] = $name;
            }
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
        $label = $labels[$code] ?? $code;
        $html .= '<option value="' . htmlspecialchars($code) . '"' . $sel . '>'
            . htmlspecialchars($label . ' (' . $code . ')') . '</option>';
    }

    return $html;
};

$totalMaterialCost = 0.0;
foreach ($bomLines as $line) {
    $rowKind = (string)($line['bom_row_kind'] ?? 'catalog');
    if ($rowKind === 'pi_reference' || !($line['include_in_total'] ?? true)) {
        continue;
    }
    $totalMaterialCost += \App\adms\Models\Repository\inventory\InvItemBomRepository::computeLineMaterialCost($line);
}

$hasPiExplosion = false;
foreach ($bomLines as $line) {
    if (in_array((string)($line['bom_row_kind'] ?? ''), ['pi_exploded', 'pi_reference'], true)) {
        $hasPiExplosion = true;
        break;
    }
}

$formatBomQty = static function (float $qty): string {
    if (abs($qty) < 1e-12) {
        return '0';
    }
    $formatted = number_format($qty, 6, '.', '');
    $trimmed = rtrim(rtrim($formatted, '0'), '.');

    return $trimmed !== '' ? $trimmed : '0';
};

$resolveCatalogLabel = static function (int $componentId) use ($listBomItems): string {
    foreach ($listBomItems as $bomItem) {
        if ((int)($bomItem['id'] ?? 0) === $componentId) {
            return trim((string)($bomItem['code'] ?? '') . ' — ' . (string)($bomItem['description'] ?? ''));
        }
    }

    return '';
};
?>
<?php if ($bomSimulationMode): ?>
<div class="alert alert-info border small py-2 mb-3">
  <i class="fa-solid fa-flask me-1"></i>
  <strong>Simulação:</strong> edite o <em>custo unitário</em> de qualquer linha, ajuste quantidades e inclua <em>componentes não cadastrados</em> (linha manual) — tudo vale só para este cenário, sem alterar o cadastro do item.
</div>
<?php elseif ($isProjectItem): ?>
<div class="alert alert-info border small py-2 mb-3">
  <i class="fa-solid fa-flask me-1"></i>
  <strong>PA - PROJETO:</strong> a mesma lista pode misturar <em>componentes de catálogo</em> (itens já cadastrados) e <em>linhas manuais</em> (MPs/MAEs ainda sem cadastro), com descrição, tipo e custo unitário informados.
  Este item não entra na sincronização SAP nem no custeio oficial fechado.
</div>
<?php elseif ($hasPiExplosion): ?>
<div class="alert alert-info border small py-2 mb-3">
  <i class="fa-solid fa-sitemap me-1"></i>
  <strong>Produto intermediário (PI):</strong> o PI permanece na estrutura SAP (linha de referência). As MPs e embalagens do PI aparecem abaixo com badge <em>via PI</em> e entram no <strong>CVAR</strong> e no total do lote. O custeio não usa o custo médio zerado do PI.
</div>
<?php endif; ?>
<div class="table-responsive inv-edit-responsive-table">
  <table class="table table-sm align-middle" id="bom-table">
    <thead class="thead-green">
      <tr>
        <th style="width: 7%;">Origem</th>
        <th style="width: <?= $hideTipoColumn ? '52%' : '28%' ?>;">Componente / descrição</th>
        <?php if (!$hideTipoColumn): ?>
        <th style="width: 8%;">Tipo</th>
        <?php endif; ?>
        <th style="width: <?= $hideTipoColumn ? '8%' : '12%' ?>;">Qtd / lote</th>
        <th style="width: 8%;">Perda %</th>
        <th style="width: 8%;">Un.</th>
        <th style="width: 12%;">Custo un.</th>
        <th style="width: 12%;">Total linha</th>
        <th style="width: <?= $bomSimulationMode ? '8%' : '4%' ?>;" class="text-end">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($bomLines as $lineIndex => $line):
        $rowKind = (string)($line['bom_row_kind'] ?? 'catalog');
        $isPiExploded = $rowKind === 'pi_exploded';
        $isPiReference = $rowKind === 'pi_reference';
        $isReadOnlyPi = $isPiExploded || $isPiReference;
        $isManual = !$isReadOnlyPi && (string)($line['line_source'] ?? 'catalog') === 'manual';
        $qty = (float)($line['quantity_per_batch'] ?? 0);
        $rowTotal = $isPiReference
            ? 0.0
            : \App\adms\Models\Repository\inventory\InvItemBomRepository::computeLineMaterialCost($line);
        $unitCost = \App\adms\Models\Repository\inventory\InvItemBomRepository::resolveLineUnitCost($line);
        $manualType = strtoupper((string)($line['manual_component_type'] ?? 'MP'));
        $lineIndexAttr = $bomSimulationMode ? ' data-line-index="' . (int)$lineIndex . '"' : '';
        $piCode = (string)($line['from_pi_code'] ?? $line['component_code'] ?? '');
        $piDesc = (string)($line['from_pi_description'] ?? $line['component_description'] ?? '');
        ?>
        <tr class="bom-row<?= $isManual ? ' bom-row-manual' : ($isReadOnlyPi ? ' bom-row-pi' : ' bom-row-catalog') ?><?= $isPiExploded ? ' bom-row-pi-exploded' : '' ?><?= $isPiReference ? ' bom-row-pi-reference table-secondary' : '' ?>"<?= $lineIndexAttr ?>>
          <td data-label="Origem">
            <?php if ($isPiExploded): ?>
              <span class="badge bg-info text-dark" title="<?= htmlspecialchars($piDesc) ?>">via PI</span>
            <?php elseif ($isPiReference): ?>
              <span class="badge bg-primary">PI</span>
            <?php else: ?>
            <input type="hidden" name="bom_line_source[]" value="<?= $isManual ? 'manual' : 'catalog' ?>">
            <span class="badge <?= $isManual ? 'bg-warning text-dark' : 'bg-secondary' ?>"><?= $isManual ? 'Manual' : 'Catálogo' ?></span>
            <?php endif; ?>
          </td>
          <td data-label="Componente / descrição">
            <?php if ($isPiExploded): ?>
              <div class="small">
                <strong><?= htmlspecialchars(trim((string)($line['component_code'] ?? '') . ' — ' . (string)($line['component_description'] ?? ''))) ?></strong>
                <div class="text-muted">Origem: PI <?= htmlspecialchars($piCode) ?></div>
              </div>
            <?php elseif ($isPiReference): ?>
              <div class="small">
                <strong><?= htmlspecialchars(trim((string)($line['component_code'] ?? '') . ' — ' . (string)($line['component_description'] ?? ''))) ?></strong>
                <div class="text-muted">
                  PI aninhado<?= !empty($line['from_pi_code']) ? ' (componente do PI ' . htmlspecialchars((string)$line['from_pi_code']) . ')' : '' ?>
                  — referência SAP; MPs abaixo entram no CVAR
                </div>
              </div>
              <input type="hidden" name="bom_line_source[]" value="catalog">
              <input type="hidden" name="bom_component_item_id[]" value="<?= (int)($line['component_item_id'] ?? 0) ?>">
              <input type="hidden" name="bom_quantity_per_batch[]" value="<?= htmlspecialchars($formatBomQty($qty)) ?>">
              <input type="hidden" name="bom_scrap_percent[]" value="<?= htmlspecialchars((string)($line['scrap_percent'] ?? '0')) ?>">
              <input type="hidden" name="bom_manual_description[]" value="">
              <input type="hidden" name="bom_manual_component_type[]" value="">
              <input type="hidden" name="bom_manual_unit[]" value="">
              <input type="hidden" name="bom_manual_unit_cost[]" value="">
              <input type="hidden" name="bom_catalog_unit_cost[]" value="">
            <?php elseif ($isManual): ?>
              <input type="text" name="bom_manual_description[]" class="form-control<?= $hideTipoColumn ? '' : ' form-control-sm' ?> bom-manual-desc" maxlength="255"
                value="<?= htmlspecialchars((string)($line['manual_description'] ?? '')) ?>" placeholder="Descrição do insumo">
              <?php if ($hideTipoColumn): ?>
                <?= $renderManualTypeField($manualType, true) ?>
              <?php endif; ?>
              <input type="hidden" name="bom_component_item_id[]" value="">
            <?php else:
              $componentId = (int)($line['component_item_id'] ?? 0);
              $catalogLabel = $resolveCatalogLabel($componentId);
              ?>
              <select name="bom_component_item_id[]" class="form-select<?= $hideTipoColumn ? ' bom-catalog-select-sim' : ' form-select-sm' ?> bom-catalog-select" title="<?= htmlspecialchars($catalogLabel) ?>">
                <?= $buildComponentOptions($componentId) ?>
              </select>
              <input type="hidden" name="bom_manual_description[]" value="">
            <?php endif; ?>
          </td>
          <?php if (!$hideTipoColumn): ?>
          <td data-label="Tipo">
            <?php if ($isPiExploded || $isPiReference): ?>
              <span class="text-muted small"><?= htmlspecialchars((string)($line['component_category'] ?? 'MP')) ?></span>
            <?php elseif ($isManual): ?>
              <?= $renderManualTypeField($manualType, false) ?>
            <?php else: ?>
              <span class="text-muted small">—</span>
              <input type="hidden" name="bom_manual_component_type[]" value="">
            <?php endif; ?>
          </td>
          <?php elseif (!$isManual): ?>
            <input type="hidden" name="bom_manual_component_type[]" value="">
          <?php endif; ?>
          <td data-label="Qtd / lote"<?= $hideTipoColumn ? ' class="bom-qty-col"' : '' ?>>
            <?php if ($isReadOnlyPi): ?>
              <span class="form-control-plaintext form-control-sm"><?= htmlspecialchars($formatBomQty($qty)) ?></span>
            <?php else: ?>
            <input type="text" inputmode="decimal" autocomplete="off" name="bom_quantity_per_batch[]" class="form-control form-control-sm bom-qty" value="<?= htmlspecialchars($formatBomQty($qty)) ?>">
            <?php endif; ?>
          </td>
          <td data-label="Perda (%)">
            <?php if ($isReadOnlyPi): ?>
              <span class="form-control-plaintext form-control-sm"><?= htmlspecialchars((string)($line['scrap_percent'] ?? '0')) ?></span>
            <?php else: ?>
            <input type="number" step="0.0001" min="0" name="bom_scrap_percent[]" class="form-control form-control-sm bom-scrap" value="<?= htmlspecialchars((string)($line['scrap_percent'] ?? '0')) ?>">
            <?php endif; ?>
          </td>
          <td data-label="Unidade">
            <?php if ($isPiExploded || $isPiReference): ?>
              <span class="text-muted small"><?= htmlspecialchars((string)($line['unit_name'] ?? '')) ?></span>
            <?php elseif ($isManual): ?>
              <?php $manualUnitCode = strtoupper(trim((string)($line['manual_unit'] ?? 'UN'))); ?>
              <select name="bom_manual_unit[]" class="form-select form-select-sm bom-manual-unit" data-prev-unit="<?= htmlspecialchars($manualUnitCode) ?>"><?= $buildUnitOptions($manualUnitCode) ?></select>
            <?php else: ?>
              <span class="bom-catalog-unit text-muted small"><?= htmlspecialchars((string)($line['unit_name'] ?? '')) ?></span>
              <input type="hidden" name="bom_manual_unit[]" value="">
            <?php endif; ?>
          </td>
          <td data-label="Custo unitário">
            <?php if ($isReadOnlyPi): ?>
              <span class="bom-catalog-cost-display"><?php if ($unitCost > 0): ?><?= number_format($unitCost, 6, ',', '.') ?><?php else: ?><span class="text-muted" title="Custo médio zerado no cadastro — sincronize o item ou a estrutura SAP">—</span><?php endif; ?></span>
            <?php elseif ($isManual): ?>
              <input type="hidden" name="bom_catalog_unit_cost[]" value="">
              <input type="number" step="0.000001" min="0" name="bom_manual_unit_cost[]" class="form-control form-control-sm bom-manual-cost" value="<?= htmlspecialchars(number_format($unitCost, 6, '.', '')) ?>">
            <?php elseif ($allowCatalogCostEdit): ?>
              <input type="number" step="0.000001" min="0" name="bom_catalog_unit_cost[]" class="form-control form-control-sm bom-catalog-cost-input bom-catalog-unit-cost" value="<?= htmlspecialchars(number_format($unitCost, 6, '.', '')) ?>">
              <input type="hidden" name="bom_manual_unit_cost[]" value="">
            <?php else: ?>
              <span class="bom-catalog-cost-display"><?= number_format($unitCost, 6, ',', '.') ?></span>
              <input type="hidden" name="bom_manual_unit_cost[]" value="">
              <input type="hidden" name="bom_catalog_unit_cost[]" value="">
              <input type="hidden" class="bom-catalog-unit-cost" value="<?= htmlspecialchars(number_format($unitCost, 6, '.', '')) ?>">
            <?php endif; ?>
          </td>
          <td data-label="Total linha" class="bom-line-total"><?= number_format($rowTotal, 6, ',', '.') ?></td>
          <td data-label="Ações" class="text-end">
            <?php if ($isReadOnlyPi): ?>
              <span class="text-muted small">—</span>
            <?php else: ?>
            <div class="d-flex gap-1 justify-content-end">
              <?php if ($bomSimulationMode): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="simResetBomRow(<?= (int)$lineIndex ?>)" title="Voltar ao cadastro original">
                  <i class="fa-solid fa-rotate-left"></i>
                </button>
              <?php endif; ?>
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeBomRow(this)">Remover</button>
            </div>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="<?= $hideTipoColumn ? 6 : 7 ?>" class="text-end"><strong>Custo total dos componentes (lote):</strong></td>
        <td colspan="2"><strong id="bom-grand-total"><?= number_format($totalMaterialCost, 6, ',', '.') ?></strong></td>
      </tr>
    </tfoot>
  </table>
</div>
<div class="mt-2 d-flex flex-wrap gap-2">
  <button type="button" class="btn btn-sm btn-outline-primary" onclick="addBomCatalogRow()">
    <i class="fa-solid fa-plus"></i> Adicionar componente
  </button>
  <?php if ($allowManualLines): ?>
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
    <?php if ($bomSimulationMode): ?>
      Alterações valem <strong>só para esta simulação</strong> — o cadastro oficial do item não é modificado. Use <i class="fa-solid fa-rotate-left"></i> para restaurar uma linha ao estado do cadastro.
    <?php elseif ($isProjectItem): ?>
      Linhas de <strong>catálogo</strong> usam itens já cadastrados; linhas <strong>manuais</strong> permitem simular MPs/MAEs hipotéticas com custo informado.
      Na linha manual, quantidade e <em>custo unitário</em> referem-se à unidade selecionada (ex.: R$/kg). O <em>total da linha</em> e o <em>custo total do lote</em> são recalculados automaticamente ao alterar quantidade, perda, custo, unidade ou componente.
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
