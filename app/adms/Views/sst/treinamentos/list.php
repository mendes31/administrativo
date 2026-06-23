<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstTreinamentoNrHelper;

function formatCellValue(string $col, mixed $value): string
{
    if ($value === null || $value === '') {
        return '-';
    }
    if ($col === 'carga_horaria_minutos' && is_numeric($value)) {
        $h = floor((int) $value / 60);
        $m = (int) $value % 60;
        return $h > 0 ? sprintf('%dh %02dmin', $h, $m) : sprintf('%d min', $m);
    }
    if ($col === 'validade_meses' && is_numeric($value)) {
        return (string) $value . ' meses';
    }
    return htmlspecialchars((string) $value);
}

$perms = $this->data['buttonPermission'] ?? [];
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_sst_treinamentos');
$filtersId = 'sstFiltersTreinamento';
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-graduation-cap me-2"></i>Treinamentos SST</h2>
        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item">Treinamentos</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Listar</span>
            <span class="ms-auto">
                <?php if (in_array('SstCreateTreinamento', $perms)): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-create-treinamento" class="btn btn-success btn-sm"><i class="fa-regular fa-square-plus"></i> Cadastrar</a>
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
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Nome ou código"
                               value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <label class="form-label" style="font-size:.7rem;">NR</label>
                        <select name="nr_referencia" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            <?php foreach (SstTreinamentoNrHelper::all() as $nr): ?>
                                <option value="<?= htmlspecialchars($nr) ?>" <?= ($this->data['filters']['nr_referencia'] ?? '') === $nr ? 'selected' : '' ?>><?= htmlspecialchars($nr) ?></option>
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
                        <a href="<?= $_ENV['URL_ADM']; ?>sst-list-treinamentos" class="btn btn-secondary btn-sm">Limpar</a>
                    </div>
                </form>
            </div>
            <?php if (!empty($this->data['items'])): ?>
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead><tr>
                            <th>Código</th><th>Nome</th><th>NR</th><th>Tipo</th><th>Validade</th><th>Status</th><th class="text-center">Ações</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($this->data['items'] as $item): $id = (int)($item['id'] ?? 0); ?>
                            <tr>
                                <td><?= formatCellValue('codigo', $item['codigo'] ?? null) ?></td>
                                <td><?= formatCellValue('nome', $item['nome'] ?? null) ?></td>
                                <td><?= formatCellValue('nr_referencia', $item['nr_referencia'] ?? null) ?></td>
                                <td><?= formatCellValue('tipo', $item['tipo'] ?? null) ?></td>
                                <td><?= formatCellValue('validade_meses', $item['validade_meses'] ?? null) ?></td>
                                <td><span class="badge bg-<?= ($item['status'] ?? '') === 'Ativo' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($item['status'] ?? '') ?></span></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <?php if (in_array('SstViewTreinamento', $perms)): ?>
                                            <a href="<?= $_ENV['URL_ADM']; ?>sst-view-treinamento/<?= $id ?>" class="btn btn-info btn-sm"><i class="fa-regular fa-eye"></i></a>
                                        <?php endif; ?>
                                        <?php if (in_array('SstUpdateTreinamento', $perms)): ?>
                                            <a href="<?= $_ENV['URL_ADM']; ?>sst-update-treinamento/<?= $id ?>" class="btn btn-warning btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>
                                        <?php endif; ?>
                                        <?php if (in_array('SstDeleteTreinamento', $perms)): ?>
                                            <form action="<?= $_ENV['URL_ADM']; ?>sst-delete-treinamento" method="POST" class="d-inline" onsubmit="return confirm('Excluir registro?');">
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
                        $viewUrl = $_ENV['URL_ADM'] . 'sst-view-treinamento/' . $id;
                    ?>
                        <div class="card mb-2 shadow-sm" onclick="window.location.href='<?= $viewUrl ?>';" style="cursor:pointer;">
                            <div class="card-body py-2 px-3">
                                <div class="fw-bold small"><?= formatCellValue('nome', $item['nome'] ?? $id) ?></div>
                                <?php if (!empty($item['codigo'])): ?><div class="small text-muted"><?= formatCellValue('codigo', $item['codigo']) ?></div><?php endif; ?>
                                <?php if (!empty($item['nr_referencia'])): ?><span class="badge bg-light text-dark border"><?= htmlspecialchars($item['nr_referencia']) ?></span><?php endif; ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars($item['status'] ?? '') ?></span>
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
