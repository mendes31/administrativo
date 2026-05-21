<?php
$urlAdm = rtrim($_ENV['URL_ADM'] ?? '', '/') . '/';
$csrfCreate = \App\adms\Helpers\CSRFHelper::generateCSRFToken('timeline_create_post');
$csrfDelete = \App\adms\Helpers\CSRFHelper::generateCSRFToken('timeline_delete_post');
$composerUid = (int)($_SESSION['user_id'] ?? 0);
$composerAvatarPath = null;
if (\App\adms\Helpers\ImageHelper::userImageExists($composerUid, (string)($_SESSION['user_image'] ?? ''))) {
    $composerAvatarPath = 'users/' . $composerUid . '/' . $_SESSION['user_image'];
}
$composerName = trim((string)($_SESSION['user_name'] ?? ''));
$composerFirst = $composerName !== '' ? preg_split('/\s+/', $composerName, 2)[0] : 'você';
$timelineSearchQuery = isset($this->data['search_query']) ? (string)$this->data['search_query'] : '';
$timelineActiveTag = isset($this->data['active_tag']) ? (string)$this->data['active_tag'] : '';
$timelineProfileUid = (int)($this->data['timeline_profile_user_id'] ?? 0);
$timelineProfile = isset($this->data['timeline_profile']) && is_array($this->data['timeline_profile']) ? $this->data['timeline_profile'] : null;
$timelineProfileIsOwn = !empty($this->data['timeline_profile_is_own']);
$timelineComposerPrefill = isset($this->data['timeline_composer_prefill']) ? (string)$this->data['timeline_composer_prefill'] : '';
/** @var array{type:string,target_user_id:int,years:?int}|array $timelineComposerContext */
$timelineComposerContext = isset($this->data['timeline_composer_context']) && is_array($this->data['timeline_composer_context'])
    ? $this->data['timeline_composer_context']
    : ['type' => '', 'target_user_id' => 0, 'years' => null];
$renderInitialsAvatar = static function (string $name, int $sizePx, string $className = ''): string {
    return \App\adms\Helpers\ImageHelper::renderInitialsAvatar($name, $sizePx, [
        'class' => $className,
    ]);
};
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($urlAdm); ?>public/adms/css/timeline-feed.css?v=42">

