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

class UpdateTimelinePost
{
    public function index(string|int|null $routeParam = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            return;
        }

        if (!CSRFHelper::validateCSRFToken('timeline_edit_post', $_POST['csrf_token'] ?? '')) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Token CSRF inválido ou sessão expirou. Atualize a página e tente novamente.',
                'csrf_expired' => true,
                'csrf_token' => CSRFHelper::generateCSRFToken('timeline_edit_post'),
            ]);
            return;
        }

        $postId = (int)($_POST['post_id'] ?? 0);
        $content = trim(TextEncodingHelper::decodeEntities((string)($_POST['content'] ?? '')));
        if ($content === '') {
            $content = ' ';
        }
        if ($postId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        $repo = new TimelineRepository();
        $post = $repo->getPostById($postId);
        if (!$post || ($post['status'] ?? '') !== 'active') {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Post não encontrado.']);
            return;
        }
        $uid = (int)$_SESSION['user_id'];
        if ((int)($post['user_id'] ?? 0) !== $uid) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Apenas o autor pode editar esta publicação.']);
            return;
        }

        $ok = $repo->updatePostContentByAuthor($postId, $uid, $content);
        if (!$ok) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Não foi possível salvar.']);
            return;
        }

        $userRepo = new UsersRepository();
        $beforeMentionIds = $repo->getMentionedUserIds('post', $postId);
        $mentionIds = TimelineMentionHelper::extractMentionedUserIds($content, $userRepo, $uid);
        $validIds = array_keys($userRepo->getIdNameMapForIds($mentionIds));
        $repo->replaceMentions('post', $postId, $validIds);
        $repo->syncPostTags($postId, $content);

        // Notifica somente novos mencionados após edição.
        $newMentionIds = array_values(array_diff($validIds, $beforeMentionIds));
        if ($newMentionIds !== []) {
            $notifRepo = new NotificationsRepository();
            $authorName = (string)($_SESSION['user_name'] ?? 'Alguém');
            $base = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
            $sharedFromPostId = (int)($post['shared_from_post_id'] ?? 0);
            $originalPostAuthorId = 0;
            if ($sharedFromPostId > 0) {
                $originalPost = $repo->getPostById($sharedFromPostId);
                $originalPostAuthorId = (int)($originalPost['user_id'] ?? 0);
            }
            foreach ($newMentionIds as $mentionedUserId) {
                $mentionedUserId = (int)$mentionedUserId;
                if ($mentionedUserId <= 0 || $mentionedUserId === $uid) {
                    continue;
                }
                $isMentionToOriginalAuthor = $originalPostAuthorId > 0 && $mentionedUserId === $originalPostAuthorId;
                $mentionTitle = $isMentionToOriginalAuthor
                    ? $authorName . ' mencionou você ao republicar sua publicação'
                    : $authorName . ' mencionou você em uma publicação';
                $notifRepo->create([
                    'user_id' => $mentionedUserId,
                    'type' => 'timeline_mention',
                    'title' => $mentionTitle,
                    'message' => mb_substr($content, 0, 180),
                    'link_url' => $base . 'timeline?post=' . $postId . '&focus=body',
                    'entity_type' => 'timeline_post',
                    'entity_id' => $postId,
                ]);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Publicação atualizada.']);
    }
}
