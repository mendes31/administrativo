<?php

declare(strict_types=1);

$form = $this->data['form'] ?? [];
$tipos = $this->data['tipos'] ?? [];
$listUsers = $this->data['listUsers'] ?? [];
$csrf = (string) ($this->data['csrf_token'] ?? '');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Iniciar Offboarding</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-offboardings', ENT_QUOTES, 'UTF-8') ?>">Offboarding</a></li>
            <li class="breadcrumb-item active">Novo</li>
        </ol>
    </div>

    <div class="card border-light shadow mb-4">
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

                <div class="col-md-6">
                    <label class="form-label" for="adms_user_id">Colaborador</label>
                    <select class="form-select" name="form[adms_user_id]" id="adms_user_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($listUsers as $u): ?>
                            <?php $uid = (int) ($u['id'] ?? 0); ?>
                            <option value="<?= $uid ?>" <?= (string) ($form['adms_user_id'] ?? '') === (string) $uid ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($u['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="tipo">Tipo</label>
                    <select class="form-select" name="form[tipo]" id="tipo" required>
                        <?php foreach ($tipos as $t): ?>
                            <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>" <?= ($form['tipo'] ?? '') === $t ? 'selected' : '' ?>>
                                <?= htmlspecialchars(str_replace('_', ' ', $t), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="data_prevista">Data prevista</label>
                    <input class="form-control" type="date" name="form[data_prevista]" id="data_prevista"
                           value="<?= htmlspecialchars((string) ($form['data_prevista'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="motivo">Motivo</label>
                    <input class="form-control" type="text" name="form[motivo]" id="motivo" maxlength="255" required
                           value="<?= htmlspecialchars((string) ($form['motivo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="tipo_impacto">Classificação (People Analytics)</label>
                    <select class="form-select" name="form[tipo_impacto]" id="tipo_impacto">
                        <?php foreach (['regrettable' => 'Regrettable', 'non_regrettable' => 'Non-regrettable', 'nao_classificado' => 'Não classificado'] as $code => $label): ?>
                            <option value="<?= $code ?>" <?= ($form['tipo_impacto'] ?? '') === $code ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label" for="observacoes">Observações</label>
                    <textarea class="form-control" name="form[observacoes]" id="observacoes" rows="3"><?= htmlspecialchars((string) ($form['observacoes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Iniciar checklist</button>
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars((string) ($_ENV['URL_ADM'] ?? '') . 'rh-offboardings', ENT_QUOTES, 'UTF-8') ?>">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
