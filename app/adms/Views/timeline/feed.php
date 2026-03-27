<?php
$urlAdm = rtrim($_ENV['URL_ADM'] ?? '', '/') . '/';
$csrfCreate = \App\adms\Helpers\CSRFHelper::generateCSRFToken('timeline_create_post');
$csrfDelete = \App\adms\Helpers\CSRFHelper::generateCSRFToken('timeline_delete_post');
$composerUid = (int)($_SESSION['user_id'] ?? 0);
$composerAvatarPath = null;
if (!empty($_SESSION['user_image']) && $_SESSION['user_image'] !== 'icon_user.png') {
    $composerAvatarPath = 'users/' . $composerUid . '/' . $_SESSION['user_image'];
}
$composerName = trim((string)($_SESSION['user_name'] ?? ''));
$composerFirst = $composerName !== '' ? preg_split('/\s+/', $composerName, 2)[0] : 'você';
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($urlAdm); ?>public/adms/css/timeline-feed.css?v=13">

<div class="container-fluid px-3 px-md-4">
    <?php include __DIR__ . '/../partials/alerts.php'; ?>
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3 mt-3">
                <h2 class="mb-0 mobile-hide-page-title"><i class="fas fa-stream text-info me-2"></i>Timeline</h2>
                <nav aria-label="breadcrumb" class="ms-md-auto mobile-hide-breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($urlAdm); ?>dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active">Timeline</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="timeline-layout">
        <div class="timeline-main">
            <?php if (!empty($this->data['can_create'])): ?>
            <div class="card timeline-composer-card timeline-composer-fb mb-3" id="timelineComposerCard">
                <div class="card-body py-2 px-3">
                    <form method="post" action="<?php echo htmlspecialchars($urlAdm); ?>create-timeline-post" enctype="multipart/form-data" class="timeline-composer-form" id="timelineComposerForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfCreate); ?>">
                        <div id="timelineComposerCreateHead" class="timeline-composer-create-head d-none mb-2 pb-2 border-bottom">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <div class="d-flex flex-column min-w-0">
                                    <span class="fw-semibold mb-0">Nova publicação</span>
                                    <?php if ($composerName !== ''): ?>
                                    <span class="small text-muted text-truncate"><?php echo htmlspecialchars($composerName); ?></span>
                                    <?php endif; ?>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm px-3 flex-shrink-0" id="timelineComposerSubmitBar">Publicar</button>
                            </div>
                        </div>
                        <div class="timeline-composer-row d-flex align-items-center gap-2">
                            <div class="timeline-composer-avatar flex-shrink-0">
                                <?php echo \App\adms\Helpers\ImageHelper::displayImage($composerAvatarPath, [
                                    'alt' => 'Sua foto',
                                    'class' => 'timeline-composer-avatar-img',
                                    'width' => '40',
                                    'height' => '40',
                                ], 'icon_user.png', 'users'); ?>
                            </div>
                            <div class="timeline-composer-input-wrap flex-grow-1 min-w-0">
                                <label for="timelineComposerText" class="visually-hidden">Texto da publicação</label>
                                <textarea name="content" id="timelineComposerText" class="form-control timeline-mention-field timeline-composer-pill" rows="1" placeholder="No que você está pensando, <?php echo htmlspecialchars($composerFirst); ?>?" autocomplete="off" maxlength="2000"></textarea>
                            </div>
                        </div>
                        <div class="timeline-composer-options d-flex align-items-center gap-1 mt-2">
                            <button type="button" class="timeline-composer-icon-btn timeline-composer-icon-photo" title="Foto" aria-label="Adicionar imagem" onclick="document.getElementById('timelineFileImage').click(); return false;">
                                <i class="fas fa-image" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="timeline-composer-icon-btn timeline-composer-icon-video" title="Vídeo (arquivo)" aria-label="Adicionar vídeo" onclick="document.getElementById('timelineFileVideo').click(); return false;">
                                <i class="fas fa-video" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="timeline-composer-icon-btn timeline-composer-icon-camera" id="btnTimelineCamera" title="Câmera (HTTPS recomendado)" aria-label="Abrir câmera para foto ou vídeo">
                                <i class="fas fa-camera" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="timeline-composer-icon-btn timeline-composer-icon-mention" id="btnTimelineMention" data-bs-toggle="modal" data-bs-target="#modalTimelineMention" title="Mencionar" aria-label="Mencionar colaborador">
                                <i class="fas fa-at" aria-hidden="true"></i>
                            </button>
                            <button type="submit" class="timeline-composer-submit ms-auto" id="timelineComposerSubmitIcon" title="Publicar" aria-label="Publicar">
                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="timelineComposerMediaBlock" class="timeline-composer-media-block d-none mt-3">
                            <div class="timeline-composer-media-preview position-relative rounded overflow-hidden bg-light border">
                                <div id="timelineComposerPreviewImagesGrid" class="timeline-composer-preview-images-grid w-100 d-none"></div>
                                <video id="timelineComposerPreviewVideo" class="timeline-composer-preview-video w-100 d-none" controls playsinline muted preload="metadata"></video>
                                <button type="button" class="btn btn-light btn-sm timeline-composer-remove-media shadow-sm border-0" id="btnTimelineComposerRemoveMedia" title="Remover mídia" aria-label="Remover mídia selecionada">
                                    <i class="fas fa-trash-alt text-danger" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="list-group list-group-flush timeline-composer-options-list rounded border mt-2 overflow-hidden small">
                                <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2 text-start" onclick="document.getElementById('timelineFileImage').click(); return false;">
                                    <span class="timeline-composer-opt-icon text-success"><i class="fas fa-images" aria-hidden="true"></i></span>
                                    <span>Adicionar ou trocar foto</span>
                                </button>
                                <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2 text-start" onclick="document.getElementById('timelineFileVideo').click(); return false;">
                                    <span class="timeline-composer-opt-icon text-danger"><i class="fas fa-film" aria-hidden="true"></i></span>
                                    <span>Adicionar ou trocar vídeo</span>
                                </button>
                                <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2 text-start" id="btnTimelineComposerCameraList">
                                    <span class="timeline-composer-opt-icon text-primary"><i class="fas fa-camera" aria-hidden="true"></i></span>
                                    <span>Usar câmera</span>
                                </button>
                                <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2 text-start" data-bs-toggle="modal" data-bs-target="#modalTimelineMention">
                                    <span class="timeline-composer-opt-icon text-warning"><i class="fas fa-at" aria-hidden="true"></i></span>
                                    <span>Mencionar colaborador</span>
                                </button>
                            </div>
                        </div>
                        <input type="file" name="images[]" id="timelineFileImage" class="visually-hidden" accept="image/jpeg,image/png,image/gif,image/webp" multiple tabindex="-1">
                        <input type="file" name="video" id="timelineFileVideo" class="visually-hidden" accept="video/mp4,video/webm" tabindex="-1">
                        <input type="file" id="timelineFileCapturePhoto" class="visually-hidden" accept="image/*" capture="environment" tabindex="-1">
                        <input type="file" id="timelineFileCaptureVideo" class="visually-hidden" accept="video/*" capture="environment" tabindex="-1">
                        <div class="timeline-composer-meta d-flex justify-content-between align-items-center gap-2 mt-1 px-1">
                            <span id="timelineComposerCharHint" class="small text-muted text-truncate d-none d-md-inline mb-0">Menções: <code>@</code> + username</span>
                            <span class="small text-muted ms-auto"><strong><span id="timelineComposerCharLeft">2000</span></strong> restantes</span>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($this->data['can_moderate'])): ?>
            <div class="alert alert-light border py-2 px-3 small mb-3">
                <a href="<?php echo htmlspecialchars($urlAdm); ?>timeline-moderate" class="fw-semibold"><i class="fas fa-shield-alt me-1"></i>Moderação e denúncias</a>
            </div>
            <?php endif; ?>

            <div class="timeline-search-fb mb-3">
                <div class="d-flex align-items-stretch gap-2 flex-wrap">
                    <div class="btn-group btn-group-sm timeline-search-mode flex-shrink-0" role="group" aria-label="Tipo de busca">
                        <button type="button" class="btn btn-outline-secondary active" id="timelineSearchTabPosts" data-mode="posts">Posts</button>
                        <button type="button" class="btn btn-outline-secondary" id="timelineSearchTabPeople" data-mode="people">Pessoas</button>
                    </div>
                    <span class="timeline-search-page-lupa flex-shrink-0 d-flex align-items-center justify-content-center" aria-hidden="true">
                        <i class="fas fa-search"></i>
                    </span>
                    <div class="timeline-search-field-wrap flex-grow-1 min-w-0">
                        <div id="timelineSearchPanelPosts">
                            <input type="text" id="timelineSearchPostsInput" class="form-control form-control-sm timeline-search-pill" placeholder="Buscar nesta página…" autocomplete="off" aria-label="Buscar postagens">
                        </div>
                        <div id="timelineSearchPanelPeople" class="d-none">
                            <input type="text" id="timelineSearchPeopleInput" class="form-control form-control-sm timeline-search-pill" placeholder="Nome ou e-mail…" autocomplete="off" aria-label="Buscar pessoas">
                        </div>
                    </div>
                </div>
                <div id="timelineSearchPeopleResults" class="list-group timeline-mention-results mt-2 d-none timeline-search-people-results" style="max-height: 220px; overflow-y: auto;"></div>
            </div>

            <?php include __DIR__ . '/partials/feed_posts.php'; ?>

            <?php if (!empty($this->data['pagination']['html'])): ?>
                <div class="mt-3"><?php echo $this->data['pagination']['html']; ?></div>
            <?php endif; ?>
        </div>

        <aside class="timeline-sidebar d-none d-lg-block">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold text-muted text-uppercase small mb-3">Dicas</h6>
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-2">Respeite o ambiente corporativo.</li>
                        <li class="mb-2">Comunicados oficiais continuam em <a href="<?php echo htmlspecialchars($urlAdm); ?>list-informativos">Informativos</a>.</li>
                        <li class="mb-2">Menções: <code>@</code> + <strong>username</strong> (autocomplete por login); vídeos curtos por arquivo ou pela câmera.</li>
                    </ul>
                </div>
            </div>
        </aside>
    </div>
