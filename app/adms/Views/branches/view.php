<?php

use App\adms\Helpers\CSRFHelper;

$csrf_token = CSRFHelper::generateCSRFToken('form_delete_branch');
?>
<div class="container-fluid px-4">
    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Filial</h2>
        <ol class="breadcrumb mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-branches" class="text-decoration-none">Filiais</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex flex-column flex-sm-row gap-2">
            <span>Visualizar</span>
            <span class="ms-sm-auto d-sm-flex flex-row">
                <?php
                if (in_array('ListBranches', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-branches' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }
                $id = ($this->data['branch']['id'] ?? '');
                if (in_array('UpdateBranch', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}update-branch/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                }
                if (in_array('DeleteBranch', $this->data['buttonPermission'])) {
                ?>
                    <form id="formDelete<?php echo ($this->data['branch']['id'] ?? ''); ?>" action="<?php echo $_ENV['URL_ADM']; ?>delete-branch" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="id" id="id" value="<?php echo ($this->data['branch']['id'] ?? ''); ?>">
                        <input type="hidden" name="name" id="name" value="<?php echo ($this->data['branch']['name'] ?? ''); ?>">
                        <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?php echo ($this->data['branch']['id'] ?? ''); ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>
                    </form>
                <?php } ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <?php if (isset($this->data['branch'])) { extract($this->data['branch']); ?>
                <dl class="row">
                    <dt class="col-sm-3">ID: </dt>
                    <dd class="col-sm-9"><?php echo $id; ?></dd>
                    <dt class="col-sm-3">Nome: </dt>
                    <dd class="col-sm-9"><?php echo $name; ?></dd>
                    <dt class="col-sm-3">Código: </dt>
                    <dd class="col-sm-9"><?php echo $code; ?></dd>
                    <dt class="col-sm-3">Endereço: </dt>
                    <dd class="col-sm-9"><?php echo $address; ?></dd>
                    <dt class="col-sm-3">Telefone: </dt>
                    <dd class="col-sm-9"><?php echo $phone; ?></dd>
                    <dt class="col-sm-3">E-mail: </dt>
                    <dd class="col-sm-9"><?php echo $email; ?></dd>
                    <dt class="col-sm-3">Status: </dt>
                    <dd class="col-sm-9"><?php echo ($active ? 'Ativo' : 'Inativo'); ?></dd>
                    <dt class="col-sm-3">Cadastrado: </dt>
                    <dd class="col-sm-9"><?php echo ($created_at ? date('d/m/Y H:i:s', strtotime($created_at)) : ""); ?></dd>
                    <dt class="col-sm-3">Editado: </dt>
                    <dd class="col-sm-9"><?php echo ($updated_at ? date('d/m/Y H:i:s', strtotime($updated_at)) : ""); ?></dd>
                </dl>
            <?php } else {
                echo "<div class='alert alert-danger' role='alert'>Filial não encontrada!</div>";
            }
            ?>
        </div>
    </div>
</div> 