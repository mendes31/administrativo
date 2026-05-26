<?php

use App\adms\Helpers\CSRFHelper;

$isEdit = !empty($this->data['category']);
$form = $isEdit ? $this->data['category'] : ($this->data['form'] ?? []);

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-tags me-2"></i><?= $isEdit ? 'Editar' : 'Cadastrar' ?> Categoria</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sac-dashboard" class="text-decoration-none">SAC</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sac-list-categories" class="text-decoration-none">Categorias</a></li>
            <li class="breadcrumb-item"><?= $isEdit ? 'Editar' : 'Cadastrar' ?></li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span><?= $isEdit ? 'Editar' : 'Cadastrar' ?></span>

            <span class="ms-auto d-sm-flex flex-row">
                <?php
                if (in_array('SacListCategories', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}sac-list-categories' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list-ul'></i> Listar</a> ";
                }
                ?>
            </span>
        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form action="<?= $_ENV['URL_ADM'] . ($isEdit ? 'sac-update-category/' . ($form['id'] ?? '') : 'sac-create-category') ?>" method="POST" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('sac_category_form'); ?>">

                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= $form['id'] ?? ''; ?>">
                <?php endif; ?>

                <div class="col-md-6">
                    <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" id="name" placeholder="Nome da categoria" value="<?= htmlspecialchars($form['name'] ?? '') ?>" required>
                </div>

                <div class="col-md-3">
                    <label for="color" class="form-label">Cor</label>
                    <input type="color" name="color" class="form-control form-control-color" id="color" value="<?= htmlspecialchars($form['color'] ?? '#0d6efd') ?>">
                </div>

                <div class="col-md-3">
                    <label for="icon" class="form-label">Ícone</label>
                    <input type="text" name="icon" class="form-control" id="icon" placeholder="fas fa-folder" value="<?= htmlspecialchars($form['icon'] ?? '') ?>">
                </div>

                <div class="col-md-12">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea name="description" class="form-control" id="description" rows="3" placeholder="Descrição da categoria (opcional)"><?= htmlspecialchars($form['description'] ?? '') ?></textarea>
                </div>

                <div class="col-md-4">
                    <label for="default_sla_response_hours" class="form-label">SLA Resposta (horas)</label>
                    <input type="number" name="default_sla_response_hours" class="form-control" id="default_sla_response_hours" min="0" step="1" value="<?= htmlspecialchars($form['default_sla_response_hours'] ?? '') ?>" placeholder="Ex: 4">
                </div>

                <div class="col-md-4">
                    <label for="default_sla_resolution_hours" class="form-label">SLA Resolução (horas)</label>
                    <input type="number" name="default_sla_resolution_hours" class="form-control" id="default_sla_resolution_hours" min="0" step="1" value="<?= htmlspecialchars($form['default_sla_resolution_hours'] ?? '') ?>" placeholder="Ex: 24">
                </div>

                <div class="col-md-4">
                    <label for="display_order" class="form-label">Ordem de exibição</label>
                    <input type="number" name="display_order" class="form-control" id="display_order" min="0" value="<?= htmlspecialchars($form['display_order'] ?? '0') ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Status</label><br>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                            <?= (!$isEdit || !empty($form['is_active'])) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Ativo</label>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-success btn-sm"><?= $isEdit ? 'Salvar' : 'Cadastrar' ?></button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sac-list-categories" class="btn btn-secondary btn-sm">Cancelar</a>
                </div>

            </form>

        </div>
    </div>
</div>
