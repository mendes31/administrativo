<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-users-cog me-2"></i>Comitês</h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>denuncias-dashboard">Canal de Denúncias</a></li>
            <li class="breadcrumb-item">Comitês</li>
        </ol>
    </div>

    <div class="card border-light shadow">
        <div class="card-header hstack">
            <span>Comitês de análise</span>
            <?php if (in_array('WhistleblowingCreateCommittee', $this->data['buttonPermission'] ?? [])): ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>create-whistleblowing-committee" class="btn btn-success btn-sm ms-auto"><i class="fa fa-plus"></i> Novo comitê</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <p class="text-muted small">Denúncias são encaminhadas automaticamente ao comitê conforme a classificação escolhida pelo denunciante.</p>
            <table class="table table-striped">
                <thead>
                    <tr><th>Nome</th><th>Membros</th><th>Classificações</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($this->data['committees'] ?? [] as $c): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars((string)$c['name']) ?></strong>
                                <?php if (!empty($c['description'])): ?><div class="small text-muted"><?= htmlspecialchars((string)$c['description']) ?></div><?php endif; ?>
                            </td>
                            <td><?= (int)($c['members_count'] ?? 0) ?></td>
                            <td><?= (int)($c['categories_count'] ?? 0) ?></td>
                            <td><?= !empty($c['is_active']) ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?></td>
                            <td>
                                <?php if (in_array('WhistleblowingUpdateCommittee', $this->data['buttonPermission'] ?? [])): ?>
                                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-whistleblowing-committee/<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
