<?php
use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\PositionDisplayHelper;

$vagas = $this->data['vagas'] ?? [];
$total = (int) ($this->data['total'] ?? 0);
$page = (int) ($this->data['page'] ?? 1);
$perPage = (int) ($this->data['per_page'] ?? 12);
$q = (string) ($this->data['q'] ?? '');
$pages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
$base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Vagas internas</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($base) ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($base) ?>employee-portal" class="text-decoration-none">Portal</a></li>
            <li class="breadcrumb-item active">Vagas internas</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-0 shadow-sm overflow-hidden">
        <div class="card-body p-4"
             style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 45%, #f8fafc 100%);">
            <div class="row g-3 align-items-center">
                <div class="col-lg-7">
                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:3rem;height:3rem;">
                            <i class="fas fa-briefcase fa-lg"></i>
                        </div>
                        <div>
                            <h5 class="mb-1 fw-semibold">Oportunidades para colaboradores</h5>
                            <p class="mb-0 text-muted small">
                                Vagas com divulgação <em>interna</em> ou <em>ambas</em>, abertas e dentro do prazo.
                                A candidatura usa seu usuário logado.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <form method="get" action="<?= htmlspecialchars($base) ?>vagas-internas" class="d-flex gap-2">
                        <div class="input-group input-group-sm shadow-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="search" name="q" class="form-control border-start-0"
                                   placeholder="Buscar por título..."
                                   value="<?= htmlspecialchars($q) ?>"
                                   aria-label="Buscar vagas">
                            <button type="submit" class="btn btn-success">Buscar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <p class="mb-0 text-muted small">
            <?php if ($total === 0): ?>
                Nenhuma oportunidade no momento
            <?php elseif ($total === 1): ?>
                <strong class="text-body">1</strong> oportunidade disponível
            <?php else: ?>
                <strong class="text-body"><?= $total ?></strong> oportunidades disponíveis
            <?php endif; ?>
            <?php if ($q !== ''): ?>
                <span class="ms-1">para “<?= htmlspecialchars($q) ?>”</span>
            <?php endif; ?>
        </p>
        <?php if ($q !== ''): ?>
            <a href="<?= htmlspecialchars($base) ?>vagas-internas" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times me-1"></i>Limpar busca
            </a>
        <?php endif; ?>
    </div>

    <?php if ($vagas === []): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 px-4">
                <div class="rounded-circle bg-light text-muted d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:4.5rem;height:4.5rem;">
                    <i class="fas fa-inbox fa-2x"></i>
                </div>
                <h5 class="mb-2">Nenhuma vaga interna disponível</h5>
                <p class="text-muted mb-0 mx-auto" style="max-width:28rem;">
                    Quando o RH publicar oportunidades com divulgação interna, elas aparecerão aqui para você se candidatar.
                </p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($vagas as $v):
                $vagaId = (int) ($v['id'] ?? 0);
                $ja = !empty($v['ja_candidatou']);
                $detalheUrl = $base . 'vagas-internas/' . $vagaId;
                $titulo = (string) ($v['titulo'] ?? '');
                $area = (string) ($v['area_nome'] ?? '');
                $cargo = !empty($v['cargo_nome'])
                    ? PositionDisplayHelper::formatForDisplay((string) $v['cargo_nome'])
                    : '';
                $contrato = (string) ($v['tipo_contrato'] ?? '');
                $local = (string) ($v['local_trabalho'] ?? '');
                $abertura = FormatHelper::formatDateTime($v['data_abertura'] ?? null) ?: '—';
                $prazo = !empty($v['data_limite_inscricao'])
                    ? FormatHelper::formatDateTime($v['data_limite_inscricao'])
                    : null;
                ?>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm <?= $ja ? 'border-start border-primary border-3' : 'border-start border-success border-3' ?>">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex flex-wrap gap-1 mb-3">
                                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">
                                    <?= htmlspecialchars((string) ($v['status_label'] ?? 'Aberta')) ?>
                                </span>
                                <?php if ($ja): ?>
                                    <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="fas fa-check me-1"></i>Candidatura enviada
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h5 class="card-title h6 fw-semibold mb-2 lh-sm" title="<?= htmlspecialchars($titulo) ?>">
                                <?= htmlspecialchars($titulo) ?>
                            </h5>

                            <div class="d-flex flex-wrap gap-1 mb-3">
                                <?php if ($area !== ''): ?>
                                    <span class="badge text-bg-light border text-muted fw-normal">
                                        <i class="fas fa-building me-1"></i><?= htmlspecialchars($area) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($cargo !== ''): ?>
                                    <span class="badge text-bg-light border text-muted fw-normal">
                                        <i class="fas fa-user-tie me-1"></i><?= htmlspecialchars($cargo) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($contrato !== ''): ?>
                                    <span class="badge text-bg-light border text-muted fw-normal">
                                        <i class="fas fa-file-contract me-1"></i><?= htmlspecialchars($contrato) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($local !== ''): ?>
                                    <span class="badge text-bg-light border text-muted fw-normal">
                                        <i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($local) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="small text-muted mt-auto mb-3">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="fas fa-calendar-plus fa-fw"></i>
                                    <span>Abertura: <?= htmlspecialchars($abertura) ?></span>
                                </div>
                                <?php if ($prazo !== null): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-hourglass-half fa-fw"></i>
                                        <span>Prazo: <?= htmlspecialchars($prazo) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="<?= htmlspecialchars($detalheUrl) ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-eye me-1"></i>Ver detalhes
                                </a>
                                <?php if ($ja): ?>
                                    <span class="btn btn-sm btn-primary disabled" aria-disabled="true">
                                        <i class="fas fa-check me-1"></i>Candidatura enviada
                                    </span>
                                <?php else: ?>
                                    <a href="<?= htmlspecialchars($detalheUrl) ?>#candidatar" class="btn btn-sm btn-success">
                                        <i class="fas fa-user-check me-1"></i>Candidate-se
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($pages > 1): ?>
            <nav class="mt-4 d-flex justify-content-center" aria-label="Paginação">
                <ul class="pagination pagination-sm mb-0 shadow-sm">
                    <?php for ($p = 1; $p <= $pages; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link"
                               href="<?= htmlspecialchars($base) ?>vagas-internas?page=<?= $p ?>&amp;q=<?= urlencode($q) ?>">
                                <?= $p ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
