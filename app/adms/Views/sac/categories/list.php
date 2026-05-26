<?php

use App\adms\Helpers\CSRFHelper;

$csrf_token = CSRFHelper::generateCSRFToken('form_delete_sac_category');

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-tags me-2"></i>Categorias de Chamados</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sac-dashboard" class="text-decoration-none">SAC</a></li>
            <li class="breadcrumb-item">Categorias</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Listar</span>

            <span class="ms-auto d-flex flex-wrap gap-1">
                <?php
                if (in_array('SacCreateCategory', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}sac-create-category' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Cadastrar</a> ";
                }
                ?>
            </span>
        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <!-- Filtros -->
            <div class="d-md-none mb-2">
                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#sacCategoriesFilters" aria-expanded="false">
                    <i class="fa fa-filter me-1"></i> Abrir filtros
                </button>
            </div>
            <div class="collapse d-md-block" id="sacCategoriesFilters">
                <form method="get" class="row g-2 mb-3 align-items-end">
                    <div class="col-8 col-md-4">
                        <label for="search" class="form-label" style="font-size:.7rem;">Buscar</label>
                        <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="Nome da categoria..." value="<?= htmlspecialchars($this->data['filtros']['search'] ?? '') ?>">
                    </div>
                    <div class="col-4 col-md-2">
                        <label for="status" class="form-label" style="font-size:.7rem;">Status</label>
                        <select name="status" id="status" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="1" <?= ($this->data['filtros']['status'] ?? '') === '1' ? 'selected' : '' ?>>Ativo</option>
                            <option value="0" <?= ($this->data['filtros']['status'] ?? '') === '0' ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="col-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filtrar</button>
                        <a href="<?= $_ENV['URL_ADM']; ?>sac-list-categories" class="btn btn-secondary btn-sm"><i class="fa fa-times"></i> Limpar</a>
                    </div>
                </form>
            </div>

            <?php if ($this->data['categories'] ?? false) { ?>

                <!-- Desktop: tabela -->
                <div class="d-none d-md-block">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th class="text-center" style="width: 80px;">Cor</th>
                                    <th class="text-center" style="width: 120px;">SLA Resposta</th>
                                    <th class="text-center" style="width: 120px;">SLA Resolução</th>
                                    <th class="text-center" style="width: 80px;">Ordem</th>
                                    <th class="text-center" style="width: 90px;">Status</th>
                                    <th class="text-center" style="width: 120px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->data['categories'] as $category) {
                                    extract($category);
                                ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($icon)): ?>
                                                <i class="<?= htmlspecialchars($icon) ?> me-1"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($name) ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge" style="background-color: <?= htmlspecialchars($color ?? '#0d6efd') ?>;">&nbsp;&nbsp;&nbsp;</span>
                                        </td>
                                        <td class="text-center"><?= isset($default_sla_response_hours) ? $default_sla_response_hours . 'h' : '—' ?></td>
                                        <td class="text-center"><?= isset($default_sla_resolution_hours) ? $default_sla_resolution_hours . 'h' : '—' ?></td>
                                        <td class="text-center"><?= $display_order ?? 0 ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= !empty($is_active) ? 'bg-success' : 'bg-danger' ?>">
                                                <?= !empty($is_active) ? 'Ativo' : 'Inativo' ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <?php
                                                if (in_array('SacUpdateCategory', $this->data['buttonPermission'])) {
                                                    echo "<a href='{$_ENV['URL_ADM']}sac-update-category/{$id}' class='btn btn-warning btn-sm' title='Editar'><i class='fa-regular fa-pen-to-square'></i></a>";
                                                }
                                                if (in_array('SacDeleteCategory', $this->data['buttonPermission'])) {
                                                ?>
                                                    <form action="<?= $_ENV['URL_ADM']; ?>sac-delete-category" method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
                                                        <input type="hidden" name="id" value="<?= $id; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Apagar" onclick="return confirm('Tem certeza que deseja excluir?')"><i class="fa-regular fa-trash-can"></i></button>
                                                    </form>
                                                <?php } ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile: cards -->
                <div class="d-block d-md-none">
                    <?php foreach ($this->data['categories'] as $category) {
                        extract($category);
                        $canUpdate = in_array('SacUpdateCategory', $this->data['buttonPermission']);
                        $canDelete = in_array('SacDeleteCategory', $this->data['buttonPermission']);
                        $hasActions = $canUpdate || $canDelete;
                    ?>
                        <div class="card mb-2 shadow-sm">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="badge me-2" style="background-color: <?= htmlspecialchars($color ?? '#0d6efd') ?>;">&nbsp;&nbsp;</span>
                                    <span class="fw-bold small">
                                        <?php if (!empty($icon)): ?><i class="<?= htmlspecialchars($icon) ?> me-1"></i><?php endif; ?>
                                        <?= htmlspecialchars($name) ?>
                                    </span>
                                    <span class="badge <?= !empty($is_active) ? 'bg-success' : 'bg-danger' ?> ms-2"><?= !empty($is_active) ? 'Ativo' : 'Inativo' ?></span>
                                </div>
                                <div class="d-flex flex-wrap gap-2 small text-muted">
                                    <span><i class="fas fa-reply me-1"></i>Resp: <?= isset($default_sla_response_hours) ? $default_sla_response_hours . 'h' : '—' ?></span>
                                    <span><i class="fas fa-check-circle me-1"></i>Resol: <?= isset($default_sla_resolution_hours) ? $default_sla_resolution_hours . 'h' : '—' ?></span>
                                    <span><i class="fas fa-sort-numeric-down me-1"></i>Ordem: <?= $display_order ?? 0 ?></span>
                                </div>
                                <?php if ($hasActions): ?>
                                    <div class="d-flex gap-1 mt-2 pt-2 border-top">
                                        <?php if ($canUpdate): ?>
                                            <a href="<?= $_ENV['URL_ADM'] ?>sac-update-category/<?= $id ?>" class="btn btn-outline-warning btn-sm flex-fill"><i class="fas fa-edit me-1"></i>Editar</a>
                                        <?php endif; ?>
                                        <?php if ($canDelete): ?>
                                            <form action="<?= $_ENV['URL_ADM'] ?>sac-delete-category" method="POST" class="flex-fill">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <input type="hidden" name="id" value="<?= $id ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('Excluir categoria?');"><i class="fas fa-trash me-1"></i>Excluir</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <!-- Paginação -->
                <div class="d-flex justify-content-end d-none d-md-flex">
                    <?= $this->data['pagination']['html'] ?? '' ?>
                </div>
                <div class="d-flex justify-content-center mt-2 d-md-none">
                    <?php
                    $paginationHtml = $this->data['pagination']['html'] ?? '';
                    if ($paginationHtml) {
                        $paginationHtml = preg_replace('/class="pagination(.*?)"/', 'class="pagination pagination-sm$1"', $paginationHtml, 1);
                        echo $paginationHtml;
                    }
                    ?>
                </div>

            <?php } else {
                echo "<div class='alert alert-danger' role='alert'>Nenhuma categoria encontrada.</div>";
            } ?>

        </div>
    </div>
</div>
