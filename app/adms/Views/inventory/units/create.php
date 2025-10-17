<?php if (!isset($this)) { exit; } ?>
<div class="card">
  <div class="card-header">Cadastrar Unidade de Medida</div>
  <div class="card-body">
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_create_inventory_unit') ?>">
      <div class="row g-3">
        <div class="col-12 col-md-3">
          <label class="form-label">Código</label>
          <input class="form-control" name="code" required>
        </div>
        <div class="col-12 col-md-9">
          <label class="form-label">Nome</label>
          <input class="form-control" name="name" required>
        </div>
      </div>
      <div class="mt-3 d-flex gap-2">
        <button class="btn btn-success">Salvar</button>
        <a class="btn btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-units">Voltar</a>
      </div>
    </form>
  </div>
</div>








