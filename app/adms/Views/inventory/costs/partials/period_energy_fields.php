<?php if (!isset($this)) { exit; } ?>
<?php
/** Campos kWh / redistribuição de energia — incluir em period_create e period_edit. */
$period = $period ?? [];
$form = $form ?? [];
$isClosed = $isClosed ?? false;
$val = static function (string $key) use ($form, $period): string {
    if (array_key_exists($key, $form)) {
        return htmlspecialchars((string)$form[$key]);
    }

    return htmlspecialchars((string)($period[$key] ?? ''));
};
$autoSplitChecked = array_key_exists('energy_auto_split', $form)
    ? !empty($form['energy_auto_split'])
    : !array_key_exists('energy_auto_split', $period) || !empty($period['energy_auto_split']);
?>
<div class="col-12">
  <hr class="my-2">
  <div class="fw-semibold mb-2">Redistribuição de energia (Pasta 7 / planilha)</div>
  <p class="small text-muted mb-3">
    Informe os kWh do período para ratear a conta <strong>29 — Energia Elétrica</strong> em três fatias (crit. 7 / 3 / 8).
    Se vazio, usa os pesos de referência Tiaraju 2025 (47,27% HVAC · 51,90% área comum · 0,83% direto).
    O kWh direto pode ficar em branco para calcular a partir do cadastro (HM × kW).
  </p>
</div>
<div class="col-12 col-md-4">
  <label class="form-label">kWh HVAC (período)</label>
  <input type="text" class="form-control" name="energy_kwh_hvac" value="<?= $val('energy_kwh_hvac') ?>"
    placeholder="Ex.: 2482720,8" <?= $isClosed ? 'readonly' : '' ?>>
</div>
<div class="col-12 col-md-4">
  <label class="form-label">kWh produção — área comum</label>
  <input type="text" class="form-control" name="energy_kwh_production_common" value="<?= $val('energy_kwh_production_common') ?>"
    placeholder="Ex.: 1045115,5" <?= $isClosed ? 'readonly' : '' ?>>
</div>
<div class="col-12 col-md-4">
  <label class="form-label">kWh direto (CFIX crit. 7)</label>
  <input type="text" class="form-control" name="energy_kwh_direct_cfix" value="<?= $val('energy_kwh_direct_cfix') ?>"
    placeholder="Opcional — calculado do cadastro" <?= $isClosed ? 'readonly' : '' ?>>
</div>
<div class="col-12">
  <div class="form-check">
    <input class="form-check-input" type="checkbox" name="energy_auto_split" value="1" id="energy_auto_split"
      <?= $autoSplitChecked ? 'checked' : '' ?> <?= $isClosed ? 'disabled' : '' ?>>
    <label class="form-check-label" for="energy_auto_split">
      Redistribuir energia automaticamente ao importar o DRE
    </label>
  </div>
  <?php if ($isClosed): ?>
    <input type="hidden" name="energy_auto_split" value="<?= $autoSplitChecked ? '1' : '0' ?>">
  <?php endif; ?>
</div>
