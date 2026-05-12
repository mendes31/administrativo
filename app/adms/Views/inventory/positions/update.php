<?php if (!isset($this)) { exit; } $position = $this->data['position'] ?? []; ?>
<div class="card">
  <div class="card-header hstack flex-wrap gap-2 align-items-center">
    <span>Editar Posição Interna</span>
    <span class="ms-auto d-flex flex-wrap gap-1">
      <?php
      $log_resumo = $this->data['log_resumo'] ?? [];
      $log_btn_class = 'btn btn-outline-info btn-sm';
      include __DIR__ . '/../../partials/button_log_alteracoes.php';
      ?>
    </span>
  </div>
  <div class="card-body">
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_update_inventory_position') ?>">
      <div class="row g-3">
        <div class="col-12 col-md-4">
          <label class="form-label">Estoque</label>
          <select class="form-select" name="inv_stock_id" required>
            <option value="">Selecione...</option>
            <?php foreach (($this->data['stocks'] ?? []) as $s): ?>
              <option value="<?= $s['id'] ?>" <?= (($position['inv_stock_id'] ?? '') == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-md-3"><label class="form-label">Código</label><input class="form-control" name="code" value="<?= htmlspecialchars($position['code'] ?? '') ?>" required></div>
        <div class="col-12 col-md-5"><label class="form-label">Descrição</label><input class="form-control" name="description" value="<?= htmlspecialchars($position['description'] ?? '') ?>" required></div>
      </div>
      <div class="mt-3 d-flex gap-2"><button class="btn btn-success">Salvar</button><a class="btn btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-positions">Voltar</a></div>
    </form>
  </div>
</div>








