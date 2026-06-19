<?php
$items = $this->data['items'] ?? [];
$pagination = $this->data['pagination'] ?? [];
$filters = $this->data['filters'] ?? [];
$users = $this->data['users'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
$statusBadge = static function (string $s): string {
    return match ($s) {
        'Assinado' => 'success',
        'Pendente' => 'warning',
        'Cancelado' => 'secondary',
        default => 'light',
    };
};
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-file-signature me-2"></i>Fichas de entrega de EPI</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item active">Fichas de EPI</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Listar</span>
            <span class="ms-auto">
                <?php if (in_array('SstCreateEpiFicha', $perms, true)): ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-create-epi-ficha" class="btn btn-success btn-sm"><i class="fa-regular fa-square-plus"></i> Nova ficha</a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Pesquisar</label>
                    <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="Nome ou nº ficha">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Colaborador</label>
                    <select name="adms_user_id" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?= (int)$u['id'] ?>" <?= ((int)($filters['adms_user_id'] ?? 0) === (int)$u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Assinatura</label>
                    <select name="status_assinatura" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach (['Pendente', 'Assinado', 'Cancelado'] as $st): ?>
                        <option value="<?= $st ?>" <?= (($filters['status_assinatura'] ?? '') === $st) ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Filtrar</button>
                </div>
            </form>

            <?php if ($items === []): ?>
            <div class="alert alert-warning mb-0">Nenhuma ficha encontrada.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead><tr>
                        <th>#</th><th>Colaborador</th><th>Data</th><th>Itens</th><th>Assinatura</th><th>Responsável</th><th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($items as $r): ?>
                    <tr>
                        <td><?= (int)$r['id'] ?></td>
                        <td><?= htmlspecialchars($r['colaborador_nome'] ?? '') ?></td>
                        <td><?= !empty($r['data_entrega']) ? date('d/m/Y', strtotime($r['data_entrega'])) : '-' ?></td>
                        <td><?= (int)($r['total_itens'] ?? 0) ?></td>
                        <td><span class="badge bg-<?= $statusBadge((string)($r['status_assinatura'] ?? '')) ?>"><?= htmlspecialchars($r['status_assinatura'] ?? '') ?></span></td>
                        <td><?= htmlspecialchars($r['entregue_por_nome'] ?? '-') ?></td>
                        <td>
                            <?php if (in_array('SstViewEpiFicha', $perms, true)): ?>
                            <a href="<?= $_ENV['URL_ADM']; ?>sst-view-epi-ficha/<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
