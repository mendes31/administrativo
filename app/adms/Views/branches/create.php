<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Filial</h2>
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
            <span>Cadastrar</span>
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
            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_branch'); ?>">
                <div class="col-6">
                    <label for="name" class="form-label">Nome</label>
                    <input type="text" name="name" class="form-control" id="name" placeholder="Nome da filial" value="<?php echo $this->data['form']['name'] ?? ''; ?>">
                </div>
                <div class="col-6">
                    <label for="code" class="form-label">Código</label>
                    <input type="text" name="code" class="form-control" id="code" placeholder="Código da filial" value="<?php echo $this->data['form']['code'] ?? ''; ?>">
                </div>
                <div class="col-6">
                    <label for="address" class="form-label">Endereço</label>
                    <input type="text" name="address" class="form-control" id="address" placeholder="Endereço" value="<?php echo $this->data['form']['address'] ?? ''; ?>">
                </div>
                <div class="col-6">
                    <label for="phone" class="form-label">Telefone</label>
                    <input type="text" name="phone" class="form-control" id="phone" placeholder="Telefone" value="<?php echo $this->data['form']['phone'] ?? ''; ?>">
                </div>
                <div class="col-6">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" name="email" class="form-control" id="email" placeholder="E-mail" value="<?php echo $this->data['form']['email'] ?? ''; ?>">
                </div>
                <div class="col-6">
                    <label for="active" class="form-label">Status</label>
                    <select name="active" class="form-select" id="active">
                        <option value="1" <?= (isset($this->data['form']['active']) && $this->data['form']['active'] == 1) ? 'selected' : ''; ?>>Ativo</option>
                        <option value="0" <?= (isset($this->data['form']['active']) && $this->data['form']['active'] == 0) ? 'selected' : ''; ?>>Inativo</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div> 