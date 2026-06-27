<?php if (!isset($this)) { exit; } ?>
<?php
use App\adms\Helpers\CSRFHelper;

$periodId = (int)($periodId ?? 0);
$scenarioRows = $scenarioRows ?? [];
$projectItems = $projectItems ?? [];
$canSaveScenario = (bool)($canSaveScenario ?? false);
$isClosed = (bool)($isClosed ?? false);
?>
<?php if ($canSaveScenario && !$isClosed): ?>
<div class="card border-warning shadow-sm mb-4">
  <div class="card-header bg-warning-subtle">
    <strong>Produção simulada (what-if)</strong>
    <span class="small text-muted ms-2">Como uma nova coluna na planilha — recalcula rateio no rascunho do período.</span>
  </div>
  <div class="card-body">
    <form method="post" action="<?= $_ENV['URL_ADM'] ?>save-inventory-cost-scenario-production/<?= $periodId ?>" class="row g-2 align-items-end">
      <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_save_inv_cost_scenario_production') ?>">
      <div class="col-12 col-md-4">
        <label class="form-label small mb-1" for="scenario_item_id">PA / projeto (opcional)</label>
        <select class="form-select form-select-sm" id="scenario_item_id" name="inv_item_id">
          <option value="0">— Informar código ERP abaixo —</option>
          <?php foreach ($projectItems as $pi):
            $pid = (int)($pi['id'] ?? 0);
            $perp = trim((string)($pi['erp_code'] ?? ''));
            $plabel = trim((string)($pi['description'] ?? ''));
            ?>
            <option value="<?= $pid ?>"><?= htmlspecialchars($perp !== '' ? $perp . ' — ' : '') . htmlspecialchars($plabel) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small mb-1" for="scenario_erp">Código ERP</label>
        <input type="text" class="form-control form-control-sm" id="scenario_erp" name="erp_code" placeholder="PROJ-001">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small mb-1" for="scenario_batches">Lotes</label>
        <input type="number" min="0" step="1" class="form-control form-control-sm" id="scenario_batches" name="batches_count" value="1">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small mb-1" for="scenario_qty">Qtd produzida</label>
        <input type="number" min="0" step="1" class="form-control form-control-sm" id="scenario_qty" name="qty_produced" value="0">
      </div>
      <div class="col-6 col-md-2">
        <button type="submit" class="btn btn-sm btn-warning w-100"><i class="fa-solid fa-plus me-1"></i> Incluir</button>
      </div>
      <div class="col-12">
        <input type="text" class="form-control form-control-sm" name="item_description" placeholder="Descrição (opcional, para SKU novo)">
      </div>
    </form>

    <?php if ($scenarioRows !== []): ?>
      <div class="table-responsive mt-3">
        <table class="table table-sm mb-0">
          <thead class="table-light">
            <tr>
              <th>SKU</th>
              <th>Descrição</th>
              <th class="text-end">Lotes</th>
              <th class="text-end">Qtd</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($scenarioRows as $sc): ?>
              <tr>
                <td class="font-monospace small"><?= htmlspecialchars((string)($sc['erp_code'] ?? '')) ?></td>
                <td class="small"><?= htmlspecialchars((string)($sc['item_description'] ?? $sc['item_name'] ?? '')) ?></td>
                <td class="text-end"><?= (int)($sc['batches_count'] ?? 0) ?></td>
                <td class="text-end"><?= number_format((float)($sc['qty_produced'] ?? 0), 0, ',', '.') ?></td>
                <td class="text-end">
                  <form method="post" action="<?= $_ENV['URL_ADM'] ?>save-inventory-cost-scenario-production/<?= $periodId ?>" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_save_inv_cost_scenario_production') ?>">
                    <input type="hidden" name="delete_scenario_id" value="<?= (int)($sc['id'] ?? 0) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remover produção simulada?')">Remover</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
