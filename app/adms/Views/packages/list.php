<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_package');

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Pacotes</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Pacotes</li>

        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Listar</span>

            <span class="ms-auto">
                <?php
                if (in_array('CreatePackage', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}create-package' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Cadastrar</a> ";
                }
                ?>
            </span>
        </div>

        <div class="card-body">

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';

            // Verifica se há departamento no array
            if ($this->data['packages'] ?? false) {
            ?>
                <form method="get" class="row g-2 mb-3 align-items-end">
                    <div class="col-md-3">
                        <label for="name" class="form-label mb-1">Nome</label>
                        <input type="text" name="name" id="name" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>" placeholder="Buscar por nome...">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <label for="per_page" class="form-label mb-1 me-2">Mostrar</label>
                        <select name="per_page" id="per_page" class="form-select form-select-sm w-auto mx-1" onchange="this.form.submit()">
                            <?php foreach ([10, 20, 50, 100] as $opt): ?>
                                <option value="<?= $opt ?>" <?= ($_GET['per_page'] ?? 10) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-label mb-1 ms-1">registros</span>
                    </div>
                    <div class="col-md-2 d-flex gap-2 align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filtrar</button>
                        <a href="list-packages" class="btn btn-secondary btn-sm"><i class="fa fa-times"></i> Limpar filtro</a>
                    </div>
                </form>
                <table class="table table-striped table-hover d-none d-md-table">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Nome</th>
                            <th scope="col" class="text-center">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        foreach ($this->data['packages'] as $package) {
                            extract($package); ?>
                            <tr>
                                <td><?php echo $id; ?></td>
                                <td><?php echo $name; ?></td>
                                <td class="text-center">
                                    <?php
                                    if (in_array('ViewPackage', $this->data['buttonPermission'])) {
                                        echo "<a href='{$_ENV['URL_ADM']}view-package/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a>";
                                    }
                                    if (in_array('UpdatePackage', $this->data['buttonPermission'])) {
                                        echo "<a href='{$_ENV['URL_ADM']}update-package/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                                    }
                                    if (in_array('DeletePackage', $this->data['buttonPermission'])) { ?>
                                        <form id="formDelete<?php echo $id; ?>" action="<?php echo $_ENV['URL_ADM']; ?>delete-package" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="id" id="id" value="<?php echo $id ?? ''; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?php echo $id; ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>
                                        </form>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <!-- Cards mobile -->
                <div class="d-md-none">
                    <?php foreach ($this->data['packages'] as $package) { extract($package); ?>
                    <div class="card mb-2 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong><?= $name ?></strong>
                                <span class="text-muted small">ID: <?= $id ?></span>
                            </div>
                            <div class="mt-2">
                                <?php if (in_array('ViewPackage', $this->data['buttonPermission'])) {
                                    echo "<a href='{$_ENV['URL_ADM']}view-package/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a>";
                                }
                                if (in_array('UpdatePackage', $this->data['buttonPermission'])) {
                                    echo "<a href='{$_ENV['URL_ADM']}update-package/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                                }
                                if (in_array('DeletePackage', $this->data['buttonPermission'])) { ?>
                                    <form id="formDeleteMobile<?= $id; ?>" action="<?= $_ENV['URL_ADM']; ?>delete-package" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
                                        <input type="hidden" name="id" id="id" value="<?= $id ?? ''; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?= $id; ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>
                                    </form>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                    <!-- Paginação e informações abaixo dos cards no mobile -->
                    <div class="d-flex d-md-none flex-column align-items-center w-100 mt-2">
                        <div class="text-secondary small w-100 text-center mb-1">
                            <?php if (!empty($this->data['pagination']['total'])): ?>
                                Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> de <?= $this->data['pagination']['total'] ?> registro(s)
                            <?php else: ?>
                                Exibindo <?= count($this->data['packages']); ?> registro(s) nesta página.
                            <?php endif; ?>
                        </div>
                        <div class="w-100 d-flex justify-content-center">
                            <?= $this->data['pagination']['html'] ?? '' ?>
                        </div>
                    </div>
                </div>


            <?php
                // Inclui o arquivo de paginação
                include_once './app/adms/Views/partials/pagination.php';
            } else { // Exibe mensagem se nenhum pacote for encontrado
                echo "<div class='alert alert-danger' role='alert'>Pacote não encontrado!</div>";
            } ?>

        </div>

    </div>
</div>