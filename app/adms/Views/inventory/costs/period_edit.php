<?php if (!isset($this)) { exit; } ?>
<?php
use App\adms\Helpers\CSRFHelper;

$period = $this->data['period'] ?? [];
$form = $this->data['form'] ?? [];
$periodId = (int)($period['id'] ?? 0);
$isClosed = (string)($period['status'] ?? '') === 'closed';
$val = static function (string $key) use ($form, $period): string {
    if (array_key_exists($key, $form)) {
        return htmlspecialchars((string)$form[$key]);
    }

    return htmlspecialchars((string)($period[$key] ?? ''));
};
?>
<div class="container-fluid px-4 pb-4">
  <div class="mb-1 hstack gap-2 flex-wrap">
    <h2 class="mt-3 mb-0">Editar Período</h2>
    <ol class="breadcrumb mb-3 mt-3 ms-auto">
      <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>list-inventory-cost-periods">Períodos</a></li>
      <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>view-inventory-cost-period/<?= $periodId ?>">Detalhe</a></li>
      <li class="breadcrumb-item active">Editar</li>
    </ol>
  </div>

  <?php include './app/adms/Views/partials/alerts.php'; ?>

  <?php if ($isClosed): ?>
    <div class="alert alert-warning">Período fechado: apenas tarifa kWh e observações podem ser alteradas.</div>
  <?php endif; ?>

  <div class="card border-light shadow">
    <div class="card-body p-4">
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_update_inv_cost_period') ?>">
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label">Nome do período</label>
            <input type="text" class="form-control" name="name" maxlength="120" required value="<?= $val('name') ?>" <?= $isClosed ? 'readonly' : '' ?>>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Data inicial</label>
            <input type="date" class="form-control" name="date_from" required value="<?= $val('date_from') ?>" <?= $isClosed ? 'readonly' : '' ?>>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Data final</label>
            <input type="date" class="form-control" name="date_to" required value="<?= $val('date_to') ?>" <?= $isClosed ? 'readonly' : '' ?>>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status" <?= $isClosed ? 'disabled' : '' ?>>
              <option value="draft" <?= (($period['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>Rascunho</option>
              <option value="closed" <?= (($period['status'] ?? '') === 'closed') ? 'selected' : '' ?>>Fechado</option>
            </select>
            <?php if ($isClosed): ?>
              <input type="hidden" name="status" value="closed">
            <?php endif; ?>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Tarifa kWh</label>
            <input type="text" class="form-control" name="kwh_tariff" value="<?= $val('kwh_tariff') ?>" placeholder="0,0000">
          </div>
          <?php
            $period = $this->data['period'] ?? [];
            $form = $this->data['form'] ?? [];
            include './app/adms/Views/inventory/costs/partials/period_energy_fields.php';
          ?>
          <div class="col-12">
            <label class="form-label">Observações</label>
            <textarea class="form-control" name="notes" rows="3"><?= $val('notes') ?></textarea>
          </div>
        </div>
        <div class="mt-4 d-flex gap-2">
          <button type="submit" class="btn btn-success">Salvar</button>
          <a href="<?= $_ENV['URL_ADM'] ?>view-inventory-cost-period/<?= $periodId ?>" class="btn btn-outline-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
</div>
