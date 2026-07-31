<?php

use App\adms\Helpers\CSRFHelper;

$url = (string) ($_ENV['URL_ADM'] ?? '');
$form = $this->data['form'] ?? [];
$sistemas = $this->data['sistemas'] ?? [];
$usuarios = $this->data['usuarios'] ?? [];
$returnTo = (string) ($this->data['return_to'] ?? '');
$csrf = CSRFHelper::generateCSRFToken('form_ti_acesso');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Liberar Acesso (TI)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url . 'ti-sistemas', ENT_QUOTES, 'UTF-8') ?>">Sistemas</a></li>
            <li class="breadcrumb-item active">Liberar acesso</li>
        </ol>
    </div>

    <div class="card border-light shadow mb-4">
        <div class="card-header">Vínculo colaborador ↔ sistema</div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <?php if (!empty($this->data['errors'])): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($this->data['errors'] as $err): ?>
                            <li><?= htmlspecialchars((string) $err, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <p class="text-muted small">O Administrativo registra o mapa. A criação da conta no equipamento/sistema de destino continua manual.</p>

            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8') ?>">

                <div class="col-md-6">
                    <label for="adms_user_id" class="form-label">Colaborador <span class="text-danger">*</span></label>
                    <select name="adms_user_id" id="adms_user_id" class="form-select" required>
                        <option value="">Selecione…</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= (int) ($u['id'] ?? 0) ?>" <?= (int) ($form['adms_user_id'] ?? 0) === (int) ($u['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($u['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="ti_sistema_id" class="form-label">Sistema <span class="text-danger">*</span></label>
                    <select name="ti_sistema_id" id="ti_sistema_id" class="form-select" required>
                        <option value="">Selecione…</option>
                        <?php foreach ($sistemas as $s): ?>
                            <option value="<?= (int) ($s['id'] ?? 0) ?>" <?= (int) ($form['ti_sistema_id'] ?? 0) === (int) ($s['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars(\App\adms\Models\Repository\TiSistemaRepository::formatLabel($s), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="login_externo" class="form-label">Login no sistema</label>
                    <input type="text" name="login_externo" id="login_externo" class="form-control" maxlength="120"
                           value="<?= htmlspecialchars((string) ($form['login_externo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Usuário naquele equipamento">
                </div>
                <div class="col-md-4">
                    <label for="perfil_obs" class="form-label">Perfil / função</label>
                    <input type="text" name="perfil_obs" id="perfil_obs" class="form-control" maxlength="180"
                           value="<?= htmlspecialchars((string) ($form['perfil_obs'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Operador, consulta…">
                </div>
                <div class="col-md-4">
                    <label for="data_liberacao" class="form-label">Data de liberação</label>
                    <input type="date" name="data_liberacao" id="data_liberacao" class="form-control"
                           value="<?= htmlspecialchars((string) ($form['data_liberacao'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea name="observacoes" id="observacoes" class="form-control" rows="2"><?= htmlspecialchars((string) ($form['observacoes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success btn-sm">Liberar</button>
                    <a href="<?= htmlspecialchars($url . 'ti-sistemas', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
