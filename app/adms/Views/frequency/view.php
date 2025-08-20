<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_frequency');

?>

<div class="container-fluid px-4">

    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Frequência</h2>

        <ol class="breadcrumb mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-frequencies" class="text-decoration-none">Frequências</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>

        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header d-flex flex-column flex-sm-row gap-2">
            <span>Visualizar</span>

            <span class="ms-sm-auto d-sm-flex flex-row">
                <?php
                if (in_array('ListFrequencies', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-frequencies' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }

                $id = ($this->data['frequency']['id'] ?? '');

                if (in_array('UpdateFrequency', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}update-frequency/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a> ";
                }
                if (in_array('DeleteFrequency', $this->data['buttonPermission'])) {
                ?>

                    <!-- Formulário para deletar Frequência -->
                    <form id="formDelete<?php echo ($this->data['frequency']['id'] ?? ''); ?>" action="<?php echo $_ENV['URL_ADM']; ?>delete-frequency" method="POST">

                        <!-- Campo oculto para o token CSRF -->
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <!-- Campo oculto para o ID do Frequência -->
                        <input type="hidden" name="id" id="id" value="<?php echo ($this->data['frequency']['id'] ?? ''); ?>">

                        <input type="hidden" name="name" id="name" value="<?php echo ($this->data['frequency']['name'] ?? ''); ?>">

                        <!-- Botão para submeter o formulário -->
                        <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?php echo ($this->data['frequency']['id'] ?? ''); ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>

                    </form>
                <?php } ?>

                </form>

            </span>
        </div>

        <div class="card-body">

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';

            // Verifica se há usuários no array
            if (isset($this->data['frequency'])) {

                // Extrai variáveis do array $this->data['costCenter'] para fácil acesso
                extract($this->data['frequency']);
            ?>

                <dl class="row">

                    <dt class="col-sm-3">ID: </dt>
                    <dd class="col-sm-9"><?php echo $id; ?></dd>

                    <dt class="col-sm-3">Nome: </dt>
                    <dd class="col-sm-9"><?php echo $name; ?></dd>

                    <dt class="col-sm-3">Dias: </dt>
                    <dd class="col-sm-9"><?php echo $days; ?></dd>

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