<div class="container-fluid px-3 px-md-4">
    <?php include __DIR__ . '/../partials/alerts.php'; ?>
    <div id="timelineInlineFeedback" class="timeline-inline-feedback" aria-live="polite" aria-atomic="true"></div>
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3 mt-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h2 class="mb-0 mobile-hide-page-title">
                    <?php if ($timelineProfileUid > 0 && $timelineProfile): ?>
                        <i class="fas fa-user text-info me-2"></i><?php echo htmlspecialchars((string)($timelineProfile['name'] ?? 'Perfil')); ?>
                    <?php else: ?>
                        <i class="fas fa-stream text-info me-2"></i>Timeline
                    <?php endif; ?>
                    </h2>
                    <a href="<?php echo htmlspecialchars($urlAdm); ?>timeline-members" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-users me-1"></i>Membros
                    </a>
                </div>
                <nav aria-label="breadcrumb" class="ms-md-auto mobile-hide-breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($urlAdm); ?>dashboard">Dashboard</a></li>
                        <?php if ($timelineProfileUid > 0): ?>
                            <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($urlAdm); ?>timeline">Timeline</a></li>
                            <li class="breadcrumb-item active">Perfil</li>
                        <?php else: ?>
                            <li class="breadcrumb-item active">Timeline</li>
                        <?php endif; ?>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="timeline-layout">
        <div class="timeline-main">
            <?php if ($timelineProfileUid > 0 && $timelineProfile): ?>
            <div class="card border-0 shadow-sm mb-3 timeline-profile-header-card">
                <div class="card-body">
                    <div class="d-flex flex-column flex-sm-row gap-3 align-items-start">
                        <?php
                        $tpAvatar = null;
                        if (\App\adms\Helpers\ImageHelper::userImageExists($timelineProfileUid, (string)($timelineProfile['image'] ?? ''))) {
                            $tpAvatar = 'users/' . $timelineProfileUid . '/' . $timelineProfile['image'];
                        }
                        if ($tpAvatar !== null) {
                            echo \App\adms\Helpers\ImageHelper::displayImage($tpAvatar, [
                                'class' => 'rounded-circle flex-shrink-0 timeline-profile-header-avatar',
                                'alt' => '',
                                'width' => '96',
                                'height' => '96',
                            ], 'icon_user.png', 'users');
                        } else {
                            echo $renderInitialsAvatar((string)($timelineProfile['name'] ?? 'Usuário'), 96, 'flex-shrink-0 timeline-profile-header-avatar');
                        }
                        ?>
                        <div class="flex-grow-1 min-w-0">
                            <h3 class="h5 mb-1"><?php echo htmlspecialchars((string)($timelineProfile['name'] ?? '')); ?></h3>
                            <div class="text-muted small mb-2">
                                @<?php echo htmlspecialchars((string)($timelineProfile['username'] ?? '')); ?>
                                <?php
                                $tpPos = \App\adms\Helpers\PositionDisplayHelper::formatForDisplay((string)($timelineProfile['pos_name'] ?? ''));
                                $tpDep = trim((string)($timelineProfile['dep_name'] ?? ''));
                                ?>
                                <?php if ($tpPos !== '' || $tpDep !== ''): ?>
                                    <span class="d-block mt-1">
                                        <?php if ($tpPos !== ''): ?><?php echo htmlspecialchars($tpPos); ?><?php endif; ?>
                                        <?php if ($tpPos !== '' && $tpDep !== ''): ?><span class="text-muted"> · </span><?php endif; ?>
                                        <?php if ($tpDep !== ''): ?><?php echo htmlspecialchars($tpDep); ?><?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($timelineProfileIsOwn): ?>
                            <form method="post" action="" class="mb-0">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($this->data['csrf_timeline_profile_bio'] ?? '')); ?>">
                                <label for="timelineBioInput" class="form-label small mb-1">Apresentação <span class="text-muted">(visível para quem visita seu perfil)</span></label>
                                <textarea name="timeline_bio" id="timelineBioInput" class="form-control form-control-sm" rows="3" maxlength="500" placeholder="Uma frase sobre você, time ou área…"><?php echo htmlspecialchars((string)($timelineProfile['timeline_bio'] ?? '')); ?></textarea>
                                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                    <button type="submit" class="btn btn-primary btn-sm">Salvar apresentação</button>
                                    <span class="small text-muted">Máx. 500 caracteres</span>
                                </div>
                            </form>
                            <?php else: ?>
                                <?php $bioShow = trim((string)($timelineProfile['timeline_bio'] ?? '')); ?>
                                <?php if ($bioShow !== ''): ?>
                                    <p class="mb-0 small text-body-secondary"><?php echo nl2br(htmlspecialchars($bioShow)); ?></p>
                                <?php else: ?>
                                    <p class="mb-0 small text-muted fst-italic">Sem apresentação.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <a href="<?php echo htmlspecialchars($urlAdm); ?>timeline-members" class="btn btn-outline-primary btn-sm"><i class="fas fa-users me-1"></i>Membros</a>
                                <a href="<?php echo htmlspecialchars($urlAdm); ?>timeline" class="btn btn-outline-secondary btn-sm"><i class="fas fa-stream me-1"></i>Voltar ao feed</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($this->data['can_create'])): ?>
            <div class="card timeline-composer-card timeline-composer-fb mb-3" id="timelineComposerCard">
                <div class="card-body py-2 px-3">
                    <form method="post" action="<?php echo htmlspecialchars($urlAdm); ?>create-timeline-post" enctype="multipart/form-data" class="timeline-composer-form" id="timelineComposerForm" novalidate>
                        <input type="hidden" name="csrf_token" id="timelineComposerCsrfToken" value="<?php echo htmlspecialchars($csrfCreate); ?>">
                        <input type="hidden" name="shared_from_post_id" id="timelineComposerSharedFromPostId" value="">
                        <input type="hidden" name="post_type" id="timelineComposerPostType" value="regular">
                        <?php if (!empty($timelineComposerContext['type']) && !$timelineProfileIsOwn && $timelineProfileUid > 0): ?>
                            <input type="hidden" name="context_type" value="<?php echo htmlspecialchars((string)$timelineComposerContext['type']); ?>">
                            <input type="hidden" name="context_target_user_id" value="<?php echo (int)$timelineComposerContext['target_user_id']; ?>">
                            <?php if ($timelineComposerContext['years'] !== null): ?>
                                <input type="hidden" name="context_years" value="<?php echo (int)$timelineComposerContext['years']; ?>">
                            <?php endif; ?>
                        <?php endif; ?>
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
                                <?php if ($composerAvatarPath !== null): ?>
                                    <?php echo \App\adms\Helpers\ImageHelper::displayImage($composerAvatarPath, [
                                        'alt' => 'Sua foto',
                                        'class' => 'timeline-composer-avatar-img',
                                        'width' => '40',
                                        'height' => '40',
                                    ], 'icon_user.png', 'users'); ?>
                                <?php else: ?>
                                    <?php echo $renderInitialsAvatar($composerName !== '' ? $composerName : 'Usuário', 40, 'timeline-composer-avatar-img'); ?>
                                <?php endif; ?>
                            </div>
                            <div class="timeline-composer-input-wrap flex-grow-1 min-w-0">
                                <label for="timelineComposerText" class="visually-hidden">Texto da publicação</label>
                                <textarea name="content" id="timelineComposerText" class="form-control timeline-mention-field timeline-composer-pill" rows="1" placeholder="No que você está pensando, <?php echo htmlspecialchars($composerFirst); ?>?" autocomplete="off" maxlength="2000"><?php echo htmlspecialchars($timelineComposerPrefill); ?></textarea>
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
                            <button type="button" class="timeline-composer-icon-btn" id="btnTimelinePoll" title="Criar enquete" aria-label="Criar enquete">
                                <i class="fas fa-poll-h" aria-hidden="true"></i>
                            </button>
                            <?php if (!empty($this->data['can_feature'])): ?>
                            <div id="timelineComposerFeatureWrap" class="timeline-composer-feature-wrap">
                                <input type="checkbox" class="visually-hidden" name="is_featured" id="timelineComposerFeatured" value="1" tabindex="-1">
                                <button type="button" class="timeline-composer-icon-btn timeline-composer-icon-feature" id="btnTimelineFeature" title="Manter em destaque (topo do dia)" aria-label="Manter em destaque" aria-pressed="false">
                                    <i class="fas fa-thumbtack" aria-hidden="true"></i>
                                </button>
                                <span class="timeline-composer-feature-label d-none d-md-inline">Destaque</span>
                            </div>
                            <?php endif; ?>
                            <button type="submit" class="timeline-composer-submit ms-auto" id="timelineComposerSubmitIcon" title="Publicar" aria-label="Publicar">
                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="timelineComposerPollBlock" class="timeline-poll-editor d-none mt-2 border rounded p-2 bg-light-subtle">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <div class="small fw-semibold mb-0"><i class="fas fa-poll-h me-1"></i>Nova enquete</div>
                                <button type="button" class="btn btn-link btn-sm text-danger p-0" id="timelineComposerPollClear">Remover enquete</button>
                            </div>
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm" name="poll_question" id="timelinePollQuestion" placeholder="Pergunta da enquete">
                            </div>
                            <div id="timelinePollOptionsWrap" class="d-flex flex-column gap-1 mb-2">
                                <input type="text" class="form-control form-control-sm" name="poll_options[]" placeholder="Opção 1">
                                <input type="text" class="form-control form-control-sm" name="poll_options[]" placeholder="Opção 2">
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm mb-2" id="timelinePollAddOption">+ opção</button>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small mb-1">Início (opcional)</label>
                                    <input type="datetime-local" class="form-control form-control-sm" name="poll_starts_at" id="timelinePollStartsAt">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1">Encerramento</label>
                                    <input type="datetime-local" class="form-control form-control-sm" name="poll_ends_at" id="timelinePollEndsAt">
                                </div>
                            </div>
                        </div>
                        <div id="timelineComposerSharePreview" class="timeline-share-preview d-none mt-2 p-2 border rounded bg-light-subtle">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="min-w-0">
                                    <div class="small text-muted">Repostando publicação de <strong id="timelineComposerShareAuthor">-</strong></div>
                                    <div class="small text-truncate" id="timelineComposerShareExcerpt">-</div>
                                </div>
                                <button type="button" class="btn btn-link btn-sm text-danger p-0" id="timelineComposerShareClear" title="Cancelar repost">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
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
                            <span id="timelineComposerCharHint" class="small text-muted text-truncate d-none d-md-inline mb-0">Menções: <code>@</code> username, <code>@todos</code>, <code>@everyone</code> ou <code>@nome-do-departamento</code> (ex.: <code>@financeiro</code>)</span>
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

            <?php if (empty($this->data['timeline_profile_user_id'])): ?>
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
                            <input type="text" id="timelineSearchPostsInput" class="form-control form-control-sm timeline-search-pill" placeholder="Buscar no feed (Enter) ou nesta página…" autocomplete="off" aria-label="Buscar postagens" value="<?php echo htmlspecialchars($timelineSearchQuery); ?>">
                        </div>
                        <div id="timelineSearchPanelPeople" class="d-none">
                            <input type="text" id="timelineSearchPeopleInput" class="form-control form-control-sm timeline-search-pill" placeholder="Nome ou e-mail…" autocomplete="off" aria-label="Buscar pessoas">
                        </div>
                    </div>
                </div>
                <div id="timelineSearchPeopleResults" class="list-group timeline-mention-results mt-2 d-none timeline-search-people-results" style="max-height: 220px; overflow-y: auto;"></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($this->data['active_tag'])): ?>
                <div class="alert alert-info py-2 px-3 small mb-3">
                    Exibindo publicações com <strong>#<?php echo htmlspecialchars($this->data['active_tag']); ?></strong>.
                    <a href="<?php echo htmlspecialchars($urlAdm); ?>timeline" class="ms-2">Limpar filtro</a>
                </div>
            <?php endif; ?>
            <?php if ($timelineSearchQuery !== ''): ?>
                <?php
                $clearSearchHref = $urlAdm . 'timeline';
                $clearQs = [];
                if (!empty($this->data['active_tag'])) {
                    $clearQs[] = 'tag=' . rawurlencode((string)$this->data['active_tag']);
                }
                if ($clearQs !== []) {
                    $clearSearchHref .= '?' . implode('&', $clearQs);
                }
                ?>
                <div class="alert alert-secondary py-2 px-3 small mb-3">
                    Resultados da busca por <strong><?php echo htmlspecialchars($timelineSearchQuery); ?></strong>.
                    <a href="<?php echo htmlspecialchars($clearSearchHref); ?>" class="ms-2">Limpar busca</a>
                </div>
            <?php endif; ?>

            <?php include __DIR__ . '/partials/feed_posts.php'; ?>

            <?php if (!empty($this->data['pagination']['html'])): ?>
                <div class="timeline-pagination-wrap mt-3 mb-2">
                    <?php echo $this->data['pagination']['html']; ?>
                </div>
            <?php endif; ?>
        </div>

        <aside class="timeline-sidebar d-none d-lg-block">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold text-muted text-uppercase small mb-3">Dicas</h6>
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-2">Respeite o ambiente corporativo.</li>
                        <li class="mb-2">Comunicados oficiais continuam em <a href="<?php echo htmlspecialchars($urlAdm); ?>list-informativos">Informativos</a>.</li>
                        <li class="mb-2">Menções: <code>@username</code>, <code>@todos</code>, <code>@everyone</code> ou <code>@nome-do-departamento</code> (slug do nome no cadastro, ex.: <code>@financeiro</code> — use o autocomplete); vídeos curtos por arquivo ou pela câmera.</li>
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
                <div class="timeline-camera-frame bg-dark rounded overflow-hidden">
                    <video id="timelineCameraPreview" class="timeline-camera-preview" autoplay playsinline muted></video>
                </div>
                <div class="text-muted small mt-2" id="timelineCameraHint">Escolha `Foto` ou `Vídeo` para capturar.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary d-none" id="btnTimelineCameraSwitch">Usar frontal</button>
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

