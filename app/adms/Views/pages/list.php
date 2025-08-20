<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_page');

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Páginas</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Páginas</li>

        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Listar</span>

            <span class="ms-auto">
                <?php
                if (in_array('CreatePage', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}create-page' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Cadastrar</a> ";
                }
                ?>
            </span>
        </div>

        <div class="card-body">

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';

            // Verifica se há página no array
            if ($this->data['pages'] ?? false) {
            ?>

                <form method="get" class="row g-2 mb-3 align-items-end" onsubmit="this.page.value=1;">
                    <div class="col-md-3">
                        <label for="nome" class="form-label mb-1">Nome</label>
                        <input type="text" name="nome" id="nome" class="form-control" value="<?= htmlspecialchars($_GET['nome'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="controller" class="form-label mb-1">Controller</label>
                        <input type="text" name="controller" id="controller" class="form-control" value="<?= htmlspecialchars($_GET['controller'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label mb-1">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Todos</option>
                            <option value="1" <?= (($_GET['status'] ?? '') === '1') ? 'selected' : '' ?>>Ativa</option>
                            <option value="0" <?= (($_GET['status'] ?? '') === '0') ? 'selected' : '' ?>>Inativa</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="publica" class="form-label mb-1">Pública</label>
                        <select name="publica" id="publica" class="form-select">
                            <option value="">Todas</option>
                            <option value="1" <?= (($_GET['publica'] ?? '') === '1') ? 'selected' : '' ?>>Sim</option>
                            <option value="0" <?= (($_GET['publica'] ?? '') === '0') ? 'selected' : '' ?>>Não</option>
                        </select>
                    </div>
                    <div class="col-auto mb-2">
                        <label for="per_page" class="form-label mb-1">Mostrar</label>
                        <div class="d-flex align-items-center">
                            <select name="per_page" id="per_page" class="form-select form-select-sm" style="min-width: 80px;" onchange="this.form.submit()">
                                <?php foreach ([10, 20, 50, 100] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= ($_GET['per_page'] ?? 10) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="form-label mb-1 ms-1">registros</span>
                        </div>
                    </div>
                    <div class="col-md-2 filtros-btns-row w-100 mt-2">
                        <button type="submit" class="btn btn-primary btn-sm btn-filtros-mobile"><i class="fa fa-search"></i> Filtrar</button>
                        <a href="?limpar_filtros=1" class="btn btn-secondary btn-sm btn-filtros-mobile"><i class="fa fa-times"></i> Limpar Filtros</a>
                    </div>
                    <input type="hidden" name="page" value="1">
                </form>

                <div class="table-responsive d-none d-md-block">
                    <table class="table table-striped table-hover" id="tabela">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Nome</th>
                                <th scope="col">Controller</th>
                                <th scope="col" class="d-none d-md-table-cell">Status</th>
                                <th scope="col" class="d-none d-md-table-cell">Pública</th>
                                <th scope="col" class="text-center">Ações</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php
                            // Percorre o array de página
                            foreach ($this->data['pages'] as $page) {

                                // Extrai variáveis do array de página
                                extract($page); ?>
                                <tr>
                                    <td><?php echo $id; ?></td>
                                    <td><?php echo $name; ?></td>
                                    <td><?php echo $controller_url; ?></td>
                                    <td class="d-none d-md-table-cell">
                                        <?php echo $page_status ? "<span class='badge text-bg-success'>Ativa</span>" : "<span class='badge text-bg-danger'>Inativa</span>"; ?>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <?php echo $public_page ? "<span class='badge text-bg-success'>Sim</span>" : "<span class='badge text-bg-danger'>Não</span>"; ?>
                                    </td>

                                    <td class="text-center">
                                        <?php
                                        if (in_array('ViewPage', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}view-page/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a>";
                                        }

                                        if (in_array('UpdatePage', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}update-page/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                                        }

                                        if (in_array('DeletePage', $this->data['buttonPermission'])) {
                                        ?>
                                            <form id="formDelete<?php echo $id; ?>" action="<?php echo $_ENV['URL_ADM']; ?>delete-page" method="POST" class="d-inline">

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
                    <?php if (!empty($this->data['pages'])): ?>
                        <?php foreach ($this->data['pages'] as $i => $page) { ?>
                            <div class="card mb-2 shadow-sm" style="border-radius: 10px;">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1"><b><?= htmlspecialchars($page['name']) ?></b></h6>
                                            <div class="mb-1"><b>Status:</b> <?= $page['page_status'] ? '<span class="badge bg-success">Ativa</span>' : '<span class="badge bg-danger">Inativa</span>' ?></div>
                                        </div>
                                        <button class="btn btn-outline-primary btn-sm ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#cardPageDetails<?= $i ?>" aria-expanded="false" aria-controls="cardPageDetails<?= $i ?>">Ver mais</button>
                                    </div>
                                    <div class="collapse mt-2" id="cardPageDetails<?= $i ?>">
                                        <div><b>ID:</b> <?= $page['id'] ?></div>
                                        <div><b>Controller:</b> <?= htmlspecialchars($page['controller_url']) ?></div>
                                        <div><b>Pública:</b> <?= $page['public_page'] ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-danger">Não</span>' ?></div>
                                        <div class="mt-2">
                                            <?php if (in_array('ViewPage', $this->data['buttonPermission'])): ?>
                                                <a href="<?= $_ENV['URL_ADM'] ?>view-page/<?= $page['id'] ?>" class="btn btn-primary btn-sm me-1 mb-1"><i class="fa-regular fa-eye"></i> Visualizar</a>
                                            <?php endif; ?>
                                            <?php if (in_array('UpdatePage', $this->data['buttonPermission'])): ?>
                                                <a href="<?= $_ENV['URL_ADM'] ?>update-page/<?= $page['id'] ?>" class="btn btn-warning btn-sm me-1 mb-1"><i class="fa-solid fa-pen-to-square"></i> Editar</a>
                                            <?php endif; ?>
                                            <?php if (in_array('DeletePage', $this->data['buttonPermission'])): ?>
                                                <form id="formDeleteMobile<?= $page['id'] ?>" action="<?= $_ENV['URL_ADM'] ?>delete-page" method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                    <input type="hidden" name="id" id="id" value="<?= $page['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?= $page['id'] ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php else: ?>
                        <div class='alert alert-danger' role='alert'>Nenhum registro encontrado.</div>
                    <?php endif; ?>
                </div>
                <!-- Paginação Desktop -->
                <div class="w-100 mt-2 d-none d-md-flex justify-content-between align-items-center">
                    <div class="text-secondary small">
                        <?php if (!empty($this->data['pagination']['total'])): ?>
                            Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> de <?= $this->data['pagination']['total'] ?> registro(s)
                        <?php else: ?>
                            Exibindo <?= count($this->data['pages']); ?> registro(s) nesta página.
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php include './app/adms/Views/partials/pagination.php'; ?>
                    </div>
                </div>
                <!-- Paginação Mobile -->
                <div class="d-flex d-md-none flex-column align-items-center w-100 mt-2">
                    <div class="text-secondary small w-100 text-center mb-1">
                        <?php if (!empty($this->data['pagination']['total'])): ?>
                            Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> de <?= $this->data['pagination']['total'] ?> registro(s)
                        <?php else: ?>
                            Exibindo <?= count($this->data['pages']); ?> registro(s) nesta página.
                        <?php endif; ?>
                    </div>
                    <div class="w-100 d-flex justify-content-center">
                        <?php include './app/adms/Views/partials/pagination.php'; ?>
                    </div>
                </div>

            <?php
                // Inclui o arquivo de paginação
                // include_once './app/adms/Views/partials/pagination.php'; // This line is removed as per the new_code
            } else { // Exibe mensagem se nenhum página for encontrado
                echo "<div class='alert alert-danger' role='alert'>Página não encontrada!</div>";
            } ?>

        </div>

    </div>
</div>

<!-- Remover o script do DataTable e qualquer referência a ele -->