<?php if (!isset($this)) { exit; } ?>
<?php
use App\adms\Helpers\CSRFHelper;

$period = $this->data['period'] ?? [];
$periodId = (int)($period['id'] ?? 0);
$expensePools = $this->data['expense_pools'] ?? [];
$totalExpense = (float)($this->data['total_expense'] ?? 0);
$dreImports = $this->data['dre_imports'] ?? [];
$allocationSummary = $this->data['allocation_summary'] ?? [];
$criterionLabels = $this->data['criterion_labels'] ?? [];
$energySplitPreview = $this->data['energy_split_preview'] ?? null;
$energyPendingTotal = (float)($this->data['energy_pending_total'] ?? 0);
$energyDirectKwhComputed = (float)($this->data['energy_direct_kwh_computed'] ?? 0);
$isClosed = (string)($period['status'] ?? '') === 'closed';
$canEdit = in_array('UpdateInvCostPeriod', $this->data['buttonPermission'] ?? [], true);
$canImport = in_array('ImportInvCostDre', $this->data['buttonPermission'] ?? [], true)
  || in_array('ViewInvCostPeriod', $this->data['buttonPermission'] ?? [], true);
$canSaveRules = in_array('SaveInvCostAllocationRules', $this->data['buttonPermission'] ?? [], true)
  || in_array('ViewInvCostPeriod', $this->data['buttonPermission'] ?? [], true);
$canSavePeriodItems = in_array('SaveInvCostPeriodItems', $this->data['buttonPermission'] ?? [], true)
  || in_array('ViewInvCostPeriod', $this->data['buttonPermission'] ?? [], true);
