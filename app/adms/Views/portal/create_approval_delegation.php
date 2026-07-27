<?php
use App\adms\Helpers\CSRFHelper;
$users = $this->data['users'] ?? [];
$currentUserId = (int) ($this->data['current_user_id'] ?? 0);
$isFull = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Nova Delegação de Aprovação</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>list-approval-delegations" class="text-decoration-none">Delegações</a></li>
            <li class="breadcrumb-item">Nova</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_create_approval_delegation'); ?>">

                <div class="col-md-6">
                    <label class="form-label" for="delegator_user_id">Gestor ausente</label>
                    <?php if ($isFull): ?>
                        <select name="delegator_user_id" id="delegator_user_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= (int) $u['id'] ?>" <?= (int) $u['id'] === $currentUserId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="hidden" name="delegator_user_id" value="<?= $currentUserId ?>">
                        <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['user_name'] ?? 'Eu') ?>" disabled>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="delegate_user_id">Substituto</label>
                    <select name="delegate_user_id" id="delegate_user_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($users as $u): ?>
                            <?php if ((int) $u['id'] === $currentUserId && !$isFull) continue; ?>
                            <option value="<?= (int) $u['id'] ?>"><?= htmlspecialchars($u['name'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="starts_at">Início</label>
                    <input type="datetime-local" name="starts_at" id="starts_at" class="form-control" required
                           value="<?= date('Y-m-d\TH:i') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="ends_at">Fim</label>
                    <input type="datetime-local" name="ends_at" id="ends_at" class="form-control" required
                           value="<?= date('Y-m-d\TH:i', strtotime('+7 days')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="notes">Observação</label>
                    <input type="text" name="notes" id="notes" class="form-control" maxlength="255"
                           placeholder="Ex.: férias / afastamento">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>list-approval-delegations" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