<div class="modal fade" id="modalTimelinePollVotes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Votos da enquete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <input type="text" class="form-control form-control-sm" id="timelinePollVotesSearch" placeholder="Buscar por nome ou @usuário">
                </div>
                <div id="timelinePollVotesBody" class="small text-muted">Carregando…</div>
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
                <p class="small text-muted mt-2 mb-0">Menções: <code>@username</code>, <code>@todos</code>, <code>@everyone</code> ou <code>@nome-do-departamento</code> (ex.: <code>@financeiro</code>). Mídia não é alterada por aqui.</p>
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

<?php
$__timelineFeedInit = [
    'base' => $urlAdm,
    'timelineResolvedPostId' => (int) ($this->data['resolved_focus_post_id'] ?? 0),
    'timelineResolvedCommentId' => (int) ($this->data['resolved_focus_comment_id'] ?? 0),
    'timelineActiveTagJs' => $timelineActiveTag,
    'canReport' => !empty($this->data['can_report']),
    'canCreate' => !empty($this->data['can_create']),
    'canLike' => !empty($this->data['can_like']),
    'pollStateMap' => $this->data['poll_map'] ?? [],
    'timelineEditCsrf' => (string) ($this->data['csrf_timeline_edit'] ?? ''),
    'timelineCommentCsrf' => (string) ($this->data['csrf_timeline_comment'] ?? ''),
    'timelineLikeCsrf' => (string) ($this->data['csrf_timeline_like'] ?? ''),
    'timelineReportCsrf' => (string) ($this->data['csrf_timeline_report'] ?? ''),
    'timelineDeleteCsrf' => $csrfDelete,
];
?>
<script>
window.__TimelineFeedInit = <?php echo json_encode($__timelineFeedInit, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="<?php echo htmlspecialchars($urlAdm); ?>public/adms/js/timeline-feed.js?v=5" defer></script>