</div>

<div id="timelineMentionDropdown" class="timeline-mention-dropdown d-none" role="listbox" aria-label="Sugestões de menção"></div>

<?php if (!empty($this->data['can_create'])): ?>
<div class="modal fade" id="modalTimelineMention" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mencionar colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Buscar por nome ou e-mail</label>
                <input type="text" class="form-control mb-2" id="timelineMentionSearch" placeholder="Digite nome ou e-mail" autocomplete="off">
                <div id="timelineMentionResults" class="list-group timeline-mention-results"></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($this->data['can_create'])): ?>
<div class="modal fade" id="modalTimelineCamera" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Câmera</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="ratio ratio-16x9 bg-dark rounded overflow-hidden">
                    <video id="timelineCameraPreview" class="w-100 h-100" autoplay playsinline muted></video>
                </div>
                <div class="text-muted small mt-2" id="timelineCameraHint">Escolha `Foto` ou `Vídeo` para capturar.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="btnTimelineCameraPhoto">Foto</button>
                <button type="button" class="btn btn-outline-secondary" id="btnTimelineCameraVideo">Vídeo</button>
                <button type="button" class="btn btn-danger d-none" id="btnTimelineCameraStop">Parar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal denúncia -->
<div class="modal fade" id="modalDenunciaTimeline" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Denunciar publicação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="denunciaPostId" value="">
                <div class="mb-2">
                    <label class="form-label">Motivo</label>
                    <select id="denunciaMotivo" class="form-select">
                        <option value="Conteúdo inadequado">Conteúdo inadequado</option>
                        <option value="Assédio ou discriminação">Assédio ou discriminação</option>
                        <option value="Spam">Spam</option>
                        <option value="Outro">Outro</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Detalhes (opcional)</label>
                    <textarea id="denunciaDetalhes" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-danger" id="btnEnviarDenuncia">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTimelineReactions" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title w-100 text-center">Reações</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 pt-2">
                <div id="timelineReactionsTabList" class="nav nav-pills nav-justified flex-nowrap overflow-auto gap-1 px-2 pb-2 border-bottom timeline-reactions-tabs"></div>
                <div id="timelineReactionsTabPanels" class="p-2" style="max-height: 60vh; overflow-y: auto;">
                    <p class="text-muted small mb-0 px-2">Carregando…</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTimelineEditPost" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar publicação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="timelineEditPostId" value="">
                <label class="form-label">Texto</label>
                <textarea id="timelineEditPostContent"
                          class="form-control timeline-mention-field"
                          rows="5"
                          placeholder="Texto da publicação"
                          autocomplete="off"
                          maxlength="2000"></textarea>
                <p class="small text-muted mt-2 mb-0">Menções: use <code>@username</code>. Mídia não é alterada por aqui.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnTimelineEditPostSave">Salvar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTimelineDeletePost" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Deletar publicação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="timelineDeletePostId" value="">
                <div class="text-muted small">
                    Esta ação remove a publicação, reações e comentários.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnTimelineDeletePostConfirm">Deletar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTimelineMediaCarousel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white">Fotos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body p-0">
                <div class="timeline-media-viewer" role="group" aria-label="Visualizador de fotos">
                    <button type="button" class="btn btn-link text-white p-3 timeline-media-viewer-prev" id="timelineMediaViewerPrev" aria-label="Anterior">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <img id="timelineMediaViewerImg" class="timeline-media-viewer-img" alt="Foto">
                    <button type="button" class="btn btn-link text-white p-3 timeline-media-viewer-next" id="timelineMediaViewerNext" aria-label="Próxima">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <div class="timeline-media-viewer-count text-white-50 small" id="timelineMediaViewerCount"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const base = <?php echo json_encode($urlAdm); ?>;
    const canReport = <?php echo !empty($this->data['can_report']) ? 'true' : 'false'; ?>;
    const canCreate = <?php echo !empty($this->data['can_create']) ? 'true' : 'false'; ?>;
    const timelineEditCsrf = <?php echo json_encode($this->data['csrf_timeline_edit'] ?? ''); ?>;
    const timelineDeleteCsrf = <?php echo json_encode($csrfDelete); ?>;

    var reactionOrder = ['like', 'love', 'care', 'haha', 'wow', 'sad', 'angry'];
    var reactionLabels = { like: 'Curtir', love: 'Amei', care: 'Cuidar', haha: 'Risada', wow: 'Uau', sad: 'Triste', angry: 'Raiva' };
    var reactionIconClasses = {
        like: 'fas fa-thumbs-up text-fb-like',
        love: 'fas fa-heart text-fb-love',
        care: 'fas fa-hand-holding-heart text-fb-care',
        haha: 'fas fa-laugh-beam text-fb-haha',
        wow: 'fas fa-grin-stars text-fb-wow',
        sad: 'fas fa-sad-tear text-fb-sad',
        angry: 'fas fa-angry text-fb-angry'
    };

    (function focusPostFromQuery() {
        try {
            var params = new URLSearchParams(window.location.search || '');
            var postId = params.get('post');
            if (!postId) return;
            var target = document.getElementById('timeline-post-' + postId);
            if (!target) return;
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target.classList.add('timeline-post-focus');
            setTimeout(function () {
                target.classList.remove('timeline-post-focus');
            }, 2200);
        } catch (e) {
            // ignore
        }
    })();

    function renderReactionStackHtml(summary) {
        summary = summary || {};
        var types = [];
        reactionOrder.forEach(function (t) {
            var n = summary[t] ? parseInt(summary[t], 10) : 0;
            if (n > 0 && types.length < 3) types.push(t);
        });
        if (!types.length) {
            return '<span class="timeline-reaction-stack-item timeline-reaction-stack-empty"><i class="far fa-thumbs-up opacity-50"></i></span>';
        }
        var html = '';
        types.forEach(function (t, idx) {
            var ic = reactionIconClasses[t] || reactionIconClasses.like;
            html += '<span class="timeline-reaction-stack-item" style="z-index:' + (10 - idx) + '"><i class="' + ic + '"></i></span>';
        });
        return html;
    }

    function updateReactionUI(postId, data) {
        var total = data.likes_count != null ? parseInt(data.likes_count, 10) : 0;
        var reaction = data.reaction;
        var summary = data.summary || {};
        var countEls = document.querySelectorAll('.timeline-like-count[data-post-id="' + postId + '"]');
        countEls.forEach(function (c) { c.textContent = total; });
        var stack = document.querySelector('.timeline-reaction-stack[data-post-id="' + postId + '"]');
        if (stack) stack.innerHTML = renderReactionStackHtml(summary);
        var hit = document.querySelector('.timeline-reactions-summary-hit[data-post-id="' + postId + '"]');
        if (hit) {
            if (total > 0) { hit.classList.remove('text-muted'); hit.classList.add('text-body'); }
            else { hit.classList.add('text-muted'); hit.classList.remove('text-body'); }
        }
        var mainBtn = document.querySelector('.timeline-curtir-main[data-post-id="' + postId + '"]');
        if (mainBtn) {
            var icEl = mainBtn.querySelector('i');
            var labEl = mainBtn.querySelector('.timeline-curtir-label');
            if (reaction && reactionIconClasses[reaction]) {
                if (icEl) icEl.className = reactionIconClasses[reaction];
                if (labEl) labEl.textContent = reactionLabels[reaction] || 'Curtir';
            } else {
                if (icEl) icEl.className = 'far fa-thumbs-up';
                if (labEl) labEl.textContent = 'Curtir';
            }
        }
        document.querySelectorAll('.timeline-reaction-tray-mobile[data-post-id="' + postId + '"]').forEach(function (tr) {
            tr.classList.remove('is-open');
        });
    }

    function buildReactionsModal(rows) {
        var tabList = document.getElementById('timelineReactionsTabList');
        var panels = document.getElementById('timelineReactionsTabPanels');
        if (!tabList || !panels) return;
        rows = rows || [];
        var counts = { all: 0 };
        reactionOrder.forEach(function (t) { counts[t] = 0; });
        rows.forEach(function (r) {
            counts.all++;
            var t = r.reaction_type || 'like';
            if (counts[t] !== undefined) counts[t]++;
        });
        var tabsHtml = '<button type="button" class="btn btn-sm btn-primary timeline-reaction-tab flex-shrink-0" data-filter="all">Tudo ' +
            (counts.all ? '<span class="badge bg-light text-primary ms-1">' + counts.all + '</span>' : '') + '</button>';
        reactionOrder.forEach(function (t) {
            if (!counts[t]) return;
            tabsHtml += '<button type="button" class="btn btn-sm btn-outline-secondary timeline-reaction-tab flex-shrink-0" data-filter="' + t + '">' +
                '<i class="' + (reactionIconClasses[t] || '') + '"></i> <span class="badge bg-secondary ms-1">' + counts[t] + '</span></button>';
        });
        tabList.className = 'd-flex flex-nowrap overflow-auto gap-1 px-2 pb-2 border-bottom timeline-reactions-tabs';
        tabList.innerHTML = tabsHtml;

        function renderList(filter) {
            var list = rows.filter(function (r) {
                if (filter === 'all') return true;
                return (r.reaction_type || 'like') === filter;
            });
            if (!list.length) {
                return '<p class="text-muted small mb-0">Ninguém nesta categoria.</p>';
            }
            var h = '<ul class="list-group list-group-flush">';
            list.forEach(function (row) {
                var rt = row.reaction_type || 'like';
                var ic = reactionIconClasses[rt] || reactionIconClasses.like;
                var un = row.username ? '@' + escapeHtml(row.username) : '';
                h += '<li class="list-group-item d-flex align-items-center gap-3 py-2 px-0 border-0 border-bottom">';
                h += '<span class="position-relative d-inline-flex timeline-react-avatar-wrap">';
                h += '<span class="rounded-circle bg-light d-flex align-items-center justify-content-center timeline-react-avatar-fallback" style="width:40px;height:40px"><i class="fas fa-user text-muted"></i></span>';
                h += '<span class="position-absolute bottom-0 end-0 rounded-circle bg-white border p-1" style="line-height:1"><i class="' + ic + '" style="font-size:0.65rem"></i></span>';
                h += '</span>';
                h += '<span class="flex-grow-1 min-w-0"><span class="fw-semibold d-block">' + escapeHtml(row.name || '') + '</span>';
                if (un) h += '<span class="text-muted small">' + un + '</span>';
                h += '</span></li>';
            });
            h += '</ul>';
            return h;
        }
        var currentFilter = 'all';
        panels.innerHTML = renderList(currentFilter);
        tabList.querySelectorAll('.timeline-reaction-tab').forEach(function (tab) {
            tab.onclick = function () {
                tabList.querySelectorAll('.timeline-reaction-tab').forEach(function (x) {
                    x.classList.remove('btn-primary');
                    x.classList.add('btn-outline-secondary');
                });
                tab.classList.remove('btn-outline-secondary');
                tab.classList.add('btn-primary');
                currentFilter = tab.getAttribute('data-filter') || 'all';
                panels.innerHTML = renderList(currentFilter);
            };
        });
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    var ajaxHeaders = {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
    };

    /** fetch + parse JSON; evita crash se o PHP devolver texto (ex.: Erro 004) */
    function fetchJson(url, options) {
        options = options || {};
        if (!options.credentials) {
            options.credentials = 'same-origin';
        }
        options.headers = Object.assign({}, ajaxHeaders, options.headers || {});
        return fetch(url, options).then(function (r) {
            return r.text().then(function (text) {
                var data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Resposta não é JSON:', (text || '').slice(0, 400));
                    throw new Error((text && text.indexOf('Erro') === 0) ? text : 'Resposta inválida do servidor.');
                }
                return data;
            });
        });
    }

    function bindPostActions() {
        document.querySelectorAll('.timeline-reaction-pick-fb').forEach(function (pick) {
            pick.onclick = function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                var bundle = pick.closest('.timeline-curtir-bundle');
                var trayM = pick.closest('.timeline-reaction-tray-mobile');
                var id = pick.getAttribute('data-post-id') ||
                    (bundle && bundle.getAttribute('data-post-id')) ||
                    (trayM && trayM.getAttribute('data-post-id'));
                if (!id) return;
                var r = pick.getAttribute('data-reaction') || 'like';
                var fd = new FormData();
                fd.append('reaction', r);
                fetchJson(base + 'timeline-like/' + id, { method: 'POST', body: fd })
                    .then(function (data) {
                        if (data && data.success) {
                            updateReactionUI(id, data);
                        }
                    })
                    .catch(function (err) { console.error(err); alert(err.message || 'Não foi possível registrar a reação.'); });
            };
        });

        document.querySelectorAll('.timeline-curtir-main').forEach(function (btn) {
            btn.onclick = function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                var id = btn.getAttribute('data-post-id');
                if (!id) return;
                var fd = new FormData();
                fd.append('reaction', 'like');
                fetchJson(base + 'timeline-like/' + id, { method: 'POST', body: fd })
                    .then(function (data) {
                        if (data && data.success) {
                            updateReactionUI(id, data);
                        }
                    })
                    .catch(function (err) { console.error(err); alert(err.message || 'Não foi possível registrar a reação.'); });
            };
        });

        document.querySelectorAll('.timeline-reaction-fb-more').forEach(function (chev) {
            chev.onclick = function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                var id = chev.getAttribute('data-post-id');
                var tray = document.querySelector('.timeline-reaction-tray-mobile[data-post-id="' + id + '"]');
                if (tray) tray.classList.toggle('is-open');
            };
        });

        document.addEventListener('click', function (ev) {
            if (ev.target.closest && (ev.target.closest('.timeline-reaction-tray-mobile') || ev.target.closest('.timeline-reaction-fb-more'))) {
                return;
            }
            document.querySelectorAll('.timeline-reaction-tray-mobile.is-open').forEach(function (t) {
                t.classList.remove('is-open');
            });
        });

        document.querySelectorAll('.timeline-reactions-summary-hit').forEach(function (btn) {
            btn.onclick = function () {
                var id = btn.getAttribute('data-post-id');
                var panels = document.getElementById('timelineReactionsTabPanels');
                if (panels) panels.innerHTML = '<p class="text-muted small mb-0 px-2">Carregando…</p>';
                fetchJson(base + 'timeline-post-reactions/' + id, {})
                    .then(function (data) {
                        if (!data || !data.success) return;
                        buildReactionsModal(data.reactions || []);
                        var mEl = document.getElementById('modalTimelineReactions');
                        if (mEl) {
                            var m = bootstrap.Modal.getInstance(mEl) || new bootstrap.Modal(mEl);
                            m.show();
                        }
                    })
                    .catch(function (err) {
                        console.error(err);
                        alert(err.message || 'Não foi possível carregar as reações.');
                    });
            };
        });

        function b64ToUtf8(b64) {
            try {
                var bin = atob(b64);
                if (typeof TextDecoder !== 'undefined') {
                    var bytes = new Uint8Array(bin.length);
                    for (var i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
                    return new TextDecoder('utf-8').decode(bytes);
                }
                return decodeURIComponent(escape(bin));
            } catch (e) {
                return '';
            }
        }

        document.querySelectorAll('.btn-timeline-edit-post').forEach(function (btn) {
            btn.onclick = function () {
                var id = btn.getAttribute('data-post-id');
                var b64 = btn.getAttribute('data-post-content-b64') || '';
                var content = b64 ? b64ToUtf8(b64) : '';
                document.getElementById('timelineEditPostId').value = id;
                document.getElementById('timelineEditPostContent').value = content;
                var mEl = document.getElementById('modalTimelineEditPost');
                if (mEl) {
                    var m = bootstrap.Modal.getInstance(mEl) || new bootstrap.Modal(mEl);
                    m.show();
                }
            };
        });

        var btnSaveEdit = document.getElementById('btnTimelineEditPostSave');
        if (btnSaveEdit) {
            btnSaveEdit.onclick = function () {
                var id = document.getElementById('timelineEditPostId').value;
                var content = document.getElementById('timelineEditPostContent').value || '';
                if (!id) {
                    return;
                }
                var fd = new FormData();
                fd.append('post_id', id);
                fd.append('content', content);
                fd.append('csrf_token', timelineEditCsrf);
                fetchJson(base + 'update-timeline-post', { method: 'POST', body: fd })
                    .then(function (data) {
                        if (data && data.success) {
                            window.location.reload();
                        } else if (data && data.message) {
                            alert(data.message);
                        }
                    })
                    .catch(function (err) { console.error(err); alert(err.message || 'Não foi possível salvar.'); });
            };
        }

        document.querySelectorAll('.btn-timeline-delete-post').forEach(function (btn) {
            btn.onclick = function () {
                var id = btn.getAttribute('data-post-id');
                var hid = document.getElementById('timelineDeletePostId');
                if (hid) hid.value = id || '';
                var mEl = document.getElementById('modalTimelineDeletePost');
                if (mEl) {
                    var m = bootstrap.Modal.getInstance(mEl) || new bootstrap.Modal(mEl);
                    m.show();
                }
            };
        });

        var btnConfirmDelete = document.getElementById('btnTimelineDeletePostConfirm');
        if (btnConfirmDelete) {
            btnConfirmDelete.onclick = function () {
                var id = document.getElementById('timelineDeletePostId').value;
                if (!id) return;

                var fd = new FormData();
                fd.append('post_id', id);
                fd.append('csrf_token', timelineDeleteCsrf);

                btnConfirmDelete.disabled = true;
                fetchJson(base + 'delete-timeline-post', { method: 'POST', body: fd })
                    .then(function (data) {
                        if (data && data.success) {
                            window.location.href = base.replace(/\/?$/, '/') + 'timeline';
                        } else if (data && data.message) {
                            alert(data.message);
                        } else {
                            alert('Não foi possível deletar a publicação.');
                        }
                    })
                    .catch(function (err) {
                        console.error(err);
                        alert(err.message || 'Não foi possível deletar.');
                    })
                    .finally(function () {
                        btnConfirmDelete.disabled = false;
                        var m = bootstrap.Modal.getInstance(document.getElementById('modalTimelineDeletePost'));
                        if (m) m.hide();
                    });
            };
        }

        document.querySelectorAll('.btn-timeline-comments-toggle').forEach(function (btn) {
            btn.onclick = function () {
                const id = btn.getAttribute('data-post-id');
                const box = document.getElementById('comments-' + id);
                if (!box) return;
                box.classList.toggle('d-none');
                if (!box.classList.contains('d-none') && box.getAttribute('data-loaded') !== '1') {
                    fetchJson(base + 'timeline-comment/' + id, {})
                        .then(function (data) {
                            if (data && data.success) {
                                renderComments(id, data.comments || []);
                                box.setAttribute('data-loaded', '1');
                            }
                        })
                        .catch(function (err) { console.error(err); alert(err.message || 'Não foi possível carregar comentários.'); });
                }
            };
        });

        document.querySelectorAll('.btn-timeline-comment-send').forEach(function (btn) {
            btn.onclick = function () {
                const id = btn.getAttribute('data-post-id');
                const inp = document.querySelector('.timeline-comment-input[data-post-id="' + id + '"]');
                if (!inp || !inp.value.trim()) return;
                const fd = new FormData();
                fd.append('post_id', id);
                fd.append('content', inp.value.trim());
                fetchJson(base + 'timeline-comment/' + id, { method: 'POST', body: fd })
                    .then(function (data) {
                        if (data && data.success) {
                            inp.value = '';
                            renderComments(id, data.comments || []);
                            const box = document.getElementById('comments-' + id);
                            if (box) { box.classList.remove('d-none'); box.setAttribute('data-loaded', '1'); }
                        } else if (data && data.message) {
                            alert(data.message);
                        }
                    })
                    .catch(function (err) { console.error(err); alert(err.message || 'Não foi possível enviar o comentário.'); });
            };
        });

        document.querySelectorAll('.btn-timeline-report').forEach(function (btn) {
            btn.onclick = function () {
                const id = btn.getAttribute('data-post-id');
                document.getElementById('denunciaPostId').value = id;
                const m = new bootstrap.Modal(document.getElementById('modalDenunciaTimeline'));
                m.show();
            };
        });
    }

    function renderComments(postId, comments) {
        const wrap = document.querySelector('.timeline-comments-list[data-post-id="' + postId + '"]');
        if (!wrap) return;
        wrap.innerHTML = '';
        comments.forEach(function (c) {
            const div = document.createElement('div');
            div.className = 'mb-2 small timeline-comment-line';
            const body = c.content_html ? c.content_html : escapeHtml(c.content || '');
            div.innerHTML = '<strong>' + escapeHtml(c.author_name || '') + '</strong> · ' + escapeHtml(c.created_at || '') + '<br>' + body;
            wrap.appendChild(div);
        });
    }

    const btnDen = document.getElementById('btnEnviarDenuncia');
    if (btnDen) {
        btnDen.onclick = function () {
            if (!canReport) return;
            const id = document.getElementById('denunciaPostId').value;
            const fd = new FormData();
            fd.append('post_id', id);
            fd.append('reason', document.getElementById('denunciaMotivo').value);
            fd.append('details', document.getElementById('denunciaDetalhes').value);
            fetchJson(base + 'timeline-report', { method: 'POST', body: fd })
                .then(function (data) {
                    alert(data && data.message ? data.message : 'Enviado.');
                    bootstrap.Modal.getInstance(document.getElementById('modalDenunciaTimeline')).hide();
                })
                .catch(function (err) { console.error(err); alert(err.message || 'Não foi possível enviar a denúncia.'); });
        };
    }

    bindPostActions();

    // Visualizador de fotos (ao clicar nas thumbs)
    var timelineViewerImages = [];
    var timelineViewerIndex = 0;

    function getMediaViewerEls() {
        return {
            modalEl: document.getElementById('modalTimelineMediaCarousel'),
            imgEl: document.getElementById('timelineMediaViewerImg'),
            prevEl: document.getElementById('timelineMediaViewerPrev'),
            nextEl: document.getElementById('timelineMediaViewerNext'),
            countEl: document.getElementById('timelineMediaViewerCount'),
        };
    }

    function timelineShowViewerAt(idx) {
        var els = getMediaViewerEls();
        if (!els.modalEl || !els.imgEl) return;
        if (!timelineViewerImages || !timelineViewerImages.length) return;

        var max = timelineViewerImages.length;
        if (idx < 0) idx = max - 1;
        if (idx >= max) idx = 0;
        timelineViewerIndex = idx;

        var p = timelineViewerImages[timelineViewerIndex];
        els.imgEl.src = base + 'serve-file?path=' + encodeURIComponent(p);

        if (els.countEl) {
            els.countEl.textContent = (timelineViewerIndex + 1) + ' / ' + max;
        }
    }

    document.addEventListener('click', function (ev) {
        var t = ev.target && ev.target.closest ? ev.target.closest('.timeline-media-clickable') : null;
        if (!t) return;
        ev.preventDefault();
        ev.stopPropagation();

        var imgsJson = t.getAttribute('data-images') || '[]';
        var imgs = [];
        try { imgs = JSON.parse(imgsJson); }
        catch (e) {
            try { imgs = JSON.parse((imgsJson || '').replace(/&quot;/g, '"')); } catch (e2) { imgs = []; }
        }
        if (!imgs || !imgs.length) return;

        var di = parseInt(t.getAttribute('data-index') || '0', 10);
        if (isNaN(di)) di = 0;

        timelineViewerImages = imgs;
        timelineShowViewerAt(di);

        var els = getMediaViewerEls();
        if (!els.modalEl) return;
        var modal = bootstrap.Modal.getOrCreateInstance(els.modalEl);
        modal.show();
    }, true);

    // Bind botões prev/next sempre (re-busca elementos se necessário)
    document.addEventListener('click', function (e) {
        if (e.target && e.target.closest && e.target.closest('#timelineMediaViewerPrev')) {
            e.preventDefault();
            timelineShowViewerAt(timelineViewerIndex - 1);
        }
        if (e.target && e.target.closest && e.target.closest('#timelineMediaViewerNext')) {
            e.preventDefault();
            timelineShowViewerAt(timelineViewerIndex + 1);
        }
    }, true);

    document.addEventListener('hidden.bs.modal', function (e) {
        if (!e || !e.target || e.target.id !== 'modalTimelineMediaCarousel') return;
        var els = getMediaViewerEls();
        timelineViewerImages = [];
        timelineViewerIndex = 0;
        if (els.imgEl) els.imgEl.src = '';
        if (els.countEl) els.countEl.textContent = '';
    }, true);

    const imgIn = document.getElementById('timelineFileImage');
    const vidIn = document.getElementById('timelineFileVideo');
    var timelinePreviewObjectUrls = [];
    var timelineSelectedImageFiles = [];

    function timelineComposerRevokePreviewUrls() {
        if (timelinePreviewObjectUrls && timelinePreviewObjectUrls.length) {
            timelinePreviewObjectUrls.forEach(function (u) {
                try { URL.revokeObjectURL(u); } catch (e) { /* ignore */ }
            });
        }
        timelinePreviewObjectUrls = [];
    }

    function timelineComposerSetMediaMode(hasMedia) {
        var card = document.getElementById('timelineComposerCard');
        var head = document.getElementById('timelineComposerCreateHead');
        var block = document.getElementById('timelineComposerMediaBlock');
        var opts = document.querySelector('#timelineComposerForm .timeline-composer-options');
        var taEl = document.getElementById('timelineComposerText');
        if (!card || !head || !block) {
            return;
        }
        if (hasMedia) {
            card.classList.add('timeline-composer-fb--with-media');
            head.classList.remove('d-none');
            block.classList.remove('d-none');
            if (opts) opts.classList.add('d-none');
            if (taEl) taEl.classList.add('timeline-composer-pill--expanded');
        } else {
            card.classList.remove('timeline-composer-fb--with-media');
            head.classList.add('d-none');
            block.classList.add('d-none');
            if (opts) opts.classList.remove('d-none');
            if (taEl) taEl.classList.remove('timeline-composer-pill--expanded');
        }
    }

    function timelineComposerRefreshMediaPreview() {
        var previewGrid = document.getElementById('timelineComposerPreviewImagesGrid');
        var previewVid = document.getElementById('timelineComposerPreviewVideo');
        var hasVideo = vidIn && vidIn.files && vidIn.files.length;
        var hasImages = imgIn && imgIn.files && imgIn.files.length;

        timelineComposerRevokePreviewUrls();

        if (previewVid) {
            previewVid.removeAttribute('src');
            previewVid.classList.add('d-none');
        }
        if (previewGrid) {
            previewGrid.innerHTML = '';
            previewGrid.classList.add('d-none');
        }

        if (!hasVideo && !hasImages) {
            timelineSelectedImageFiles = [];
            timelineComposerSetMediaMode(false);
            return;
        }

        if (hasVideo) {
            var vFile = vidIn.files[0];
            if (previewVid) {
                var vUrl = URL.createObjectURL(vFile);
                timelinePreviewObjectUrls.push(vUrl);
                previewVid.src = vUrl;
                previewVid.classList.remove('d-none');
            }
            timelineSelectedImageFiles = [];
            timelineComposerSetMediaMode(true);
            return;
        }

        // Fotos (até 6)
        var maxPhotos = 6;
        var files = Array.from(imgIn.files || []).slice(0, maxPhotos);
        timelineSelectedImageFiles = files;

        if (previewGrid) {
            files.forEach(function (f) {
                var u = URL.createObjectURL(f);
                timelinePreviewObjectUrls.push(u);
                var im = document.createElement('img');
                im.src = u;
                im.className = 'timeline-composer-preview-thumb';
                im.alt = 'Pré-visualização';
                previewGrid.appendChild(im);
            });
            previewGrid.classList.remove('d-none');
        }

        timelineComposerSetMediaMode(true);
        var taFire = document.getElementById('timelineComposerText');
        if (taFire) {
            taFire.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    if (imgIn && vidIn) {
        imgIn.addEventListener('change', function () {
            if (!(imgIn.files && imgIn.files.length)) {
                timelineComposerRefreshMediaPreview();
                return;
            }

            // Fotos e vídeo não podem coexistir.
            vidIn.value = '';

            // Permite adicionar mais fotos (append) até 6.
            var newFiles = Array.from(imgIn.files || []);
            var maxPhotos = 6;
            var merged = timelineSelectedImageFiles.concat(newFiles).slice(0, maxPhotos);
            timelineSelectedImageFiles = merged;

            var dt = new DataTransfer();
            merged.forEach(function (f) { dt.items.add(f); });
            imgIn.files = dt.files;

            timelineComposerRefreshMediaPreview();
        });
        vidIn.addEventListener('change', function () {
            if (vidIn.files && vidIn.files.length) {
                // Vídeo impede fotos no mesmo post.
                imgIn.value = '';
                timelineSelectedImageFiles = [];
            }
            timelineComposerRefreshMediaPreview();
        });
    }

    var btnRemoveMedia = document.getElementById('btnTimelineComposerRemoveMedia');
    if (btnRemoveMedia && imgIn && vidIn) {
        btnRemoveMedia.addEventListener('click', function () {
            imgIn.value = '';
            vidIn.value = '';
            timelineSelectedImageFiles = [];
            timelineComposerRefreshMediaPreview();
        });
    }

    const ta = document.getElementById('timelineComposerText');
    const charLeftEl = document.getElementById('timelineComposerCharLeft');
    if (ta && charLeftEl) {
        const maxLen = parseInt(ta.getAttribute('maxlength') || '2000', 10);
        const updateCharLeft = function () {
            const len = (ta.value || '').length;
            const left = maxLen - len;
            charLeftEl.textContent = left >= 0 ? String(left) : '0';
        };
        ta.addEventListener('input', updateCharLeft);
        updateCharLeft();
        function autoGrowComposer() {
            var maxH = ta.classList.contains('timeline-composer-pill--expanded') ? 280 : 160;
            ta.style.height = 'auto';
            ta.style.height = Math.min(ta.scrollHeight, maxH) + 'px';
        }
        ta.addEventListener('input', autoGrowComposer);
        ta.addEventListener('focus', autoGrowComposer);
        autoGrowComposer();
    }

    const searchTabPosts = document.getElementById('timelineSearchTabPosts');
    const searchTabPeople = document.getElementById('timelineSearchTabPeople');
    const searchPanelPosts = document.getElementById('timelineSearchPanelPosts');
    const searchPanelPeople = document.getElementById('timelineSearchPanelPeople');
    const searchPostsInput = document.getElementById('timelineSearchPostsInput');
    const searchPeopleInput = document.getElementById('timelineSearchPeopleInput');
    const searchPeopleResults = document.getElementById('timelineSearchPeopleResults');

    if (searchTabPosts && searchTabPeople && searchPanelPosts && searchPanelPeople && searchPostsInput && searchPeopleInput && searchPeopleResults) {
        function setSearchMode(mode) {
            if (mode === 'people') {
                searchTabPeople.classList.add('active');
                searchTabPosts.classList.remove('active');
                searchPanelPeople.classList.remove('d-none');
                searchPanelPosts.classList.add('d-none');
                if (searchPeopleResults) searchPeopleResults.classList.remove('d-none');
            } else {
                searchTabPosts.classList.add('active');
                searchTabPeople.classList.remove('active');
                searchPanelPosts.classList.remove('d-none');
                searchPanelPeople.classList.add('d-none');
                if (searchPeopleResults) searchPeopleResults.classList.add('d-none');
            }
        }

        function applyPostFilter(q) {
            var query = (q || '').trim().toLowerCase();
            var cards = document.querySelectorAll('.timeline-post-card');
            cards.forEach(function (card) {
                var text = (card.textContent || '').toLowerCase();
                card.style.display = !query || text.indexOf(query) !== -1 ? '' : 'none';
            });
        }

        searchTabPosts.onclick = function () { setSearchMode('posts'); applyPostFilter(searchPostsInput.value); };
        searchTabPeople.onclick = function () { setSearchMode('people'); };

        var peopleTimer = null;
        searchPeopleInput.addEventListener('input', function () {
            clearTimeout(peopleTimer);
            var q = (searchPeopleInput.value || '').trim();
            if (!q) {
                searchPeopleResults.innerHTML = '';
                return;
            }
            peopleTimer = setTimeout(function () {
                fetchJson(base + 'timeline-search-users?q=' + encodeURIComponent(q), {})
                    .then(function (data) {
                        searchPeopleResults.innerHTML = '';
                        var users = (data && data.users) ? data.users : [];
                        if (!users.length) {
                            searchPeopleResults.innerHTML = '<div class="list-group-item text-muted small">Nenhum resultado</div>';
                            return;
                        }
                        users.forEach(function (u) {
                            var b = document.createElement('button');
                            b.type = 'button';
                            b.className = 'list-group-item list-group-item-action text-start';
                            b.innerHTML = '<span class="fw-semibold">@' + escapeHtml(u.username || '') + '</span><br><span class="small text-muted">' + escapeHtml(u.name || '') + ' · ' + escapeHtml(u.email || '') + '</span>';
                            b.onclick = function () {
                                var username = u.username || '';
                                var token = username ? '@' + username : '';
                                searchPostsInput.value = token;
                                applyPostFilter(token);
                                setSearchMode('posts');
                                searchPeopleInput.value = '';
                                searchPeopleResults.innerHTML = '';
                            };
                            searchPeopleResults.appendChild(b);
                        });
                    })
                    .catch(function () {
                        searchPeopleResults.innerHTML = '<div class="list-group-item text-muted small">Erro ao buscar</div>';
                    });
            }, 250);
        });

        searchPostsInput.addEventListener('input', function () {
            applyPostFilter(searchPostsInput.value);
        });

        // inicial
        setSearchMode('posts');
        applyPostFilter('');
    }

    const mentionSearch = document.getElementById('timelineMentionSearch');
    const mentionResults = document.getElementById('timelineMentionResults');
    const mentionDd = document.getElementById('timelineMentionDropdown');

    function appendMentionToComposer(username) {
        if (!ta || !username) return;
        const ins = '@' + username + ' ';
        ta.value = (ta.value || '') + (ta.value && !/\s$/.test(ta.value) ? ' ' : '') + ins;
        ta.focus();
        const mEl = document.getElementById('modalTimelineMention');
        if (mEl) {
            const mi = bootstrap.Modal.getInstance(mEl);
            if (mi) mi.hide();
        }
    }

    if (canCreate && mentionSearch && mentionResults) {
        let modalMentionTimer = null;
        mentionSearch.addEventListener('input', function () {
            clearTimeout(modalMentionTimer);
            const q = mentionSearch.value.trim();
            mentionResults.innerHTML = '';
            modalMentionTimer = setTimeout(function () {
                fetchJson(base + 'timeline-search-users?q=' + encodeURIComponent(q), {})
                    .then(function (data) {
                        mentionResults.innerHTML = '';
                        if (!data || !data.users || !data.users.length) {
                            mentionResults.innerHTML = '<div class="list-group-item text-muted small">Nenhum resultado</div>';
                            return;
                        }
                        data.users.forEach(function (u) {
                            const a = document.createElement('button');
                            a.type = 'button';
                            a.className = 'list-group-item list-group-item-action text-start';
                            a.innerHTML = '<span class="fw-semibold">@' + escapeHtml(u.username || '') + '</span><br><span class="small text-muted">' + escapeHtml(u.name || '') + ' · ' + escapeHtml(u.email || '') + '</span>';
                            a.onclick = function () { appendMentionToComposer(u.username || String(u.id)); mentionSearch.value = ''; };
                            mentionResults.appendChild(a);
                        });
                    });
            }, 250);
        });
    }

    (function timelineInlineMentions() {
        if (!mentionDd) return;

        var mState = { active: false, el: null, start: 0, query: '', users: [], hi: -1, timer: null };

        function getMentionContext(text, pos) {
            var before = text.slice(0, pos);
            var m = before.match(/(?:^|\s)@([^\s@]*)$/);
            if (!m) return null;
            var atIdx = before.length - m[0].length + m[0].indexOf('@');
            var query = text.slice(atIdx + 1, pos);
            if (query.indexOf(' ') >= 0 || query.indexOf('\n') >= 0) return null;
            return { start: atIdx, query: query };
        }

        function positionMentionDropdown(el) {
            var r = el.getBoundingClientRect();
            mentionDd.style.position = 'fixed';
            mentionDd.style.left = Math.round(r.left) + 'px';
            mentionDd.style.width = Math.round(Math.max(280, r.width)) + 'px';
            // Se o dropdown cair fora da viewport (ex.: dentro do modal), reposiciona acima do campo.
            var fallbackHeight = 240; // compatível com o CSS (max-height)
            var top = r.bottom + 4;
            if (top + fallbackHeight > (window.innerHeight || document.documentElement.clientHeight)) {
                top = r.top - fallbackHeight - 4;
            }
            mentionDd.style.top = Math.round(Math.max(8, top)) + 'px';
        }

        function hideMentionDd() {
            mentionDd.classList.add('d-none');
            mentionDd.innerHTML = '';
            mState = { active: false, el: null, start: 0, query: '', users: [], hi: -1, timer: null };
        }

        function renderMentionItems() {
            mentionDd.innerHTML = '';
            if (!mState.users.length) {
                mentionDd.innerHTML = '<div class="timeline-mention-dropdown-item text-muted small">Nenhum colaborador encontrado</div>';
                return;
            }
            mState.users.forEach(function (u, i) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'timeline-mention-dropdown-item' + (i === mState.hi ? ' active' : '');
                b.setAttribute('role', 'option');
                b.innerHTML = '<span class="fw-semibold">@' + escapeHtml(u.username || '') + '</span><span class="d-block small text-muted">' + escapeHtml(u.name || '') + ' · ' + escapeHtml(u.email || '') + '</span>';
                b.onclick = function (ev) {
                    ev.preventDefault();
                    applyMentionPick(u);
                };
                mentionDd.appendChild(b);
            });
        }

        function applyMentionPick(u) {
            if (!mState.el || !mState.active) return;
            var el = mState.el;
            var text = el.value;
            var pos = typeof el.selectionStart === 'number' ? el.selectionStart : text.length;
            var ctx = getMentionContext(text, pos);
            if (!ctx) {
                hideMentionDd();
                return;
            }
            var replacement = '@' + (u.username || u.id) + ' ';
            var newText = text.slice(0, ctx.start) + replacement + text.slice(pos);
            el.value = newText;
            var cursor = ctx.start + replacement.length;
            el.selectionStart = el.selectionEnd = cursor;
            el.focus();
            hideMentionDd();
        }

        function fetchMentionUsers(q) {
            fetchJson(base + 'timeline-search-users?q=' + encodeURIComponent(q), {})
                .then(function (data) {
                    mState.users = (data && data.users) ? data.users : [];
                    mState.hi = mState.users.length ? 0 : -1;
                    renderMentionItems();
                    // Garante que o dropdown fique acima do modal/backdrop (evita stacking-context do container).
                    if (mentionDd && mentionDd.parentNode !== document.body) {
                        document.body.appendChild(mentionDd);
                    }
                    if (mentionDd) {
                        mentionDd.style.zIndex = '20000';
                        mentionDd.style.pointerEvents = 'auto';
                        mentionDd.classList.remove('d-none');
                    }
                })
                .catch(function () { hideMentionDd(); });
        }

        document.addEventListener('input', function (ev) {
            var el = ev.target;
            if (!el.classList || !el.classList.contains('timeline-mention-field')) return;
            var text = el.value;
            var pos = typeof el.selectionStart === 'number' ? el.selectionStart : text.length;
            var ctx = getMentionContext(text, pos);
            if (!ctx) {
                hideMentionDd();
                return;
            }
            mState.el = el;
            mState.start = ctx.start;
            mState.query = ctx.query;
            mState.active = true;
            positionMentionDropdown(el);
            clearTimeout(mState.timer);
            mState.timer = setTimeout(function () {
                fetchMentionUsers(ctx.query);
            }, 200);
        }, true);

        document.addEventListener('keydown', function (ev) {
            if (!mState.active || mentionDd.classList.contains('d-none')) return;
            var t = ev.target;
            if (!t.classList || !t.classList.contains('timeline-mention-field')) return;
            if (ev.key === 'Escape') {
                ev.preventDefault();
                hideMentionDd();
                return;
            }
            if (!mState.users.length) return;
            if (ev.key === 'ArrowDown') {
                ev.preventDefault();
                mState.hi = Math.min(mState.hi + 1, mState.users.length - 1);
                renderMentionItems();
                return;
            }
            if (ev.key === 'ArrowUp') {
                ev.preventDefault();
                mState.hi = Math.max(mState.hi - 1, 0);
                renderMentionItems();
                return;
            }
            if (ev.key === 'Enter' && mState.hi >= 0 && mState.users[mState.hi]) {
                ev.preventDefault();
                applyMentionPick(mState.users[mState.hi]);
            }
        }, true);

        document.addEventListener('click', function (ev) {
            if (!mState.active) return;
            if (ev.target.closest && ev.target.closest('#timelineMentionDropdown')) return;
            if (ev.target.closest && ev.target.closest('.timeline-mention-field')) return;
            hideMentionDd();
        });

        window.addEventListener('scroll', function () {
            if (mState.active && mState.el && !mentionDd.classList.contains('d-none')) {
                positionMentionDropdown(mState.el);
            }
        }, true);
    })();

    const modalCameraEl = document.getElementById('modalTimelineCamera');
    const previewEl = document.getElementById('timelineCameraPreview');
    const btnCamPhoto = document.getElementById('btnTimelineCameraPhoto');
    const btnCamVideo = document.getElementById('btnTimelineCameraVideo');
    const btnCamStop = document.getElementById('btnTimelineCameraStop');
    const btnCamOpen = document.getElementById('btnTimelineCamera');
    const capturePhotoIn = document.getElementById('timelineFileCapturePhoto');
    const captureVideoIn = document.getElementById('timelineFileCaptureVideo');
    const cameraHintEl = document.getElementById('timelineCameraHint');

    if (modalCameraEl && previewEl && btnCamOpen && btnCamPhoto && btnCamVideo && btnCamStop && imgIn && vidIn && capturePhotoIn && captureVideoIn) {
        let camStream = null;
        let mediaRecorder = null;
        let mediaChunks = [];
        let stopTimer = null;
        let isRecording = false;

        function stopCameraTracks() {
            if (camStream) {
                camStream.getTracks().forEach(function (t) { t.stop(); });
                camStream = null;
            }
            if (previewEl) previewEl.srcObject = null;
            isRecording = false;
        }

        function resetCameraUI() {
            if (btnCamStop) btnCamStop.classList.add('d-none');
            if (btnCamVideo) btnCamVideo.disabled = false;
            if (btnCamPhoto) btnCamPhoto.disabled = false;
        }

        function setCameraHint(message, isError) {
            if (!cameraHintEl) return;
            cameraHintEl.textContent = message || 'Escolha `Foto` ou `Vídeo` para capturar.';
            cameraHintEl.classList.toggle('text-danger', !!isError);
            cameraHintEl.classList.toggle('text-muted', !isError);
        }

        function getCameraErrorMessage(err) {
            var name = (err && err.name) ? String(err.name) : '';
            if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
                return 'Permissão da câmera negada. Libere a permissão do site nas configurações do navegador.';
            }
            if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
                return 'Nenhuma câmera foi encontrada neste dispositivo.';
            }
            if (name === 'NotReadableError' || name === 'TrackStartError') {
                return 'A câmera está em uso por outro aplicativo.';
            }
            if (name === 'OverconstrainedError' || name === 'ConstraintNotSatisfiedError') {
                return 'Não foi possível atender à configuração de câmera solicitada.';
            }
            if (name === 'SecurityError') {
                return 'Acesso bloqueado por política de segurança do navegador/servidor.';
            }
            return (err && err.message) ? err.message : 'Não foi possível acessar a câmera ao vivo.';
        }

        function ensureCameraStream() {
            if (camStream) return Promise.resolve(camStream);
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                return Promise.reject(new Error('Seu navegador não suporta acesso à câmera.'));
            }
            // Alguns devices/navegadores falham com facingMode específico.
            // Tentamos em cascata: traseira -> frontal -> qualquer câmera.
            var attempts = [
                { video: { facingMode: { ideal: 'environment' } }, audio: false },
                { video: { facingMode: { ideal: 'user' } }, audio: false },
                { video: true, audio: false }
            ];
            var i = 0;
            function tryNext(lastErr) {
                if (i >= attempts.length) {
                    return Promise.reject(lastErr || new Error('Não foi possível iniciar a câmera.'));
                }
                var constraints = attempts[i++];
                return navigator.mediaDevices.getUserMedia(constraints).then(function (stream) {
                    camStream = stream;
                    previewEl.srcObject = stream;
                    if (previewEl && typeof previewEl.play === 'function') {
                        previewEl.play().catch(function () { /* ignore */ });
                    }
                    return stream;
                }).catch(function (err) {
                    return tryNext(err);
                });
            }
            return tryNext(null).then(function (stream) {
                if (!stream || !stream.getVideoTracks || !stream.getVideoTracks().length) {
                    throw new Error('A câmera não retornou trilha de vídeo.');
                }
                return stream;
            });
        }

        function canUseLiveCamera() {
            return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
        }

        function isSecureCameraContext() {
            var h = (window.location.hostname || '').toLowerCase();
            return window.isSecureContext === true || h === 'localhost' || h === '127.0.0.1';
        }

        function openCaptureWithFallback(captureInput, regularInput) {
            try {
                captureInput.click();
            } catch (e) {
                if (regularInput) regularInput.click();
                return;
            }
            // Alguns navegadores desktop ignoram `capture`; nesses casos abre seletor normal.
            setTimeout(function () {
                if (captureInput && captureInput.files && captureInput.files.length === 0 && regularInput) {
                    regularInput.click();
                }
            }, 300);
        }

        function openNativeCaptureFallback(kind) {
            var k = kind || 'photo';
            alert('Câmera ao vivo exige HTTPS (ou localhost). Vamos abrir a captura nativa do dispositivo.');
            if (k === 'video') {
                openCaptureWithFallback(captureVideoIn, vidIn);
                return;
            }
            openCaptureWithFallback(capturePhotoIn, imgIn);
        }

        function openCameraModal() {
            const m = bootstrap.Modal.getInstance(modalCameraEl) || new bootstrap.Modal(modalCameraEl);
            m.show();
        }

        function stopRecordingAndClose() {
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
            } else {
                stopCameraTracks();
                var mi = bootstrap.Modal.getInstance(modalCameraEl);
                if (mi) mi.hide();
            }
        }

        btnCamOpen.addEventListener('click', function () {
            resetCameraUI();
            setCameraHint('Preparando câmera...', false);
            if (!isSecureCameraContext() || !canUseLiveCamera()) {
                setCameraHint('Prévia ao vivo indisponível neste contexto. Abrindo captura nativa...', true);
                openNativeCaptureFallback('photo');
                return;
            }
            openCameraModal();
        });

        modalCameraEl.addEventListener('shown.bs.modal', function () {
            resetCameraUI();
            setCameraHint('Solicitando permissão para câmera...', false);
            ensureCameraStream()
                .then(function () {
                    setCameraHint('Câmera ativa. Escolha `Foto` ou `Vídeo` para capturar.', false);
                })
                .catch(function (e) {
                    var msg = getCameraErrorMessage(e);
                    setCameraHint(msg, true);
                    alert(msg + ' Vamos abrir a captura nativa do dispositivo.');
                    var mi = bootstrap.Modal.getInstance(modalCameraEl);
                    if (mi) mi.hide();
                    openCaptureWithFallback(capturePhotoIn, imgIn);
                });
        });

        modalCameraEl.addEventListener('hidden.bs.modal', function () {
            if (stopTimer) {
                clearTimeout(stopTimer);
                stopTimer = null;
            }
            if (isRecording) {
                // Se o modal for fechado durante gravação, finalize e limpe ao parar.
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    mediaRecorder.stop();
                    return;
                }
            }
            resetCameraUI();
            stopCameraTracks();
            setCameraHint('Escolha `Foto` ou `Vídeo` para capturar.', false);
        });

        btnCamPhoto.addEventListener('click', function () {
            if (!camStream) return;
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                // Evita captura durante gravação.
                return;
            }
            const canvas = document.createElement('canvas');
            const w = previewEl.videoWidth || 640;
            const h = previewEl.videoHeight || 480;
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(previewEl, 0, 0, w, h);

            canvas.toBlob(function (blob) {
                if (!blob) return;
                const dt = new DataTransfer();
                dt.items.add(new File([blob], 'camera-' + Date.now() + '.jpg', { type: 'image/jpeg' }));
                // Fotos podem coexistir com outras fotos (até 6). Mas não com vídeo.
                vidIn.value = '';
                var newFile = dt.files[0];
                var merged = (timelineSelectedImageFiles || []).concat([newFile]).slice(0, 6);
                timelineSelectedImageFiles = merged;
                var dt2 = new DataTransfer();
                merged.forEach(function (f) { dt2.items.add(f); });
                imgIn.files = dt2.files;
                timelineComposerRefreshMediaPreview();
                resetCameraUI();
                stopCameraTracks();
                var mi = bootstrap.Modal.getInstance(modalCameraEl);
                if (mi) mi.hide();
            }, 'image/jpeg', 0.88);
        });

        btnCamVideo.addEventListener('click', function () {
            if (!camStream) return;
            if (isRecording) return;
            mediaChunks = [];

            let mime = 'video/webm;codecs=vp8,opus';
            if (window.MediaRecorder && !MediaRecorder.isTypeSupported(mime)) {
                mime = 'video/webm';
            }

            try {
                mediaRecorder = new MediaRecorder(camStream, { mimeType: mime });
            } catch (e) {
                alert('Gravação não suportada neste navegador.');
                return;
            }

            isRecording = true;
            resetCameraUI();
            btnCamStop.classList.remove('d-none');
            btnCamVideo.disabled = true;
            btnCamPhoto.disabled = true;
            setCameraHint('Gravando vídeo... toque em "Parar" para finalizar.', false);

            mediaRecorder.ondataavailable = function (e) {
                if (e.data && e.data.size) mediaChunks.push(e.data);
            };
            mediaRecorder.onstop = function () {
                if (stopTimer) {
                    clearTimeout(stopTimer);
                    stopTimer = null;
                }
                const blob = new Blob(mediaChunks, { type: 'video/webm' });
                const dt = new DataTransfer();
                dt.items.add(new File([blob], 'gravacao-' + Date.now() + '.webm', { type: 'video/webm' }));
                vidIn.files = dt.files;
                imgIn.value = '';
                timelineSelectedImageFiles = [];
                timelineComposerRefreshMediaPreview();

                resetCameraUI();
                stopCameraTracks();
                setCameraHint('Vídeo capturado com sucesso.', false);
                var mi = bootstrap.Modal.getInstance(modalCameraEl);
                if (mi) mi.hide();
            };

            mediaRecorder.start(1000);
            const maxMs = 90000;
            stopTimer = setTimeout(function () {
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    mediaRecorder.stop();
                }
            }, maxMs);
        });

        btnCamStop.addEventListener('click', function () {
            stopRecordingAndClose();
        });

        var btnCamList = document.getElementById('btnTimelineComposerCameraList');
        if (btnCamList) {
            btnCamList.addEventListener('click', function () {
                resetCameraUI();
                if (!isSecureCameraContext() || !canUseLiveCamera()) {
                    openNativeCaptureFallback('video');
                    return;
                }
                openCameraModal();
            });
        }

        capturePhotoIn.addEventListener('change', function () {
            var files = Array.from(capturePhotoIn.files || []);
            if (!files.length) return;
            vidIn.value = '';
            var merged = (timelineSelectedImageFiles || []).concat(files).slice(0, 6);
            timelineSelectedImageFiles = merged;
            var dt = new DataTransfer();
            merged.forEach(function (f) { dt.items.add(f); });
            imgIn.files = dt.files;
            timelineComposerRefreshMediaPreview();
            capturePhotoIn.value = '';
        });

        captureVideoIn.addEventListener('change', function () {
            var files = Array.from(captureVideoIn.files || []);
            if (!files.length) return;
            var f = files[0];
            var dt = new DataTransfer();
            dt.items.add(f);
            vidIn.files = dt.files;
            imgIn.value = '';
            timelineSelectedImageFiles = [];
            timelineComposerRefreshMediaPreview();
            captureVideoIn.value = '';
        });
    }

    // Envio via fetch: em alguns navegadores (mobile/câmera) arquivos atribuídos com DataTransfer não entram no POST nativo.
    const composerForm = document.getElementById('timelineComposerForm');
    if (composerForm) {
        composerForm.addEventListener('submit', function (ev) {
            ev.preventDefault();
            const submitBtns = composerForm.querySelectorAll('button[type="submit"]');
            submitBtns.forEach(function (b) { b.disabled = true; });
            const fd = new FormData(composerForm);
            const actionUrl = composerForm.getAttribute('action');
            fetch(actionUrl, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                redirect: 'follow',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json, text/html;q=0.9, */*;q=0.8'
                }
            })
                .then(function (response) {
                    var ct = (response.headers.get('Content-Type') || '').toLowerCase();
                    if (ct.indexOf('application/json') !== -1) {
                        return response.json().then(function (data) {
                            return { kind: 'json', data: data, response: response };
                        });
                    }
                    if (response.redirected && response.url) {
                        window.location.href = response.url;
                        return null;
                    }
                    return response.text().then(function (html) {
                        return { kind: 'html', response: response, html: html };
                    });
                })
                .then(function (result) {
                    if (!result) {
                        return;
                    }
                    if (result.kind === 'json') {
                        var d = result.data || {};
                        if (d.success === false || d.error) {
                            alert(d.error || d.message || 'Não foi possível publicar. Tente novamente.');
                            return;
                        }
                        window.location.href = base.replace(/\/?$/, '/') + 'timeline';
                        return;
                    }
                    var h = result.html || '';
                    if (h.indexOf('Erro 004') !== -1 || h.indexOf('Erro 003') !== -1) {
                        document.open();
                        document.write(h);
                        document.close();
                        return;
                    }
                    window.location.href = base.replace(/\/?$/, '/') + 'timeline';
                })
                .catch(function () {
                    alert('Não foi possível enviar a publicação. Verifique a conexão e tente novamente.');
                })
                .finally(function () {
                    submitBtns.forEach(function (b) { b.disabled = false; });
                });
        });
    }
})();
</script>
