<?php if (!isset($this)) { exit; } $item = $this->data['item'] ?? []; ?>
<div class="container-fluid px-4">
  <div class="mb-1 hstack gap-2">
    <ol class="breadcrumb mb-3 mt-3 ms-auto">
      <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard" class="text-decoration-none">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>list-inventory-items" class="text-decoration-none">Itens</a></li>
      <li class="breadcrumb-item active">Visualizar</li>
    </ol>
    <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1 align-items-center">
      <?php if (!empty($this->data['buttonPermission']) && in_array('ListInventoryItems', $this->data['buttonPermission'])): ?>
        <a href="<?= $_ENV['URL_ADM'] ?>list-inventory-items" class="btn btn-info btn-sm me-1 mb-1"><i class="fa-solid fa-list"></i> Listar</a>
      <?php endif; ?>
      <?php if (!empty($this->data['buttonPermission']) && in_array('UpdateInventoryItem', $this->data['buttonPermission'])): ?>
        <a href="<?= $_ENV['URL_ADM'] . 'update-inventory-item/' . ($item['id'] ?? '') ?>" class="btn btn-warning btn-sm me-1 mb-1"><i class="fa-solid fa-pen-to-square"></i> Editar</a>
      <?php endif; ?>
      <?php
      $log_resumo = $this->data['log_resumo'] ?? [];
      $log_btn_class = 'btn btn-outline-info btn-sm';
      include __DIR__ . '/../../partials/button_log_alteracoes.php';
      ?>
    </span>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Item: <?= htmlspecialchars(($item['code'] ?? '') . ' - ' . ($item['description'] ?? '')) ?></span>
    <div class="small text-muted">Administração: <?= htmlspecialchars($item['admin_type'] ?? 'none') ?> | Unidade: <?= htmlspecialchars($item['unit_name'] ?? '') ?> | Categoria: <?= htmlspecialchars($item['category_name'] ?? '') ?></div>
  </div>
  <div class="card-body">
    <div class="row g-3 mb-3">
      <div class="col-12 col-md-4">
        <div class="d-flex flex-wrap gap-2"><span class="text-muted">Código</span><span class="fw-semibold"><?= htmlspecialchars($item['code'] ?? '') ?></span></div>
        <div class="d-flex flex-wrap gap-2"><span class="text-muted">Código ERP</span><span class="fw-semibold"><?= htmlspecialchars($item['erp_code'] ?? '') ?></span></div>
        <div class="d-flex flex-wrap gap-2"><span class="text-muted">Unidade</span><span class="fw-semibold"><?= htmlspecialchars($item['unit_name'] ?? '') ?></span></div>
        <div class="d-flex flex-wrap gap-2"><span class="text-muted">Administração</span><span class="fw-semibold"><?= htmlspecialchars($item['admin_type'] ?? 'none') ?></span></div>
      </div>
      <div class="col-12 col-md-4">
        <div class="d-flex flex-wrap gap-2"><span class="text-muted">Custo médio</span><span class="fw-semibold"><?= number_format((float)($this->data['header_average_cost'] ?? ($item['average_cost'] ?? 0)), 4, ',', '.') ?></span></div>
        <div class="d-flex flex-wrap gap-2"><span class="text-muted">Último custo</span><span class="fw-semibold"><?= number_format((float)($item['last_cost'] ?? 0), 4, ',', '.') ?></span></div>
        <div class="d-flex flex-wrap gap-2"><span class="text-muted">Descrição</span><span class="fw-semibold"><?= htmlspecialchars($item['description'] ?? '') ?></span></div>
      </div>
      <div class="col-12 col-md-4">
        <div class="d-flex flex-wrap gap-2"><span class="text-muted">Estoque mínimo</span><span class="fw-semibold"><?= number_format((float)($item['min_stock'] ?? 0), 4, ',', '.') ?></span></div>
        <div class="d-flex flex-wrap gap-2"><span class="text-muted">Estoque máximo</span><span class="fw-semibold"><?= number_format((float)($item['max_stock'] ?? 0), 4, ',', '.') ?></span></div>
        <div class="d-flex flex-wrap gap-2"><span class="text-muted">Categoria</span><span class="fw-semibold"><?= htmlspecialchars($item['category_name'] ?? '') ?></span></div>
        <div class="d-flex flex-wrap gap-2"><span class="text-muted">Ativo</span><span class="fw-semibold"><?= !empty($item['active']) ? 'Sim' : 'Não' ?></span></div>
      </div>
    </div>

    <h6 class="mt-2 mb-2">Saldos por depósito/posição</h6>
    <div class="table-responsive">
      <table class="table table-striped align-middle w-100">
        <thead class="thead-green">
          <tr>
            <th>Depósito</th><th>Posição</th><th>Lote</th><th>Validade</th>
            <th class="text-end">Em estoque</th>
            <th class="text-end">Custo médio</th>
            <th class="text-end">Valor</th>
          </tr>
        </thead>
        <tbody>
          <?php $totalQty = 0; $totalVal = 0; foreach (($this->data['balances'] ?? []) as $b): $val = (float)($b['qty'] ?? 0) * (float)($b['average_cost'] ?? 0); $totalQty += (float)($b['qty'] ?? 0); $totalVal += $val; ?>
          <tr>
            <td><?= htmlspecialchars($b['stock_name'] ?? '') ?></td>
            <td><?= htmlspecialchars(trim(($b['position_code'] ?? '') . ' ' . ($b['position_description'] ?? ''))) ?></td>
            <td><?= htmlspecialchars($b['batch_code'] ?? '') ?></td>
            <td><?= htmlspecialchars($b['expiration_date'] ?? '') ?></td>
            <td class="text-end">&nbsp;<?= number_format((float)($b['qty'] ?? 0), 4, ',', '.') ?></td>
            <td class="text-end">&nbsp;<?= number_format((float)($b['average_cost'] ?? 0), 4, ',', '.') ?></td>
            <td class="text-end">&nbsp;<?= number_format($val, 2, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="4" class="text-end">Totais:</th>
            <th class="text-end"><?= number_format($totalQty, 4, ',', '.') ?></th>
            <th></th>
            <th class="text-end"><?= number_format($totalVal, 2, ',', '.') ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>



