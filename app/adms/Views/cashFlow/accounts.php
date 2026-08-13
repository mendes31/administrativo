<?php
/** @var array $this->data */
$syncUrl = htmlspecialchars($this->data['sync_url'] ?? '', ENT_QUOTES, 'UTF-8');
$types = [
    'BANK' => 'Banco / conta corrente',
    'CASH' => 'Caixa',
    'INVESTMENT' => 'Aplicação',
    'TRANSIT' => 'Trânsito',
    'OTHER' => 'Outra',
];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Contas financeiras SAP</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>fin-cash-flow-dashboard" class="text-decoration-none">Fluxo de Caixa SAP</a></li>
            <li class="breadcrumb-item">Contas</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Parametrização das contas usadas no fluxo de caixa</span>
            <span class="ms-auto d-flex gap-2">
                <?php if (in_array('FinCashFlowDashboard', $this->data['buttonPermission'] ?? [], true)): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>fin-cash-flow-dashboard" class="btn btn-info btn-sm"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
                <?php endif; ?>
                <?php if (in_array('FinCashFlowDashboardSync', $this->data['buttonPermission'] ?? [], true)): ?>
                    <button type="button" class="btn btn-success btn-sm" id="btnSyncAccounts"><i class="fa-solid fa-rotate"></i> Importar contas do SAP</button>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <p class="text-muted small">O sync importa DSC1 (bancos) e contas financeiras do SAP. Depois classifique aplicações, exclusões e limites. Contas editadas manualmente não têm o tipo sobrescrito na próxima importação.</p>
            <div id="syncMsg" class="alert d-none" role="status"></div>

            <?php if (!empty($this->data['accounts'])): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Conta SAP</th>
                            <th>Descrição</th>
                            <th>Banco</th>
                            <th>Tipo</th>
                            <th>Limite</th>
                            <th>Fluxo</th>
                            <th>Disponível</th>
                            <th>Ativa</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->data['accounts'] as $acc): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $acc['sap_gl_account']) ?></td>
                            <td><?= htmlspecialchars((string) $acc['description']) ?></td>
                            <td><?= htmlspecialchars(trim(($acc['bank_code'] ?? '') . ' ' . ($acc['bank_account'] ?? ''))) ?></td>
                            <td><?= htmlspecialchars($types[$acc['account_type']] ?? (string) $acc['account_type']) ?></td>
                            <td class="text-end">R$ <?= number_format((float) $acc['credit_limit'], 2, ',', '.') ?></td>
                            <td><?= !empty($acc['include_in_cash_flow']) ? 'Sim' : 'Não' ?></td>
                            <td><?= !empty($acc['include_in_availability']) ? 'Sim' : 'Não' ?></td>
                            <td><?= !empty($acc['active']) ? 'Sim' : 'Não' ?></td>
                            <td class="text-center">
                                <?php if (in_array('UpdateFinCashAccount', $this->data['buttonPermission'] ?? [], true)): ?>
                                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-fin-cash-account/<?= (int) $acc['id'] ?>" class="btn btn-warning btn-sm" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p>Nenhuma conta cadastrada. Importe as contas do SAP para começar.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
(function () {
  const btn = document.getElementById('btnSyncAccounts');
  const msg = document.getElementById('syncMsg');
  if (!btn) return;
  btn.addEventListener('click', async function () {
    btn.disabled = true;
    msg.className = 'alert alert-info';
    msg.textContent = 'Importando contas do SAP…';
    try {
      const res = await fetch(<?= json_encode($syncUrl) ?>, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ mode: 'accounts' })
      });
      const json = await res.json();
      if (!res.ok || json.success === false) throw new Error(json.error || json.message || 'Falha');
      msg.className = 'alert alert-success';
      msg.textContent = json.message || 'Contas importadas.';
      setTimeout(function () { location.reload(); }, 800);
    } catch (err) {
      msg.className = 'alert alert-danger';
      msg.textContent = err.message || String(err);
      btn.disabled = false;
    }
  });
})();
</script>
