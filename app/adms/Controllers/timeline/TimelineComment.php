<?php

declare(strict_types=1);

namespace App\adms\Controllers\timeline;

use App\adms\Helpers\TimelineMentionHelper;
use App\adms\Models\Repository\TimelineRepository;
use App\adms\Models\Repository\UsersRepository;

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

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $comments = $repo->getCommentsForPost($pid);
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

        $userRepo = new UsersRepository();
        $text = trim((string)($_POST['content'] ?? ''));
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
        $mentionIds = TimelineMentionHelper::extractMentionedUserIds($text, $userRepo);
        $validIds = array_keys($userRepo->getIdNameMapForIds($mentionIds));
        $repo->replaceMentions('comment', $cid, $validIds);

        $comments = $repo->getCommentsForPost($pid);
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
                TimelineMentionHelper::extractMentionedUserIds((string)($c['content'] ?? ''), $userRepo)
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
