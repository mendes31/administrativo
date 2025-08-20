<?php

use App\adms\Helpers\CSRFHelper;

?>

<div class="container-fluid px-4">

    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Banco</h2>

        <ol class="breadcrumb mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-banks" class="text-decoration-none">Bancos</a>
            </li>
            <li class="breadcrumb-item">Editar</li>

        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">

            <span>Editar</span>

            <span class="ms-auto d-sm-flex flex-row">
            <?php
                if (in_array('ListBanks', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-banks' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }

                $id = ($this->data['form']['id'] ?? '');
                if (in_array('ViewBank', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}view-bank/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a> ";
                }
                ?>
            </span>

        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form action="" method="POST" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_banks'); ?>">

                <input type="hidden" name="id" id="id" value="<?php echo $this->data['form']['id'] ?? ''; ?>">

                <div class="col-6">
                    <label for="bank_name" class="form-label">Nome</label>
                    <input type="text" name="bank_name" class="form-control" id="bank_name" placeholder="Nome do banco" value="<?php echo $this->data['form']['bank_name'] ?? ''; ?>">
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
                    <input type="text" name="account" class="form-control" id="account" placeholder="Conta" value="<?php echo $this->data['form']['account'] ?? ''; ?>">
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
                    <button type="submit" class="btn btn-warning btn-sm">Salvar</button>
                </div>

            </form>

        </div>
    </div>

</div>