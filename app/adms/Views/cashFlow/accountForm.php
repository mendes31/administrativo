<?php
use App\adms\Helpers\CSRFHelper;
$form = $this->data['form'] ?? [];
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
        <h2 class="mt-3">Conta financeira SAP</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-fin-cash-accounts" class="text-decoration-none">Contas financeiras</a></li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Editar</span>
            <span class="ms-auto">
                <?php if (in_array('ListFinCashAccounts', $this->data['buttonPermission'] ?? [], true)): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-fin-cash-accounts" class="btn btn-info btn-sm"><i class="fa-solid fa-list"></i> Listar</a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_fin_cash_account'); ?>">
                <input type="hidden" name="id" value="<?= (int) ($form['id'] ?? 0) ?>">
                <div class="col-md-4">
                    <label class="form-label">Conta SAP</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars((string) ($form['sap_gl_account'] ?? '')) ?>" disabled>
                </div>
                <div class="col-md-8">
                    <label for="description" class="form-label">Descrição</label>
                    <input type="text" name="description" id="description" class="form-control" maxlength="255" value="<?= htmlspecialchars((string) ($form['description'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label for="account_type" class="form-label">Tipo</label>
                    <select name="account_type" id="account_type" class="form-select">
                        <?php foreach ($types as $k => $label): ?>
                            <option value="<?= $k ?>" <?= (($form['account_type'] ?? '') === $k) ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="credit_limit" class="form-label">Limite bancário (R$)</label>
                    <input type="text" name="credit_limit" id="credit_limit" class="form-control" value="<?= htmlspecialchars((string) ($form['credit_limit'] ?? '0')) ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="active" id="active" value="1" <?= !empty($form['active']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="active">Ativa</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="include_in_cash_flow" id="include_in_cash_flow" value="1" <?= !empty($form['include_in_cash_flow']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="include_in_cash_flow">No fluxo</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="include_in_availability" id="include_in_availability" value="1" <?= !empty($form['include_in_availability']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="include_in_availability">Na disponibilidade</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>
