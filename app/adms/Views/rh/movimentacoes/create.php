<?php

declare(strict_types=1);

$form = $this->data['form'] ?? [];
$tipos = $this->data['tipos'] ?? [];
$users = $this->data['listUsers'] ?? [];
$deps = $this->data['listDepartments'] ?? [];
$pos = $this->data['listPositions'] ?? [];
$sups = $this->data['listSupervisors'] ?? [];
$csrf = (string) ($this->data['csrf_token'] ?? '');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Registrar movimentação</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-movimentacoes', ENT_QUOTES, 'UTF-8') ?>">Movimentações</a></li>
            <li class="breadcrumb-item active">Nova</li>
        </ol>
    </div>

    <div class="card border-light shadow">
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <p class="small text-muted">Ao salvar, a lotação atual do usuário (departamento, cargo e gestor) é atualizada imediatamente.</p>

            <form method="post" action="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-movimentacoes-create', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="adms_user_id">Colaborador *</label>
                        <select class="form-select" name="form[adms_user_id]" id="adms_user_id" required>
                            <option value="">Selecione…</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= (int) ($u['id'] ?? 0) ?>" <?= (string) ($form['adms_user_id'] ?? '') === (string) ($u['id'] ?? '') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($u['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> (#<?= (int) ($u['id'] ?? 0) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="tipo">Tipo *</label>
                        <select class="form-select" name="form[tipo]" id="tipo" required>
                            <?php foreach ($tipos as $t): ?>
                                <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>" <?= ($form['tipo'] ?? '') === $t ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(str_replace('_', ' ', $t), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="data_vigencia">Vigência *</label>
                        <input class="form-control" type="date" name="form[data_vigencia]" id="data_vigencia" required
                               value="<?= htmlspecialchars((string) ($form['data_vigencia'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="departamento_id_depois">Departamento destino *</label>
                        <select class="form-select" name="form[departamento_id_depois]" id="departamento_id_depois" required>
                            <option value="">Selecione…</option>
                            <?php foreach ($deps as $d): ?>
                                <option value="<?= (int) ($d['id'] ?? 0) ?>" <?= (string) ($form['departamento_id_depois'] ?? '') === (string) ($d['id'] ?? '') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($d['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="cargo_id_depois">Cargo destino *</label>
                        <select class="form-select" name="form[cargo_id_depois]" id="cargo_id_depois" required>
                            <option value="">Selecione…</option>
                            <?php foreach ($pos as $p): ?>
                                <option value="<?= (int) ($p['id'] ?? 0) ?>" <?= (string) ($form['cargo_id_depois'] ?? '') === (string) ($p['id'] ?? '') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($p['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="gestor_id_depois">Gestor destino</label>
                        <select class="form-select" name="form[gestor_id_depois]" id="gestor_id_depois">
                            <option value="">Sem gestor</option>
                            <?php foreach ($sups as $s): ?>
                                <option value="<?= (int) ($s['id'] ?? 0) ?>" <?= (string) ($form['gestor_id_depois'] ?? '') === (string) ($s['id'] ?? '') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($s['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="motivo">Motivo *</label>
                        <input class="form-control" type="text" name="form[motivo]" id="motivo" required maxlength="255"
                               value="<?= htmlspecialchars((string) ($form['motivo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="observacoes">Observações</label>
                        <textarea class="form-control" name="form[observacoes]" id="observacoes" rows="2"><?= htmlspecialchars((string) ($form['observacoes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Aplicar movimentação e atualizar a lotação do usuário?');">Salvar</button>
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-movimentacoes', ENT_QUOTES, 'UTF-8') ?>">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
