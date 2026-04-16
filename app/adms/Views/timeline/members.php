<?php
$urlAdm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
$members = isset($this->data['members']) && is_array($this->data['members']) ? $this->data['members'] : [];
$searchQuery = isset($this->data['search_query']) ? (string)$this->data['search_query'] : '';
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($urlAdm); ?>public/adms/css/timeline-feed.css?v=36">
<link rel="stylesheet" href="<?php echo htmlspecialchars($urlAdm); ?>public/adms/css/timeline-members.css?v=1">

<div class="container-fluid px-3 px-md-4 timeline-members-page">
    <?php include __DIR__ . '/../partials/alerts.php'; ?>

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3 mt-3">
        <div>
            <h2 class="mb-1"><i class="fas fa-users text-info me-2"></i>Membros</h2>
            <p class="text-muted small mb-0">Conheça os colegas e acesse o perfil na timeline.</p>
        </div>
        <nav aria-label="breadcrumb" class="ms-md-auto">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($urlAdm); ?>dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($urlAdm); ?>timeline">Timeline</a></li>
                <li class="breadcrumb-item active">Membros</li>
            </ol>
        </nav>
    </div>

    <div class="timeline-members-filter-bar mb-3">
        <form method="get" action="<?php echo htmlspecialchars($urlAdm); ?>timeline-members" class="card border-0 shadow-sm mb-0">
            <div class="card-body py-3">
                <label for="timelineMembersSearch" class="form-label small text-muted mb-1">Buscar por nome, @usuário, departamento ou apresentação</label>
                <div class="input-group input-group-sm flex-nowrap">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="search" name="q" id="timelineMembersSearch" class="form-control"
                           value="<?php echo htmlspecialchars($searchQuery); ?>"
                           placeholder="Ex.: Maria, @maria.silva, TI…" maxlength="200" autocomplete="off">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                    <?php if ($searchQuery !== ''): ?>
                        <a href="<?php echo htmlspecialchars($urlAdm); ?>timeline-members" class="btn btn-outline-secondary">Limpar</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <?php if ($members === []): ?>
        <div class="alert alert-light border text-center py-5">
            <i class="fas fa-user-slash fa-2x text-muted mb-3 d-block"></i>
            <?php if ($searchQuery !== ''): ?>
                <p class="mb-0">Nenhum colaborador encontrado para esta busca.</p>
            <?php else: ?>
                <p class="mb-0">Nenhum colaborador ativo disponível.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="timeline-members-list position-relative">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3 mb-4">
            <?php foreach ($members as $m): ?>
                <?php
                $mid = (int)($m['id'] ?? 0);
                $name = trim((string)($m['name'] ?? ''));
                $username = trim((string)($m['username'] ?? ''));
                $dep = trim((string)($m['dep_name'] ?? ''));
                $bioRaw = trim((string)($m['timeline_bio'] ?? ''));
                $bioShort = $bioRaw !== '' ? mb_substr(preg_replace('/\s+/u', ' ', $bioRaw), 0, 140) : '';
                if ($bioShort !== '' && mb_strlen($bioRaw) > 140) {
                    $bioShort .= '…';
                }
                $avatar = null;
                if (!empty($m['image']) && (string)$m['image'] !== 'icon_user.png') {
                    $avatar = 'users/' . $mid . '/' . $m['image'];
                }
                $profileUrl = $urlAdm . 'timeline-profile/' . $mid;
                ?>
                <div class="col">
                    <a href="<?php echo htmlspecialchars($profileUrl); ?>" class="text-decoration-none text-reset d-block h-100">
                        <div class="card border-0 shadow-sm h-100 timeline-member-card">
                            <div class="card-body d-flex flex-column gap-2">
                                <div class="d-flex gap-3 align-items-start">
                                    <?php
                                    echo \App\adms\Helpers\ImageHelper::displayImage($avatar, [
                                        'class' => 'rounded-circle flex-shrink-0',
                                        'alt' => '',
                                        'width' => '64',
                                        'height' => '64',
                                    ], 'icon_user.png', 'users');
                                    ?>
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="fw-semibold text-body"><?php echo htmlspecialchars($name !== '' ? $name : '—'); ?></div>
                                        <?php if ($username !== ''): ?>
                                            <div class="small text-muted">@<?php echo htmlspecialchars($username); ?></div>
                                        <?php endif; ?>
                                        <?php if ($dep !== ''): ?>
                                            <div class="small text-body-secondary mt-1"><?php echo htmlspecialchars($dep); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($bioShort !== ''): ?>
                                    <p class="small text-body-secondary mb-0"><?php echo htmlspecialchars($bioShort); ?></p>
                                <?php else: ?>
                                    <p class="small text-muted fst-italic mb-0">Sem apresentação no perfil.</p>
                                <?php endif; ?>
                                <div class="mt-auto pt-1 small text-primary">
                                    Ver perfil <i class="fas fa-arrow-right ms-1"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        </div>
    <?php endif; ?>

    <div class="mb-4">
        <a href="<?php echo htmlspecialchars($urlAdm); ?>timeline" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-stream me-1"></i>Voltar ao feed
        </a>
    </div>
</div>
