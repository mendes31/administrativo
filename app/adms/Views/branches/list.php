<?php

use App\adms\Helpers\CSRFHelper;

$csrf_token = CSRFHelper::generateCSRFToken('form_delete_branch');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Filiais</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Filiais</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Listar</span>
            <span class="ms-auto">
            <?php
                if (in_array('CreateBranch', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}create-branch' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Cadastrar</a> ";
                }
            ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <?php if ($this->data['branches'] ?? false) { ?>
                <form method="get" class="row g-2 mb-3 align-items-end">
                    <div class="col-md-2">
                        <label for="name" class="form-label mb-1">Nome</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($this->data['filtros']['name'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="code" class="form-label mb-1">Código</label>
                        <input type="text" name="code" id="code" class="form-control" value="<?= htmlspecialchars($this->data['filtros']['code'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="email" class="form-label mb-1">E-mail</label>
                        <input type="text" name="email" id="email" class="form-control" value="<?= htmlspecialchars($this->data['filtros']['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="active" class="form-label mb-1">Status</label>
                        <select name="active" id="active" class="form-select">
                            <option value="">Todos</option>
                            <option value="1" <?= ($this->data['filtros']['active'] ?? '') === '1' ? 'selected' : '' ?>>Ativo</option>
                            <option value="0" <?= ($this->data['filtros']['active'] ?? '') === '0' ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <label for="per_page" class="form-label mb-1 me-2">Mostrar</label>
                        <select name="per_page" id="per_page" class="form-select form-select-sm w-auto mx-1" onchange="this.form.submit()">
                            <?php foreach ([10, 20, 50, 100] as $opt): ?>
                                <option value="<?= $opt ?>" <?= ($this->data['per_page'] ?? 10) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-label mb-1 ms-1">registros</span>
                    </div>
                    <div class="col-md-2 mt-2">
                        <button type="submit" class="btn btn-primary mt-4">Filtrar</button>
                        <a href="?limpar_filtros=1" class="btn btn-secondary mt-4 ms-2">Limpar Filtros</a>
                    </div>
                </form>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Nome</th>
                            <th scope="col">Código</th>
                            <th scope="col">Endereço</th>
                            <th scope="col">Telefone</th>
                            <th scope="col">E-mail</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->data['branches'] as $branch) { extract($branch); ?>
                            <tr>
                                <td><?php echo $id; ?></td>
                                <td><?php echo $name; ?></td>
                                <td><?php echo $code; ?></td>
                                <td><?php echo $address; ?></td>
                                <td><?php echo $phone; ?></td>
                                <td><?php echo $email; ?></td>
                                <td><?php echo $active ? 'Ativo' : 'Inativo'; ?></td>
                                <td class="text-center">
                                    <?php
                                    if (in_array('ViewBranch', $this->data['buttonPermission'])) {
                                        echo "<a href='{$_ENV['URL_ADM']}view-branch/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a>";
                                    }
                                    if (in_array('UpdateBranch', $this->data['buttonPermission'])) {
                                        echo "<a href='{$_ENV['URL_ADM']}update-branch/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                                    }
                                    if (in_array('DeleteBranch', $this->data['buttonPermission'])) {
                                    ?>
                                        <form id="formDelete<?php echo $id; ?>" action="<?php echo $_ENV['URL_ADM']; ?>delete-branch" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="id" id="id" value="<?php echo $id ?? ''; ?>">
                                            <input type="hidden" name="name" id="name" value="<?php echo $name ?? ''; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?php echo $id; ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>
                                        </form>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="text-secondary small">
                        <?php if (!empty($this->data['pagination']['total'])): ?>
                            Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> de <?= $this->data['pagination']['total'] ?> registro(s)
                        <?php else: ?>
                            Exibindo <?= count($this->data['branches']); ?> registro(s) nesta página.
                        <?php endif; ?>
                    </div>
                    <div>
                        <?= $this->data['pagination']['html'] ?? '' ?>
                    </div>
                </div>
            <?php } else {
                echo "<div class='alert alert-danger' role='alert'>Nenhuma Filial encontrada!</div>";
            } ?>
        </div>
    </div>
</div> 