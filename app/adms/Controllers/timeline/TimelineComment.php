<?php

declare(strict_types=1);

namespace App\adms\Controllers\timeline;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\TextEncodingHelper;
use App\adms\Helpers\TimelineMentionHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\NotificationsRepository;
use App\adms\Models\Repository\TimelineRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\GamificationAwardService;

class TimelineComment
{
    public function index(string|null $postId = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SERVER['REQUEST_METHOD'])) {
            return;
        }
        $pid = (int)($postId ?? $_GET['post_id'] ?? $_POST['post_id'] ?? 0);
        if ($pid <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Post inválido']);
            return;
        }
        $repo = new TimelineRepository();
        $post = $repo->getPostById($pid);
        if (!$post || ($post['status'] ?? '') !== 'active') {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Post não encontrado']);
            return;
        }

        $permRepo = new ButtonPermissionUserRepository();
        $perms = $permRepo->buttonPermission(['TimelineComment', 'TimelineViewComments']);
        $canComment = is_array($perms) && in_array('TimelineComment', $perms, true);
        $canViewComments = is_array($perms)
            && (in_array('TimelineComment', $perms, true) || in_array('TimelineViewComments', $perms, true));

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (!$canViewComments) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sem permissão para visualizar comentários.']);
                return;
            }
            $comments = $repo->getCommentsForPost($pid, 50, (int)($_SESSION['user_id'] ?? 0));
            $this->attachCommentsHtml($comments);
            echo json_encode(['success' => true, 'comments' => $comments]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }

        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            return;
        }
        if (!$canComment) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão para interagir nesta publicação.']);
            return;
        }

        if (!CSRFHelper::validateCSRFToken('timeline_comment_post', $_POST['csrf_token'] ?? '')) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Token CSRF inválido ou sessão expirou. Atualize a página e tente novamente.',
                'csrf_expired' => true,
                'csrf_token' => CSRFHelper::generateCSRFToken('timeline_comment_post'),
            ]);
            return;
        }

        $userRepo = new UsersRepository();
        $text = trim(TextEncodingHelper::decodeEntities((string)($_POST['content'] ?? '')));
        $pollOptionId = (int)($_POST['poll_option_id'] ?? 0);
        if ($pollOptionId > 0) {
            $actorId = (int)($_SESSION['user_id'] ?? 0);
            $poll = $repo->voteOnPollForPost($pid, $actorId, $pollOptionId);
            if ($poll === null) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Não foi possível registrar o voto.']);
                return;
            }
            try {
                (new GamificationAwardService())->awardTimelineEvent(
                    (int)$_SESSION['user_id'],
                    'timeline_poll_vote',
                    'timeline_post',
                    $pid
                );
            } catch (\Throwable) {
            }
            echo json_encode(['success' => true, 'poll' => $poll, 'csrf_token' => CSRFHelper::generateCSRFToken('timeline_comment_post')]);
            return;
        }
        if ($text === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Comentário vazio']);
            return;
        }
        $cid = $repo->addComment($pid, (int)$_SESSION['user_id'], $text);
        if ($cid <= 0) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao comentar']);
            return;
        }
        try {
            (new GamificationAwardService())->awardTimelineEvent(
                (int)$_SESSION['user_id'],
                'timeline_comment_created',
                'timeline_comment',
                    $cid,
                    ['content' => $text]
            );
        } catch (\Throwable) {
        }
        $actorId = (int)($_SESSION['user_id'] ?? 0);
        $actorName = (string)($_SESSION['user_name'] ?? 'Alguém');
        $mentionIds = TimelineMentionHelper::extractMentionedUserIds($text, $userRepo, $actorId);
        $validIds = array_keys($userRepo->getIdNameMapForIds($mentionIds));
        $repo->replaceMentions('comment', $cid, $validIds);

        $notifRepo = new NotificationsRepository();
        $base = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';

        // Notifica o autor do post quando outra pessoa comenta.
        // Se o autor foi @mencionado, só a notificação de menção (prioridade maior) — evita duplicata.
        $postOwnerId = (int)($post['user_id'] ?? 0);
        $postOwnerMentioned = $postOwnerId > 0 && in_array($postOwnerId, $validIds, true);
        if ($postOwnerId > 0 && $postOwnerId !== $actorId && !$postOwnerMentioned) {
            $notifRepo->create([
                'user_id' => $postOwnerId,
                'type' => 'timeline_comment',
                'title' => $actorName . ' comentou na sua publicação',
                'message' => mb_substr($text, 0, 180),
                'link_url' => $base . 'timeline?comment=' . $cid,
                'entity_type' => 'timeline_post',
                'entity_id' => $pid,
            ]);
        }

        // Notifica menções no comentário (exceto o próprio autor da ação).
        foreach ($validIds as $mentionedUserId) {
            $mentionedUserId = (int)$mentionedUserId;
            if ($mentionedUserId <= 0 || $mentionedUserId === $actorId) {
                continue;
            }
            $isMentionToPostAuthor = $postOwnerId > 0 && $mentionedUserId === $postOwnerId;
            $mentionTitle = $isMentionToPostAuthor
                ? $actorName . ' comentou e mencionou você na sua publicação'
                : $actorName . ' mencionou você em um comentário';
            $notifRepo->create([
                'user_id' => $mentionedUserId,
                'type' => 'timeline_mention',
                'title' => $mentionTitle,
                'message' => mb_substr($text, 0, 180),
                'link_url' => $base . 'timeline?comment=' . $cid,
                'entity_type' => 'timeline_post',
                'entity_id' => $pid,
            ]);
        }

        $comments = $repo->getCommentsForPost($pid, 50, (int)($_SESSION['user_id'] ?? 0));
        $this->attachCommentsHtml($comments);
        echo json_encode(['success' => true, 'comments' => $comments]);
    }

    /**
     * @param array<int, array<string, mixed>> $comments
     */
    private function attachCommentsHtml(array &$comments): void
    {
        $userRepo = new UsersRepository();
        $allIds = [];
        foreach ($comments as $c) {
            $allIds = array_merge(
                $allIds,
                TimelineMentionHelper::extractMentionedUserIds((string)($c['content'] ?? ''), $userRepo, null, false)
            );
        }
        $allIds = array_values(array_unique($allIds));
        $map = $userRepo->getIdNameMapForIds($allIds);
        $base = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
        foreach ($comments as &$c) {
            $c['content_html'] = TimelineMentionHelper::renderHtml((string)($c['content'] ?? ''), $base, $map, $userRepo);
        }
        unset($c);
    }
}
