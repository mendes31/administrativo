<?php if (!isset($this)) { exit; } ?>
<?php
use App\adms\Helpers\CSRFHelper;

$periodId = (int)($period['id'] ?? 0);
$rhLines = $this->data['rh_lines'] ?? [];
$rhImports = $this->data['rh_imports'] ?? [];
$rhPreview = $this->data['rh_preview'] ?? null;
$rhSimPreview = $this->data['rh_simulation_preview'] ?? null;
$criterionLabels = $this->data['criterion_labels'] ?? [];
$isClosed = (string)($period['status'] ?? '') === 'closed';
$canImport = in_array('ImportInvCostRhDistribution', $this->data['buttonPermission'] ?? [], true)
    || in_array('ViewInvCostPeriod', $this->data['buttonPermission'] ?? [], true);
$canSave = in_array('SaveInvCostRhDistribution', $this->data['buttonPermission'] ?? [], true)
    || in_array('ViewInvCostPeriod', $this->data['buttonPermission'] ?? [], true);
$canTemplate = in_array('DownloadInvCostRhDistributionTemplate', $this->data['buttonPermission'] ?? [], true);
$simPct = $period['rh_simulation_increase_pct'] ?? null;
$fmtMoney = static fn(float $v): string => number_format($v, 2, ',', '.');
$fmtPct = static fn(float $v): string => number_format($v, 4, ',', '.') . '%';
?>
<p class="small text-muted mb-3">
  Cadastro da <strong>Pasta 9 — Distribuição RH</strong>: informe área e valor (salário/folha de referência).
  O sistema calcula os <strong>%</strong> e, no CFIX, reparte o pool <strong>Gastos com pessoal</strong> do DRE
  (sem duplicar valores). Cada área rateia pelo <strong>critério</strong> indicado (como na planilha, linhas 2336–2356).
</p>
<p class="small text-muted mb-3">
  O card <strong>Resumo CFIX</strong> (topo da página) refere-se às <strong>contas do DRE</strong> (aba Despesas), não a estas áreas.
  <em>Com critério, sem destino</em> = conta com critério, mas nenhum SKU absorveu (arredondamento ou driver zerado) — não significa área RH sem critério.
</p>

<?php if (is_array($rhPreview) && (float)($rhPreview['personnel_pool_total'] ?? 0) > 0): ?>
<div class="alert alert-info py-2 small mb-3">
  Pool de pessoal no DRE: <strong>R$ <?= $fmtMoney((float)$rhPreview['personnel_pool_total']) ?></strong>
  <?php if ($rhLines !== []): ?>
    · Distribuição cadastrada soma <strong>R$ <?= $fmtMoney((float)($rhPreview['distribution_total'] ?? 0)) ?></strong>
    (base para % — não soma ao DRE).
  <?php endif; ?>
</div>
<?php elseif ($rhLines === []): ?>
<div class="alert alert-warning py-2 small mb-3">
  Importe ou cadastre a distribuição. Enquanto vazio, as contas de folha do DRE continuam rateando pelo critério definido em cada linha.
</div>
<?php endif; ?>

