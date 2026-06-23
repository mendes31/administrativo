<?php

use App\adms\Helpers\CSRFHelper;

function formatGheCell(string $col, mixed $value): string
{
    if ($value === null || $value === '') {
        return '-';
    }
    return htmlspecialchars((string) $value);
}

$perms = $this->data['buttonPermission'] ?? [];
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_sst_ghe');
$filtersId = 'sstFiltersGhe';
$departments = $this->data['departments'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-industry me-2"></i>GHE — Ambientes de Trabalho</h2>
        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item">GHE</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Listar</span>
            <span class="ms-auto">
                <?php if (in_array('SstCreateGhe', $perms)): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-create-ghe" class="btn btn-success btn-sm"><i class="fa-regular fa-square-plus"></i> Cadastrar</a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <div class="d-md-none mb-2">
                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $filtersId ?>">
                    <i class="fa fa-filter me-1"></i> Filtros
                </button>
            </div>
            <div class="collapse d-md-block" id="<?= $filtersId ?>">
                <form method="get" class="row g-2 mb-3 align-items-end">
                    <div class="col-6 col-sm-4 col-md-3">
                        <label class="form-label" style="font-size:.7rem;">Pesquisar</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Nome, código ou local"
                               value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-sm-4 col-md-3">
                        <label class="form-label" style="font-size:.7rem;">Departamento</label>
                        <select name="adms_department_id" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <?php foreach ($departments as $dep): ?>
                                <option value="<?= (int)($dep['id'] ?? 0) ?>" <?= (string)($this->data['filters']['adms_department_id'] ?? '') === (string)($dep['id'] ?? '') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dep['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <label class="form-label" style="font-size:.7rem;">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="Ativo" <?= ($this->data['filters']['status'] ?? '') === 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                            <option value="Inativo" <?= ($this->data['filters']['status'] ?? '') === 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filtrar</button>
                        <a href="<?= $_ENV['URL_ADM']; ?>sst-list-ghe" class="btn btn-secondary btn-sm">Limpar</a>
                    </div>
                </form>
            </div>
            <?php if (!empty($this->data['items'])): ?>
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead><tr>
                            <th>Código</th><th>Nome</th><th>Local</th><th>Departamento</th><th>Colaboradores</th><th>Status</th><th class="text-center">Ações</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($this->data['items'] as $item): $id = (int)($item['id'] ?? 0); ?>
                            <tr>
                                <td><?= formatGheCell('codigo', $item['codigo'] ?? null) ?></td>
                                <td><?= formatGheCell('nome', $item['nome'] ?? null) ?></td>
                                <td><?= formatGheCell('ambiente_local', $item['ambiente_local'] ?? null) ?></td>
                                <td><?= formatGheCell('departamento_nome', $item['departamento_nome'] ?? null) ?></td>
                                <td><?= (int)($item['total_colaboradores'] ?? 0) ?></td>
                                <td><span class="badge bg-<?= ($item['status'] ?? '') === 'Ativo' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($item['status'] ?? '') ?></span></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <?php if (in_array('SstViewGhe', $perms)): ?>
                                            <a href="<?= $_ENV['URL_ADM']; ?>sst-view-ghe/<?= $id ?>" class="btn btn-info btn-sm"><i class="fa-regular fa-eye"></i></a>
                                        <?php endif; ?>
                                        <?php if (in_array('SstUpdateGhe', $perms)): ?>
                                            <a href="<?= $_ENV['URL_ADM']; ?>sst-update-ghe/<?= $id ?>" class="btn btn-warning btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>
                                        <?php endif; ?>
                                        <?php if (in_array('SstDeleteGhe', $perms)): ?>
                                            <form action="<?= $_ENV['URL_ADM']; ?>sst-delete-ghe" method="POST" class="d-inline" onsubmit="return confirm('Excluir GHE? Colaboradores e treinamentos vinculados serão removidos.');">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <input type="hidden" name="id" value="<?= $id ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fa-regular fa-trash-can"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-block d-md-none">
                    <?php foreach ($this->data['items'] as $item):
                        $id = (int)($item['id'] ?? 0);
                        $viewUrl = $_ENV['URL_ADM'] . 'sst-view-ghe/' . $id;
                    ?>
                        <div class="card mb-2 shadow-sm" onclick="window.location.href='<?= $viewUrl ?>';" style="cursor:pointer;">
                            <div class="card-body py-2 px-3">
                                <div class="fw-bold small"><?= formatGheCell('nome', $item['nome'] ?? $id) ?></div>
                                <?php if (!empty($item['ambiente_local'])): ?><div class="small text-muted"><?= formatGheCell('ambiente_local', $item['ambiente_local']) ?></div><?php endif; ?>
                                <span class="badge bg-light text-dark border"><?= (int)($item['total_colaboradores'] ?? 0) ?> colab.</span>
                                <span class="badge bg-secondary"><?= htmlspecialchars($item['status'] ?? '') ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex justify-content-end mt-2 d-none d-md-flex"><?= $this->data['pagination']['html'] ?? '' ?></div>
                <div class="d-flex justify-content-center mt-2 d-md-none"><?= $this->data['pagination']['html'] ?? '' ?></div>
            <?php else: ?>
                <div class="alert alert-warning">Nenhum GHE encontrado.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
