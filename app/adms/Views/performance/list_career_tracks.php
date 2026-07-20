<?php ?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Trilhas de Carreira</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">Carreira</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between flex-wrap gap-2">
            <span><i class="fas fa-route me-2"></i>Trilhas</span>
            <div>
                <?php if (in_array('ListCareerPromotions', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-career-promotions" class="btn btn-sm btn-outline-primary">Promoções</a>
                <?php } ?>
                <?php if (in_array('CreateCareerTrack', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-career-track" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>Nova</a>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Status: todos</option>
                        <option value="active" <?= ($this->data['filters']['status'] ?? '') === 'active' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inactive" <?= ($this->data['filters']['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
                <div class="col-md-4"><input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar..." value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>"></div>
                <div class="col-md-2 d-flex gap-1">
                    <button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-search"></i></button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-career-tracks?limpar=1" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a>
                </div>
            </form>
            <?php if (empty($this->data['tracks'])): ?>
                <div class="alert alert-info">Nenhuma trilha.</div>
            <?php else: ?>
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>Nome</th><th>Níveis</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($this->data['tracks'] as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['name'] ?? '') ?></td>
                                <td><?= (int) ($t['levels_count'] ?? 0) ?></td>
                                <td><?= ($t['status'] ?? '') === 'active' ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?></td>
                                <td class="text-end">
                                    <?php if (in_array('ViewCareerTrack', $this->data['buttonPermission'] ?? [], true)) { ?>
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo $_ENV['URL_ADM']; ?>view-career-track/<?= (int) $t['id'] ?>">Ver</a>
                                    <?php } ?>
                                    <?php if (in_array('UpdateCareerTrack', $this->data['buttonPermission'] ?? [], true)) { ?>
                                        <a class="btn btn-sm btn-outline-warning" href="<?php echo $_ENV['URL_ADM']; ?>update-career-track/<?= (int) $t['id'] ?>">Editar</a>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?= $this->data['pagination'] ?? '' ?>
            <?php endif; ?>
        </div>
    </div>
</div>
