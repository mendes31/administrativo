<?php if (!isset($this)) { exit; } ?>
<div class="container-fluid px-4">
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Papéis de Mão de Obra</span>
    <?php if (in_array('CreateInventoryLaborRole', $this->data['buttonPermission'] ?? [])): ?>
      <a href="<?= $_ENV['URL_ADM'] ?>create-inventory-labor-role" class="btn btn-sm btn-success">Cadastrar</a>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <form class="row g-2 mb-3" method="get">
      <input type="hidden" name="url" value="list-inventory-labor-roles">
      <div class="col-md-6"><input class="form-control" name="name" placeholder="Nome" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>"></div>
      <div class="col-md-6 d-flex gap-2">
        <select class="form-select" name="active"><option value="">Ativo?</option><option value="1" <?= (($_GET['active'] ?? '')==='1')?'selected':'' ?>>Sim</option><option value="0" <?= (($_GET['active'] ?? '')==='0')?'selected':'' ?>>Não</option></select>
        <button class="btn btn-primary">Filtrar</button>
        <a class="btn btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-labor-roles">Limpar</a>
      </div>
    </form>
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead class="thead-green"><tr><th>Código</th><th>Nome</th><th>Cargo RH</th><th class="text-end">R$/min padrão</th><th class="text-center">Ativo</th><th class="text-end">Ações</th></tr></thead>
        <tbody>
          <?php foreach (($this->data['rows'] ?? []) as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['code'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['position_name'] ?? '—') ?></td>
            <td class="text-end"><?= number_format((float)($row['default_cost_per_min'] ?? 0), 4, ',', '.') ?></td>
            <td class="text-center"><?= !empty($row['active']) ? 'Sim' : 'Não' ?></td>
            <td class="text-end">
              <?php $log_resumo = $row['log_resumo'] ?? []; $log_btn_class = 'btn btn-sm btn-outline-info'; include __DIR__ . '/../../partials/button_log_alteracoes.php'; ?>
              <?php if (in_array('UpdateInventoryLaborRole', $this->data['buttonPermission'] ?? [])): ?><a class="btn btn-sm btn-outline-primary" href="<?= $_ENV['URL_ADM'] ?>update-inventory-labor-role/<?= $row['id'] ?>">Editar</a><?php endif; ?>
              <?php if (in_array('DeleteInventoryLaborRole', $this->data['buttonPermission'] ?? [])): ?>
              <form method="post" class="d-inline"><input type="hidden" name="csrf_token" value="<?= \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_delete_inventory_labor_role') ?>"><input type="hidden" name="id" value="<?= $row['id'] ?>"><button class="btn btn-sm btn-outline-danger" formaction="<?= $_ENV['URL_ADM'] ?>delete-inventory-labor-role" onclick="return confirm('Excluir?')">Excluir</button></form>
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
</div>
