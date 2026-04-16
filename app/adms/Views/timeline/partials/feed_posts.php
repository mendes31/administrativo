<?php
use App\adms\Helpers\TimelineReactionHelper;
use App\adms\Helpers\TimelineMentionHelper;
use App\adms\Models\Repository\UsersRepository;

$posts = $this->data['posts'] ?? [];
$reactionMap = $this->data['reaction_map'] ?? [];
$reactionSummaries = $this->data['reaction_summaries'] ?? [];
$pollMap = $this->data['poll_map'] ?? [];
$currentUserId = (int)($this->data['current_user_id'] ?? 0);
$canComment = !empty($this->data['can_comment']);
$canLike = !empty($this->data['can_like']);
$canViewReactions = !empty($this->data['can_view_reactions']);
$canViewComments = !empty($this->data['can_view_comments']);
$canShare = !empty($this->data['can_share']);
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
    $poll = $pollMap[$pid] ?? null;
    $canModerate = !empty($this->data['can_moderate']);
    $canDelete = $isAuthor || $canModerate;
    $imgs = $postRow['image_paths'] ?? [];
    if (!is_array($imgs)) $imgs = [];
    if ($imgs === [] && !empty($postRow['image_path'])) {
        $imgs = [(string)$postRow['image_path']];
    }
    $imgsJson = htmlspecialchars(json_encode(array_values($imgs)), ENT_QUOTES, 'UTF-8');
    $shareText = trim((string)($postRow['content'] ?? ''));
    $shareTextB64 = base64_encode($shareText);
    $shareAuthor = (string)($postRow['author_name'] ?? 'Usuário');
    $sharedPost = $postRow['shared_post'] ?? null;
    $showLikeAction = $canLike;
    $showCommentAction = $canComment;
    $showShareAction = $canShare;
    $showReportAction = !empty($this->data['can_report']);
    $primaryActionsCount = ($showLikeAction ? 1 : 0) + ($showCommentAction ? 1 : 0) + ($showShareAction ? 1 : 0);
