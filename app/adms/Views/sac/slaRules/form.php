<?php

use App\adms\Helpers\CSRFHelper;

$isEdit = !empty($this->data['rule']);
$form = $isEdit ? $this->data['rule'] : ($this->data['form'] ?? []);

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-stopwatch me-2"></i><?= $isEdit ? 'Editar' : 'Cadastrar' ?> Regra de SLA</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sac-dashboard" class="text-decoration-none">SAC</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sac-list-sla-rules" class="text-decoration-none">Regras de SLA</a></li>
            <li class="breadcrumb-item"><?= $isEdit ? 'Editar' : 'Cadastrar' ?></li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span><?= $isEdit ? 'Editar' : 'Cadastrar' ?></span>

            <span class="ms-auto d-sm-flex flex-row">
                <?php
                if (in_array('SacListSlaRules', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}sac-list-sla-rules' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list-ul'></i> Listar</a> ";
                }
                ?>
            </span>
        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form action="<?= $_ENV['URL_ADM'] . ($isEdit ? 'sac-update-sla-rule/' . ($form['id'] ?? '') : 'sac-create-sla-rule') ?>" method="POST" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('sac_sla_rule_form'); ?>">

                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= $form['id'] ?? ''; ?>">
                <?php endif; ?>

                <div class="col-md-6">
                    <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" id="name" placeholder="Nome da regra" value="<?= htmlspecialchars($form['name'] ?? '') ?>" required>
                </div>

                <div class="col-md-3">
                    <label for="category_id" class="form-label">Categoria</label>
                    <select name="category_id" class="form-select" id="category_id">
                        <option value="">Todas as categorias</option>
                        <?php if ($this->data['categories'] ?? false): ?>
                            <?php foreach ($this->data['categories'] as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (isset($form['category_id']) && $form['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="priority" class="form-label">Prioridade</label>
                    <select name="priority" class="form-select" id="priority">
                        <?php $pVal = $form['priority'] ?? ''; ?>
                        <option value="">Todas as prioridades</option>
                        <option value="Baixa" <?= $pVal === 'Baixa' ? 'selected' : '' ?>>Baixa</option>
                        <option value="Média" <?= $pVal === 'Média' ? 'selected' : '' ?>>Média</option>
                        <option value="Alta" <?= $pVal === 'Alta' ? 'selected' : '' ?>>Alta</option>
                        <option value="Urgente" <?= $pVal === 'Urgente' ? 'selected' : '' ?>>Urgente</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="response_time_hours" class="form-label">Tempo de Resposta (horas) <span class="text-danger">*</span></label>
                    <input type="number" name="response_time_hours" class="form-control" id="response_time_hours" min="1" step="1" value="<?= htmlspecialchars($form['response_time_hours'] ?? '') ?>" placeholder="Ex: 4" required>
                </div>

                <div class="col-md-3">
                    <label for="resolution_time_hours" class="form-label">Tempo de Resolução (horas) <span class="text-danger">*</span></label>
                    <input type="number" name="resolution_time_hours" class="form-control" id="resolution_time_hours" min="1" step="1" value="<?= htmlspecialchars($form['resolution_time_hours'] ?? '') ?>" placeholder="Ex: 24" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Escalonamento</label><br>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="escalation_enabled" name="escalation_enabled" value="1"
                            <?= !empty($form['escalation_enabled']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="escalation_enabled">Habilitar</label>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Status</label><br>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                            <?= (!$isEdit || !empty($form['is_active'])) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Ativo</label>
                    </div>
                </div>

                <div id="escalation-fields" class="col-12" style="display: <?= !empty($form['escalation_enabled']) ? 'block' : 'none' ?>;">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="escalation_after_hours" class="form-label">Escalonar após (horas)</label>
                            <input type="number" name="escalation_after_hours" class="form-control" id="escalation_after_hours" min="1" step="1" value="<?= htmlspecialchars($form['escalation_after_hours'] ?? '') ?>" placeholder="Ex: 8">
                        </div>

                        <div class="col-md-4">
                            <label for="escalation_user_id" class="form-label">Escalonar para</label>
                            <select name="escalation_user_id" class="form-select" id="escalation_user_id">
                                <option value="">Selecione</option>
                                <?php if ($this->data['users'] ?? false): ?>
                                    <?php foreach ($this->data['users'] as $user): ?>
                                        <option value="<?= $user['id'] ?>" <?= (isset($form['escalation_user_id']) && $form['escalation_user_id'] == $user['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-success btn-sm"><?= $isEdit ? 'Salvar' : 'Cadastrar' ?></button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sac-list-sla-rules" class="btn btn-secondary btn-sm">Cancelar</a>
                </div>

            </form>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('escalation_enabled');
    var fields = document.getElementById('escalation-fields');
    if (toggle && fields) {
        toggle.addEventListener('change', function () {
            fields.style.display = this.checked ? 'block' : 'none';
        });
    }
});
</script>
