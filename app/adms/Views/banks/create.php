<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Banco</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-banks" class="text-decoration-none">Bancos</a>
            </li>
            <li class="breadcrumb-item">Cadastrar</li>

        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Cadastrar</span>

            <span class="ms-auto d-sm-flex flex-row">
            <?php
                if (in_array('ListBanks', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-banks' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }
                ?>
            </span>

        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <!-- Formulário para cadastrar um novo Departamentos -->
            <form action="" method="POST" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_bank'); ?>">

                <div class="col-6">
                    <label for="bank_name" class="form-label">Nome</label>
                    <input type="text" name="bank_name" class="form-control" id="bank_name" placeholder="Nome para cadastro do banco" value="<?php echo $this->data['form']['bank_name'] ?? ''; ?>">
                </div>

                <div class="col-6">
                    <label for="bank" class="form-label">Banco</label>
                    <input type="text" name="bank" class="form-control" id="bank" placeholder="Banco" value="<?php echo $this->data['form']['bank'] ?? ''; ?>">
                </div>

                <div class="col-6">
                    <label for="type" class="form-label">Tipo</label>
                    <select name="type" class="form-select" id="type">
                        <option value="Corrente" <?= ($this->data['form']['type'] ?? '') === 'Corrente' ? 'selected' : '' ?>>Corrente</option>
                        <option value="Aplicação" <?= ($this->data['form']['type'] ?? '') === 'Aplicação' ? 'selected' : '' ?>>Aplicação</option>
                    </select>
                </div>
                <div class="col-6">
                    <label for="account" class="form-label">Conta</label>
                    <input type="text" name="account" class="form-control" id="account" placeholder="conta" value="<?php echo $this->data['form']['account'] ?? ''; ?>">
                </div>
                    <div class="col-6">
                        <label for="agency" class="form-label">Agência</label>
                        <input type="text" name="agency" class="form-control" id="agency" placeholder="Agência" value="<?php echo $this->data['form']['agency'] ?? ''; ?>">
                    </div>

                    <div class="col-6">
                        <label for="balance" class="form-label">Saldo</label>
                        <input type="text" name="balance" class="form-control" id="balance" placeholder="Saldo" value="<?php echo $this->data['form']['balance'] ?? ''; ?>">
                    </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">Cadastrar</button>
                </div>

            </form>

        </div>
                <!-- <?php echo var_dump($this->data['form']); ?> -->
    </div>

</div>