?>
    <article class="card timeline-post-card mb-3" id="timeline-post-<?php echo $pid; ?>" data-post-id="<?php echo $pid; ?>">
        <div class="card-body position-relative">
            <span class="timeline-focus-badge" aria-hidden="true">
                <i class="fas fa-bell me-1"></i>Publicação da notificação
            </span>
            <div class="timeline-post-header mb-2">
                <?php
                echo \App\adms\Helpers\ImageHelper::displayImage($avatarPath, [
                    'class' => 'timeline-avatar',
                    'alt' => '',
                ], 'icon_user.png', 'users');
                ?>
                <div class="flex-grow-1 min-w-0">
                    <?php
                    $authorUserId = (int)($postRow['user_id'] ?? 0);
                    $authorLabel = htmlspecialchars($postRow['author_name'] ?? 'Usuário');
                    ?>
                    <div class="fw-bold text-truncate">
                        <?php if ($authorUserId > 0): ?>
                            <a href="<?php echo htmlspecialchars($urlAdm); ?>timeline-profile/<?php echo $authorUserId; ?>" class="text-reset text-decoration-none timeline-author-profile-link"><?php echo $authorLabel; ?></a>
                        <?php else: ?>
                            <?php echo $authorLabel; ?>
                        <?php endif; ?>
                    </div>
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
                    echo '<p class="mb-0 timeline-post-text">', TimelineMentionHelper::renderHtml($txt, $urlAdm, $mentionMap, $usersRepoMention), '</p>';
                    echo '<button type="button" class="btn btn-link btn-sm p-0 mt-1 timeline-post-readmore d-none" aria-expanded="false">Ler mais</button>';
                }
                ?>
            </div>
            <?php if (is_array($poll)): ?>
                <div class="timeline-poll-card mb-2 p-2 rounded border" data-post-id="<?php echo $pid; ?>">
                    <div class="small fw-semibold mb-1"><?php echo htmlspecialchars($poll['question'] ?? 'Enquete'); ?></div>
                    <div class="small text-muted mb-2" data-poll-status-label="<?php echo $pid; ?>">
                        <?php if (($poll['status'] ?? '') === 'scheduled'): ?>
                            Inicia em <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime((string)($poll['starts_at'] ?? 'now')))); ?>
                        <?php elseif (($poll['status'] ?? '') === 'closed'): ?>
                            Encerrada em <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime((string)($poll['ends_at'] ?? 'now')))); ?>
                        <?php else: ?>
                            Encerra em <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime((string)($poll['ends_at'] ?? 'now')))); ?>
                        <?php endif; ?>
                    </div>
                    <div class="timeline-poll-options" data-post-id="<?php echo $pid; ?>">
                        <?php foreach (($poll['options'] ?? []) as $opt): ?>
                            <?php
                            $optId = (int)($opt['id'] ?? 0);
                            $votes = (int)($opt['votes'] ?? 0);
                            $pct = (int)($opt['percent'] ?? 0);
                            $isMine = (int)($poll['user_vote_option_id'] ?? 0) === $optId;
                            $isOpen = ($poll['status'] ?? '') === 'open';
                            ?>
                            <?php if ($isOpen && $canComment): ?>
                                <button type="button"
                                        class="timeline-poll-option-btn<?php echo $isMine ? ' active' : ''; ?>"
                                        data-post-id="<?php echo $pid; ?>"
                                        data-option-id="<?php echo $optId; ?>">
                                    <span class="timeline-poll-option-label"><?php echo htmlspecialchars((string)($opt['text'] ?? '')); ?></span>
                                    <span class="timeline-poll-option-meta"><?php echo $votes; ?> voto<?php echo $votes !== 1 ? 's' : ''; ?> · <?php echo $pct; ?>%</span>
                                    <span class="timeline-poll-option-bar" style="width: <?php echo max(0, min(100, $pct)); ?>%;"></span>
                                </button>
                            <?php else: ?>
                                <div class="timeline-poll-option-btn timeline-poll-option-static<?php echo $isMine ? ' active' : ''; ?>" data-option-id="<?php echo $optId; ?>">
                                    <span class="timeline-poll-option-label"><?php echo htmlspecialchars((string)($opt['text'] ?? '')); ?></span>
                                    <span class="timeline-poll-option-meta"><?php echo $votes; ?> voto<?php echo $votes !== 1 ? 's' : ''; ?> · <?php echo $pct; ?>%</span>
                                    <span class="timeline-poll-option-bar" style="width: <?php echo max(0, min(100, $pct)); ?>%;"></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="small text-muted mt-2">
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none btn-poll-votes-open" data-post-id="<?php echo $pid; ?>">
                            <span data-poll-total="<?php echo $pid; ?>"><?php echo (int)($poll['total_votes'] ?? 0); ?></span> votos totais
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (is_array($sharedPost)): ?>
                <?php
                $sharedAuthorName = (string)($sharedPost['author_name'] ?? 'Usuário');
                $sharedAuthorUid = (int)($sharedPost['user_id'] ?? 0);
                $sharedTxt = trim((string)($sharedPost['content'] ?? ''));
                $sharedExcerpt = mb_substr($sharedTxt, 0, 260) . (mb_strlen($sharedTxt) > 260 ? '…' : '');
                $sharedHasMedia = !empty($sharedPost['video_path']) || !empty($sharedPost['image_paths']);
                $sharedImgs = $sharedPost['image_paths'] ?? [];
                if (!is_array($sharedImgs)) { $sharedImgs = []; }
                $sharedId = (int)($sharedPost['id'] ?? 0);
                ?>
                <div class="timeline-shared-shell mb-2">
                    <div class="timeline-shared-meta small text-muted mb-1">
                        <i class="fas fa-retweet me-1"></i>Você repostou uma publicação de
                        <?php if ($sharedAuthorUid > 0): ?>
                            <a href="<?php echo htmlspecialchars($urlAdm); ?>timeline-profile/<?php echo $sharedAuthorUid; ?>" class="text-reset fw-semibold text-decoration-none timeline-shared-author-link"><?php echo htmlspecialchars($sharedAuthorName); ?></a>.
                        <?php else: ?>
                            <?php echo htmlspecialchars($sharedAuthorName); ?>.
                        <?php endif; ?>
                    </div>
                    <div class="timeline-shared-card p-2 rounded border">
                        <div class="small fw-semibold mb-1">
                            <?php if ($sharedAuthorUid > 0): ?>
                                <a href="<?php echo htmlspecialchars($urlAdm); ?>timeline-profile/<?php echo $sharedAuthorUid; ?>" class="text-reset text-decoration-none timeline-shared-author-link"><?php echo htmlspecialchars($sharedAuthorName); ?></a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($sharedAuthorName); ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($sharedExcerpt !== ''): ?>
                            <div class="small"><?php echo TimelineMentionHelper::renderHtml($sharedExcerpt, $urlAdm, $mentionMap, $usersRepoMention); ?></div>
                        <?php else: ?>
                            <div class="small text-muted">Publicação original sem texto.</div>
                        <?php endif; ?>
                        <?php if (!empty($sharedPost['video_path'])): ?>
                            <a class="timeline-shared-media-link mt-2" href="<?php echo htmlspecialchars($urlAdm); ?>timeline?post=<?php echo $sharedId; ?>">
                                <span class="timeline-shared-media-video"><i class="fas fa-play-circle me-1"></i>Vídeo na publicação original</span>
                            </a>
                        <?php elseif (!empty($sharedImgs)): ?>
                            <a class="timeline-shared-media-link mt-2" href="<?php echo htmlspecialchars($urlAdm); ?>timeline?post=<?php echo $sharedId; ?>">
                                <img src="<?php echo htmlspecialchars($urlAdm); ?>serve-file?path=<?php echo urlencode((string)$sharedImgs[0]); ?>"
                                     class="timeline-shared-media-thumb"
                                     alt="Prévia da publicação original">
                            </a>
                        <?php endif; ?>
                        <div class="small mt-2 d-flex align-items-center justify-content-between gap-2">
                            <span class="text-muted"><?php echo $sharedHasMedia ? '<i class="fas fa-paperclip me-1"></i>Mídia anexada' : '&nbsp;'; ?></span>
                            <?php if ($sharedId > 0): ?>
                                <a class="timeline-shared-link" href="<?php echo htmlspecialchars($urlAdm); ?>timeline?post=<?php echo $sharedId; ?>">Abrir original</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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
                <?php if ($canViewReactions): ?>
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
                <?php else: ?>
                    <span class="d-flex align-items-center gap-2 text-muted">
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
                    </span>
                <?php endif; ?>
                <?php if ($canViewComments): ?>
                    <button type="button" class="btn btn-link btn-sm text-muted p-0 text-decoration-none btn-timeline-comments-toggle" data-post-id="<?php echo $pid; ?>">
                        <?php echo $commentsCount; ?> comentário<?php echo $commentsCount !== 1 ? 's' : ''; ?>
                    </button>
                <?php else: ?>
                    <span class="small text-muted"><?php echo $commentsCount; ?> comentário<?php echo $commentsCount !== 1 ? 's' : ''; ?></span>
                <?php endif; ?>
            </div>

            <div class="timeline-action-bar d-flex flex-column gap-2 pt-2">
                <div class="timeline-action-row timeline-primary-actions-<?php echo $primaryActionsCount; ?> d-flex gap-1 align-items-stretch">
                    <?php if ($showLikeAction): ?>
                        <div class="d-flex gap-0 min-w-0 timeline-curtir-bundle timeline-action-item" data-post-id="<?php echo $pid; ?>">
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
                    <?php endif; ?>
                    <?php if ($showCommentAction): ?>
                        <button type="button" class="btn btn-light btn-sm text-secondary btn-timeline-comments-toggle timeline-action-item" data-post-id="<?php echo $pid; ?>">
                            <i class="far fa-comment me-1"></i>Comentar
                        </button>
                    <?php endif; ?>
                    <?php if ($showShareAction): ?>
                        <button type="button" class="btn btn-light btn-sm text-secondary btn-timeline-share timeline-action-item"
                                data-post-id="<?php echo $pid; ?>"
                                data-post-author="<?php echo htmlspecialchars($shareAuthor, ENT_QUOTES, 'UTF-8'); ?>"
                                data-post-content-b64="<?php echo htmlspecialchars($shareTextB64, ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="fas fa-retweet me-1"></i>Repostar
                        </button>
                    <?php endif; ?>
                    <?php if ($showReportAction): ?>
                    <button type="button" class="btn btn-light btn-sm text-danger btn-timeline-report timeline-action-item" data-post-id="<?php echo $pid; ?>" title="Denunciar">
                        <i class="far fa-flag"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <?php if ($canLike): ?>
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
                <?php endif; ?>
            </div>

            <?php if ($canViewComments): ?>
                <div id="comments-<?php echo $pid; ?>" class="timeline-comments-box mt-2 d-none">
                    <div class="timeline-comments-list mb-2" data-post-id="<?php echo $pid; ?>"></div>
                    <?php if ($canComment): ?>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control timeline-comment-input timeline-mention-field" data-post-id="<?php echo $pid; ?>" placeholder="Escreva um comentário… (use @ para mencionar)" autocomplete="off">
                            <button class="btn btn-outline-primary btn-timeline-comment-send" type="button" data-post-id="<?php echo $pid; ?>">Enviar</button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </article>
<?php endforeach; ?>
