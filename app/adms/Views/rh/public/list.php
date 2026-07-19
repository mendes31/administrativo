<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $vagas
 * @var int $total
 * @var int $page
 * @var int $per_page
 * @var string $q
 * @var string $base_url
 */
$vagas = $vagas ?? [];
$total = (int) ($total ?? 0);
$page = max(1, (int) ($page ?? 1));
$perPage = max(1, (int) ($per_page ?? 12));
$q = (string) ($q ?? '');
$base_url = rtrim((string) ($base_url ?? ''), '/');
$totalPages = max(1, (int) ceil($total / $perPage));
?>
<form class="vp-search vp-card" method="get" action="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-9">
            <label for="q" class="form-label">Buscar por título</label>
            <input type="search" class="form-control" id="q" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: analista, auxiliar…">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100">Buscar</button>
        </div>
    </div>
</form>

<?php if ($vagas === []): ?>
    <div class="vp-card vp-empty">
        <p class="mb-0">Nenhuma vaga publicada no momento<?= $q !== '' ? ' para esta busca' : '' ?>.</p>
    </div>
<?php else: ?>
    <p class="vp-meta mb-3"><?= $total ?> vaga<?= $total === 1 ? '' : 's' ?> encontrada<?= $total === 1 ? '' : 's' ?>.</p>
    <?php foreach ($vagas as $vaga): ?>
        <?php
        $id = (int) ($vaga['id'] ?? 0);
        $titulo = (string) ($vaga['titulo'] ?? '');
        $area = trim((string) ($vaga['area_nome'] ?? ''));
        $cargo = trim((string) ($vaga['cargo_nome'] ?? ''));
        $local = trim((string) ($vaga['local_trabalho'] ?? ''));
        $tipo = trim((string) ($vaga['tipo_contrato'] ?? ''));
        ?>
        <article class="vp-card">
            <div class="d-flex justify-content-between gap-2 flex-wrap">
                <h2>
                    <a class="vp-link" href="<?= htmlspecialchars($base_url . '/' . $id, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </h2>
                <?php if ($tipo !== ''): ?>
                    <span class="vp-badge"><?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
            <p class="vp-meta mb-2">
                <?= htmlspecialchars($area !== '' ? $area : 'Área a definir', ENT_QUOTES, 'UTF-8') ?>
                <?php if ($cargo !== ''): ?>
                    · <?= htmlspecialchars($cargo, ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
                <?php if ($local !== ''): ?>
                    · <?= htmlspecialchars($local, ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </p>
            <a class="vp-link" href="<?= htmlspecialchars($base_url . '/' . $id, ENT_QUOTES, 'UTF-8') ?>">Ver detalhes</a>
        </article>
    <?php endforeach; ?>

    <?php if ($totalPages > 1): ?>
        <nav class="d-flex justify-content-between align-items-center mt-3" aria-label="Paginação">
            <?php
            $qs = $q !== '' ? '&q=' . rawurlencode($q) : '';
            $prev = $page > 1 ? $base_url . '?page=' . ($page - 1) . $qs : '';
            $next = $page < $totalPages ? $base_url . '?page=' . ($page + 1) . $qs : '';
            ?>
            <span class="vp-meta">Página <?= $page ?> de <?= $totalPages ?></span>
            <div class="d-flex gap-2">
                <?php if ($prev !== ''): ?>
                    <a class="vp-link" href="<?= htmlspecialchars($prev, ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
                <?php endif; ?>
                <?php if ($next !== ''): ?>
                    <a class="vp-link" href="<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>">Próxima</a>
                <?php endif; ?>
            </div>
        </nav>
    <?php endif; ?>
<?php endif; ?>
