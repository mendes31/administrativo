<?php

namespace App\adms\Controllers\timeline;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\NotificationsRepository;
use App\adms\Models\Repository\TimelineRepository;
use App\adms\Models\Services\GamificationAwardService;

class TimelineLike
{
    public function index(string|null $id = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            return;
        }
        $permRepo = new ButtonPermissionUserRepository();
        $perms = $permRepo->buttonPermission(['TimelineLike']);
        $canLike = is_array($perms) && in_array('TimelineLike', $perms, true);
        if (!$canLike) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão para reagir a publicações.']);
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        if (!CSRFHelper::validateCSRFToken('timeline_like_post', $_POST['csrf_token'] ?? '')) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Token CSRF inválido ou sessão expirou. Atualize a página e tente novamente.',
                'csrf_expired' => true,
                'csrf_token' => CSRFHelper::generateCSRFToken('timeline_like_post'),
            ]);
            return;
        }
        $postId = (int)($id ?? $_POST['post_id'] ?? 0);
        if ($postId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Post inválido']);
            return;
        }
        $repo = new TimelineRepository();
        $post = $repo->getPostById($postId);
        if (!$post || ($post['status'] ?? '') !== 'active') {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Post não encontrado']);
            return;
        }
        $reaction = (string)($_POST['reaction'] ?? $_GET['reaction'] ?? 'heart');
        $actorId = (int)$_SESSION['user_id'];
        $result = $repo->setReaction($postId, $actorId, $reaction);

        // Notifica o autor quando outra pessoa reage.
        $postOwnerId = (int)($post['user_id'] ?? 0);
        if ($result['liked'] && $postOwnerId > 0 && $postOwnerId !== $actorId) {
            $notifRepo = new NotificationsRepository();
            $base = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
            $actorName = (string)($_SESSION['user_name'] ?? 'Alguém');
            $notifRepo->create([
                'user_id' => $postOwnerId,
                'type' => 'timeline_reaction',
                'title' => $actorName . ' reagiu à sua publicação',
                'message' => 'Reação: ' . (string)($result['reaction'] ?? 'curtir'),
                'link_url' => $base . 'timeline?post=' . $postId,
                'entity_type' => 'timeline_post',
                'entity_id' => $postId,
            ]);
        }

        if (!empty($result['liked'])) {
            try {
                (new GamificationAwardService())->awardTimelineEvent(
                    $actorId,
                    'timeline_reaction_created',
                    'timeline_post',
                    $postId,
                    ['reaction' => (string)($result['reaction'] ?? '')]
                );
            } catch (\Throwable) {
            }
        }

        echo json_encode([
            'success' => true,
            'liked' => $result['liked'],
            'reaction' => $result['reaction'],
            'likes_count' => $result['likes_count'],
            'summary' => $result['summary'],
            'csrf_token' => CSRFHelper::generateCSRFToken('timeline_like_post'),
        ]);
    }
}
