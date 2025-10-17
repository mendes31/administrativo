<?php if (!isset($this)) { exit; } ?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Relatório de Histórico de Movimentações</span>
    <a class="btn btn-sm btn-outline-success" href="<?= $_ENV['URL_ADM'] ?>export-inventory-history-csv?<?= http_build_query($_GET) ?>">Exportar CSV</a>
  </div>
  <div class="card-body">
    <form class="row g-2 mb-3" method="get">
      <input type="hidden" name="url" value="report-inventory-history">
      <div class="col-12 col-md-2">
        <label class="form-label">Tipo</label>
        <select class="form-select" name="type">
          <option value="">Todos</option>
          <option value="entry" <?= (($_GET['type'] ?? '') === 'entry') ? 'selected' : '' ?>>Entrada</option>
          <option value="exit" <?= (($_GET['type'] ?? '') === 'exit') ? 'selected' : '' ?>>Saída</option>
          <option value="transfer" <?= (($_GET['type'] ?? '') === 'transfer') ? 'selected' : '' ?>>Transferência</option>
          <option value="adjust" <?= (($_GET['type'] ?? '') === 'adjust') ? 'selected' : '' ?>>Ajuste</option>
        </select>
      </div>
      <div class="col-12 col-md-2"><label class="form-label">De</label><input type="date" class="form-control" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>"></div>
      <div class="col-12 col-md-2"><label class="form-label">Até</label><input type="date" class="form-control" name="to" value="<?= htmlspecialchars($_GET['to'] ?? '') ?>"></div>
      <div class="col-12 col-md-3">
        <label class="form-label">Item</label>
        <select class="form-select" name="inv_item_id">
          <option value="">Todos</option>
          <?php foreach (($this->data['items'] ?? []) as $i): ?>
            <option value="<?= $i['id'] ?>" <?= (($_GET['inv_item_id'] ?? '') == $i['id']) ? 'selected' : '' ?>><?= htmlspecialchars($i['description']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label">Estoque</label>
        <select class="form-select" name="inv_stock_id">
          <option value="">Todos</option>
          <?php foreach (($this->data['stocks'] ?? []) as $s): ?>
            <option value="<?= $s['id'] ?>" <?= (($_GET['inv_stock_id'] ?? '') == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 d-flex gap-2 align-items-end">
        <button class="btn btn-primary">Filtrar</button>
        <a class="btn btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>report-inventory-history">Limpar</a>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-striped align-middle w-100">
        <thead class="thead-green">
          <tr>
            <th>Data</th><th>Tipo</th><th>Item</th><th class="text-end">Qtd</th><th>Lote</th><th>De</th><th>Para</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($this->data['rows'] ?? []) as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['movement_date'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['type'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['inv_item_id'] ?? '') ?></td>
              <td class="text-end"><?= number_format((float)($r['qty'] ?? 0), 4, ',', '.') ?></td>
              <td><?= htmlspecialchars($r['batch_code'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['from_stock_id'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['to_stock_id'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="d-flex justify-content-end"><?= $this->data['paginator'] ?? '' ?></div>
  </div>
</div>


