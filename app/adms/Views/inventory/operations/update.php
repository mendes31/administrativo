<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">

  <div class="mb-1 hstack gap-2">
    <h2 class="mt-3">Operações de Produção</h2>

    <ol class="breadcrumb mb-3 mt-3 ms-auto">
      <li class="breadcrumb-item">
        <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
      </li>
      <li class="breadcrumb-item">
        <a href="<?php echo $_ENV['URL_ADM']; ?>list-inventory-operations" class="text-decoration-none">Operações</a>
      </li>
      <li class="breadcrumb-item">Editar</li>
    </ol>
  </div>

    <div class="card mb-4 border-light shadow">
    <div class="card-header hstack gap-2 flex-wrap align-items-center">
      <span>Editar</span>
      <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1 align-items-center">
        <?php if (in_array('ListInventoryOperations', $this->data['buttonPermission'])) { echo "<a href='{$_ENV['URL_ADM']}list-inventory-operations' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> "; } ?>
        <?php
        $log_resumo = $this->data['log_resumo'] ?? [];
        $log_btn_class = 'btn btn-outline-info btn-sm';
        include __DIR__ . '/../../partials/button_log_alteracoes.php';
        ?>
      </span>
    </div>

    <div class="card-body">
      <?php include './app/adms/Views/partials/alerts.php'; ?>

      <form action="" method="POST" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_inventory_operation'); ?>">

        <div class="col-12 col-md-3">
          <label for="code" class="form-label">Código</label>
          <input type="text" name="code" id="code" class="form-control" value="<?php echo $this->data['form']['code'] ?? ''; ?>" placeholder="Ex.: MIX, ENV">
        </div>

        <div class="col-12 col-md-9">
          <label for="name" class="form-label">Nome</label>
          <input type="text" name="name" id="name" class="form-control" value="<?php echo $this->data['form']['name'] ?? ''; ?>" placeholder="Ex.: Mistura, Envase, Pesagem">
        </div>

        <div class="col-12 col-md-4">
          <label for="default_cost_per_hour" class="form-label">Custo/hora padrão</label>
          <input type="number" step="0.0001" min="0" name="default_cost_per_hour" id="default_cost_per_hour" class="form-control" value="<?php echo $this->data['form']['default_cost_per_hour'] ?? '0'; ?>">
          <small class="text-muted">Informado manualmente na V1. Usado em simulações de custo.</small>
        </div>

        <div class="col-12 col-md-4">
          <label for="active" class="form-label">Ativo</label>
          <div class="form-check form-switch mt-2">
            <?php $active = (isset($this->data['form']['active']) ? (int)$this->data['form']['active'] : 1) ? 'checked' : ''; ?>
            <input class="form-check-input" type="checkbox" id="active" name="active" <?php echo $active; ?> />
            <label class="form-check-label" for="active">Sim</label>
          </div>
        </div>

        <div class="col-12">
          <label for="description" class="form-label">Descrição</label>
          <textarea name="description" id="description" rows="3" class="form-control"><?php echo $this->data['form']['description'] ?? ''; ?></textarea>
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-success">Salvar</button>
        </div>
      </form>
    </div>
  </div>

</div>

