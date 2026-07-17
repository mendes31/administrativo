<div class="container-fluid px-4">
    <div class="mb-1 d-flex flex-wrap align-items-center gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-users-cog me-2"></i>Comitês</h2>
        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>denuncias-dashboard">Canal de Denúncias</a></li>
            <li class="breadcrumb-item">Comitês</li>
        </ol>
    </div>

    <div class="card border-light shadow">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span>Comitês de análise</span>
            <?php if (in_array('WhistleblowingCreateCommittee', $this->data['buttonPermission'] ?? [])): ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>create-whistleblowing-committee" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> Novo comitê</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <p class="text-muted small">Denúncias são encaminhadas automaticamente ao comitê conforme a classificação escolhida pelo denunciante.</p>
            <div class="row g-3">
                <?php foreach ($this->data['committees'] ?? [] as $c): ?>
                    <?php
                    $committeeId = (int) ($c['id'] ?? 0);
                    $details = $this->data['committee_details'][$committeeId] ?? ['members' => [], 'categories' => []];
                    $members = $details['members'] ?? [];
                    $categories = $details['categories'] ?? [];
                    ?>
                    <div class="col-12">
                        <article class="card committee-card border">
                            <div class="card-body">
                                <div class="d-flex flex-column flex-md-row align-items-md-start gap-3">
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <h5 class="mb-0 text-break"><?= htmlspecialchars((string) $c['name']) ?></h5>
                                            <?= !empty($c['is_active'])
                                                ? '<span class="badge bg-success">Ativo</span>'
                                                : '<span class="badge bg-secondary">Inativo</span>' ?>
                                        </div>
                                        <?php if (!empty($c['description'])): ?>
                                            <p class="small text-muted mb-2 text-break"><?= htmlspecialchars((string) $c['description']) ?></p>
                                        <?php endif; ?>

                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="badge rounded-pill text-bg-light border">
                                                <i class="fas fa-users me-1 text-primary"></i><?= count($members) ?>
                                                <?= count($members) === 1 ? 'membro' : 'membros' ?>
                                            </span>
                                            <span class="badge rounded-pill text-bg-light border">
                                                <i class="fas fa-tags me-1 text-primary"></i><?= count($categories) ?>
                                                <?= count($categories) === 1 ? 'classificação' : 'classificações' ?>
                                            </span>
                                        </div>
                                    </div>

                                    <?php if (in_array('WhistleblowingUpdateCommittee', $this->data['buttonPermission'] ?? [])): ?>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>update-whistleblowing-committee/<?= $committeeId ?>"
                                            class="btn btn-sm btn-outline-primary flex-shrink-0">
                                            <i class="fas fa-pen me-1"></i>Editar membros
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div class="row g-3 mt-1">
                                    <div class="col-lg-7">
                                        <h6 class="small text-uppercase text-muted mb-2">Membros vinculados</h6>
                                        <?php if ($members !== []): ?>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php foreach ($members as $member): ?>
                                                    <span class="committee-member-chip border rounded-pill px-3 py-2"
                                                        title="<?= htmlspecialchars((string) ($member['email'] ?: 'Sem e-mail cadastrado'), ENT_QUOTES, 'UTF-8') ?>">
                                                        <i class="fas fa-user-check me-1 text-success"></i>
                                                        <?= htmlspecialchars((string) $member['name']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="small text-danger"><i class="fas fa-exclamation-circle me-1"></i>Nenhum membro vinculado</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-lg-5">
                                        <h6 class="small text-uppercase text-muted mb-2">Classificações atendidas</h6>
                                        <?php if ($categories !== []): ?>
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php foreach ($categories as $category): ?>
                                                    <span class="badge text-bg-info"><?= htmlspecialchars((string) $category) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="small text-muted">Nenhuma classificação vinculada.</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($this->data['committees'])): ?>
                    <div class="col-12">
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-users-cog fa-2x mb-2"></i>
                            <p class="mb-0">Nenhum comitê cadastrado.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.committee-card { border-radius: 14px; transition: box-shadow .15s ease, border-color .15s ease; }
.committee-card:hover { border-color: #b8c5d6 !important; box-shadow: 0 .25rem .75rem rgba(0, 0, 0, .06); }
.committee-card .min-width-0 { min-width: 0; }
.committee-member-chip { background: #f8f9fa; font-size: .875rem; }
@media (max-width: 767.98px) {
    .container-fluid.px-4 { padding-left: 1rem !important; padding-right: 1rem !important; }
    .committee-member-chip { width: 100%; border-radius: .5rem !important; }
}
</style>
