<?php

use App\adms\Helpers\CSRFHelper;

$form = $this->data['form'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Estabelecimento</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-branches" class="text-decoration-none">Filiais</a>
            </li>
            <li class="breadcrumb-item">Cadastrar</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Cadastrar Matriz ou Filial</span>
            <span class="ms-auto d-sm-flex flex-row">
            <?php
                if (in_array('ListBranches', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-branches' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }
            ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <p class="text-muted small mb-3">
                Preencha os campos no mesmo formato do <strong>Comprovante de Inscrição e Situação Cadastral (CNPJ)</strong>.
                A razão social pode ser igual entre estabelecimentos; diferencie pelo nome fantasia, CNPJ e tipo Matriz/Filial.
            </p>
            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_branch'); ?>">
                <?php include __DIR__ . '/partials/form_fields.php'; ?>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>
