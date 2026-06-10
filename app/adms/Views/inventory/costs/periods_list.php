<?php if (!isset($this)) { exit; } ?>
<div class="container-fluid px-4 pb-4">
  <div class="mb-1 hstack gap-2 flex-wrap">
    <h2 class="mt-3 mb-0">Períodos de Custeio</h2>
    <ol class="breadcrumb mb-3 mt-3 ms-auto">
      <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>list-inventory-items">Itens</a></li>
      <li class="breadcrumb-item active">Períodos</li>
    </ol>
  </div>

  <?php include './app/adms/Views/partials/alerts.php'; ?>

  <div class="card border-light shadow">
    <div class="card-header hstack gap-2">
      <span class="fw-semibold">Janelas analíticas</span>
      <span class="ms-auto">
        <?php if (in_array('CreateInvCostPeriod', $this->data['buttonPermission'] ?? [], true)): ?>
          <a href="<?= $_ENV['URL_ADM'] ?>create-inventory-cost-period" class="btn btn-sm btn-success">
            <i class="fa-solid fa-plus"></i> Novo período
          </a>
        <?php endif; ?>
        <a href="<?= $_ENV['URL_ADM'] ?>list-inventory-cost-production-batches" class="btn btn-sm btn-outline-secondary">
          Lotes produzidos
        </a>
      </span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th class="ps-3">Nome</th>
              <th>De</th>
              <th>Até</th>
              <th>Status</th>
              <th class="text-end">Tarifa kWh</th>
              <th class="pe-3">Cadastrado</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($this->data['rows'])): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">Nenhum período cadastrado.</td></tr>
            <?php else: ?>
              <?php foreach ($this->data['rows'] as $row): ?>
                <tr>
                  <td class="ps-3 fw-semibold"><?= htmlspecialchars($row['name'] ?? '') ?></td>
                  <td class="text-nowrap"><?= !empty($row['date_from']) ? date('d/m/Y', strtotime((string)$row['date_from'])) : '—' ?></td>
                  <td class="text-nowrap"><?= !empty($row['date_to']) ? date('d/m/Y', strtotime((string)$row['date_to'])) : '—' ?></td>
                  <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['status'] ?? 'draft') ?></span></td>
                  <td class="text-end"><?= $row['kwh_tariff'] !== null ? number_format((float)$row['kwh_tariff'], 4, ',', '.') : '—' ?></td>
                  <td class="pe-3 small text-muted"><?= !empty($row['created_at']) ? date('d/m/Y H:i', strtotime((string)$row['created_at'])) : '—' ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php if (!empty($this->data['pagination']['html'])): ?>
        <div class="p-3 d-flex justify-content-end"><?= $this->data['pagination']['html'] ?></div>
      <?php endif; ?>
    </div>
  </div>
</div>
