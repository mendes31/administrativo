<?php
use App\adms\Helpers\TimelineReactionHelper;
use App\adms\Helpers\TimelineMentionHelper;
use App\adms\Models\Repository\UsersRepository;

$posts = $this->data['posts'] ?? [];
$reactionMap = $this->data['reaction_map'] ?? [];
$reactionSummaries = $this->data['reaction_summaries'] ?? [];
$currentUserId = (int)($this->data['current_user_id'] ?? 0);
$urlAdm = $_ENV['URL_ADM'] ?? '';
$mentionMap = $this->data['mention_name_map'] ?? [];
$usersRepoMention = new UsersRepository();
?>
<?php if (empty($posts)): ?>
    <div class="text-center text-muted py-5">
        <i class="fas fa-comments fa-3x mb-3 opacity-50"></i>
        <p class="mb-0">Nenhuma publicação ainda. Seja o primeiro a publicar!</p>
    </div>
<?php endif; ?>

<?php foreach ($posts as $postRow):
    $pid = (int)($postRow['id'] ?? 0);
    $myReaction = $reactionMap[$pid] ?? null;
    $summary = $reactionSummaries[$pid] ?? [];
    $totalReactions = array_sum($summary);
    $isAuthor = $currentUserId > 0 && (int)($postRow['user_id'] ?? 0) === $currentUserId;
    $avatarPath = null;
    if (!empty($postRow['author_image']) && $postRow['author_image'] !== 'icon_user.png') {
        $avatarPath = 'users/' . (int)($postRow['user_id'] ?? 0) . '/' . $postRow['author_image'];
    }
    $txt = trim((string)($postRow['content'] ?? ''));
    $editB64 = base64_encode($txt);
    $stackTypes = TimelineReactionHelper::stackTypesFromSummary($summary);
    $commentsCount = (int)($postRow['comments_count'] ?? 0);
    $canModerate = !empty($this->data['can_moderate']);
    $canDelete = $isAuthor || $canModerate;
    $imgs = $postRow['image_paths'] ?? [];
    if (!is_array($imgs)) $imgs = [];
    if ($imgs === [] && !empty($postRow['image_path'])) {
        $imgs = [(string)$postRow['image_path']];
    }
    $imgsJson = htmlspecialchars(json_encode(array_values($imgs)), ENT_QUOTES, 'UTF-8');
