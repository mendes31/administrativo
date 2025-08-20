<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_ropa');

?>

<div class="container-fluid px-4">

    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">ROPA</h2>

        <ol class="breadcrumb mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-ropa" class="text-decoration-none">LGPD</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header d-flex flex-column flex-sm-row gap-2">
            <span>Visualizar</span>

            <span class="ms-sm-auto d-sm-flex flex-row">
                <?php
                if (in_array('ListLgpdRopa', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}lgpd-ropa' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }

                $id = ($this->data['registro']['id'] ?? '');

                if (in_array('LgpdDataMappingCreate', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}lgpd-data-mapping-create-from-ropa/$id' class='btn btn-success btn-sm me-1 mb-1'><i class='fa-solid fa-plus-circle'></i> Criar Data Mapping</a> ";
                }

                if (in_array('EditLgpdRopa', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}lgpd-ropa-edit/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                }
                if (in_array('DeleteLgpdRopa', $this->data['buttonPermission'])) {
                ?>

                    <!-- Formulário para deletar registro ROPA -->
                    <form id="formDelete<?php echo ($this->data['registro']['id'] ?? ''); ?>" action="<?php echo $_ENV['URL_ADM']; ?>lgpd-ropa-delete" method="POST">

                        <!-- Campo oculto para o token CSRF -->
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <!-- Campo oculto para o ID do registro -->
                        <input type="hidden" name="id" id="id" value="<?php echo ($this->data['registro']['id'] ?? ''); ?>">

                        <!-- Botão para submeter o formulário -->
                        <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?php echo ($this->data['registro']['id'] ?? ''); ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>

                    </form>
                <?php } ?>

            </span>
        </div>

        <div class="card-body">

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';

            // Verifica se há registro no array
            if (isset($this->data['registro'])) {

                // Extrai variáveis do array $this->data['registro'] para fácil acesso
                extract($this->data['registro']);
            ?>

                <dl class="row">

                    <dt class="col-sm-3">ID: </dt>
                    <dd class="col-sm-9"><?php echo $id; ?></dd>

                    <dt class="col-sm-3">Código: </dt>
                    <dd class="col-sm-9"><?php echo $codigo; ?></dd>

                    <dt class="col-sm-3">Atividade: </dt>
                    <dd class="col-sm-9"><?php echo $atividade; ?></dd>

                    <dt class="col-sm-3">Departamento: </dt>
                    <dd class="col-sm-9"><?php echo $departamento_nome; ?></dd>

                    <dt class="col-sm-3">Base Legal: </dt>
                    <dd class="col-sm-9"><?php echo $base_legal; ?></dd>

                    <dt class="col-sm-3">Período de Retenção: </dt>
                    <dd class="col-sm-9"><?php echo $retencao ? $retencao : '-'; ?></dd>

                    <dt class="col-sm-3">Riscos: </dt>
                    <dd class="col-sm-9"><?php echo $riscos ? $riscos : '-'; ?></dd>

                    <dt class="col-sm-3">Medidas de Segurança: </dt>
                    <dd class="col-sm-9"><?php echo (isset($medidas_seguranca) && $medidas_seguranca) ? $medidas_seguranca : '-'; ?></dd>

                    <dt class="col-sm-3">Observações: </dt>
                    <dd class="col-sm-9"><?php echo (isset($observacoes) && $observacoes) ? $observacoes : '-'; ?></dd>

                    <dt class="col-sm-3">Status: </dt>
                    <dd class="col-sm-9">
                        <?php echo $status === 'Ativo' ? "<span class='badge text-bg-success'>Ativo</span>" : "<span class='badge text-bg-danger'>Inativo</span>"; ?>
                    </dd>

                    <dt class="col-sm-3">Cadastrado: </dt>
                    <dd class="col-sm-9"><?php echo ($created_at ? date('d/m/Y H:i:s', strtotime($created_at)) : ""); ?></dd>

                    <dt class="col-sm-3">Editado: </dt>
                    <dd class="col-sm-9"><?php echo ($updated_at ? date('d/m/Y H:i:s', strtotime($updated_at)) : ""); ?></dd>

                </dl>

            <?php
            } else { // Caso o registro não seja encontrado
                echo "<div class='alert alert-danger' role='alert'>Registro não encontrado!</div>";
            }
            ?>

        </div>

    </div>

</div> 