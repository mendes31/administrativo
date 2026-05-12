<?php if (!isset($this)) { exit; } $unit = $this->data['unit'] ?? []; ?>
<div class="card">
  <div class="card-header hstack flex-wrap gap-2 align-items-center">
    <span>Editar Unidade de Medida</span>
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
      <input type="hidden" name="csrf_token" value="<?= \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_update_inventory_unit') ?>">
      <div class="row g-3">
        <div class="col-12 col-md-3">
          <label class="form-label">Código</label>
          <input class="form-control" name="code" value="<?= htmlspecialchars($unit['code'] ?? '') ?>" required>
        </div>
        <div class="col-12 col-md-9">
          <label class="form-label">Nome</label>
          <input class="form-control" name="name" value="<?= htmlspecialchars($unit['name'] ?? '') ?>" required>
        </div>
      </div>
      <div class="mt-3 d-flex gap-2">
        <button class="btn btn-success">Salvar</button>
        <a class="btn btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-units">Voltar</a>
      </div>
    </form>
  </div>
</div>








