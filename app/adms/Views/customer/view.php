<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_customer');

?>

<div class="container-fluid px-4">

    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Cliente</h2>

        <ol class="breadcrumb mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-customers" class="text-decoration-none">Clientes</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>

        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header d-flex flex-column flex-sm-row gap-2">
            <span>Visualizar</span>

            <span class="ms-sm-auto d-sm-flex flex-row">
                <?php
                if (in_array('ListCustomers', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-customers' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }

                $id = ($this->data['customer']['id'] ?? '');

                if (in_array('UpdateCustomer', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}update-customer/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a> ";
                }
                if (in_array('DeleteCustomer', $this->data['buttonPermission'])) {
                ?>

                    <!-- Formulário para deletar Frequência -->
                    <form id="formDelete<?php echo ($this->data['customer']['id'] ?? ''); ?>" action="<?php echo $_ENV['URL_ADM']; ?>delete-customer" method="POST">

                        <!-- Campo oculto para o token CSRF -->
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <!-- Campo oculto para o ID do Frequência -->
                        <input type="hidden" name="id" id="id" value="<?php echo ($this->data['customer']['id'] ?? ''); ?>">

                        <input type="hidden" name="card_code" id="card_code" value="<?php echo ($this->data['customer']['card_code'] ?? ''); ?>">

                        <input type="hidden" name="card_name" id="name" value="<?php echo ($this->data['customer']['card_name'] ?? ''); ?>">

                        <!-- Botão para submeter o formulário -->
                        <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?php echo ($this->data['customer']['id'] ?? ''); ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>

                    </form>
                <?php } ?>

                </form>

            </span>
        </div>

        <div class="card-body">

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';

            // Verifica se há usuários no array
            if (isset($this->data['customer'])) {

                // Extrai variáveis do array $this->data['costCenter'] para fácil acesso
                extract($this->data['customer']);
            ?>

                <dl class="row">

                    <dt class="col-sm-3">ID: </dt>
                    <dd class="col-sm-9"><?php echo $id; ?></dd>

                    <dt class="col-sm-3">Código: </dt>
                    <dd class="col-sm-9"><?php echo $card_code; ?></dd>

                    <dt class="col-sm-3">Nome: </dt>
                    <dd class="col-sm-9"><?php echo $card_name; ?></dd>

                    <dt class="col-sm-3">Pessoa: </dt>
                    <dd class="col-sm-9"><?php echo $type_person; ?></dd>

                    <dt class="col-sm-3">Documento: </dt>
                    <dd class="col-sm-9"><?php echo $doc; ?></dd>

                    <dt class="col-sm-3">Telefone: </dt>
                    <dd class="col-sm-9"><?php echo $phone; ?></dd>

                    <dt class="col-sm-3">Email: </dt>
                    <dd class="col-sm-9"><?php echo $email; ?></dd>

                    <dt class="col-sm-3">Endereço: </dt>
                    <dd class="col-sm-9"><?php echo $address; ?></dd>

                    <dt class="col-sm-3">Descrição/Observação: </dt>
                    <dd class="col-sm-9"><?php echo $description; ?></dd>

                    <dt class="col-sm-3">Data Nascimento: </dt>
                    <dd class="col-sm-9"><?php echo $date_birth ? date('d/m/Y', strtotime($date_birth)) : ""; ?></dd>

                    <dt class="col-sm-3">Ativo: </dt>
                    <dd class="col-sm-9"><?php echo ($active == 1) ? 'SIM' : 'NÃO'; ?></dd>

                    <dt class="col-sm-3">Cadastrado: </dt>
                    <dd class="col-sm-9"><?php echo ($created_at ? date('d/m/Y H:i:s', strtotime($created_at)) : ""); ?></dd>

                    <dt class="col-sm-3">Editado: </dt>
                    <dd class="col-sm-9"><?php echo ($updated_at ? date('d/m/Y H:i:s', strtotime($updated_at)) : ""); ?></dd>
                </dl>

            <?php
            } else { // Caso o Frequência não seja encontrado
                echo "<div class='alert alert-danger' role='alert'>Frequência não encontrada!</div>";
            }
            ?>

        </div>

    </div>

</div>