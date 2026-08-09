<?php
$urlAdm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
$members = isset($this->data['members']) && is_array($this->data['members']) ? $this->data['members'] : [];
$searchQuery = isset($this->data['search_query']) ? (string)$this->data['search_query'] : '';
$renderInitialsAvatar = static function (string $name, int $sizePx = 64): string {
    return \App\adms\Helpers\ImageHelper::renderInitialsAvatar($name, $sizePx, [
        'class' => 'flex-shrink-0',
    ]);
};
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($urlAdm); ?>public/adms/css/timeline-feed.css?v=36">
<link rel="stylesheet" href="<?php echo htmlspecialchars($urlAdm); ?>public/adms/css/timeline-members.css?v=2">

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
                <label for="timelineMembersSearch" class="form-label small text-muted mb-1">Buscar por nome, @usuário, cargo, departamento ou apresentação</label>
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
                $displayName = $name !== '' ? $name : 'Usuário';
                $username = trim((string)($m['username'] ?? ''));
                $dep = trim((string)($m['dep_name'] ?? ''));
                $posDisplay = \App\adms\Helpers\PositionDisplayHelper::formatForDisplay((string)($m['pos_name'] ?? ''));
                $bioRaw = trim((string)($m['timeline_bio'] ?? ''));
                $bioShort = $bioRaw !== '' ? mb_substr(preg_replace('/\s+/u', ' ', $bioRaw), 0, 140) : '';
                if ($bioShort !== '' && mb_strlen($bioRaw) > 140) {
                    $bioShort .= '…';
                }
                $avatarPath = null;
                $avatarUrl = '';
                if (\App\adms\Helpers\ImageHelper::userImageExists($mid, (string)($m['image'] ?? ''))) {
                    $avatarPath = 'users/' . $mid . '/' . $m['image'];
                    $avatarUrl = \App\adms\Helpers\ImageHelper::getImageUrl($avatarPath);
                }
                $profileUrl = $urlAdm . 'timeline-profile/' . $mid;
                ?>
                <div class="col">
                    <div class="card border-0 shadow-sm h-100 timeline-member-card">
                        <div class="card-body d-flex flex-column gap-2">
                            <div class="d-flex gap-3 align-items-start">
                                <?php if ($avatarPath !== null && $avatarUrl !== ''): ?>
                                    <button type="button"
                                            class="timeline-avatar-zoom-btn p-0 border-0 bg-transparent flex-shrink-0"
                                            data-avatar-src="<?php echo htmlspecialchars($avatarUrl); ?>"
                                            data-avatar-name="<?php echo htmlspecialchars($displayName); ?>"
                                            title="Ampliar foto"
                                            aria-label="Ampliar foto de <?php echo htmlspecialchars($displayName); ?>">
                                        <?php
                                        echo \App\adms\Helpers\ImageHelper::displayImage($avatarPath, [
                                            'class' => 'rounded-circle',
                                            'alt' => $displayName,
                                            'width' => '64',
                                            'height' => '64',
                                        ], 'icon_user.png', 'users');
                                        ?>
                                    </button>
                                <?php else: ?>
                                    <button type="button"
                                            class="timeline-avatar-zoom-btn p-0 border-0 bg-transparent flex-shrink-0"
                                            data-avatar-initials="1"
                                            data-avatar-name="<?php echo htmlspecialchars($displayName); ?>"
                                            title="Ampliar avatar"
                                            aria-label="Ampliar avatar de <?php echo htmlspecialchars($displayName); ?>">
                                        <?php echo $renderInitialsAvatar($displayName, 64); ?>
                                    </button>
                                <?php endif; ?>
                                <a href="<?php echo htmlspecialchars($profileUrl); ?>" class="text-decoration-none text-reset min-w-0 flex-grow-1">
                                    <div class="fw-semibold text-body"><?php echo htmlspecialchars($name !== '' ? $name : '—'); ?></div>
                                    <?php if ($username !== ''): ?>
                                        <div class="small text-muted">@<?php echo htmlspecialchars($username); ?></div>
                                    <?php endif; ?>
                                    <?php if ($posDisplay !== '' || $dep !== ''): ?>
                                        <div class="small text-body-secondary mt-1">
                                            <?php if ($posDisplay !== ''): ?><?php echo htmlspecialchars($posDisplay); ?><?php endif; ?>
                                            <?php if ($posDisplay !== '' && $dep !== ''): ?><span class="text-muted"> · </span><?php endif; ?>
                                            <?php if ($dep !== ''): ?><?php echo htmlspecialchars($dep); ?><?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </a>
                            </div>
                            <?php if ($bioShort !== ''): ?>
                                <p class="small text-body-secondary mb-0"><?php echo htmlspecialchars($bioShort); ?></p>
                            <?php else: ?>
                                <p class="small text-muted fst-italic mb-0">Sem apresentação no perfil.</p>
                            <?php endif; ?>
                            <div class="mt-auto pt-1">
                                <a href="<?php echo htmlspecialchars($profileUrl); ?>" class="small text-primary text-decoration-none">
                                    Ver perfil <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
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

<div class="modal fade" id="timelineMemberAvatarModal" tabindex="-1" aria-labelledby="timelineMemberAvatarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 bg-dark bg-opacity-75">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white fs-6" id="timelineMemberAvatarModalLabel">Foto</h5>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body d-flex flex-column align-items-center justify-content-center p-3">
                <img id="timelineMemberAvatarModalImg" src="" alt="" class="img-fluid rounded-circle d-none timeline-member-zoom-img">
                <div id="timelineMemberAvatarModalInitials" class="rounded-circle d-none align-items-center justify-content-center fw-bold text-secondary timeline-member-zoom-initials"></div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function initialsFromName(name) {
        var parts = String(name || '').trim().split(/\s+/).filter(Boolean);
        var out = '';
        for (var i = 0; i < parts.length && out.length < 2; i++) {
            out += parts[i].charAt(0);
        }
        return (out || 'U').toUpperCase();
    }

    function openMemberAvatar(btn) {
        var modalEl = document.getElementById('timelineMemberAvatarModal');
        var imgEl = document.getElementById('timelineMemberAvatarModalImg');
        var initialsEl = document.getElementById('timelineMemberAvatarModalInitials');
        var titleEl = document.getElementById('timelineMemberAvatarModalLabel');
        if (!modalEl || !imgEl || !initialsEl || typeof bootstrap === 'undefined') {
            return;
        }
        var name = btn.getAttribute('data-avatar-name') || 'Usuário';
        var src = btn.getAttribute('data-avatar-src') || '';
        if (titleEl) {
            titleEl.textContent = name;
        }
        if (src) {
            imgEl.src = src;
            imgEl.alt = name;
            imgEl.classList.remove('d-none');
            initialsEl.classList.add('d-none');
            initialsEl.classList.remove('d-inline-flex');
        } else {
            imgEl.classList.add('d-none');
            imgEl.removeAttribute('src');
            initialsEl.textContent = initialsFromName(name);
            initialsEl.classList.remove('d-none');
            initialsEl.classList.add('d-inline-flex');
        }
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.timeline-avatar-zoom-btn');
        if (!btn || !document.querySelector('.timeline-members-page')) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        openMemberAvatar(btn);
    });
})();
</script>
