<?php use App\adms\Helpers\CSRFHelper; if (!isset($this)) { exit; } ?>
<div class="container-fluid px-4">
<div class="card mb-4 border-light shadow">
  <div class="card-header">Cadastrar Papel de MO</div>
  <div class="card-body">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <form method="POST" class="row g-3">
      <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_create_inventory_labor_role') ?>">
      <div class="col-md-3"><label class="form-label">Código</label><input name="code" class="form-control" value="<?= htmlspecialchars($this->data['form']['code'] ?? '') ?>" placeholder="OPERADOR"></div>
      <div class="col-md-5"><label class="form-label">Nome *</label><input name="name" class="form-control" value="<?= htmlspecialchars($this->data['form']['name'] ?? '') ?>" required></div>
      <div class="col-md-4">
        <label class="form-label">R$/min padrão</label>
        <input type="number" step="0.000001" min="0" name="default_cost_per_min" class="form-control" value="<?= htmlspecialchars((string)($this->data['form']['default_cost_per_min'] ?? '0.05')) ?>">
        <div class="form-text">Referência para uso futuro na rota consolidada. Custeio oficial de MO via rateio CFIX (crit. 2 HH).</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Cargo RH (opcional)</label>
        <select name="adms_position_id" class="form-select"><option value="">—</option>
          <?php foreach (($this->data['listPositions'] ?? []) as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3"><div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="active" checked><label class="form-check-label">Ativo</label></div></div>
      <div class="col-12"><button class="btn btn-success">Salvar</button> <a class="btn btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-labor-roles">Voltar</a></div>
    </form>
  </div>
</div>
</div>