?>
    <article class="card timeline-post-card mb-3" id="timeline-post-<?php echo $pid; ?>" data-post-id="<?php echo $pid; ?>">
        <div class="card-body">
            <div class="timeline-post-header mb-2">
                <?php
                echo \App\adms\Helpers\ImageHelper::displayImage($avatarPath, [
                    'class' => 'timeline-avatar',
                    'alt' => '',
                ], 'icon_user.png', 'users');
                ?>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-bold text-truncate"><?php echo htmlspecialchars($postRow['author_name'] ?? 'Usuário'); ?></div>
                    <div class="text-muted small">
                        <?php echo date('d/m/Y H:i', strtotime($postRow['created_at'] ?? 'now')); ?>
                        <?php if (!empty($postRow['edited_at'])): ?>
                            <span class="ms-1">· editado</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($isAuthor || $canDelete): ?>
                <div class="timeline-post-header-actions">
                    <?php if ($isAuthor): ?>
                        <button type="button" class="btn btn-link btn-sm text-muted py-0 btn-timeline-edit-post"
                                data-post-id="<?php echo $pid; ?>"
                                data-post-content-b64="<?php echo htmlspecialchars($editB64, ENT_QUOTES, 'UTF-8'); ?>"
                                title="Editar publicação">
                            <i class="fas fa-pen"></i>
                        </button>
                    <?php endif; ?>
                    <?php if ($canDelete): ?>
                        <button type="button" class="btn btn-link btn-sm text-danger py-0 btn-timeline-delete-post"
                                data-post-id="<?php echo $pid; ?>"
                                title="Deletar publicação"
                                aria-label="Deletar publicação">
                            <i class="fas fa-trash"></i>
                        </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="timeline-post-body mb-2" id="timeline-post-body-<?php echo $pid; ?>">
                <?php
                if ($txt !== '') {
                    echo '<p class="mb-0">', TimelineMentionHelper::renderHtml($txt, $urlAdm, $mentionMap, $usersRepoMention), '</p>';
                }
                ?>
            </div>
            <?php if (!empty($postRow['video_path'])): ?>
                <div class="mb-2 mx-n3">
                    <video class="timeline-post-media w-100 rounded" controls playsinline preload="metadata"
                           src="<?php echo htmlspecialchars($urlAdm); ?>serve-file?path=<?php echo urlencode($postRow['video_path']); ?>"></video>
                </div>
            <?php elseif (!empty($imgs)): ?>
                <div class="mb-2 mx-n3">
                    <?php if (count($imgs) === 1): ?>
                        <img src="<?php echo htmlspecialchars($urlAdm); ?>serve-file?path=<?php echo urlencode($imgs[0]); ?>"
                             class="timeline-post-media timeline-media-clickable"
                             data-images="<?php echo $imgsJson; ?>"
                             data-index="0"
                             alt="">
                    <?php else: ?>
                        <?php $colCount = count($imgs) === 2 ? 2 : 3; ?>
                        <div class="timeline-media-grid" style="grid-template-columns: repeat(<?php echo (int)$colCount; ?>, minmax(0, 1fr));">
                            <?php foreach ($imgs as $i => $img): ?>
                                <img src="<?php echo htmlspecialchars($urlAdm); ?>serve-file?path=<?php echo urlencode($img); ?>"
                                     class="timeline-media-grid-item timeline-media-clickable"
                                     data-images="<?php echo $imgsJson; ?>"
                                     data-index="<?php echo (int)$i; ?>"
                                     alt="">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="timeline-engagement-bar d-flex justify-content-between align-items-center py-2 px-1 border-top border-bottom">
                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-body timeline-reactions-summary-hit d-flex align-items-center gap-2 <?php echo $totalReactions > 0 ? '' : 'text-muted'; ?>"
                        data-post-id="<?php echo $pid; ?>"
                        title="Ver reações">
                    <span class="timeline-reaction-stack" data-post-id="<?php echo $pid; ?>">
                        <?php if ($totalReactions > 0): ?>
                            <?php foreach ($stackTypes as $idx => $st): ?>
                                <span class="timeline-reaction-stack-item" style="z-index: <?php echo 10 - $idx; ?>">
                                    <i class="<?php echo htmlspecialchars(TimelineReactionHelper::iconClass($st) . ' ' . TimelineReactionHelper::colorClass($st)); ?>"></i>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="timeline-reaction-stack-item timeline-reaction-stack-empty"><i class="far fa-thumbs-up opacity-50"></i></span>
                        <?php endif; ?>
                    </span>
                    <span class="timeline-like-count small" data-post-id="<?php echo $pid; ?>"><?php echo $totalReactions; ?></span>
                </button>
                <button type="button" class="btn btn-link btn-sm text-muted p-0 text-decoration-none btn-timeline-comments-toggle" data-post-id="<?php echo $pid; ?>">
                    <?php echo $commentsCount; ?> comentário<?php echo $commentsCount !== 1 ? 's' : ''; ?>
                </button>
            </div>

            <div class="timeline-action-bar d-flex flex-column gap-2 pt-2">
                <div class="d-flex gap-1 align-items-stretch">
                    <div class="d-flex flex-fill gap-0 min-w-0 timeline-curtir-bundle" data-post-id="<?php echo $pid; ?>">
                        <div class="position-relative flex-fill timeline-curtir-wrap" data-post-id="<?php echo $pid; ?>">
                            <button type="button" class="btn btn-light w-100 btn-sm timeline-curtir-main d-flex align-items-center justify-content-center gap-2 text-secondary"
                                    data-post-id="<?php echo $pid; ?>">
                                <?php if ($myReaction): ?>
                                    <i class="<?php echo htmlspecialchars(TimelineReactionHelper::iconClass($myReaction) . ' ' . TimelineReactionHelper::colorClass($myReaction)); ?>"></i>
                                    <span class="timeline-curtir-label small fw-semibold"><?php echo htmlspecialchars(TimelineReactionHelper::label($myReaction)); ?></span>
                                <?php else: ?>
                                    <i class="far fa-thumbs-up"></i>
                                    <span class="timeline-curtir-label small fw-semibold">Curtir</span>
                                <?php endif; ?>
                            </button>
                            <div class="timeline-reaction-tray shadow rounded-pill bg-white border p-1 d-none d-md-flex" role="group" aria-label="Escolher reação">
                                <?php foreach (TimelineReactionHelper::TYPES as $rt):
                                    $lab = TimelineReactionHelper::label($rt);
                                    $ic = TimelineReactionHelper::iconClass($rt);
                                    $col = TimelineReactionHelper::colorClass($rt);
                                ?>
                                <button type="button" class="btn btn-sm rounded-circle timeline-reaction-pick timeline-reaction-pick-fb border-0"
                                        data-post-id="<?php echo $pid; ?>"
                                        data-reaction="<?php echo htmlspecialchars($rt); ?>"
                                        title="<?php echo htmlspecialchars($lab); ?>">
                                    <i class="<?php echo htmlspecialchars($ic . ' ' . $col); ?>"></i>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button type="button" class="btn btn-light btn-sm timeline-reaction-fb-more d-md-none flex-shrink-0 px-2" data-post-id="<?php echo $pid; ?>" aria-label="Mais reações">
                            <i class="fas fa-caret-down"></i>
                        </button>
                    </div>
                    <button type="button" class="btn btn-light flex-fill btn-sm text-secondary btn-timeline-comments-toggle" data-post-id="<?php echo $pid; ?>">
                        <i class="far fa-comment me-1"></i>Comentar
                    </button>
                    <?php if (!empty($this->data['can_report'])): ?>
                    <button type="button" class="btn btn-light btn-sm text-danger btn-timeline-report flex-shrink-0" data-post-id="<?php echo $pid; ?>" title="Denunciar">
                        <i class="far fa-flag"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <div class="timeline-reaction-tray-mobile w-100" data-post-id="<?php echo $pid; ?>" role="group" aria-label="Reações">
                    <?php foreach (TimelineReactionHelper::TYPES as $rt):
                        $lab = TimelineReactionHelper::label($rt);
                        $ic = TimelineReactionHelper::iconClass($rt);
                        $col = TimelineReactionHelper::colorClass($rt);
                    ?>
                    <button type="button" class="btn btn-sm rounded-circle timeline-reaction-pick timeline-reaction-pick-fb border bg-white shadow-sm"
                            data-post-id="<?php echo $pid; ?>"
                            data-reaction="<?php echo htmlspecialchars($rt); ?>"
                            title="<?php echo htmlspecialchars($lab); ?>">
                        <i class="<?php echo htmlspecialchars($ic . ' ' . $col); ?>"></i>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="comments-<?php echo $pid; ?>" class="timeline-comments-box mt-2 d-none">
                <div class="timeline-comments-list mb-2" data-post-id="<?php echo $pid; ?>"></div>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control timeline-comment-input timeline-mention-field" data-post-id="<?php echo $pid; ?>" placeholder="Escreva um comentário… (use @ para mencionar)" autocomplete="off">
                    <button class="btn btn-outline-primary btn-timeline-comment-send" type="button" data-post-id="<?php echo $pid; ?>">Enviar</button>
                </div>
            </div>
        </div>
    </article>
<?php endforeach; ?>
