<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_document');

// Recupera valores dos filtros para manter preenchido após submit
$filter_name = htmlspecialchars($_GET['name'] ?? '');
$filter_version = htmlspecialchars($_GET['version'] ?? '');
$filter_status = $_GET['status'] ?? '';
$filter_cod_doc = htmlspecialchars($_GET['cod_doc'] ?? '');
?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Documentos</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Documentos</li>

        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Listar</span>

            <span class="ms-auto">
                <?php
                if (in_array('CreateDocument', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}create-document' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Cadastrar</a> ";
                }
                ?>
            </span>
        </div>

        <div class="card-body">

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';
            ?>

            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-2">
                    <label for="cod_doc" class="form-label mb-1">Código</label>
                    <input type="text" name="cod_doc" id="cod_doc" value="<?= $filter_cod_doc ?>" class="form-control form-control-sm" placeholder="Buscar por código...">
                </div>
                <div class="col-md-3">
                    <label for="name" class="form-label mb-1">Nome</label>
                    <input type="text" name="name" id="name" value="<?= $filter_name ?>" class="form-control form-control-sm" placeholder="Buscar por nome...">
                </div>
                <div class="col-md-2">
                    <label for="version" class="form-label mb-1">Versão</label>
                    <input type="text" name="version" id="version" value="<?= $filter_version ?>" class="form-control form-control-sm" placeholder="Buscar por versão...">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label mb-1">Status</label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <option value="" <?= $filter_status === '' ? 'selected' : '' ?>>Todos</option>
                        <option value="1" <?= $filter_status === '1' ? 'selected' : '' ?>>Ativa</option>
                        <option value="0" <?= $filter_status === '0' ? 'selected' : '' ?>>Inativa</option>
                    </select>
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
                    <a href="list-documents" class="btn btn-secondary btn-sm"><i class="fa fa-times"></i> Limpar filtro</a>
                </div>
            </form>

            <?php
            // Verifica se há documento no array
            if ($this->data['documents'] ?? false) {
            ?>

                <!-- Tabela Desktop -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-striped table-hover" id="tabela">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Código</th>
                                <th scope="col">Nome</th>
                                <th scope="col">Versão</th>
                                <th scope="col" class="d-none d-md-table-cell">Status</th>
                                <th scope="col" class="text-center">Ações</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php
                            // Percorre o array de documento
                            foreach ($this->data['documents'] as $document) {
                                extract($document); ?>
                                <tr>
                                    <td><?php echo $id; ?></td>
                                    <td><?php echo $cod_doc; ?></td>
                                    <td><?php echo $name_doc; ?></td>
                                    <td><?php echo $version; ?></td>
                                    <td class="d-none d-md-table-cell">
                                        <?php echo $active ? "<span class='badge text-bg-success'>Ativa</span>" : "<span class='badge text-bg-danger'>Inativa</span>"; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                         if (in_array('ListDocumentPositions', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}list-document-positions/$id' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-lock-open'></i> Cargos/Treinamentos</a>";
                                        }
                                        if (in_array('ViewDocument', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}view-document/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a>";
                                        }
                                        if (in_array('UpdateDocument', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}update-document/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                                        }
                                        if (in_array('DeleteDocument', $this->data['buttonPermission'])) {
                                        ?>
                                            <form id="formDelete<?php echo $id; ?>" action="<?php echo $_ENV['URL_ADM']; ?>delete-document" method="POST" class="d-inline">
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
                </div>

                <!-- CARDS MOBILE -->
                <div class="d-block d-md-none">
                    <?php foreach ($this->data['documents'] as $i => $document) { extract($document); ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-1"><b><?= $name_doc ?></b></h5>
                                        <div class="mb-1"><b>ID:</b> <?= $id ?></div>
                                        <div class="mb-1"><b>Código:</b> <?= $cod_doc ?></div>
                                        <div class="mb-1"><b>Versão:</b> <?= $version ?></div>
                                        <div class="mb-1"><b>Status:</b> <?= $active ? "<span class='badge text-bg-success'>Ativa</span>" : "<span class='badge text-bg-danger'>Inativa</span>" ?></div>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <?php
                                    if (in_array('ListDocumentPositions', $this->data['buttonPermission'])) {
                                        echo "<a href='{$_ENV['URL_ADM']}list-document-positions/$id' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-lock-open'></i> Cargos/Treinamentos</a>";
                                    }
                                    if (in_array('ViewDocument', $this->data['buttonPermission'])) {
                                        echo "<a href='{$_ENV['URL_ADM']}view-document/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a>";
                                    }
                                    if (in_array('UpdateDocument', $this->data['buttonPermission'])) {
                                        echo "<a href='{$_ENV['URL_ADM']}update-document/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                                    }
                                    if (in_array('DeleteDocument', $this->data['buttonPermission'])) {
                                    ?>
                                        <form id="formDeleteMobile<?= $id; ?>" action="<?= $_ENV['URL_ADM']; ?>delete-document" method="POST" class="d-inline">
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
                                Exibindo <?= count($this->data['documents']); ?> registro(s) nesta página.
                            <?php endif; ?>
                        </div>
                        <div class="w-100 d-flex justify-content-center">
                            <?= $this->data['pagination']['html'] ?? '' ?>
                        </div>
                    </div>
                </div>

                <!-- Paginação Desktop -->
                <div class="d-none d-md-flex justify-content-between align-items-center mt-2">
                    <div class="text-secondary small">
                        <?php if (!empty($this->data['pagination']['total'])): ?>
                            Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> de <?= $this->data['pagination']['total'] ?> registro(s)
                        <?php else: ?>
                            Exibindo <?= count($this->data['documents']); ?> registro(s) nesta página.
                        <?php endif; ?>
                    </div>
                    <div>
                        <?= $this->data['pagination']['html'] ?? '' ?>
                    </div>
                </div>

            <?php
            } else { // Exibe mensagem se nenhum página for encontrado
                echo "<div class='alert alert-danger' role='alert'>Documento não encontrado!</div>";
            } ?>

        </div>

    </div>
</div>

<script type="text/javascript">
    // DataTables removido para padronização do sistema
</script>