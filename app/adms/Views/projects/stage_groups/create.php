<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Gestão de Projetos - Grupos de Etapas</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Gestão de Projetos</li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-stage-groups" class="text-decoration-none">Grupos de Etapas</a>
            </li>
            <li class="breadcrumb-item">Cadastrar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Cadastrar Grupo de Etapas</span>

            <span class="ms-auto d-sm-flex flex-row">
                <?php
                if (in_array('ListStageGroups', $this->data['buttonPermission'] ?? [])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-stage-groups' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }
                ?>
            </span>

        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form action="" method="POST" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_stage_group'); ?>">

                <div class="col-12 col-md-6">
                    <label for="name" class="form-label">Nome do grupo</label>
                    <input type="text" name="name" id="name" class="form-control"
                           value="<?php echo htmlspecialchars($this->data['form']['name'] ?? ''); ?>">
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label d-block">Opções</label>
                    <div class="form-check form-switch mt-1">
                        <?php $active = !isset($this->data['form']['active']) || !empty($this->data['form']['active']); ?>
                        <input class="form-check-input" type="checkbox" id="active" name="active"
                               <?php echo $active ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="active">Ativo</label>
                    </div>
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea name="description" id="description" rows="3" class="form-control"><?php
                        echo htmlspecialchars($this->data['form']['description'] ?? '');
                    ?></textarea>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">Cadastrar</button>
                </div>

            </form>

        </div>
    </div>

</div>

