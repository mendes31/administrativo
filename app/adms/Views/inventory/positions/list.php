<?php if (!isset($this)) { exit; } ?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Posições Internas</span>
    <div>
      <?php if (!empty($this->data['buttonPermission']) && in_array('CreateInventoryPosition', $this->data['buttonPermission'])): ?>
        <a href="<?= $_ENV['URL_ADM'] ?>create-inventory-position" class="btn btn-sm btn-success">Cadastrar</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="card-body">
    <form class="row g-2 mb-3" method="get">
      <input type="hidden" name="url" value="list-inventory-positions">
      <div class="col-12 col-md-3">
        <select class="form-select" name="inv_stock_id">
          <option value="">Estoque</option>
          <?php foreach (($this->data['stocks'] ?? []) as $s): ?>
            <option value="<?= $s['id'] ?>" <?= (($_GET['inv_stock_id'] ?? '') == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-3"><input class="form-control" name="code" placeholder="Código" value="<?= htmlspecialchars($_GET['code'] ?? '') ?>"></div>
      <div class="col-12 col-md-4"><input class="form-control" name="description" placeholder="Descrição" value="<?= htmlspecialchars($_GET['description'] ?? '') ?>"></div>
      <div class="col-12 col-md-2 d-flex gap-2"><button class="btn btn-primary">Filtrar</button><a class="btn btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-positions">Limpar</a></div>
    </form>

    <div class="table-responsive">
      <table class="table table-striped align-middle w-100">
        <thead class="thead-green"><tr><th>Estoque</th><th>Código</th><th>Descrição</th><th class="text-end">Ações</th></tr></thead>
        <tbody>
          <?php foreach (($this->data['rows'] ?? []) as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['inv_stock_id']) ?></td>
            <td><?= htmlspecialchars($row['code']) ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td class="text-end">
              <?php if (!empty($this->data['buttonPermission']) && in_array('UpdateInventoryPosition', $this->data['buttonPermission'])): ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= $_ENV['URL_ADM'] ?>update-inventory-position/<?= $row['id'] ?>">Editar</a>
              <?php endif; ?>
              <?php if (!empty($this->data['buttonPermission']) && in_array('DeleteInventoryPosition', $this->data['buttonPermission'])): ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_delete_inventory_position') ?>">
                  <input type="hidden" name="id" value="<?= $row['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger" formaction="<?= $_ENV['URL_ADM'] ?>delete-inventory-position" onclick="return confirm('Excluir posição?')">Excluir</button>
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








