<?php
use App\adms\Helpers\CSRFHelper;
$perms = $this->data['buttonPermission'] ?? [];
$csrfDelete = CSRFHelper::generateCSRFToken('form_delete_sst_cipa');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3"><i class="fas fa-users-cog me-2"></i>CIPA — Mandatos</h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item">CIPA</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-3 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Mandatos</span>
            <span class="ms-auto">
                <?php if (in_array('SstCreateCipaMandato', $perms, true)): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-create-cipa-mandato" class="btn btn-success btn-sm"><i class="fa-regular fa-square-plus"></i> Novo mandato</a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3"><label class="form-label small">Status</label><select name="status" class="form-select form-select-sm"><option value="">Todos</option>
                    <option value="Ativo" <?= ($this->data['filters']['status'] ?? '') === 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                    <option value="Encerrado" <?= ($this->data['filters']['status'] ?? '') === 'Encerrado' ? 'selected' : '' ?>>Encerrado</option>
                </select></div>
                <div class="col-auto"><button class="btn btn-primary btn-sm">Filtrar</button></div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead><tr><th>Título</th><th>Período</th><th>Status</th><th>Membros</th><th>Reuniões</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($this->data['items'] ?? [] as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['titulo'] ?? '') ?></td>
                            <td><?= !empty($r['data_inicio']) ? date('d/m/Y', strtotime($r['data_inicio'])) : '-' ?> — <?= !empty($r['data_fim']) ? date('d/m/Y', strtotime($r['data_fim'])) : 'atual' ?></td>
                            <td><span class="badge bg-<?= ($r['status'] ?? '') === 'Ativo' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($r['status'] ?? '') ?></span></td>
                            <td><?= (int)($r['total_membros'] ?? 0) ?></td>
                            <td><?= (int)($r['total_reunioes'] ?? 0) ?></td>
                            <td class="text-nowrap">
                                <?php if (in_array('SstViewCipaMandato', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-view-cipa-mandato/<?= (int)$r['id'] ?>" class="btn btn-info btn-sm"><i class="fa-regular fa-eye"></i></a><?php endif; ?>
                                <?php if (in_array('SstUpdateCipaMandato', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-update-cipa-mandato/<?= (int)$r['id'] ?>" class="btn btn-warning btn-sm"><i class="fa-regular fa-pen-to-square"></i></a><?php endif; ?>
                                <?php if (in_array('SstDeleteCipaMandato', $perms, true)): ?>
                                    <form action="<?= $_ENV['URL_ADM']; ?>sst-delete-cipa-mandato" method="POST" class="d-inline" onsubmit="return confirm('Excluir mandato?');">
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
