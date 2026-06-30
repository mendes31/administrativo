<?php if (!isset($this)) { exit; } ?>
<?php
use App\adms\Helpers\CSRFHelper;

$rows = $this->data['rows'] ?? [];
$canSave = in_array('SaveInvEnergyClassFactors', $this->data['buttonPermission'] ?? [], true)
    || in_array('ListInvEnergyClassFactors', $this->data['buttonPermission'] ?? [], true);
$fmtMult = static fn(mixed $v): string => number_format((float)$v, 4, ',', '.');
?>
<div class="container-fluid px-4 pb-4">
  <div class="mb-1 hstack gap-2 flex-wrap">
    <h2 class="mt-3 mb-0">Classes HVAC — critério 8</h2>
    <ol class="breadcrumb mb-3 mt-3 ms-auto">
      <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>list-inventory-cost-periods">Períodos</a></li>
      <li class="breadcrumb-item active">Classes HVAC</li>
    </ol>
  </div>

  <?php include './app/adms/Views/partials/alerts.php'; ?>

  <div class="card border-light shadow">
    <div class="card-header fw-semibold">Multiplicadores de rateio (CFIX crit. 8)</div>
    <div class="card-body">
      <p class="small text-muted">
        O <strong>SAP</strong> grava o código da UDF <em>ClasseHvac</em> em cada item: <code>NA</code> (padrão), <code>CM</code> ou <code>PROB</code>.
        <code>NA</code> = não aplicável (multiplicador <strong>0</strong> — fora do rateio crit. 8). Os multiplicadores de CM/PROB calibram aqui, sem alterar o SAP.
      </p>

      <?php if ($rows === []): ?>
        <p class="text-danger mb-0">Tabela não encontrada. Execute a migration <code>20260630140000_create_inv_energy_class_factors</code>.</p>
      <?php elseif ($canSave): ?>
        <form method="post" action="<?= $_ENV['URL_ADM'] ?>save-inventory-energy-class-factors">
          <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_save_inv_energy_class_factors') ?>">
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead class="table-light">
                <tr>
                  <th style="width:8rem">Código</th>
                  <th>Descrição</th>
                  <th style="width:10rem">Multiplicador</th>
                  <th>Observações</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $row): ?>
                  <?php $id = (int)($row['id'] ?? 0); ?>
                  <tr>
                    <td><code><?= htmlspecialchars((string)($row['code'] ?? '')) ?></code></td>
                    <td>
                      <input type="text" class="form-control form-control-sm"
                        name="factors[<?= $id ?>][label]"
                        value="<?= htmlspecialchars((string)($row['label'] ?? '')) ?>"
                        maxlength="120">
                    </td>
                    <td>
                      <input type="text" class="form-control form-control-sm text-end"
                        name="factors[<?= $id ?>][multiplier]"
                        value="<?= $fmtMult($row['multiplier'] ?? 1) ?>"
                        inputmode="decimal" required>
                    </td>
                    <td>
                      <input type="text" class="form-control form-control-sm"
                        name="factors[<?= $id ?>][notes]"
                        value="<?= htmlspecialchars((string)($row['notes'] ?? '')) ?>">
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="hstack gap-2">
            <button type="submit" class="btn btn-primary">Salvar multiplicadores</button>
            <a href="<?= $_ENV['URL_ADM'] ?>list-inventory-cost-periods" class="btn btn-outline-secondary">Voltar aos períodos</a>
          </div>
        </form>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead class="table-light">
              <tr><th>Código</th><th>Descrição</th><th class="text-end">Multiplicador</th><th>Obs.</th></tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $row): ?>
                <tr>
                  <td><code><?= htmlspecialchars((string)($row['code'] ?? '')) ?></code></td>
                  <td><?= htmlspecialchars((string)($row['label'] ?? '')) ?></td>
                  <td class="text-end"><?= $fmtMult($row['multiplier'] ?? 1) ?></td>
                  <td class="small text-muted"><?= htmlspecialchars((string)($row['notes'] ?? '')) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
