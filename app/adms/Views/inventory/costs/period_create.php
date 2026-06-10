<?php if (!isset($this)) { exit; } ?>
<?php use App\adms\Helpers\CSRFHelper; ?>
<?php $form = $this->data['form'] ?? []; ?>
<div class="container-fluid px-4 pb-4">
  <div class="mb-1 hstack gap-2 flex-wrap">
    <h2 class="mt-3 mb-0">Cadastrar Período de Custeio</h2>
    <ol class="breadcrumb mb-3 mt-3 ms-auto">
      <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>list-inventory-cost-periods">Períodos</a></li>
      <li class="breadcrumb-item active">Novo</li>
    </ol>
  </div>

  <?php include './app/adms/Views/partials/alerts.php'; ?>

  <div class="card border-light shadow">
    <div class="card-body p-4">
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_create_inv_cost_period') ?>">

        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label">Nome do período</label>
            <input type="text" class="form-control" name="name" maxlength="120" required
              value="<?= htmlspecialchars((string)($form['name'] ?? '')) ?>"
              placeholder="Ex.: Jan–Abr/2026">
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Data inicial</label>
            <input type="date" class="form-control" name="date_from" required value="<?= htmlspecialchars((string)($form['date_from'] ?? '')) ?>">
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Data final</label>
            <input type="date" class="form-control" name="date_to" required value="<?= htmlspecialchars((string)($form['date_to'] ?? '')) ?>">
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
              <option value="draft" <?= (($form['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>Rascunho</option>
              <option value="closed" <?= (($form['status'] ?? '') === 'closed') ? 'selected' : '' ?>>Fechado</option>
            </select>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Tarifa kWh (opcional)</label>
            <input type="text" class="form-control" name="kwh_tariff" value="<?= htmlspecialchars((string)($form['kwh_tariff'] ?? '')) ?>" placeholder="0,0000">
          </div>
          <div class="col-12">
            <label class="form-label">Observações</label>
            <textarea class="form-control" name="notes" rows="3"><?= htmlspecialchars((string)($form['notes'] ?? '')) ?></textarea>
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <button type="submit" class="btn btn-success">Salvar período</button>
          <a href="<?= $_ENV['URL_ADM'] ?>list-inventory-cost-periods" class="btn btn-outline-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
</div>
