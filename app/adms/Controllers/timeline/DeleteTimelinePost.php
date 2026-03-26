<?php
declare(strict_types=1);

namespace App\adms\Controllers\timeline;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\TimelineRepository;

class DeleteTimelinePost
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

        if (!CSRFHelper::validateCSRFToken('timeline_delete_post', $_POST['csrf_token'] ?? '')) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido ou sessão expirou. Atualize a página e tente novamente.']);
            return;
        }

        $postId = (int)($_POST['post_id'] ?? $routeParam ?? 0);
        if ($postId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        $uid = (int)$_SESSION['user_id'];

        $repo = new TimelineRepository();
        $post = $repo->getPostById($postId);
        if (!$post || ($post['status'] ?? '') !== 'active') {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Post não encontrado.']);
            return;
        }

        if ((int)($post['user_id'] ?? 0) !== $uid) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Apenas o autor pode deletar esta publicação.']);
            return;
        }

        // Precisa buscar antes do delete, pois a remoção apaga adms_timeline_post_images.
        $imgPaths = $repo->getPostImagesByPostId($postId);

        $ok = $repo->deletePostByAuthor($postId, $uid);
        if (!$ok) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Não foi possível deletar.']);
            return;
        }

        // Limpeza física (best-effort) para evitar lixo no storage.
        $basePath = dirname(__DIR__, 4);
        $uploadsRoot = $basePath . '/public/adms/uploads';

        // Compat: posts antigos usam adms_timeline_posts.image_path.
        $this->bestEffortDeleteUploadFile($uploadsRoot, (string)($post['image_path'] ?? ''));
        // Novos posts com múltiplas fotos usam adms_timeline_post_images.
        foreach ($imgPaths as $imgPath) {
            $this->bestEffortDeleteUploadFile($uploadsRoot, (string)$imgPath);
        }
        $this->bestEffortDeleteUploadFile($uploadsRoot, (string)($post['video_path'] ?? ''));

        echo json_encode(['success' => true, 'message' => 'Publicação deletada.']);
    }

    private function bestEffortDeleteUploadFile(string $uploadsRoot, string $relativePath): void
    {
        $relativePath = trim($relativePath);
        if ($relativePath === '') return;

        // Evitar travessia de caminho — como a aplicação grava paths controlados, isso deve ser suficiente.
        if (str_contains($relativePath, '..') || str_starts_with($relativePath, '/')) return;

        $candidate = rtrim($uploadsRoot, '/\\') . DIRECTORY_SEPARATOR . $relativePath;
        $candidateNorm = str_replace('\\', '/', $candidate);
        $rootNorm = str_replace('\\', '/', rtrim($uploadsRoot, '/\\'));
        if (strpos($candidateNorm, $rootNorm) !== 0) return;

        if (is_file($candidate)) {
            @unlink($candidate);
        }
    }
}

