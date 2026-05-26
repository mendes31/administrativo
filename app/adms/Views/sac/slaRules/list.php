<?php

use App\adms\Helpers\CSRFHelper;

$csrf_token = CSRFHelper::generateCSRFToken('form_delete_sac_sla_rule');

$priorityLabels = [
    'Baixa' => 'Baixa',
    'Média' => 'Média',
    'Alta' => 'Alta',
    'Urgente' => 'Urgente',
];

$priorityBadges = [
    'Baixa' => 'secondary',
    'Média' => 'primary',
    'Alta' => 'warning',
    'Urgente' => 'danger',
];

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-stopwatch me-2"></i>Regras de SLA</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sac-dashboard" class="text-decoration-none">SAC</a></li>
            <li class="breadcrumb-item">Regras de SLA</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Listar</span>

            <span class="ms-auto d-flex flex-wrap gap-1">
                <?php
                if (in_array('SacCreateSlaRule', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}sac-create-sla-rule' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Cadastrar</a> ";
                }
                ?>
            </span>
        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <!-- Filtros -->
            <div class="d-md-none mb-2">
                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#sacSlaFilters" aria-expanded="false">
                    <i class="fa fa-filter me-1"></i> Abrir filtros
                </button>
            </div>
            <div class="collapse d-md-block" id="sacSlaFilters">
                <form method="get" class="row g-2 mb-3 align-items-end">
                    <div class="col-8 col-md-4">
                        <label for="search" class="form-label" style="font-size:.7rem;">Buscar</label>
                        <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="Nome da regra..." value="<?= htmlspecialchars($this->data['filtros']['search'] ?? '') ?>">
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
                        <a href="<?= $_ENV['URL_ADM']; ?>sac-list-sla-rules" class="btn btn-secondary btn-sm"><i class="fa fa-times"></i> Limpar</a>
                    </div>
                </form>
            </div>

            <?php if ($this->data['rules'] ?? false) { ?>

                <!-- Desktop: tabela -->
                <div class="d-none d-md-block">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th class="text-center">Categoria</th>
                                    <th class="text-center">Prioridade</th>
                                    <th class="text-center" style="width: 100px;">Resposta</th>
                                    <th class="text-center" style="width: 100px;">Resolução</th>
                                    <th class="text-center" style="width: 110px;">Escalonamento</th>
                                    <th class="text-center" style="width: 90px;">Status</th>
                                    <th class="text-center" style="width: 120px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->data['rules'] as $rule) {
                                    extract($rule);
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($name) ?></td>
                                        <td class="text-center"><?= !empty($category_name) ? htmlspecialchars($category_name) : '<span class="text-muted">Todas</span>' ?></td>
                                        <td class="text-center"><?= !empty($priority) ? ($priorityLabels[$priority] ?? htmlspecialchars($priority)) : '<span class="text-muted">Todas</span>' ?></td>
                                        <td class="text-center"><?= $response_time_hours ?>h</td>
                                        <td class="text-center"><?= $resolution_time_hours ?>h</td>
                                        <td class="text-center">
                                            <span class="badge <?= !empty($escalation_enabled) ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                                <?= !empty($escalation_enabled) ? 'Sim' : 'Não' ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= !empty($is_active) ? 'bg-success' : 'bg-danger' ?>">
                                                <?= !empty($is_active) ? 'Ativo' : 'Inativo' ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <?php
                                                if (in_array('SacUpdateSlaRule', $this->data['buttonPermission'])) {
                                                    echo "<a href='{$_ENV['URL_ADM']}sac-update-sla-rule/{$id}' class='btn btn-warning btn-sm' title='Editar'><i class='fa-regular fa-pen-to-square'></i></a>";
                                                }
                                                if (in_array('SacDeleteSlaRule', $this->data['buttonPermission'])) {
                                                ?>
                                                    <form action="<?= $_ENV['URL_ADM']; ?>sac-delete-sla-rule" method="POST" class="d-inline">
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
                    <?php foreach ($this->data['rules'] as $rule) {
                        extract($rule);
                        $canUpdate = in_array('SacUpdateSlaRule', $this->data['buttonPermission']);
                        $canDelete = in_array('SacDeleteSlaRule', $this->data['buttonPermission']);
                        $hasActions = $canUpdate || $canDelete;
                        $pBadge = $priorityBadges[$priority ?? ''] ?? 'secondary';
                    ?>
                        <div class="card mb-2 shadow-sm">
                            <div class="card-body py-2 px-3">
                                <div class="fw-bold small mb-1"><?= htmlspecialchars($name) ?></div>
                                <div class="d-flex flex-wrap gap-1 mb-1">
                                    <span class="badge <?= !empty($is_active) ? 'bg-success' : 'bg-danger' ?>"><?= !empty($is_active) ? 'Ativo' : 'Inativo' ?></span>
                                    <?php if (!empty($priority)): ?>
                                        <span class="badge bg-<?= $pBadge ?>"><?= $priorityLabels[$priority] ?? htmlspecialchars($priority) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border">Todas prioridades</span>
                                    <?php endif; ?>
                                    <?php if (!empty($category_name)): ?>
                                        <span class="badge bg-info"><?= htmlspecialchars($category_name) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border">Todas categorias</span>
                                    <?php endif; ?>
                                    <?php if (!empty($escalation_enabled)): ?>
                                        <span class="badge bg-warning text-dark"><i class="fas fa-level-up-alt me-1"></i>Escalonamento</span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex flex-wrap gap-3 small text-muted">
                                    <span><i class="fas fa-reply me-1"></i>Resp: <?= $response_time_hours ?>h</span>
                                    <span><i class="fas fa-check-circle me-1"></i>Resol: <?= $resolution_time_hours ?>h</span>
                                </div>
                                <?php if ($hasActions): ?>
                                    <div class="d-flex gap-1 mt-2 pt-2 border-top">
                                        <?php if ($canUpdate): ?>
                                            <a href="<?= $_ENV['URL_ADM'] ?>sac-update-sla-rule/<?= $id ?>" class="btn btn-outline-warning btn-sm flex-fill"><i class="fas fa-edit me-1"></i>Editar</a>
                                        <?php endif; ?>
                                        <?php if ($canDelete): ?>
                                            <form action="<?= $_ENV['URL_ADM'] ?>sac-delete-sla-rule" method="POST" class="flex-fill">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <input type="hidden" name="id" value="<?= $id ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('Excluir regra?');"><i class="fas fa-trash me-1"></i>Excluir</button>
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
                echo "<div class='alert alert-danger' role='alert'>Nenhuma regra de SLA encontrada.</div>";
            } ?>

        </div>
    </div>
</div>
