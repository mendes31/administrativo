<?php

namespace App\adms\Controllers\timeline;

use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\TimelineRepository;

class TimelineReport
{
    public function index(string|int|null $routeParam = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            return;
        }
        $permRepo = new ButtonPermissionUserRepository();
        $perms = $permRepo->buttonPermission(['TimelineReport']);
        if (!is_array($perms) || !in_array('TimelineReport', $perms, true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão para denunciar']);
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Use POST']);
            return;
        }
        $postId = (int)($_POST['post_id'] ?? 0);
        $reason = trim((string)($_POST['reason'] ?? ''));
        $details = trim((string)($_POST['details'] ?? ''));
        if ($postId <= 0 || $reason === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
            return;
        }
        $repo = new TimelineRepository();
        $post = $repo->getPostById($postId);
        if (!$post || ($post['status'] ?? '') !== 'active') {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Post não encontrado']);
            return;
        }
        $repo->createReport($postId, (int)$_SESSION['user_id'], $reason, $details !== '' ? $details : null);
        echo json_encode(['success' => true, 'message' => 'Denúncia registrada.']);
    }
}
