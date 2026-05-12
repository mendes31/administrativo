<?php if (!isset($this)) { exit; } $category = $this->data['category'] ?? []; ?>
<div class="card">
  <div class="card-header hstack flex-wrap gap-2 align-items-center">
    <span>Editar Categoria</span>
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
      <input type="hidden" name="csrf_token" value="<?= \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_update_inventory_category') ?>">
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Nome</label>
          <input class="form-control" name="name" value="<?= htmlspecialchars($category['name'] ?? '') ?>" required>
        </div>
      </div>
      <div class="mt-3 d-flex gap-2">
        <button class="btn btn-success">Salvar</button>
        <a class="btn btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-categories">Voltar</a>
      </div>
    </form>
  </div>
</div>








