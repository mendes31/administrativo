<?php

declare(strict_types=1);

namespace App\adms\Controllers\timeline;

use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\TimelineRepository;

class TimelinePostReactions
{
    public function index(string|int|null $id = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            return;
        }
        $permRepo = new ButtonPermissionUserRepository();
        $perms = $permRepo->buttonPermission(['TimelinePostReactions']);
        $canViewReactions = is_array($perms) && in_array('TimelinePostReactions', $perms, true);
        if (!$canViewReactions) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão para visualizar reações.']);
            return;
        }
        $postId = (int)($id ?? $_GET['post_id'] ?? 0);
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
        $list = $repo->listReactionsForPost($postId);
        echo json_encode(['success' => true, 'reactions' => $list]);
    }
}
