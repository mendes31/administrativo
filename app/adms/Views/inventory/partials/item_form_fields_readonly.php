<?php
/**
 * Campos do cadastro do item — somente leitura (espelha aba Dados gerais do editar).
 *
 * @var array<string, mixed> $item
 */
use App\adms\Helpers\InvCostComplexityHelper;
use App\adms\Helpers\InvCostEnergyClassHelper;

$item = $item ?? [];
$fmtNum = static fn(mixed $v, int $dec = 4): string => number_format((float)$v, $dec, ',', '.');
$ro = 'form-control form-control-sm bg-light';
$energyCode = InvCostEnergyClassHelper::resolveForCosting($item['energy_class'] ?? null);
$complexityCode = InvCostComplexityHelper::resolveForCosting($item['complexity_level'] ?? null);
$productionLine = mb_strtoupper(trim((string)($item['production_line'] ?? '')), 'UTF-8');
$adminType = match ((string)($item['admin_type'] ?? 'none')) {
    'lot' => 'Lotes',
    'serial' => 'Números de série',
    default => 'Nenhum',
};
$productionLineLabel = match ($productionLine) {
    'TIARAJU' => 'TIARAJU (Própria)',
    'TERCEIRO' => 'TERCEIRO',
    default => $productionLine !== '' ? $productionLine : '—',
};
?>
<div class="row g-3">
    <div class="col-12 col-md-3">
        <label class="form-label">Código</label>
        <input type="text" class="<?= $ro ?>" readonly value="<?= htmlspecialchars((string)($item['code'] ?? ''), ENT_QUOTES) ?>">
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label">Código ERP</label>
        <input type="text" class="<?= $ro ?>" readonly value="<?= htmlspecialchars((string)($item['erp_code'] ?? ''), ENT_QUOTES) ?>">
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label">Descrição</label>
        <input type="text" class="<?= $ro ?>" readonly value="<?= htmlspecialchars((string)($item['description'] ?? ''), ENT_QUOTES) ?>">
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label">Unidade</label>
        <input type="text" class="<?= $ro ?>" readonly value="<?= htmlspecialchars((string)($item['unit_name'] ?? '—'), ENT_QUOTES) ?>">
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label">Grupo de Itens</label>
        <input type="text" class="<?= $ro ?>" readonly value="<?= htmlspecialchars((string)($item['category_name'] ?? '—'), ENT_QUOTES) ?>">
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label">Administrar por</label>
        <input type="text" class="<?= $ro ?>" readonly value="<?= htmlspecialchars($adminType, ENT_QUOTES) ?>">
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label">Lote padrão (mín.)</label>
        <input type="text" class="<?= $ro ?>" readonly value="<?= $fmtNum($item['standard_batch_size'] ?? 1, 6) ?>">
    </div>

    <div class="col-12">
        <hr class="my-1">
        <p class="small text-muted mb-0">Parâmetros de rateio CFIX (critérios 4, 6 e 8). Sincronizados do SAP ou editados manualmente.</p>
    </div>

    <div class="col-12 col-md-3">
        <label class="form-label">Classe energia (HVAC)</label>
        <input type="text" class="<?= $ro ?>" readonly
            value="<?= htmlspecialchars($energyCode . ' — ' . InvCostEnergyClassHelper::label($energyCode), ENT_QUOTES) ?>">
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label">Complexidade</label>
        <input type="text" class="<?= $ro ?>" readonly
            value="<?= htmlspecialchars($complexityCode . ' — ' . InvCostComplexityHelper::label($complexityCode), ENT_QUOTES) ?>">
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label">Linha de produção</label>
        <input type="text" class="<?= $ro ?>" readonly value="<?= htmlspecialchars($productionLineLabel, ENT_QUOTES) ?>">
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label">Forma farmacêutica</label>
        <input type="text" class="<?= $ro ?>" readonly value="<?= htmlspecialchars((string)($item['pharma_form_name'] ?? '—'), ENT_QUOTES) ?>">
    </div>

    <div class="col-12 col-md-3">
        <label class="form-label">Estoque mínimo</label>
        <input type="text" class="<?= $ro ?>" readonly value="<?= $fmtNum($item['min_stock'] ?? 0) ?>">
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label">Estoque máximo</label>
        <input type="text" class="<?= $ro ?>" readonly value="<?= $fmtNum($item['max_stock'] ?? 0) ?>">
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label">Custo médio</label>
        <input type="text" class="<?= $ro ?>" readonly
            value="<?= $fmtNum($headerAverageCost ?? ($item['average_cost'] ?? 0), 6) ?>"
            title="Custo total do lote (materiais + rota).">
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label">Último custo</label>
        <input type="text" class="<?= $ro ?>" readonly value="<?= $fmtNum($item['last_cost'] ?? 0, 6) ?>">
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label">Ativo</label>
        <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" disabled <?= !empty($item['active']) ? 'checked' : '' ?>>
            <label class="form-check-label"><?= !empty($item['active']) ? 'Sim' : 'Não' ?></label>
        </div>
    </div>

    <?php include __DIR__ . '/item_sap_sync_fields.php'; ?>
</div>
