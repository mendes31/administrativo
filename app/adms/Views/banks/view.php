<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_bank');

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
            <li class="breadcrumb-item">Visualizar</li>

        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header d-flex flex-column flex-sm-row gap-2">
            <span>Visualizar</span>

            <span class="ms-sm-auto d-sm-flex flex-row">
                <?php
                if (in_array('ListBanks', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-banks' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }

                $id = ($this->data['banks']['id'] ?? '');

                if (in_array('UpdateBank', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}update-bank/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                }
                if (in_array('DeleteBank', $this->data['buttonPermission'])) {
                ?>

                    <!-- Formulário para deletar nível de acesso -->
                    <form id="formDelete<?php echo ($this->data['banks']['id'] ?? ''); ?>" action="<?php echo $_ENV['URL_ADM']; ?>delete-bank" method="POST">

                        <!-- Campo oculto para o token CSRF -->
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <!-- Campo oculto para o ID do nível de acesso -->
                        <input type="hidden" name="id" id="id" value="<?php echo ($this->data['banks']['id'] ?? ''); ?>">

                        <input type="hidden" name="bank_name" id="id" value="<?php echo ($this->data['banks']['bank_name'] ?? ''); ?>">

                        <!-- Botão para submeter o formulário -->
                        <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?php echo ($this->data['banks']['id'] ?? ''); ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>

                    </form>
                <?php } ?>
                </form>
            </span>
        </div>

        <div class="card-body">

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';

            // Verifica se há usuários no array
            if (isset($this->data['banks'])) {

                // Extrai variáveis do array $this->data['banks'] para fácil acesso
                extract($this->data['banks']);
            ?>

                <dl class="row">

                    <dt class="col-sm-3">ID: </dt>
                    <dd class="col-sm-9"><?php echo $id; ?></dd>

                    <dt class="col-sm-3">Nome: </dt>
                    <dd class="col-sm-9"><?php echo $bank_name; ?></dd>

                    <dt class="col-sm-3">Banco: </dt>
                    <dd class="col-sm-9"><?php echo $bank; ?></dd>

                    <dt class="col-sm-3">Tipo: </dt>
                    <dd class="col-sm-9"><?php echo $type; ?></dd>

                    <dt class="col-sm-3">Conta: </dt>
                    <dd class="col-sm-9"><?php echo $account; ?></dd>

                    <dt class="col-sm-3">Agência: </dt>
                    <dd class="col-sm-9"><?php echo $agency; ?></dd>

                    <dt class="col-sm-3">Saldo: </dt>
                    <dd class="col-sm-9"><?php echo $balance; ?></dd>

                    <dt class="col-sm-3">Cadastrado: </dt>
                    <dd class="col-sm-9"><?php echo ($created_at ? date('d/m/Y H:i:s', strtotime($created_at)) : ""); ?></dd>

                    <dt class="col-sm-3">Editado: </dt>
                    <dd class="col-sm-9"><?php echo ($updated_at ? date('d/m/Y H:i:s', strtotime($updated_at)) : ""); ?></dd>
                </dl>

            <?php
            } else { // Caso o nível de acesso não seja encontrado
                echo "<div class='alert alert-danger' role='alert'>Banco não encontrado!</div>";
            }
            ?>

        </div>

    </div>

</div>