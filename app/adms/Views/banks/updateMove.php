<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Transferência</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-mov-between-accounts" class="text-decoration-none">Transferências</a>
            </li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Editar</span>

            <span class="ms-auto d-sm-flex flex-row">
            <?php
                if (in_array('ListMovBetweenAccounts', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-mov-between-accounts' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a>";
                }
            ?>
            </span>
        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_mov_between_accounts'); ?>">
                <input type="hidden" name="id" id="id" value="<?php echo $this->data['form']['id'] ?? ''; ?>">
                <div class="col-6">
                    <label for="source_account_id" class="form-label">Conta Origem</label>
                    <select name="source_account_id" class="form-select" id="source_account_id">
                        <option value="">Selecione</option>
                        <?php
                        foreach ($this->data['accounts'] as $account) {
                            $selected = (!empty($this->data['form']['source_account_id']) && $this->data['form']['source_account_id'] == $account['id']) ? 'selected' : '';
                            echo "<option value='{$account['id']}' $selected>{$account['bank_name']} - {$account['account']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-6">
                    <label for="destination_account_id" class="form-label">Conta Destino</label>
                    <select name="destination_account_id" class="form-select" id="destination_account_id">
                        <option value="">Selecione</option>
                        <?php
                        foreach ($this->data['accounts'] as $account) {
                            $selected = (!empty($this->data['form']['destination_account_id']) && $this->data['form']['destination_account_id'] == $account['id']) ? 'selected' : '';
                            echo "<option value='{$account['id']}' $selected>{$account['bank_name']} - {$account['account']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-6">
                    <label for="value" class="form-label">Valor</label>
                    <input type="text" name="value" class="form-control" id="value" placeholder="Valor da transferência" value="<?php echo $this->data['form']['value'] ?? ''; ?>">
                </div>

                <div class="col-6">
                    <label for="description" class="form-label">Descrição</label>
                    <input type="text" name="description" class="form-control" id="description" placeholder="Descrição da transferência" value="<?php echo $this->data['form']['description'] ?? ''; ?>">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">Editar</button>
                </div>
            </form>

        </div>
        <!-- <?php echo var_dump($this->data['form']); ?> -->
    </div>

</div>
