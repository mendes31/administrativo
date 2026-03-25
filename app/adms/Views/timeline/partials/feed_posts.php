<?php
use App\adms\Helpers\TimelineMentionHelper;
use App\adms\Models\Repository\UsersRepository;

$posts = $this->data['posts'] ?? [];
$liked = $this->data['liked_map'] ?? [];
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
    $likedThis = !empty($liked[$pid]);
    $avatarPath = null;
    if (!empty($postRow['author_image']) && $postRow['author_image'] !== 'icon_user.png') {
        $avatarPath = 'users/' . (int)($postRow['user_id'] ?? 0) . '/' . $postRow['author_image'];
    }
?>
    <article class="card timeline-post-card mb-3">
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
                    <div class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($postRow['created_at'] ?? 'now')); ?></div>
                </div>
            </div>
            <div class="timeline-post-body mb-2">
                <?php
                $txt = trim((string)($postRow['content'] ?? ''));
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
            <?php elseif (!empty($postRow['image_path'])): ?>
                <div class="mb-2 mx-n3">
                    <img src="<?php echo htmlspecialchars($urlAdm); ?>serve-file?path=<?php echo urlencode($postRow['image_path']); ?>"
                         class="timeline-post-media" alt="">
                </div>
            <?php endif; ?>
            <div class="timeline-actions border-top pt-2 mt-2">
                <button type="button" class="btn btn-link btn-sm text-decoration-none timeline-like-btn <?php echo $likedThis ? 'active' : ''; ?> btn-timeline-like" data-post-id="<?php echo $pid; ?>">
                    <i class="<?php echo $likedThis ? 'fas' : 'far'; ?> fa-heart"></i>
                    <span class="timeline-like-count" data-post-id="<?php echo $pid; ?>"><?php echo (int)($postRow['likes_count'] ?? 0); ?></span>
                </button>
                <button type="button" class="btn btn-link btn-sm text-decoration-none text-muted btn-timeline-comments-toggle" data-post-id="<?php echo $pid; ?>">
                    <i class="far fa-comment"></i> <?php echo (int)($postRow['comments_count'] ?? 0); ?>
                </button>
                <?php if (!empty($this->data['can_report'])): ?>
                <button type="button" class="btn btn-link btn-sm text-danger btn-timeline-report" data-post-id="<?php echo $pid; ?>">Denunciar</button>
                <?php endif; ?>
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
