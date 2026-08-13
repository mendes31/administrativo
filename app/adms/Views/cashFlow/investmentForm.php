<?php
use App\adms\Helpers\CSRFHelper;
$form = $this->data['form'] ?? [];
$isEdit = !empty($this->data['is_edit']) || !empty($form['id']);
$csrfName = $isEdit ? 'form_update_fin_cash_investment' : 'form_create_fin_cash_investment';
$typeLabels = [
    'APPLICATION' => 'Aplicação',
    'REDEMPTION' => 'Resgate',
    'YIELD' => 'Rendimento',
];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><?= $isEdit ? 'Editar' : 'Cadastrar' ?> aplicação financeira</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-fin-cash-investments" class="text-decoration-none">Aplicações</a></li>
            <li class="breadcrumb-item"><?= $isEdit ? 'Editar' : 'Cadastrar' ?></li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span><?= $isEdit ? 'Editar lançamento' : 'Novo lançamento' ?></span>
            <span class="ms-auto">
                <?php if (in_array('ListFinCashInvestments', $this->data['buttonPermission'] ?? [], true)): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-fin-cash-investments" class="btn btn-info btn-sm"><i class="fa-solid fa-list"></i> Listar</a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken($csrfName); ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int) ($form['id'] ?? 0) ?>">
                <?php endif; ?>

                <div class="col-md-4">
                    <label for="movement_type" class="form-label">Tipo</label>
                    <select name="movement_type" id="movement_type" class="form-select" required>
                        <option value="">Selecione</option>
                        <?php foreach ($typeLabels as $k => $v): ?>
                            <option value="<?= $k ?>" <?= (($form['movement_type'] ?? '') === $k) ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="category" class="form-label">Categoria</label>
                    <select name="category" id="category" class="form-select">
                        <option value="STANDARD" <?= (($form['category'] ?? 'STANDARD') === 'STANDARD') ? 'selected' : '' ?>>Padrão</option>
                        <option value="GUARANTEE" <?= (($form['category'] ?? '') === 'GUARANTEE') ? 'selected' : '' ?>>Garantia</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="movement_date" class="form-label">Data</label>
                    <input type="date" name="movement_date" id="movement_date" class="form-control" required value="<?= htmlspecialchars((string) ($form['movement_date'] ?? date('Y-m-d'))) ?>">
                </div>
                <div class="col-md-6">
                    <label for="bank_label" class="form-label">Banco / conta</label>
                    <input type="text" name="bank_label" id="bank_label" class="form-control" list="bankSuggestions" required maxlength="120" placeholder="Ex.: Banco ABC, Safra (garantia)" value="<?= htmlspecialchars((string) ($form['bank_label'] ?? '')) ?>">
                    <datalist id="bankSuggestions">
                        <?php foreach (($this->data['bank_suggestions'] ?? []) as $label): ?>
                            <option value="<?= htmlspecialchars((string) $label) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-3">
                    <label for="amount" class="form-label">Valor (R$)</label>
                    <input type="text" name="amount" id="amount" class="form-control" required placeholder="0,00" value="<?= htmlspecialchars((string) ($form['amount'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label for="account_id" class="form-label">Conta SAP (opcional)</label>
                    <select name="account_id" id="account_id" class="form-select">
                        <option value="">Não vincular</option>
                        <?php foreach (($this->data['account_options'] ?? []) as $acc): ?>
                            <option value="<?= (int) $acc['id'] ?>" <?= ((int) ($form['account_id'] ?? 0) === (int) $acc['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars(($acc['description'] ?: $acc['sap_gl_account']) . ' (' . $acc['sap_gl_account'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label for="description" class="form-label">Descrição</label>
                    <input type="text" name="description" id="description" class="form-control" maxlength="255" value="<?= htmlspecialchars((string) ($form['description'] ?? '')) ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm"><?= $isEdit ? 'Salvar' : 'Cadastrar' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
