<?php

use App\adms\Helpers\CSRFHelper;

function formatCellValue(string $col, mixed $value): string
{
    if ($value === null || $value === '') {
        return '-';
    }
    if (is_bool($value) || $col === 'obrigatorio' || $col === 'termo_assinado') {
        return ($value === true || $value === 1 || $value === '1') ? 'Sim' : 'Não';
    }
    if (str_contains($col, 'data_') && is_string($value)) {
        return strlen($value) > 10 ? date('d/m/Y H:i', strtotime($value)) : date('d/m/Y', strtotime($value));
    }
    return htmlspecialchars((string) $value);
}

$entity = $this->data['entity'];
$perms = $this->data['buttonPermission'] ?? [];
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_sst_riscos_cargo');
$filtersId = 'sstFiltersRiscoCargo';
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-shield-virus me-2"></i><?= htmlspecialchars('Riscos por Cargo') ?></h2>
        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><?= htmlspecialchars('Riscos por Cargo') ?></li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Listar</span>
            <span class="ms-auto">
                <?php if (in_array('SstCreateRiscoCargo', $perms)): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-create-risco-cargo" class="btn btn-success btn-sm"><i class="fa-regular fa-square-plus"></i> Cadastrar</a>
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
                        <input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>">
                    </div>
                    
                    
                    <div class="col-12 col-sm-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filtrar</button>
                        <a href="<?= $_ENV['URL_ADM']; ?>sst-list-risco-cargo" class="btn btn-secondary btn-sm">Limpar</a>
                    </div>
                </form>
            </div>
            <?php if (!empty($this->data['items'])): ?>
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead><tr>
                            <th>Id</th>
<th>Cargo</th>
<th>Departamento</th>
<th>Risco</th>
<th>Nivel</th>

                            <th class="text-center">Ações</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($this->data['items'] as $item):
                            $id = (int)($item['id'] ?? 0);
                        ?>
                            <tr>
                                <td><?= formatCellValue('id', $item['id'] ?? null) ?></td>
<td><?= formatCellValue('cargo_nome', $item['cargo_nome'] ?? null) ?></td>
<td><?= formatCellValue('departamento_nome', $item['departamento_nome'] ?? null) ?></td>
<td><?= formatCellValue('risco_nome', $item['risco_nome'] ?? null) ?></td>
<td><?= formatCellValue('nivel', $item['nivel'] ?? null) ?></td>

                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        
                                        <?php if (in_array('SstUpdateRiscoCargo', $perms)): ?>
                                            <a href="<?= $_ENV['URL_ADM']; ?>sst-update-risco-cargo/<?= $id ?>" class="btn btn-warning btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>
                                        <?php endif; ?>
                                        <?php if (in_array('SstDeleteRiscoCargo', $perms)): ?>
                                            <form action="<?= $_ENV['URL_ADM']; ?>sst-delete-risco-cargo" method="POST" class="d-inline" onsubmit="return confirm('Excluir registro?');">
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
                        $canView = in_array('SstViewRiscoCargo', $perms);
                        $viewUrl = $_ENV['URL_ADM'] . 'sst-view-risco-cargo/' . $id;
                    ?>
                        <div class="card mb-2 shadow-sm"<?php if ($canView): ?> onclick="window.location.href='<?= $viewUrl ?>';" style="cursor:pointer;"<?php endif; ?>>
                            <div class="card-body py-2 px-3">
                                <div class="fw-bold small"><?= formatCellValue('cargo_nome', $item['cargo_nome'] ?? $id) ?></div>
                                <?php if (!empty($item['departamento_nome'])): ?>
                                    <div class="small text-muted"><?= formatCellValue('departamento_nome', $item['departamento_nome']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($item['status'])): ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($item['status']) ?></span>
                                <?php endif; ?>
                                <div class="d-flex gap-1 mt-2 pt-2 border-top" onclick="event.stopPropagation();">
                                    <?php if ($canView): ?><a href="<?= $viewUrl ?>" class="btn btn-outline-info btn-sm flex-fill">Ver</a><?php endif; ?>
                                    <?php if (in_array('SstUpdateRiscoCargo', $perms)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-update-risco-cargo/<?= $id ?>" class="btn btn-outline-warning btn-sm flex-fill">Editar</a><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex justify-content-end mt-2 d-none d-md-flex"><?= $this->data['pagination']['html'] ?? '' ?></div>
                <div class="d-flex justify-content-center mt-2 d-md-none"><?= $this->data['pagination']['html'] ?? '' ?></div>
            <?php else: ?>
                <div class="alert alert-warning">Nenhum registro encontrado.</div>
            <?php endif; ?>
        </div>
    </div>
</div>