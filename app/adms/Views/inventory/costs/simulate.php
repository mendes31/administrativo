<?php if (!isset($this)) { exit; } ?>
<?php
$breakdown = $this->data['breakdown'] ?? null;
$scenario = $this->data['scenario'] ?? ['material_adjust_pct' => 0, 'operations_adjust_pct' => 0, 'global_adjust_pct' => 0];
$selectedItem = $this->data['selected_item'] ?? null;
$itemId = (int)($this->data['selected_item_id'] ?? 0);
$fmtMoney = static fn(float $v): string => number_format($v, 4, ',', '.');
$fmtPct = static fn(float $v): string => number_format($v, 2, ',', '.');
$baseUrl = $_ENV['URL_ADM'] . 'simulate-inventory-cost/' . $itemId;
?>
<?php include __DIR__ . '/../partials/cost_tables_compact_style.php'; ?>
<div class="container-fluid px-4 pb-4">

  <div class="mb-1 hstack gap-2 flex-wrap">
    <h2 class="mt-3 mb-0">Simulação de Custos</h2>
    <ol class="breadcrumb mb-3 mt-3 ms-auto">
      <li class="breadcrumb-item">
        <a href="<?= $_ENV['URL_ADM'] ?>dashboard" class="text-decoration-none">Dashboard</a>
      </li>
      <li class="breadcrumb-item">
        <a href="<?= $_ENV['URL_ADM'] ?>list-inventory-items" class="text-decoration-none">Itens</a>
      </li>
      <?php if ($itemId > 0): ?>
        <li class="breadcrumb-item">
          <a href="<?= $_ENV['URL_ADM'] ?>view-inventory-item/<?= $itemId ?>" class="text-decoration-none">Visualizar</a>
        </li>
      <?php endif; ?>
      <li class="breadcrumb-item active">Simulação</li>
    </ol>
  </div>

  <?php include './app/adms/Views/partials/alerts.php'; ?>

  <div class="card mb-4 border-light shadow">
    <div class="card-header hstack gap-2 flex-wrap align-items-center py-3">
      <span class="fw-semibold">Cenários de ajuste</span>
      <span class="ms-auto d-flex flex-wrap gap-2">
        <?php if ($itemId > 0): ?>
          <a class="btn btn-sm btn-outline-primary" href="<?= $_ENV['URL_ADM'] ?>view-inventory-item/<?= $itemId ?>">
            <i class="fa-regular fa-eye"></i> Voltar ao item
          </a>
        <?php endif; ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= $_ENV['URL_ADM'] ?>list-inventory-items">
          <i class="fa-solid fa-list"></i> Listar itens
        </a>
      </span>
    </div>

    <div class="card-body p-4">
      <?php if (is_array($selectedItem)): ?>
        <div class="rounded border bg-light p-3 mb-4">
          <div class="fw-semibold fs-6">
            <?= htmlspecialchars(($selectedItem['code'] ?? '') . ' — ' . ($selectedItem['description'] ?? '')) ?>
          </div>
          <div class="small text-muted mt-2 d-flex flex-wrap gap-3">
            <?php if (!empty($selectedItem['erp_code'])): ?>
              <span><span class="text-secondary">ERP:</span> <?= htmlspecialchars($selectedItem['erp_code']) ?></span>
            <?php endif; ?>
            <span><span class="text-secondary">Categoria:</span> <?= htmlspecialchars($selectedItem['category_name'] ?? '—') ?></span>
            <span><span class="text-secondary">Unidade:</span> <?= htmlspecialchars($selectedItem['unit_name'] ?? '—') ?></span>
          </div>
        </div>
      <?php endif; ?>

      <p class="text-muted mb-4">
        Compare o custo base (lista de materiais + rota) com cenários de ajuste percentual.
        Valores positivos aumentam o custo; negativos reduzem.
      </p>

      <form method="get" class="border rounded p-3 p-md-4 bg-white">
        <input type="hidden" name="url" value="simulate-inventory-cost/<?= $itemId ?>">
        <div class="row g-3 align-items-end">
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label mb-1">% Materiais</label>
            <input type="text" class="form-control" name="material_adjust_pct" value="<?= $fmtPct((float)($scenario['material_adjust_pct'] ?? 0)) ?>" placeholder="0">
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label mb-1">% Rota</label>
            <input type="text" class="form-control" name="operations_adjust_pct" value="<?= $fmtPct((float)($scenario['operations_adjust_pct'] ?? 0)) ?>" placeholder="0">
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label mb-1">% Global</label>
            <input type="text" class="form-control" name="global_adjust_pct" value="<?= $fmtPct((float)($scenario['global_adjust_pct'] ?? 0)) ?>" placeholder="0">
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <div class="d-flex flex-wrap gap-2">
              <button type="submit" class="btn btn-primary flex-grow-1 flex-sm-grow-0">
                <i class="fa-solid fa-calculator me-1"></i> Simular
              </button>
              <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($baseUrl) ?>">Limpar</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <?php if (is_array($breakdown) && is_array($selectedItem)): ?>
    <div class="row g-4 mb-4">
      <div class="col-12 col-md-4">
        <div class="card h-100 border-primary border-light shadow-sm">
          <div class="card-body p-4">
            <h6 class="text-muted text-uppercase small mb-2">Custo base</h6>
            <div class="fs-3 fw-semibold mb-2">R$ <?= $fmtMoney((float)($breakdown['base_total'] ?? 0)) ?></div>
            <small class="text-muted d-block">
              Materiais: R$ <?= $fmtMoney((float)($breakdown['material_cost'] ?? 0)) ?><br>
              Rota: R$ <?= $fmtMoney((float)($breakdown['operations_cost'] ?? 0)) ?>
            </small>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="card h-100 border-success border-light shadow-sm">
          <div class="card-body p-4">
            <h6 class="text-muted text-uppercase small mb-2">Custo simulado</h6>
            <div class="fs-3 fw-semibold text-success mb-2">R$ <?= $fmtMoney((float)($breakdown['simulated_total'] ?? 0)) ?></div>
            <small class="text-muted d-block">
              Materiais: R$ <?= $fmtMoney((float)($breakdown['simulated_material_cost'] ?? 0)) ?><br>
              Rota: R$ <?= $fmtMoney((float)($breakdown['simulated_operations_cost'] ?? 0)) ?>
            </small>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="card h-100 border-light shadow-sm">
          <div class="card-body p-4">
            <h6 class="text-muted text-uppercase small mb-2">Variação</h6>
            <?php
              $base = (float)($breakdown['base_total'] ?? 0);
              $sim = (float)($breakdown['simulated_total'] ?? 0);
              $diff = $sim - $base;
              $pct = $base > 0 ? (($diff / $base) * 100) : 0;
              $diffClass = $diff >= 0 ? 'text-danger' : 'text-success';
            ?>
            <div class="fs-3 fw-semibold <?= $diffClass ?> mb-2">R$ <?= $fmtMoney($diff) ?></div>
            <small class="<?= $diffClass ?>"><?= $fmtPct($pct) ?>% em relação ao base</small>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 inv-cost-compact-tables">
      <div class="col-12 col-xl-6">
        <div class="card border-light shadow h-100">
          <div class="card-header fw-semibold">Lista de materiais</div>
          <div class="card-body p-0">
            <div class="table-wrap">
              <table class="table table-striped align-middle">
                <thead class="thead-green">
                  <tr>
                    <th class="ps-1 col-w-component">Componente</th>
                    <th class="text-end col-w-qty">Qtd</th>
                    <th class="text-end col-w-cost">C. un.</th>
                    <th class="text-end pe-1 col-w-line">C. linha</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($breakdown['materials'])): ?>
                    <tr>
                      <td colspan="4" class="text-center text-muted py-2">Sem componentes na BOM.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($breakdown['materials'] as $line):
                      $compCode = trim((string)($line['component_code'] ?? ''));
                      $compDesc = trim((string)($line['component_description'] ?? ''));
                      $compFull = trim($compCode . ' ' . $compDesc);
                    ?>
                      <tr>
                        <td class="ps-1" title="<?= htmlspecialchars($compFull) ?>">
                          <span class="cell-component-name"><?= htmlspecialchars($compDesc !== '' ? $compDesc : $compCode) ?></span>
                          <?php if ($compCode !== '' && $compDesc !== ''): ?>
                            <span class="cell-desc-sub"><?= htmlspecialchars($compCode) ?></span>
                          <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap col-num"><?= $fmtMoney((float)($line['effective_qty'] ?? 0)) ?></td>
                        <td class="text-end text-nowrap col-num"><?= $fmtMoney((float)($line['unit_cost'] ?? 0)) ?></td>
                        <td class="text-end pe-1 text-nowrap col-num fw-semibold"><?= $fmtMoney((float)($line['line_cost'] ?? 0)) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-xl-6">
        <div class="card border-light shadow h-100">
          <div class="card-header fw-semibold">Rota de produção</div>
          <div class="card-body p-0">
            <div class="table-wrap">
              <table class="table table-striped align-middle">
                <thead class="thead-green">
                  <tr>
                    <th class="ps-1 col-w-component">Operação</th>
                    <th class="text-end col-w-min">Min</th>
                    <th class="text-end col-w-mo">MO/Máq/En</th>
                    <th class="text-end pe-1 col-w-line">C. linha</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($breakdown['operations'])): ?>
                    <tr>
                      <td colspan="4" class="text-center text-muted py-2">Sem operações na rota.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($breakdown['operations'] as $line):
                      $opCode = trim((string)($line['operation_code'] ?? ''));
                      $opName = trim((string)($line['operation_name'] ?? ''));
                      $notes = trim((string)($line['notes'] ?? ''));
                      $opTitle = $opName !== '' ? $opName : $opCode;
                      $resource = '';
                      if (preg_match('/Recurso SAP:\s*(.+)$/i', $notes, $m)) {
                          $resource = trim($m[1]);
                      }
                      $opFull = trim($opTitle . ($resource !== '' ? ' — ' . $resource : '') . ($opCode !== '' && $opCode !== $opTitle ? ' (' . $opCode . ')' : ''));
                    ?>
                      <tr>
                        <td class="ps-1" title="<?= htmlspecialchars($opFull) ?>">
                          <span class="cell-op-title"><?= htmlspecialchars($opTitle) ?></span>
                          <?php if ($resource !== ''): ?>
                            <span class="cell-desc-sub"><?= htmlspecialchars($resource) ?></span>
                          <?php elseif ($notes !== ''): ?>
                            <span class="cell-desc-sub"><?= htmlspecialchars($notes) ?></span>
                          <?php elseif ($opCode !== '' && $opCode !== $opTitle): ?>
                            <span class="cell-desc-sub"><?= htmlspecialchars($opCode) ?></span>
                          <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap col-num"><?= $fmtMoney((float)($line['time_minutes'] ?? 0)) ?></td>
                        <td class="text-end text-nowrap col-num col-w-mo">
                          <?= $fmtMoney((float)($line['labor_cost_per_min'] ?? 0)) ?>/<?= $fmtMoney((float)($line['machine_cost_per_min'] ?? 0)) ?>/<?= $fmtMoney((float)($line['energy_cost_per_min'] ?? 0)) ?>
                        </td>
                        <td class="text-end pe-1 text-nowrap col-num fw-semibold"><?= $fmtMoney((float)($line['line_cost'] ?? 0)) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

</div>
