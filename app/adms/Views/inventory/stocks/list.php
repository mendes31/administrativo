<?php if (!isset($this)) { exit; } ?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Estoques</span>
    <div>
      <?php if (!empty($this->data['buttonPermission']) && in_array('CreateInventoryStock', $this->data['buttonPermission'])): ?>
        <a href="<?= $_ENV['URL_ADM'] ?>create-inventory-stock" class="btn btn-sm btn-success">Cadastrar</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="card-body">
    <form class="row g-2 mb-3" method="get">
      <input type="hidden" name="url" value="list-inventory-stocks">
      <div class="col-12 col-md-3"><input class="form-control" name="code" placeholder="Código" value="<?= htmlspecialchars($_GET['code'] ?? '') ?>"></div>
      <div class="col-12 col-md-5"><input class="form-control" name="name" placeholder="Nome" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>"></div>
      <div class="col-12 col-md-4 d-flex gap-2 align-items-start">
        <select class="form-select" name="active">
          <option value="">Status</option>
          <option value="1" <?= (($_GET['active'] ?? '') === '1') ? 'selected' : '' ?>>Ativo</option>
          <option value="0" <?= (($_GET['active'] ?? '') === '0') ? 'selected' : '' ?>>Inativo</option>
        </select>
        <button class="btn btn-primary">Filtrar</button>
        <a class="btn btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-stocks">Limpar</a>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-striped align-middle w-100">
        <thead class="thead-green">
          <tr>
            <th style="width:12%;min-width:90px;">Código</th>
            <th style="width:58%;min-width:200px;">Nome</th>
            <th style="width:15%;min-width:110px;">Status</th>
            <th class="text-end" style="width:15%;min-width:140px;">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($this->data['rows'] ?? []) as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['code']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= ((int)($row['active'] ?? 0)) ? 'Ativo' : 'Inativo' ?></td>
            <td class="text-end">
              <div class="d-inline-flex flex-wrap gap-1 justify-content-end">
              <?php
              $log_resumo = $row['log_resumo'] ?? [];
              $log_btn_class = 'btn btn-sm btn-outline-info';
              include __DIR__ . '/../../partials/button_log_alteracoes.php';
              ?>
              <?php if (!empty($this->data['buttonPermission']) && in_array('UpdateInventoryStock', $this->data['buttonPermission'])): ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= $_ENV['URL_ADM'] ?>update-inventory-stock/<?= $row['id'] ?>">Editar</a>
              <?php endif; ?>
              <?php if (!empty($this->data['buttonPermission']) && in_array('DeleteInventoryStock', $this->data['buttonPermission'])): ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_delete_inventory_stock') ?>">
                  <input type="hidden" name="id" value="<?= $row['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger" formaction="<?= $_ENV['URL_ADM'] ?>delete-inventory-stock" onclick="return confirm('Excluir estoque?')">Excluir</button>
                </form>
              <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="d-flex justify-content-end"><?= $this->data['paginator'] ?? '' ?></div>
  </div>
</div>


