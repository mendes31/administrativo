<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Documento</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-documents" class="text-decoration-none">Documentos</a>
            </li>
            <li class="breadcrumb-item">Cadastrar</li>

        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Cadastrar</span>

            <span class="ms-auto d-sm-flex flex-row">
            <?php
                if (in_array('ListDocuments', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-documents' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }
                ?>
            </span>

        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if (!empty($this->data['errors'])): ?>
                <div class="alert alert-danger">
                    <?php foreach ($this->data['errors'] as $error) {
                        echo "<p>$error</p>";
                    } ?>
                </div>
            <?php endif; ?>

            <!-- Formulário para cadastrar um novo documento -->
            <form action="" method="POST" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_document'); ?>">

                <div class="col-12">
                    <label for="cod_doc" class="form-label">Código Documento</label>
                    <input type="text" name="cod_doc" class="form-control" id="cod_doc" placeholder="Código do documento" value="<?php echo $this->data['form']['cod_doc'] ?? ''; ?>">
                </div>

                <div class="col-12">
                    <label for="name" class="form-label">Nome</label>
                    <input type="text" name="name" class="form-control" id="name" placeholder="Nome do documento" value="<?php echo $this->data['form']['name'] ?? ''; ?>">
                </div>

                <div class="col-12">
                    <label for="version" class="form-label">Versão</label>
                    <input type="text" name="version" class="form-control" id="version" placeholder="Versão do documento" value="<?php echo $this->data['form']['version'] ?? ''; ?>">
                </div>

                <div class="col-md-6 col-sm-12">
                    <label for="page_status" class="form-label">Status</label>
                    <select name="page_status" class="form-select" id="page_status">
                        <option value="" selected>Selecione</option>
                        <option value="1" <?php echo isset($this->data['form']['page_status']) && $this->data['form']['page_status'] == 1 ? 'selected' : ''; ?>>Ativa</option>
                        <option value="0" <?php echo isset($this->data['form']['page_status']) && $this->data['form']['page_status'] == 0 ? 'selected' : ''; ?>>Inativa</option>
                    </select>
                </div>

                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">Cadastrar</button>
                </div>

            </form>

        </div>

    </div>

</div>