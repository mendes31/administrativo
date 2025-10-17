<?php if (!isset($this)) { exit; } ?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Categorias de Itens</span>
    <div>
      <?php if (!empty($this->data['buttonPermission']) && in_array('CreateInventoryCategory', $this->data['buttonPermission'])): ?>
        <a href="<?= $_ENV['URL_ADM'] ?>create-inventory-category" class="btn btn-sm btn-success">Cadastrar</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="card-body">
    <form class="row g-2 mb-3" method="get">
      <input type="hidden" name="url" value="list-inventory-categories">
      <div class="col-12 col-md-6">
        <input class="form-control" name="name" placeholder="Nome" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>">
      </div>
      <div class="col-12 col-md-6 d-flex gap-2">
        <button class="btn btn-primary">Filtrar</button>
        <a class="btn btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-categories">Limpar</a>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-striped align-middle w-100">
        <thead class="thead-green"><tr><th>Nome</th><th class="text-end">Ações</th></tr></thead>
        <tbody>
          <?php foreach (($this->data['rows'] ?? []) as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td class="text-end">
                <?php if (!empty($this->data['buttonPermission']) && in_array('UpdateInventoryCategory', $this->data['buttonPermission'])): ?>
                  <a class="btn btn-sm btn-outline-primary" href="<?= $_ENV['URL_ADM'] ?>update-inventory-category/<?= $row['id'] ?>">Editar</a>
                <?php endif; ?>
                <?php if (!empty($this->data['buttonPermission']) && in_array('DeleteInventoryCategory', $this->data['buttonPermission'])): ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_delete_inventory_category') ?>">
                  <input type="hidden" name="id" value="<?= $row['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger" formaction="<?= $_ENV['URL_ADM'] ?>delete-inventory-category" onclick="return confirm('Excluir categoria?')">Excluir</button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="d-flex justify-content-end"><?= $this->data['paginator'] ?? '' ?></div>
  </div>
</div>