<?php if ($canImport && !$isClosed): ?>
<div class="card border-light shadow mb-4">
  <div class="card-header hstack gap-2 flex-wrap">
    <span class="fw-semibold">Importar CSV</span>
    <?php if ($canTemplate): ?>
      <a href="<?= $_ENV['URL_ADM'] ?>download-inventory-cost-rh-distribution-template" class="btn btn-sm btn-outline-secondary ms-auto">
        <i class="fa-solid fa-download me-1"></i> Baixar template
      </a>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <p class="small text-muted mb-3">
      Formato: <code>area;valor;criterio</code> — critério (1–8) opcional; se omitido, usa o padrão da área.
      Salve o arquivo como <strong>CSV UTF-8 (ponto e vírgula)</strong> para preservar acentos (Produção, Manutenção, etc.).
    </p>
    <form method="post" enctype="multipart/form-data" action="<?= $_ENV['URL_ADM'] ?>view-inventory-cost-period/<?= $periodId ?>?tab=rh" class="row g-3 align-items-end">
      <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_import_inv_cost_rh') ?>">
      <div class="col-12 col-md-6">
        <label class="form-label">Arquivo CSV</label>
        <input type="file" class="form-control" name="rh_file" accept=".csv,.txt" required>
      </div>
      <div class="col-12 col-md-4">
        <div class="form-check mt-4">
          <input class="form-check-input" type="checkbox" name="replace_previous_rh" value="1" id="replace_previous_rh" checked>
          <label class="form-check-label" for="replace_previous_rh">Substituir distribuição anterior</label>
        </div>
      </div>
      <div class="col-12 col-md-2">
        <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-file-import me-1"></i> Importar</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="card border-light shadow mb-4">
  <div class="card-header fw-semibold">Áreas e percentuais</div>
  <div class="card-body p-0">
    <form method="post" action="<?= $_ENV['URL_ADM'] ?>view-inventory-cost-period/<?= $periodId ?>?tab=rh">
      <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_save_inv_cost_rh') ?>">
      <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle" id="rh-lines-table">
          <thead class="table-light">
            <tr>
              <th class="ps-3" style="min-width:220px">Área</th>
              <th class="text-end">Valor (R$)</th>
              <th class="text-end">% área</th>
              <th style="min-width:200px">Critério rateio</th>
              <th class="text-end pe-3">Fatia DRE pessoal</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $displayLines = $rhLines !== [] ? $rhLines : [['area_name' => '', 'amount' => '', 'share_pct' => 0, 'criterion' => 2]];
            $sliceMap = [];
            if (is_array($rhPreview)) {
                foreach ($rhPreview['slices'] ?? [] as $sl) {
                    $sliceMap[(string)($sl['area_name'] ?? '')] = $sl;
                }
            }
            foreach ($displayLines as $i => $line):
                $area = (string)($line['area_name'] ?? '');
                $slice = $sliceMap[$area] ?? null;
            ?>
            <tr>
              <td class="ps-3">
                <input type="text" class="form-control form-control-sm" name="rh_lines[<?= $i ?>][area_name]"
                  value="<?= htmlspecialchars($area) ?>" title="<?= htmlspecialchars($area) ?>"
                  <?= ($isClosed || !$canSave) ? 'readonly' : '' ?>>
              </td>
              <td>
                <input type="text" class="form-control form-control-sm text-end" name="rh_lines[<?= $i ?>][amount]"
                  value="<?= $line['amount'] !== '' && $line['amount'] !== null ? number_format((float)$line['amount'], 2, ',', '.') : '' ?>"
                  <?= ($isClosed || !$canSave) ? 'readonly' : '' ?>>
              </td>
              <td class="text-end text-muted"><?= (float)($line['share_pct'] ?? 0) > 0 ? $fmtPct((float)$line['share_pct']) : '—' ?></td>
              <td>
                <select class="form-select form-select-sm" name="rh_lines[<?= $i ?>][criterion]" <?= ($isClosed || !$canSave) ? 'disabled' : '' ?>>
                  <?php foreach ($criterionLabels as $num => $label): ?>
                    <option value="<?= $num ?>" <?= (int)($line['criterion'] ?? 2) === $num ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td class="text-end pe-3 small">
                <?= $slice !== null && (float)($slice['slice_amount'] ?? 0) > 0 ? 'R$ ' . $fmtMoney((float)$slice['slice_amount']) : '—' ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($canSave && !$isClosed): ?>
      <div class="p-3 border-top hstack gap-2 flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="rh-add-row"><i class="fa-solid fa-plus me-1"></i> Área</button>
        <button type="submit" class="btn btn-success ms-auto"><i class="fa-solid fa-floppy-disk me-1"></i> Salvar distribuição</button>
      </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="card border-light shadow mb-4">
  <div class="card-header fw-semibold">Simulação (+ % folha)</div>
  <div class="card-body">
    <p class="small text-muted mb-3">
      Informe um aumento percentual (ex.: <strong>10</strong> para +10% no pool de pessoal) para ver o impacto no CFIX
      <strong>sem alterar</strong> o custeio oficial nem o snapshot gravado.
    </p>
    <form method="post" action="<?= $_ENV['URL_ADM'] ?>view-inventory-cost-period/<?= $periodId ?>?tab=rh" class="row g-3 align-items-end">
      <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_rh_simulation_inv_cost') ?>">
      <div class="col-12 col-md-4">
        <label class="form-label">Aumento simulado (%)</label>
        <input type="text" class="form-control" name="rh_simulation_increase_pct"
          value="<?= $simPct !== null && $simPct !== '' ? number_format((float)$simPct, 2, ',', '.') : '' ?>"
          placeholder="Ex.: 10" <?= ($isClosed || !$canSave) ? 'readonly' : '' ?>>
      </div>
      <div class="col-auto">
        <?php if ($canSave && !$isClosed): ?>
          <button type="submit" class="btn btn-outline-primary">Aplicar simulação</button>
        <?php endif; ?>
      </div>
    </form>
    <?php if (is_array($rhSimPreview) && (float)($rhSimPreview['total_cfix_allocated'] ?? 0) > 0): ?>
      <hr>
      <p class="small mb-1">CFIX rateado com simulação RH (+<?= number_format((float)$simPct, 2, ',', '.') ?>%):</p>
      <p class="mb-0"><strong>R$ <?= $fmtMoney((float)($rhSimPreview['total_cfix_allocated'] ?? 0)) ?></strong>
        <span class="text-muted small">(oficial: R$ <?= $fmtMoney((float)($this->data['allocation_summary']['total_cfix_allocated'] ?? 0)) ?>)</span>
      </p>
    <?php endif; ?>
  </div>
