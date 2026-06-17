<?php
use App\adms\Helpers\CSRFHelper;
$perms = $this->data['buttonPermission'] ?? [];
$csrfDelete = CSRFHelper::generateCSRFToken('form_delete_sst_inspecoes');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3"><i class="fas fa-search me-2"></i>Inspeções de segurança</h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item">Inspeções</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-3 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Listar</span>
            <span class="ms-auto">
                <?php if (in_array('SstCreateInspecao', $perms, true)): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-create-inspecao" class="btn btn-success btn-sm"><i class="fa-regular fa-square-plus"></i> Nova inspeção</a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-4"><label class="form-label small">Busca</label><input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>"></div>
                <div class="col-md-2"><label class="form-label small">Tipo</label><select name="tipo" class="form-select form-select-sm"><option value="">Todos</option>
                    <?php foreach (['Rotina', 'Especial', 'CIPA', 'Outra'] as $t): ?><option value="<?= $t ?>" <?= ($this->data['filters']['tipo'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
                </select></div>
                <div class="col-md-2"><label class="form-label small">Status</label><select name="status" class="form-select form-select-sm"><option value="">Todos</option>
                    <?php foreach (['Aberta', 'Em tratamento', 'Encerrada'] as $s): ?><option value="<?= $s ?>" <?= ($this->data['filters']['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                </select></div>
                <div class="col-auto"><button class="btn btn-primary btn-sm">Filtrar</button></div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead><tr><th>Data</th><th>Título</th><th>Tipo</th><th>Depto</th><th>Status</th><th>Itens</th><th>NC abertas</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($this->data['items'] ?? [] as $r): ?>
                        <tr>
                            <td><?= !empty($r['data_inspecao']) ? date('d/m/Y', strtotime($r['data_inspecao'])) : '-' ?></td>
                            <td><?= htmlspecialchars($r['titulo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['tipo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['departamento_nome'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['status'] ?? '') ?></td>
                            <td><?= (int)($r['total_itens'] ?? 0) ?></td>
                            <td><?= (int)($r['nao_conformes_abertas'] ?? 0) ?></td>
                            <td class="text-nowrap">
                                <?php if (in_array('SstViewInspecao', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-view-inspecao/<?= (int)$r['id'] ?>" class="btn btn-info btn-sm"><i class="fa-regular fa-eye"></i></a><?php endif; ?>
                                <?php if (in_array('SstUpdateInspecao', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-update-inspecao/<?= (int)$r['id'] ?>" class="btn btn-warning btn-sm"><i class="fa-regular fa-pen-to-square"></i></a><?php endif; ?>
                                <?php if (in_array('SstDeleteInspecao', $perms, true)): ?>
                                    <form action="<?= $_ENV['URL_ADM']; ?>sst-delete-inspecao" method="POST" class="d-inline" onsubmit="return confirm('Excluir inspeção?');">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfDelete ?>">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fa-regular fa-trash-can"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-2 d-none d-md-flex"><?= $this->data['pagination']['html'] ?? '' ?></div>
            <div class="d-flex justify-content-center mt-2 d-md-none"><?= $this->data['pagination']['html'] ?? '' ?></div>
        </div>
    </div>
</div>
