<?php if (!isset($this)) { exit; } ?>
<div class="card">
  <div class="card-header">Cadastrar Estoque</div>
  <div class="card-body">
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_create_inventory_stock') ?>">
      <div class="row g-3">
        <div class="col-12 col-md-3">
          <label class="form-label">Código</label>
          <input class="form-control" name="code" required>
        </div>
        <div class="col-12 col-md-7">
          <label class="form-label">Nome</label>
          <input class="form-control" name="name" required>
        </div>
        <div class="col-12 col-md-2 form-check mt-4 pt-2">
          <input class="form-check-input" type="checkbox" name="active" id="active" checked>
          <label class="form-check-label" for="active">Ativo</label>
        </div>
      </div>
      <div class="mt-3 d-flex gap-2"><button class="btn btn-success">Salvar</button><a class="btn btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-stocks">Voltar</a></div>
    </form>
  </div>
</div>


