<?php

declare(strict_types=1);

namespace App\adms\Controllers\timeline;

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
