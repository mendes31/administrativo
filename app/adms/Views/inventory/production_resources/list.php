<?php if (!isset($this)) { exit; } ?>
<div class="container-fluid px-4">
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Recursos de Produção</span>
    <?php if (!empty($this->data['buttonPermission']) && in_array('CreateInventoryProductionResource', $this->data['buttonPermission'])): ?>
      <a href="<?= $_ENV['URL_ADM'] ?>create-inventory-production-resource" class="btn btn-sm btn-success">Cadastrar</a>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <form class="row g-2 mb-3" method="get">
      <input type="hidden" name="url" value="list-inventory-production-resources">
      <div class="col-12 col-md-3"><input class="form-control" name="erp_code" placeholder="Código ERP" value="<?= htmlspecialchars($_GET['erp_code'] ?? '') ?>"></div>
      <div class="col-12 col-md-3"><input class="form-control" name="name" placeholder="Nome" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>"></div>
      <div class="col-12 col-md-2">
        <select class="form-select" name="resource_type">
          <option value="">Tipo</option>
          <?php foreach (['LABOR'=>'MO','MACHINE'=>'Máquina','ENERGY'=>'Energia','MIXED'=>'Misto'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= (($_GET['resource_type'] ?? '') === $k) ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-4 d-flex gap-2">
        <select class="form-select" name="active">
          <option value="">Ativo?</option>
          <option value="1" <?= (($_GET['active'] ?? '') === '1') ? 'selected' : '' ?>>Sim</option>
          <option value="0" <?= (($_GET['active'] ?? '') === '0') ? 'selected' : '' ?>>Não</option>
        </select>
        <button class="btn btn-primary">Filtrar</button>
        <a class="btn btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-production-resources">Limpar</a>
      </div>
    </form>
    <div class="table-responsive">
      <table class="table table-striped align-middle w-100">
        <thead class="thead-green">
          <tr>
            <th>Cód. ERP</th><th>Nome</th><th>Tipo</th>
            <th class="text-end">MO/min</th><th class="text-end">Máq/min</th><th class="text-end">En/min</th>
            <th class="text-center">Ativo</th><th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($this->data['rows'] ?? []) as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['erp_code'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['resource_type'] ?? '') ?></td>
            <td class="text-end"><?= number_format((float)($row['labor_cost_per_min'] ?? 0), 4, ',', '.') ?></td>
            <td class="text-end"><?= number_format((float)($row['machine_cost_per_min'] ?? 0), 4, ',', '.') ?></td>
            <td class="text-end"><?= number_format((float)($row['energy_cost_per_min'] ?? 0), 4, ',', '.') ?></td>
            <td class="text-center"><?= !empty($row['active']) ? 'Sim' : 'Não' ?></td>
            <td class="text-end">
              <div class="d-inline-flex flex-wrap gap-1 justify-content-end">
                <?php $log_resumo = $row['log_resumo'] ?? []; $log_btn_class = 'btn btn-sm btn-outline-info'; include __DIR__ . '/../../partials/button_log_alteracoes.php'; ?>
                <?php if (in_array('UpdateInventoryProductionResource', $this->data['buttonPermission'] ?? [])): ?>
                  <a class="btn btn-sm btn-outline-primary" href="<?= $_ENV['URL_ADM'] ?>update-inventory-production-resource/<?= $row['id'] ?>">Editar</a>
                <?php endif; ?>
                <?php if (in_array('DeleteInventoryProductionResource', $this->data['buttonPermission'] ?? [])): ?>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_delete_inventory_production_resource') ?>">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger" formaction="<?= $_ENV['URL_ADM'] ?>delete-inventory-production-resource" onclick="return confirm('Excluir recurso?')">Excluir</button>
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
</div>
