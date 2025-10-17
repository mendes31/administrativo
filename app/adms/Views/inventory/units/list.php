<?php if (!isset($this)) { exit; } ?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Unidades de Medida</span>
    <div class="d-flex gap-2">
      <?php if (!empty($this->data['buttonPermission']) && in_array('CreateInventoryUnit', $this->data['buttonPermission'])): ?>
        <a href="<?= $_ENV['URL_ADM'] ?>create-inventory-unit" class="btn btn-sm btn-success">Cadastrar</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="card-body">
    <form class="row g-2 mb-3" method="get">
      <input type="hidden" name="url" value="list-inventory-units">
      <div class="col-12 col-md-3">
        <input class="form-control" name="code" placeholder="Código" value="<?= htmlspecialchars($_GET['code'] ?? '') ?>">
      </div>
      <div class="col-12 col-md-5">
        <input class="form-control" name="name" placeholder="Nome" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>">
      </div>
      <div class="col-12 col-md-4 d-flex gap-2">
        <button class="btn btn-primary">Filtrar</button>
        <a class="btn btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-units">Limpar</a>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-striped align-middle w-100">
        <thead class="thead-green">
          <tr>
            <th>Código</th>
            <th>Nome</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach (($this->data['rows'] ?? []) as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['code']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td class="text-end">
              <?php if (!empty($this->data['buttonPermission']) && in_array('UpdateInventoryUnit', $this->data['buttonPermission'])): ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= $_ENV['URL_ADM'] ?>update-inventory-unit/<?= $row['id'] ?>">Editar</a>
              <?php endif; ?>
              <?php if (!empty($this->data['buttonPermission']) && in_array('DeleteInventoryUnit', $this->data['buttonPermission'])): ?>
              <form method="post" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_delete_inventory_unit') ?>">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" formaction="<?= $_ENV['URL_ADM'] ?>delete-inventory-unit" onclick="return confirm('Excluir unidade?')">Excluir</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-end">
      <?= $this->data['paginator'] ?? '' ?>
    </div>
  </div>
</div>








