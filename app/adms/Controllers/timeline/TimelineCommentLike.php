<?php

declare(strict_types=1);

namespace App\adms\Controllers\timeline;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\TimelineRepository;

class TimelineCommentLike
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
        $perms = $permRepo->buttonPermission(['TimelineLike', 'TimelineComment']);
        $canLike = is_array($perms) && in_array('TimelineLike', $perms, true);
        $canViewComments = is_array($perms) && in_array('TimelineComment', $perms, true);
        if (!$canLike || !$canViewComments) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão para reagir a comentários.']);
            return;
        }

        $commentId = (int)($id ?? $_GET['comment_id'] ?? $_POST['comment_id'] ?? 0);
        if ($commentId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Comentário inválido']);
            return;
        }

        $repo = new TimelineRepository();
        $comment = $repo->getCommentById($commentId);
        if (!$comment || ($comment['status'] ?? '') !== 'active' || ($comment['post_status'] ?? '') !== 'active') {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Comentário não encontrado']);
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
            $rows = $repo->listReactionsForComment($commentId);
            echo json_encode(['success' => true, 'reactions' => $rows]);
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

        $reaction = (string)($_POST['reaction'] ?? $_GET['reaction'] ?? 'like');
        $result = $repo->setCommentReaction($commentId, (int)$_SESSION['user_id'], $reaction);

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
