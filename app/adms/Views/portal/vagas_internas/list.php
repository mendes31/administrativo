<?php
use App\adms\Helpers\FormatHelper;
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

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <span><i class="fas fa-briefcase me-2"></i>Oportunidades para colaboradores</span>
            <form method="get" action="<?= htmlspecialchars($base) ?>vagas-internas" class="d-flex gap-2">
                <input type="search" name="q" class="form-control form-control-sm" placeholder="Buscar título..."
                       value="<?= htmlspecialchars($q) ?>">
                <button type="submit" class="btn btn-sm btn-outline-primary">Buscar</button>
            </form>
        </div>
        <div class="card-body">
            <p class="small text-muted">
                Vagas com divulgação <em>interna</em> ou <em>ambas</em>, abertas e dentro do prazo.
                A candidatura usa seu usuário logado (sem portal público).
            </p>
            <?php if ($vagas === []): ?>
                <div class="alert alert-info mb-0">Nenhuma vaga interna disponível no momento.</div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($vagas as $v): ?>
                        <a href="<?= htmlspecialchars($base) ?>vagas-internas/<?= (int) ($v['id'] ?? 0) ?>"
                           class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between gap-2">
                                <h6 class="mb-1"><?= htmlspecialchars((string) ($v['titulo'] ?? '')) ?></h6>
                                <small class="text-muted text-nowrap">
                                    <?= FormatHelper::formatDateTime($v['data_abertura'] ?? null) ?>
                                </small>
                            </div>
                            <p class="mb-0 small text-muted">
                                <?= htmlspecialchars((string) ($v['area_nome'] ?? '—')) ?>
                                <?php if (!empty($v['cargo_nome'])): ?>
                                    · <?= htmlspecialchars(\App\adms\Helpers\PositionDisplayHelper::formatForDisplay((string) $v['cargo_nome'])) ?>
                                <?php endif; ?>
                                · <?= htmlspecialchars((string) ($v['tipo_contrato'] ?? '')) ?>
                                · <?= htmlspecialchars((string) ($v['visibilidade'] ?? '')) ?>
                            </p>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php if ($pages > 1): ?>
                    <nav class="mt-3" aria-label="Paginação">
                        <ul class="pagination pagination-sm mb-0">
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
    </div>
</div>
