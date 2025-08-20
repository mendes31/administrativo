<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_page');

?>

<div class="container-fluid px-4">

    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Página</h2>

        <ol class="breadcrumb mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-pages" class="text-decoration-none">Páginas</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>

        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header d-flex flex-column flex-sm-row gap-2">
            <span>Visualizar</span>

            <span class="ms-sm-auto d-sm-flex flex-row">
                <?php
                if (in_array('ListPages', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-pages' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }

                $id = ($this->data['page']['id'] ?? '');

                if (in_array('UpdatePage', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}update-page/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                }
                if (in_array('DeletePage', $this->data['buttonPermission'])) {
                ?>

                    <!-- Formulário para deletar nível de acesso -->
                    <form id="formDelete<?php echo ($this->data['page']['id'] ?? ''); ?>" action="<?php echo $_ENV['URL_ADM']; ?>delete-page" method="POST">

                        <!-- Campo oculto para o token CSRF -->
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <!-- Campo oculto para o ID do nível de acesso -->
                        <input type="hidden" name="id" id="id" value="<?php echo ($this->data['page']['id'] ?? ''); ?>">

                        <!-- Botão para submeter o formulário -->
                        <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?php echo ($this->data['page']['id'] ?? ''); ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>

                    </form>
                <?php } ?>
                </form>

            </span>
        </div>

        <div class="card-body">

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';

            // Verifica se há usuários no array
            if (isset($this->data['page'])) {

                // Extrai variáveis do array $this->data['page'] para fácil acesso
                extract($this->data['page']);
            ?>

                <dl class="row">

                    <dt class="col-sm-3">ID: </dt>
                    <dd class="col-sm-9"><?php echo $id; ?></dd>

                    <dt class="col-sm-3">Nome: </dt>
                    <dd class="col-sm-9"><?php echo $name; ?></dd>

                    <dt class="col-sm-3">Controller: </dt>
                    <dd class="col-sm-9"><?php echo $controller; ?></dd>

                    <dt class="col-sm-3">URL: </dt>
                    <dd class="col-sm-9"><?php echo $controller_url; ?></dd>

                    <dt class="col-sm-3">Diretório: </dt>
                    <dd class="col-sm-9"><?php echo $directory; ?></dd>

                    <dt class="col-sm-3">Observação: </dt>
                    <dd class="col-sm-9"><?php echo $obs ? $obs : '-'; ?></dd>

                    <dt class="col-sm-3">Status: </dt>
                    <dd class="col-sm-9">
                        <?php echo $page_status ? "<span class='badge text-bg-success'>Ativa</span>" : "<span class='badge text-bg-danger'>Inativa</span>"; ?>
                    </dd>

                    <dt class="col-sm-3">Pública: </dt>
                    <dd class="col-sm-9">
                        <?php echo $public_page ? "<span class='badge text-bg-success'>Sim</span>" : "<span class='badge text-bg-danger'>Não</span>";; ?>
                    </dd>

                    <dt class="col-sm-3">Pacote: </dt>
                    <dd class="col-sm-9"><?php echo $app_name; ?></dd>

                    <dt class="col-sm-3">Grupo: </dt>
                    <dd class="col-sm-9"><?php echo $agp_name; ?></dd>

                    <dt class="col-sm-3">Cadastrado: </dt>
                    <dd class="col-sm-9"><?php echo ($created_at ? date('d/m/Y H:i:s', strtotime($created_at)) : ""); ?></dd>

                    <dt class="col-sm-3">Editado: </dt>
                    <dd class="col-sm-9"><?php echo ($updated_at ? date('d/m/Y H:i:s', strtotime($updated_at)) : ""); ?></dd>
                </dl>

            <?php
            } else { // Caso a página não seja encontrado
                echo "<div class='alert alert-danger' role='alert'>Página não encontrada!</div>";
            }
            ?>

        </div>

    </div>

</div>