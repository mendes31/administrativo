<?php
$items = $this->data['items'] ?? [];
$filters = $this->data['filters'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-boxes me-2"></i>Posição de estoque EPI</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item active">Estoque EPI</li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-header hstack gap-2">
            <span>Saldo por EPI</span>
            <span class="ms-auto d-flex gap-1 flex-wrap">
                <?php if (in_array('SstCreateEpiMovimento', $perms, true)): ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-create-epi-movimento?tipo=Entrada" class="btn btn-success btn-sm"><i class="fas fa-arrow-down"></i> Entrada</a>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-create-epi-movimento?tipo=Saída" class="btn btn-outline-danger btn-sm"><i class="fas fa-arrow-up"></i> Saída</a>
                <?php endif; ?>
                <?php if (in_array('SstListEpiMovimentos', $perms, true)): ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-list-epi-movimentos" class="btn btn-outline-secondary btn-sm">Histórico</a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Nome do EPI" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="estoque_baixo" value="1" id="eb" <?= !empty($filters['estoque_baixo']) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="eb">Só estoque baixo</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead><tr>
                        <th>EPI</th><th>CA</th><th>Saldo</th><th>Mínimo</th><th>Status</th><th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($items as $r):
                        $id = (int)($r['id'] ?? 0);
                        $saldo = (int)($r['estoque_calculado'] ?? $r['estoque_atual'] ?? 0);
                        $min = (int)($r['estoque_minimo'] ?? 0);
                        $baixo = !empty($r['estoque_baixo']);
                    ?>
                    <tr class="<?= $baixo ? 'table-warning' : '' ?>">
                        <td><?= htmlspecialchars($r['nome'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['ca_numero'] ?? '-') ?></td>
                        <td class="fw-semibold"><?= $saldo ?><?php if ($baixo): ?> <span class="badge bg-warning text-dark">Comprar</span><?php endif; ?></td>
                        <td><?= $min ?></td>
                        <td><?= htmlspecialchars($r['status'] ?? '') ?></td>
                        <td class="text-nowrap">
                            <?php if (in_array('SstCreateEpiMovimento', $perms, true)): ?>
                            <a href="<?= $_ENV['URL_ADM']; ?>sst-create-epi-movimento?adms_sst_epi_id=<?= $id ?>&tipo=Entrada" class="btn btn-sm btn-outline-success">+</a>
                            <a href="<?= $_ENV['URL_ADM']; ?>sst-list-epi-movimentos?adms_sst_epi_id=<?= $id ?>" class="btn btn-sm btn-outline-primary">Hist.</a>
                            <?php endif; ?>
                            <a href="<?= $_ENV['URL_ADM']; ?>sst-view-epi/<?= $id ?>" class="btn btn-sm btn-outline-secondary">EPI</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted small mb-0 mt-2">O saldo é calculado pelas movimentações. O estoque mínimo é configurado no cadastro do EPI para alertas de compra.</p>
        </div>
    </div>
</div>
