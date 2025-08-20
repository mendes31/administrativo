<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_bank');

?>

<div class="container-fluid px-4">

    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Transferência entre Contas</h2>

        <ol class="breadcrumb mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-mov-between-accounts" class="text-decoration-none">Transferência entre Contas</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>

        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header d-flex flex-column flex-sm-row gap-2">
            <span>Visualizar</span>

            <span class="ms-sm-auto d-sm-flex flex-row">
                <?php
                if (in_array('ListMovBetweenAccounts', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-mov-between-accounts' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }

                $id = ($this->data['transfer']['id'] ?? '');

                if (in_array('UpdateMovBetweenAccounts', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}update-mov-between-accounts/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                }
                if (in_array('DeleteMovBetweenAccounts', $this->data['buttonPermission'])) {
                ?>

                    <!-- Formulário para deletar nível de acesso -->
                    <form id="formDelete<?php echo ($this->data['transfer']['id'] ?? ''); ?>" action="<?php echo $_ENV['URL_ADM']; ?>delete-mov-between-accounts" method="POST">

                        <!-- Campo oculto para o token CSRF -->
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <!-- Campo oculto para o ID do nível de acesso -->
                        <input type="hidden" name="id" id="id" value="<?php echo ($this->data['transfer']['id'] ?? ''); ?>">

                        <input type="hidden" name="description" id="id" value="<?php echo ($this->data['transfer']['description'] ?? ''); ?>">

                        <!-- Botão para submeter o formulário -->
                            <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?php echo ($this->data['transfer']['id'] ?? ''); ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button> 

                    </form>
                <?php } ?>
                
            </span>
        </div>

        <div class="card-body">

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';

            // Verifica se há usuários no array
            if (isset($this->data['transfer'])) {

                // Extrai variáveis do array $this->data['transfer'] para fácil acesso
                extract($this->data['transfer']);
            ?>

                <dl class="row">

                    <dt class="col-sm-3">ID: </dt>
                    <dd class="col-sm-9"><?php echo $this->data['transfer']['id'] ?? ''; ?></dd>

                    <dt class="col-sm-3">Descrição: </dt>
                    <dd class="col-sm-9"><?php echo$this->data['transfer']['description'] ?? ''; ?></dd>

                    <dt class="col-sm-3">Conta Origem: </dt>
                    <dd class="col-sm-9"><?php echo $this->data['transfer']['origin_name'] ?? ''; ?></dd>

                    <dt class="col-sm-3">Conta Destino: </dt>
                    <dd class="col-sm-9"><?php echo $this->data['transfer']['destination_name'] ?? ''; ?></dd>

                    <dt class="col-sm-3">Valor: </dt>
                    <dd class="col-sm-9">R$ <?php echo number_format($this->data['transfer']['amount'] ?? 0, 2, ',', '.'); ?></dd>

                    <dt class="col-sm-3">Usuário: </dt>
                    <dd class="col-sm-9"><?php echo $this->data['transfer']['user_name'] ?? ''; ?></dd>

                    <dt class="col-sm-3">Cadastrado: </dt>
                    <dd class="col-sm-9"><?php echo (!empty($this->data['transfer']['created_at']) ? date('d/m/Y H:i:s', strtotime($this->data['transfer']['created_at'])) : ''); ?></dd>

                    <dt class="col-sm-3">Editado: </dt>
                    <dd class="col-sm-9"><?php echo (!empty($this->data['transfer']['updated_at']) ? date('d/m/Y H:i:s', strtotime($this->data['transfer']['updated_at'])) : ''); ?></dd>
                </dl>

            <?php
            } else { // Caso a transferência entre contas não seja encontrada
                echo "<div class='alert alert-danger' role='alert'>Transferência entre contas não encontrada!</div>";
            }
            ?>

        </div>

    </div>

</div>