$productionItems = $this->data['production_items'] ?? [];
$productionItemsCount = (int)($this->data['production_items_all_count'] ?? count($productionItems));
$snapshotComputedAt = $this->data['snapshot_computed_at'] ?? null;
$snapshotRowCount = (int)($this->data['snapshot_row_count'] ?? 0);
$canRecalculateSnapshots = (bool)($this->data['can_recalculate_snapshots'] ?? false);
$productionItemsFilteredCount = count($productionItems);
$skuFilter = trim((string)($this->data['sku_filter'] ?? ''));
$activeTab = (string)($_GET['tab'] ?? 'despesas');
if (!in_array($activeTab, ['despesas', 'skus', 'resultados'], true)) {
    $activeTab = 'despesas';
}
$tabDespesasActive = $activeTab === 'despesas';
$tabSkusActive = $activeTab === 'skus';
$tabResultadosActive = $activeTab === 'resultados';
$fmtMoney = static fn(float $v): string => number_format($v, 2, ',', '.');
?>
<div class="container-fluid px-4 pb-4">
  <div class="mb-1 hstack gap-2 flex-wrap">
    <h2 class="mt-3 mb-0"><?= htmlspecialchars((string)($period['name'] ?? 'Período')) ?></h2>
    <ol class="breadcrumb mb-3 mt-3 ms-auto">
      <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>list-inventory-cost-periods">Períodos</a></li>
      <li class="breadcrumb-item active">Detalhe</li>
    </ol>
  </div>

  <?php include './app/adms/Views/partials/alerts.php'; ?>

  <div class="row g-3 mb-4">
    <div class="col-12 col-lg-8">
      <div class="card border-light shadow h-100">
        <div class="card-header hstack gap-2">
          <span class="fw-semibold">Parâmetros do período</span>
          <?php if ($canEdit): ?>
            <a href="<?= $_ENV['URL_ADM'] ?>update-inventory-cost-period/<?= $periodId ?>" class="btn btn-sm btn-outline-primary ms-auto">Editar</a>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <div class="row g-2 small">
            <div class="col-sm-6"><span class="text-muted">Intervalo:</span>
              <strong><?= !empty($period['date_from']) ? date('d/m/Y', strtotime((string)$period['date_from'])) : '—' ?></strong>
              – <strong><?= !empty($period['date_to']) ? date('d/m/Y', strtotime((string)$period['date_to'])) : '—' ?></strong>
            </div>
            <div class="col-sm-3"><span class="text-muted">Status:</span>
              <span class="badge bg-light text-dark border"><?= htmlspecialchars((string)($period['status'] ?? 'draft')) ?></span>
              <?php if ($isClosed): ?><span class="badge bg-secondary ms-1">histórico preservado</span><?php endif; ?>
            </div>
            <div class="col-sm-3"><span class="text-muted">Tarifa kWh:</span>
              <strong><?= $period['kwh_tariff'] !== null ? number_format((float)$period['kwh_tariff'], 4, ',', '.') : '—' ?></strong>
            </div>
            <?php if (!empty($period['energy_auto_split'])): ?>
            <div class="col-sm-3"><span class="text-muted">Energia auto-split:</span> <span class="badge bg-light text-dark border">Sim</span></div>
            <?php endif; ?>
            <?php if (!empty($period['notes'])): ?>
              <div class="col-12"><span class="text-muted">Obs.:</span> <?= nl2br(htmlspecialchars((string)$period['notes'])) ?></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-4">
      <div class="card border-light shadow h-100">
        <div class="card-header fw-semibold">Resumo CFIX</div>
        <div class="card-body">
          <div class="d-flex justify-content-between mb-2"><span class="text-muted">Despesas importadas</span><strong>R$ <?= $fmtMoney($totalExpense) ?></strong></div>
          <div class="d-flex justify-content-between mb-2"><span class="text-muted">CFIX rateado</span><strong>R$ <?= $fmtMoney((float)($allocationSummary['total_cfix_allocated'] ?? 0)) ?></strong></div>
          <?php
            $poolsWithoutCrit = (float)($allocationSummary['pools_without_criterion'] ?? 0);
            $unallocatedNoRecipient = (float)($allocationSummary['unallocated_without_recipient'] ?? 0);
            $unallocatedTotal = (float)($allocationSummary['unallocated_expense'] ?? 0);
          ?>
          <?php if ($poolsWithoutCrit > 0.005): ?>
          <div class="d-flex justify-content-between mb-2"><span class="text-muted" title="Contas do DRE sem critério 1–8 selecionado">Sem critério definido</span><span class="text-warning">R$ <?= $fmtMoney($poolsWithoutCrit) ?></span></div>
          <?php endif; ?>
          <?php if ($unallocatedNoRecipient > 0.005): ?>
          <div class="d-flex justify-content-between mb-2"><span class="text-muted" title="Critério informado, mas nenhum SKU no período recebeu rateio (driver ou % zerado)">Com critério, sem destino</span><span class="text-warning">R$ <?= $fmtMoney($unallocatedNoRecipient) ?></span></div>
          <?php endif; ?>
          <?php if ($unallocatedTotal > 0.005 && $poolsWithoutCrit <= 0.005 && $unallocatedNoRecipient <= 0.005): ?>
          <div class="d-flex justify-content-between"><span class="text-muted">Não rateado</span><span class="text-warning">R$ <?= $fmtMoney($unallocatedTotal) ?></span></div>
          <?php elseif ($unallocatedTotal <= 0.005): ?>
          <div class="d-flex justify-content-between"><span class="text-muted">Pendente de rateio</span><span class="text-success">R$ 0,00</span></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <?php
  $tabBaseUrl = $_ENV['URL_ADM'] . 'view-inventory-cost-period/' . $periodId;
  $tabQuery = $skuFilter !== '' ? ('&sku_filter=' . rawurlencode($skuFilter)) : '';
  ?>
  <ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
      <a class="nav-link<?= $tabDespesasActive ? ' active' : '' ?>" id="tab-despesas"
        href="<?= $tabBaseUrl ?>?tab=despesas<?= $tabQuery ?>" role="tab"
        aria-controls="pane-despesas" aria-selected="<?= $tabDespesasActive ? 'true' : 'false' ?>">
        Despesas e critérios de rateio
      </a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link<?= $tabSkusActive ? ' active' : '' ?>" id="tab-skus"
        href="<?= $tabBaseUrl ?>?tab=skus<?= $tabQuery ?>" role="tab"
        aria-controls="pane-skus" aria-selected="<?= $tabSkusActive ? 'true' : 'false' ?>">
        SKUs produzidos no período
        <?php if ($productionItemsCount > 0): ?>
          <span class="badge bg-secondary ms-1"><?= $skuFilter !== '' ? $productionItemsFilteredCount . '/' . $productionItemsCount : $productionItemsCount ?></span>
        <?php endif; ?>
      </a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link<?= $tabResultadosActive ? ' active' : '' ?>" id="tab-resultados"
        href="<?= $tabBaseUrl ?>?tab=resultados<?= $tabQuery ?>" role="tab"
        aria-controls="pane-resultados" aria-selected="<?= $tabResultadosActive ? 'true' : 'false' ?>">
        Resultados por SKU
        <?php
        $skuResultsCount = (int)($this->data['sku_results_all_count'] ?? 0);
        if ($skuResultsCount > 0): ?>
          <span class="badge bg-secondary ms-1"><?= $skuFilter !== '' ? count($this->data['sku_results'] ?? []) . '/' . $skuResultsCount : $skuResultsCount ?></span>
        <?php endif; ?>
      </a>
    </li>
  </ul>

  <?php if (in_array($activeTab, ['skus', 'resultados'], true)): ?>
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3 small text-muted">
      <?php if (!empty($snapshotComputedAt)): ?>
        <span>Snapshot: <strong><?= (int)$snapshotRowCount ?> SKU(s)</strong> · calculado em <?= date('d/m/Y H:i', strtotime((string)$snapshotComputedAt)) ?></span>
      <?php else: ?>
        <span>Snapshot ainda não calculado para este período.</span>
      <?php endif; ?>
      <?php if ($canRecalculateSnapshots): ?>
        <form method="post" action="<?= $_ENV['URL_ADM'] ?>view-inventory-cost-period/<?= $periodId ?>" class="ms-auto">
          <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_recalculate_inv_cost_snapshots') ?>">
          <input type="hidden" name="recalculate_snapshots" value="1">
          <input type="hidden" name="return_tab" value="<?= htmlspecialchars($activeTab) ?>">
          <?php if ($skuFilter !== ''): ?>
            <input type="hidden" name="sku_filter" value="<?= htmlspecialchars($skuFilter) ?>">
          <?php endif; ?>
          <button type="submit" class="btn btn-sm btn-outline-primary">
            <i class="fa-solid fa-rotate me-1"></i> Recalcular SKUs e resultados
          </button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="tab-content">
    <div class="tab-pane fade<?= $tabDespesasActive ? ' show active' : '' ?>" id="pane-despesas" role="tabpanel" aria-labelledby="tab-despesas" tabindex="0">

  <div class="card border-light shadow mb-4">
    <div class="card-header hstack gap-2 flex-wrap">
      <span class="fw-semibold">Redistribuição — Energia Elétrica</span>
      <?php if (!$isClosed && $energyPendingTotal > 0): ?>
        <form method="post" action="<?= $_ENV['URL_ADM'] ?>view-inventory-cost-period/<?= $periodId ?>" class="ms-auto">
          <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_apply_inv_cost_energy_split') ?>">
          <input type="hidden" name="apply_energy_split" value="1">
          <button type="submit" class="btn btn-sm btn-outline-primary">
            <i class="fa-solid fa-bolt me-1"></i> Aplicar redistribuição
          </button>
        </form>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <?php if ($energyPendingTotal <= 0): ?>
        <p class="text-muted mb-0 small">Nenhuma conta <strong>29</strong> aguardando fatia (já redistribuída ou ainda não importada). kWh direto calculado do cadastro: <strong><?= number_format($energyDirectKwhComputed, 2, ',', '.') ?></strong> kWh.</p>
      <?php elseif (is_array($energySplitPreview)): ?>
        <p class="small text-muted mb-3">
          Base: R$ <?= $fmtMoney($energyPendingTotal) ?> (conta 29) ·
          Pesos: <?= htmlspecialchars((string)($energySplitPreview['weight_source'] ?? '')) ?>
          <?php if ($energyDirectKwhComputed > 0): ?>
            · kWh direto (cadastro): <?= number_format($energyDirectKwhComputed, 2, ',', '.') ?>
          <?php endif; ?>
        </p>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead class="table-light">
              <tr>
                <th>Fatia</th>
                <th class="text-end">Valor (R$)</th>
                <th class="text-end">%</th>
                <th>Critério</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Direto (linha produtiva)</td>
                <td class="text-end"><?= $fmtMoney((float)($energySplitPreview['direto']['amount'] ?? 0)) ?></td>
                <td class="text-end"><?= number_format((float)($energySplitPreview['direto']['pct'] ?? 0), 2, ',', '.') ?>%</td>
                <td>7 — Energia (kWh)</td>
              </tr>
              <tr>
                <td>Produção (área comum)</td>
                <td class="text-end"><?= $fmtMoney((float)($energySplitPreview['comum']['amount'] ?? 0)) ?></td>
                <td class="text-end"><?= number_format((float)($energySplitPreview['comum']['pct'] ?? 0), 2, ',', '.') ?>%</td>
                <td>3 — Horas-máquina (HM)</td>
              </tr>
              <tr>
                <td>HVAC</td>
                <td class="text-end"><?= $fmtMoney((float)($energySplitPreview['hvac']['amount'] ?? 0)) ?></td>
                <td class="text-end"><?= number_format((float)($energySplitPreview['hvac']['pct'] ?? 0), 2, ',', '.') ?>%</td>
                <td>8 — HVAC (CM/Prob/Outro)</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="small text-muted mt-3 mb-0">
          Edite os kWh em <a href="<?= $_ENV['URL_ADM'] ?>update-inventory-cost-period/<?= $periodId ?>">Editar período</a>.
          Com auto-split ativo, a importação do DRE aplica esta divisão e vincula os critérios automaticamente.
        </p>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($canImport && !$isClosed): ?>
    <?php $canDownloadTemplate = in_array('DownloadInvCostDreTemplate', $this->data['buttonPermission'] ?? [], true); ?>
    <div class="card border-light shadow mb-4">
      <div class="card-header hstack gap-2 flex-wrap">
        <span class="fw-semibold">Importar DRE (CSV)</span>
        <?php if ($canDownloadTemplate): ?>
          <a href="<?= $_ENV['URL_ADM'] ?>download-inventory-cost-dre-template" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="fa-solid fa-download me-1"></i> Baixar template
          </a>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <p class="small text-muted mb-3">
          Exporte do DRE apenas as <strong>linhas de detalhe</strong> de <em>Despesas gerais administrativas</em> (e folha, se aplicável) — não importe receitas, custos de vendas nem totais agregados
          (<em>Despesas operacionais</em>, <em>Resultado líquido</em>, etc.).
          Formato mínimo: <code>codigo;descricao;valor</code> — salve o Excel como <strong>CSV UTF-8</strong> (ponto e vírgula).
          A coluna <code>area</code> é <strong>opcional</strong> (só para conferência; o rateio usa o critério 1–8 em cada linha).
        </p>
        <form method="post" enctype="multipart/form-data" action="<?= $_ENV['URL_ADM'] ?>view-inventory-cost-period/<?= $periodId ?>" class="row g-3 align-items-end">
          <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_import_inv_cost_dre') ?>">
          <div class="col-12 col-md-6">
            <label class="form-label">Arquivo CSV</label>
            <input type="file" class="form-control" name="dre_file" accept=".csv,.txt" required>
          </div>
          <div class="col-12 col-md-4">
            <div class="form-check mt-4">
              <input class="form-check-input" type="checkbox" name="replace_previous" value="1" id="replace_previous" checked>
              <label class="form-check-label" for="replace_previous">Substituir despesas anteriores deste período</label>
              <div class="form-text">Marcado (padrão): apaga tudo e importa de novo. Desmarcado: atualiza cada conta pelo código, sem duplicar linhas.</div>
            </div>
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" name="apply_suggested_criteria" value="1" id="apply_suggested_criteria" checked>
              <label class="form-check-label" for="apply_suggested_criteria">Aplicar mapa de critérios sugeridos</label>
              <div class="form-text">Vincula critérios 1–8 às contas do CSV (exceto conta 29 pai e linhas que já têm critério).</div>
            </div>
          </div>
          <div class="col-12 col-md-2">
            <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-file-import me-1"></i> Importar</button>
          </div>
        </form>
      </div>
    </div>
  <?php elseif ($isClosed): ?>
    <div class="alert alert-secondary">Período <strong>fechado</strong>: DRE e critérios estão bloqueados para preservar o histórico de custos.</div>
  <?php endif; ?>

  <div class="card border-light shadow mb-4">
    <div class="card-header fw-semibold">Linhas importadas do DRE</div>
    <div class="card-body p-0">
      <?php if ($expensePools === []): ?>
        <p class="text-muted p-4 mb-0">Nenhuma despesa importada. Use a aba <strong>Despesas e critérios de rateio</strong> para carregar o DRE.</p>
      <?php else: ?>
        <form method="post" action="<?= $_ENV['URL_ADM'] ?>view-inventory-cost-period/<?= $periodId ?>">
          <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_save_inv_cost_allocation_rules') ?>">
          <div class="table-responsive">
            <table class="table table-sm table-striped mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th class="ps-3">Conta</th>
                  <th>Descrição</th>
                  <th class="text-end">Valor (R$)</th>
                  <th style="min-width:220px">Critério</th>
                  <th class="text-end pe-3" style="width:100px">Peso %</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($expensePools as $pool): ?>
                  <?php
                    $poolId = (int)$pool['id'];
                    $rule = $pool['rules'][0] ?? null;
                    $selectedCriterion = (int)($rule['criterion'] ?? 0);
                    $weightPct = (float)($rule['weight_pct'] ?? 100);
                  ?>
                  <tr>
                    <td class="ps-3 text-nowrap fw-semibold"><?= htmlspecialchars((string)($pool['account_code'] ?? '')) ?></td>
                    <td title="<?= htmlspecialchars((string)($pool['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($pool['description'] ?? '')) ?></td>
                    <td class="text-end text-nowrap"><?= $fmtMoney((float)($pool['amount'] ?? 0)) ?></td>
                    <td>
                      <select class="form-select form-select-sm" name="rules[<?= $poolId ?>][criterion]" <?= ($isClosed || !$canSaveRules) ? 'disabled' : '' ?>>
                        <option value="">— selecione —</option>
                        <?php foreach ($criterionLabels as $num => $label): ?>
                          <option value="<?= $num ?>" <?= $selectedCriterion === $num ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </td>
                    <td class="text-end pe-3">
                      <input type="text" class="form-control form-control-sm text-end" name="rules[<?= $poolId ?>][weight_pct]"
                        value="<?= number_format($weightPct, 2, ',', '.') ?>" <?= ($isClosed || !$canSaveRules) ? 'disabled' : '' ?>>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php if ($canSaveRules && !$isClosed): ?>
            <div class="p-3 border-top">
              <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk me-1"></i> Salvar critérios</button>
            </div>
          <?php endif; ?>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($dreImports !== []): ?>
    <div class="card border-light shadow mb-4">
      <div class="card-header fw-semibold">Histórico de importações</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-3">Arquivo</th>
                <th class="text-end">Linhas</th>
                <th>Substituiu</th>
                <th class="pe-3">Data</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($dreImports as $imp): ?>
                <tr>
                  <td class="ps-3"><?= htmlspecialchars((string)($imp['filename'] ?? '—')) ?></td>
                  <td class="text-end"><?= (int)($imp['rows_imported'] ?? 0) ?></td>
                  <td><?= !empty($imp['replace_previous']) ? 'Sim' : 'Não' ?></td>
                  <td class="pe-3 small text-muted"><?= !empty($imp['imported_at']) ? date('d/m/Y H:i', strtotime((string)$imp['imported_at'])) : '—' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

    </div><!-- /pane-despesas -->

    <div class="tab-pane fade<?= $tabSkusActive ? ' show active' : '' ?>" id="pane-skus" role="tabpanel" aria-labelledby="tab-skus" tabindex="0">
      <?php
      $scenarioRows = $this->data['scenario_rows'] ?? [];
      $projectItems = $this->data['project_items'] ?? [];
      $canSaveScenario = (bool)($this->data['can_save_scenario'] ?? false);
      require __DIR__ . '/period_scenario_production.php';
      $productionItemsAllCount = $productionItemsCount;
      require __DIR__ . '/period_production_items.php';
      ?>
    </div><!-- /pane-skus -->

    <div class="tab-pane fade<?= $tabResultadosActive ? ' show active' : '' ?>" id="pane-resultados" role="tabpanel" aria-labelledby="tab-resultados" tabindex="0">
      <?php
      $skuResults = $this->data['sku_results'] ?? [];
      $skuResultsAllCount = (int)($this->data['sku_results_all_count'] ?? count($skuResults));
      require __DIR__ . '/period_sku_results.php';
      ?>
    </div><!-- /pane-resultados -->

  </div><!-- /tab-content -->
</div>
