<?php use App\adms\Helpers\CSRFHelper; if (!isset($this)) { exit; } ?>
<div class="container-fluid px-4">
<div class="card mb-4 border-light shadow">
  <div class="card-header">Cadastrar Recurso de Produção</div>
  <div class="card-body">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <form method="POST" class="row g-3">
      <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_create_inventory_production_resource') ?>">
      <div class="col-md-3"><label class="form-label">Código ERP *</label><input name="erp_code" class="form-control" value="<?= htmlspecialchars($this->data['form']['erp_code'] ?? '') ?>" required></div>
      <div class="col-md-5"><label class="form-label">Nome *</label><input name="name" class="form-control" value="<?= htmlspecialchars($this->data['form']['name'] ?? '') ?>" required></div>
      <div class="col-md-4">
        <label class="form-label">Tipo</label>
        <select name="resource_type" class="form-select">
          <?php foreach (['MACHINE'=>'Máquina/Equipamento','LABOR'=>'Mão de obra','ENERGY'=>'Energia','MIXED'=>'Misto'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= (($this->data['form']['resource_type'] ?? 'MACHINE') === $k) ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3"><label class="form-label">MO/min (R$)</label><input type="number" step="0.000001" min="0" name="labor_cost_per_min" class="form-control" value="<?= htmlspecialchars((string)($this->data['form']['labor_cost_per_min'] ?? '0')) ?>"></div>
      <div class="col-md-3"><label class="form-label">Máq/min (R$)</label><input type="number" step="0.000001" min="0" name="machine_cost_per_min" class="form-control" value="<?= htmlspecialchars((string)($this->data['form']['machine_cost_per_min'] ?? '0')) ?>"></div>
      <div class="col-md-3"><label class="form-label">Energia/min (R$)</label><input type="number" step="0.000001" min="0" name="energy_cost_per_min" class="form-control" value="<?= htmlspecialchars((string)($this->data['form']['energy_cost_per_min'] ?? '0')) ?>"></div>
      <div class="col-md-3"><label class="form-label">Potência (kW)</label><input type="number" step="0.0001" min="0" name="power_kw" class="form-control" value="<?= htmlspecialchars((string)($this->data['form']['power_kw'] ?? '')) ?>"></div>
      <div class="col-md-3"><div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="active" checked><label class="form-check-label">Ativo</label></div></div>
      <div class="col-12"><button class="btn btn-success">Salvar</button> <a class="btn btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-production-resources">Voltar</a></div>
    </form>
  </div>
</div>
</div>
