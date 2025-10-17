<?php if (!isset($this)) { exit; } ?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Relatório de Saldos de Estoque</span>
    <a class="btn btn-sm btn-outline-success" href="<?= $_ENV['URL_ADM'] ?>export-inventory-balance-csv?<?= http_build_query($_GET) ?>">Exportar CSV</a>
  </div>
  <div class="card-body">
    <form class="row g-2 mb-3" method="get">
      <input type="hidden" name="url" value="report-inventory-balance">
      <div class="col-12 col-md-4">
        <label class="form-label">Item</label>
        <select class="form-select" name="inv_item_id">
          <option value="">Todos</option>
          <?php foreach (($this->data['items'] ?? []) as $i): ?>
            <option value="<?= $i['id'] ?>" <?= (($_GET['inv_item_id'] ?? '') == $i['id']) ? 'selected' : '' ?>><?= htmlspecialchars($i['description']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label">Estoque</label>
        <select class="form-select" name="inv_stock_id">
          <option value="">Todos</option>
          <?php foreach (($this->data['stocks'] ?? []) as $s): ?>
            <option value="<?= $s['id'] ?>" <?= (($_GET['inv_stock_id'] ?? '') == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label">Posição</label>
        <select class="form-select" name="inv_position_id">
          <option value="">Todas</option>
          <?php foreach (($this->data['positions'] ?? []) as $p): ?>
            <option value="<?= $p['id'] ?>" <?= (($_GET['inv_position_id'] ?? '') == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars(($p['code'] ?? '') . ' - ' . ($p['description'] ?? '')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label">Lote</label>
        <input class="form-control" name="batch_code" value="<?= htmlspecialchars($_GET['batch_code'] ?? '') ?>">
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label">Validade</label>
        <input type="date" class="form-control" name="expiration_date" value="<?= htmlspecialchars($_GET['expiration_date'] ?? '') ?>">
      </div>
      <div class="col-12 col-md-6 d-flex gap-2 align-items-end">
        <button class="btn btn-primary">Filtrar</button>
        <a class="btn btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>report-inventory-balance">Limpar</a>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-striped align-middle w-100">
        <thead class="thead-green">
          <tr>
            <th>Item</th><th>Estoque</th><th>Posição</th><th>Lote</th><th>Validade</th><th class="text-end">Qtd</th><th class="text-end">Custo Médio</th><th class="text-end">Valor</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($this->data['rows'] ?? []) as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['item_description'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['stock_name'] ?? '') ?></td>
              <td><?= htmlspecialchars(($r['position_code'] ?? '') . ' ' . ($r['position_description'] ?? '')) ?></td>
              <td><?= htmlspecialchars($r['batch_code'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['expiration_date'] ?? '') ?></td>
              <td class="text-end"><?= number_format((float)($r['qty'] ?? 0), 4, ',', '.') ?></td>
              <td class="text-end"><?= number_format((float)($r['average_cost'] ?? 0), 4, ',', '.') ?></td>
              <td class="text-end"><?= number_format((float)($r['total_value'] ?? 0), 2, ',', '.') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="d-flex justify-content-end"><?= $this->data['paginator'] ?? '' ?></div>
  </div>
</div>


