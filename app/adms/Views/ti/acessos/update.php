<?php

use App\adms\Helpers\CSRFHelper;

$url = (string) ($_ENV['URL_ADM'] ?? '');
$form = $this->data['form'] ?? [];
$acesso = $this->data['acesso'] ?? [];
$returnTo = (string) ($this->data['return_to'] ?? '');
$csrf = CSRFHelper::generateCSRFToken('form_ti_acesso_update');
$sistemaId = (int) ($acesso['ti_sistema_id'] ?? 0);
$cancelUrl = $returnTo !== '' ? $returnTo : ($url . 'ti-sistemas-view/' . $sistemaId);
$sistemaLabel = (string) ($acesso['sistema_nome'] ?? 'Sistema');
if (!empty($acesso['sistema_equipamento_tag'])) {
    $sistemaLabel .= ' · ' . (string) $acesso['sistema_equipamento_tag'];
}
?>
<div class="container-fluid px-2 px-md-4">
    <div class="mb-1 d-flex flex-column flex-sm-row gap-1 gap-sm-2">
        <h2 class="mt-2 mt-sm-3 mb-1 h3">Editar Acesso (TI)</h2>
        <ol class="breadcrumb mb-2 mb-sm-3 mt-0 mt-sm-3 ms-sm-auto small">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url . 'ti-sistemas', ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">Sistemas</a></li>
            <?php if ($sistemaId > 0): ?>
                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url . 'ti-sistemas-view/' . $sistemaId, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">#<?= $sistemaId ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active">Editar acesso</li>
        </ol>
    </div>

    <div class="card border-light shadow mb-4">
        <div class="card-header">Login e perfil no mapa</div>
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

            <div class="user-view-panel mb-3">
                <div class="user-view-panel-body">
                    <div class="user-view-row">
                        <div class="user-view-row-label">Colaborador</div>
                        <div class="user-view-row-value">
                            <?= htmlspecialchars((string) ($acesso['usuario_nome'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($acesso['usuario_username'])): ?>
                                <span class="text-muted fw-normal small">(<?= htmlspecialchars((string) $acesso['usuario_username'], ENT_QUOTES, 'UTF-8') ?>)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="user-view-row">
                        <div class="user-view-row-label">Sistema</div>
                        <div class="user-view-row-value"><?= htmlspecialchars($sistemaLabel, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
            </div>
            <p class="text-muted small">Colaborador e sistema não mudam aqui. Para trocar a pessoa, inative este vínculo e libere um novo acesso.</p>

            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8') ?>">

                <div class="col-12 col-md-4">
                    <label for="login_externo" class="form-label">Login no sistema</label>
                    <input type="text" name="login_externo" id="login_externo" class="form-control" maxlength="120"
                           value="<?= htmlspecialchars((string) ($form['login_externo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Usuário naquele equipamento">
                </div>
                <div class="col-12 col-md-4">
                    <label for="perfil_obs" class="form-label">Perfil / função</label>
                    <input type="text" name="perfil_obs" id="perfil_obs" class="form-control" maxlength="180"
                           value="<?= htmlspecialchars((string) ($form['perfil_obs'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Operador, consulta…">
                </div>
                <div class="col-12 col-md-4">
                    <label for="data_liberacao" class="form-label">Data de liberação</label>
                    <input type="date" name="data_liberacao" id="data_liberacao" class="form-control"
                           value="<?= htmlspecialchars((string) ($form['data_liberacao'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea name="observacoes" id="observacoes" class="form-control" rows="2"><?= htmlspecialchars((string) ($form['observacoes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="col-12 d-flex flex-column flex-sm-row gap-2">
                    <button type="submit" class="btn btn-warning btn-sm"><i class="fa-regular fa-pen-to-square me-1"></i>Salvar</button>
                    <a href="<?= htmlspecialchars($cancelUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
