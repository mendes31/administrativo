<?php if (!isset($this)) { exit; } ?>
<?php use App\adms\Helpers\CSRFHelper; ?>
<div class="container-fluid px-4 pb-4">
  <div class="mb-1 hstack gap-2 flex-wrap">
    <h2 class="mt-3 mb-0">Lotes Produzidos</h2>
    <ol class="breadcrumb mb-3 mt-3 ms-auto">
      <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>list-inventory-items">Itens</a></li>
      <li class="breadcrumb-item active">Lotes produzidos</li>
    </ol>
  </div>

  <?php include './app/adms/Views/partials/alerts.php'; ?>

  <div class="card mb-4 border-light shadow">
    <div class="card-header hstack gap-2 flex-wrap align-items-center">
      <span class="fw-semibold">Cadastro global de produção</span>
      <span class="ms-auto d-flex flex-wrap gap-2">
        <a class="btn btn-sm btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-cost-periods">
          <i class="fa-regular fa-calendar"></i> Períodos
        </a>
        <form method="post" class="d-inline">
          <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_sync_production_batches') ?>">
          <input type="hidden" name="sync_sap_production_batches" value="1">
          <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Sincronizar todos os lotes (TJQP e APQP) com o SAP?')">
            <i class="fa-solid fa-rotate"></i> Sync completa SAP
          </button>
        </form>
        <form method="post" class="d-inline">
          <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_sync_production_batches') ?>">
          <input type="hidden" name="sync_sap_production_batches" value="1">
          <input type="hidden" name="sync_incremental" value="1">
          <button type="submit" class="btn btn-sm btn-outline-primary">
            <i class="fa-solid fa-forward"></i> Sync incremental
          </button>
        </form>
      </span>
    </div>
    <div class="card-body">
      <p class="text-muted small mb-3">
        Sincronização global (sem filtro de período). Depósitos: TJQP e APQP. Use os filtros abaixo apenas para consulta.
        Total cadastrado: <strong><?= number_format((int)($this->data['total_rows'] ?? 0), 0, ',', '.') ?></strong> linhas.
        A coluna <strong>Eficiência</strong> usa o lote padrão do item (<em>Lote padrão (mín.)</em> no cadastro): qty produzida ÷ lote mínimo.
      </p>

      <form class="row g-2 mb-3" method="get">
        <input type="hidden" name="url" value="list-inventory-cost-production-batches">
        <div class="col-12 col-md-2">
          <input class="form-control form-control-sm" name="erp_code" placeholder="Cód. item" value="<?= htmlspecialchars($this->data['filters']['erp_code'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-2">
          <input class="form-control form-control-sm" name="batch_number" placeholder="Lote" value="<?= htmlspecialchars($this->data['filters']['batch_number'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-2">
          <select class="form-select form-select-sm" name="warehouse_code">
            <option value="">Depósito</option>
            <?php foreach (($this->data['warehouses'] ?? []) as $wh): ?>
              <option value="<?= htmlspecialchars($wh['code'] ?? '') ?>" <?= (($this->data['filters']['warehouse_code'] ?? '') === ($wh['code'] ?? '')) ? 'selected' : '' ?>>
                <?= htmlspecialchars($wh['code'] ?? '') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-md-2">
          <input type="date" class="form-control form-control-sm" name="date_from" value="<?= htmlspecialchars($this->data['filters']['date_from'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-2">
          <input type="date" class="form-control form-control-sm" name="date_to" value="<?= htmlspecialchars($this->data['filters']['date_to'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-2 d-flex gap-1">
          <button class="btn btn-sm btn-primary flex-grow-1">Filtrar</button>
          <a class="btn btn-sm btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-cost-production-batches">Limpar</a>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Data prod.</th>
              <th>Depósito</th>
              <th>Item</th>
              <th>Descrição</th>
              <th>Lote</th>
              <th class="text-end">Qtd</th>
              <th class="text-end">Lote mín.</th>
              <th class="text-end">Eficiência</th>
              <th>Doc. entrada</th>
              <th>OP</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($this->data['rows'])): ?>
              <tr><td colspan="10" class="text-center text-muted py-4">Nenhum lote encontrado. Execute a sincronização SAP.</td></tr>
            <?php else: ?>
              <?php foreach ($this->data['rows'] as $row): ?>
                <tr>
                  <td class="text-nowrap"><?= !empty($row['production_date']) ? date('d/m/Y', strtotime((string)$row['production_date'])) : '—' ?></td>
                  <td class="text-nowrap">
                    <span class="badge bg-secondary"><?= htmlspecialchars($row['warehouse_code'] ?? '') ?></span>
                  </td>
                  <td class="text-nowrap"><?= htmlspecialchars($row['erp_code'] ?? '') ?></td>
                  <td><?= htmlspecialchars($row['item_description'] ?? '') ?></td>
                  <td class="text-nowrap"><?= htmlspecialchars($row['batch_number'] ?? '') ?></td>
                  <td class="text-end text-nowrap"><?= number_format((float)($row['quantity'] ?? 0), 2, ',', '.') ?></td>
                  <td class="text-end text-nowrap text-muted">
                    <?= isset($row['min_batch_size']) && (float)$row['min_batch_size'] > 0
                      ? number_format((float)$row['min_batch_size'], 2, ',', '.')
                      : '—' ?>
                  </td>
                  <td class="text-end text-nowrap">
                    <?php if (isset($row['efficiency_pct']) && $row['efficiency_pct'] !== null): ?>
                      <?php
                      $effPct = (float)$row['efficiency_pct'];
                      $effClass = $effPct >= 100 ? 'text-success' : ($effPct >= 95 ? 'text-body' : 'text-warning');
                      ?>
                      <span class="<?= $effClass ?>"><?= number_format($effPct, 2, ',', '.') ?>%</span>
                    <?php else: ?>
                      <span class="text-muted" title="Cadastre o lote padrão no item ou vincule o código ERP">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-nowrap"><?= htmlspecialchars((string)($row['goods_receipt_doc_num'] ?? '—')) ?></td>
                  <td class="text-nowrap"><?= htmlspecialchars((string)($row['production_order_num'] ?? ($row['base_entry'] ?? '—'))) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if (!empty($this->data['pagination']['html'])): ?>
        <div class="d-flex justify-content-end mt-3"><?= $this->data['pagination']['html'] ?></div>
      <?php endif; ?>
    </div>
  </div>
</div>
