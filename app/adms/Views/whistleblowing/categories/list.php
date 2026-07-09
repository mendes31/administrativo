<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-tags me-2"></i>Classificações de denúncias</h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>denuncias-dashboard">Canal de Denúncias</a></li>
            <li class="breadcrumb-item">Classificações</li>
        </ol>
    </div>

    <div class="card border-light shadow">
        <div class="card-header hstack">
            <span>Tipos exibidos no canal público</span>
            <?php if (in_array('WhistleblowingCreateCategory', $this->data['buttonPermission'] ?? [])): ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>create-whistleblowing-category" class="btn btn-success btn-sm ms-auto">
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
            <div class="table-responsive">
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
        </div>
    </div>
</div>