</div>

<?php if ($rhImports !== []): ?>
<div class="card border-light shadow mb-4">
  <div class="card-header fw-semibold">Histórico de importações</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead class="table-light">
          <tr><th class="ps-3">Arquivo</th><th class="text-end">Linhas</th><th class="pe-3">Data</th></tr>
        </thead>
        <tbody>
          <?php foreach ($rhImports as $imp): ?>
            <tr>
              <td class="ps-3"><?= htmlspecialchars((string)($imp['filename'] ?? '—')) ?></td>
              <td class="text-end"><?= (int)($imp['rows_imported'] ?? 0) ?></td>
              <td class="pe-3 small text-muted"><?= !empty($imp['imported_at']) ? date('d/m/Y H:i', strtotime((string)$imp['imported_at'])) : '—' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($canSave && !$isClosed): ?>
<script>
(function () {
  const table = document.getElementById('rh-lines-table');
  if (!table) return;
  const tbody = table.querySelector('tbody');
  const btn = document.getElementById('rh-add-row');
  if (!btn || !tbody) return;
  btn.addEventListener('click', function () {
    const i = tbody.querySelectorAll('tr').length;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="ps-3"><input type="text" class="form-control form-control-sm" name="rh_lines[${i}][area_name]"></td>
      <td><input type="text" class="form-control form-control-sm text-end" name="rh_lines[${i}][amount]"></td>
      <td class="text-end text-muted">—</td>
      <td><select class="form-select form-select-sm" name="rh_lines[${i}][criterion]">
        <?php foreach ($criterionLabels as $num => $label): ?>
        <option value="<?= $num ?>"><?= htmlspecialchars($label, ENT_QUOTES) ?></option>
        <?php endforeach; ?>
      </select></td>
      <td class="text-end pe-3">—</td>`;
    tbody.appendChild(tr);
  });
})();
</script>
<?php endif; ?>
