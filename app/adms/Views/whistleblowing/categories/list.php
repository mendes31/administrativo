<div class="container-fluid px-4">
    <div class="mb-1 d-flex flex-wrap align-items-center gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-tags me-2"></i>Classificações de denúncias</h2>
        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>denuncias-dashboard">Canal de Denúncias</a></li>
            <li class="breadcrumb-item">Classificações</li>
        </ol>
    </div>

    <div class="card border-light shadow">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span>Tipos exibidos no canal público</span>
            <?php if (in_array('WhistleblowingCreateCategory', $this->data['buttonPermission'] ?? [])): ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>create-whistleblowing-category" class="btn btn-success btn-sm">
                    <i class="fa fa-plus"></i> Nova classificação
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <p class="text-muted small">
                As opções do formulário público e dos comitês são carregadas desta lista.
                Classificações inativas não aparecem para novos registros.
            </p>
            <div class="table-responsive d-none d-md-block">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Ordem</th>
                            <th>Nome</th>
                            <th>Denúncias</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->data['categories'] ?? [] as $cat): ?>
                            <tr>
                                <td><?= (int)($cat['sort_order'] ?? 0) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars((string)($cat['name'] ?? '')) ?></strong>
                                    <?php if (!empty($cat['description'])): ?>
                                        <div class="small text-muted"><?= htmlspecialchars((string)$cat['description']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int)($cat['reports_count'] ?? 0) ?></td>
                                <td>
                                    <?= !empty($cat['is_active'])
                                        ? '<span class="badge bg-success">Ativa</span>'
                                        : '<span class="badge bg-secondary">Inativa</span>' ?>
                                </td>
                                <td>
                                    <?php if (in_array('WhistleblowingUpdateCategory', $this->data['buttonPermission'] ?? [])): ?>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>update-whistleblowing-category/<?= (int)($cat['id'] ?? 0) ?>"
                                           class="btn btn-sm btn-outline-primary">Editar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($this->data['categories'])): ?>
                            <tr><td colspan="5" class="text-muted text-center py-3">Nenhuma classificação cadastrada.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-md-none">
                <?php foreach ($this->data['categories'] ?? [] as $cat): ?>
                    <article class="border rounded-3 p-3 mb-2">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div class="min-width-0">
                                <strong class="d-block text-break"><?= htmlspecialchars((string) ($cat['name'] ?? '')) ?></strong>
                                <?php if (!empty($cat['description'])): ?>
                                    <small class="text-muted d-block text-break mt-1"><?= htmlspecialchars((string) $cat['description']) ?></small>
                                <?php endif; ?>
                            </div>
                            <?= !empty($cat['is_active'])
                                ? '<span class="badge bg-success">Ativa</span>'
                                : '<span class="badge bg-secondary">Inativa</span>' ?>
                        </div>
                        <div class="d-flex justify-content-between gap-2 small text-muted my-3">
                            <span>Ordem: <strong><?= (int) ($cat['sort_order'] ?? 0) ?></strong></span>
                            <span>Denúncias: <strong><?= (int) ($cat['reports_count'] ?? 0) ?></strong></span>
                        </div>
                        <?php if (in_array('WhistleblowingUpdateCategory', $this->data['buttonPermission'] ?? [])): ?>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>update-whistleblowing-category/<?= (int) ($cat['id'] ?? 0) ?>"
                                class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-pen me-1"></i>Editar</a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                <?php if (empty($this->data['categories'])): ?>
                    <p class="text-muted text-center py-3 mb-0">Nenhuma classificação cadastrada.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.min-width-0 { min-width: 0; }
@media (max-width: 767.98px) {
    .container-fluid.px-4 { padding-left: .75rem !important; padding-right: .75rem !important; }
}
</style>
