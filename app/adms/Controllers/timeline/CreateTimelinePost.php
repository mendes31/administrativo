<?php

declare(strict_types=1);

namespace App\adms\Controllers\timeline;

use App\adms\Helpers\TimelineMentionHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\TimelineRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Helpers\CSRFHelper;

class CreateTimelinePost
{
    public function index(string|int|null $routeParam = null): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'timeline');
            exit;
        }

        $permRepo = new ButtonPermissionUserRepository();
        $perms = $permRepo->buttonPermission(['CreateTimelinePost']);
        if (!is_array($perms) || !in_array('CreateTimelinePost', $perms, true)) {
            $_SESSION['error'] = 'Sem permissão para publicar na timeline.';
            header('Location: ' . $_ENV['URL_ADM'] . 'timeline');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('timeline_create_post', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido ou sessão expirou. Atualize a página e tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'timeline');
            exit;
        }

        $userRepo = new UsersRepository();
        $content = trim((string)($_POST['content'] ?? ''));
        $imagePath = null;
        $videoPath = null;

        if (!empty($_FILES['video']['tmp_name']) && (int)($_FILES['video']['error'] ?? 0) === UPLOAD_ERR_OK) {
            $videoPath = $this->uploadVideo($_FILES['video']);
            if ($videoPath === null) {
                $_SESSION['msg_warning'] = 'Vídeo inválido (use MP4 ou WebM, máx. 50MB).';
                header('Location: ' . $_ENV['URL_ADM'] . 'timeline');
                exit;
            }
        } elseif (!empty($_FILES['image']['tmp_name']) && (int)($_FILES['image']['error'] ?? 0) === UPLOAD_ERR_OK) {
            $imagePath = $this->uploadImage($_FILES['image']);
            if ($imagePath === null) {
                $_SESSION['msg_warning'] = 'Não foi possível salvar a imagem (formato/tamanho ou envio incompleto). Tente outro arquivo ou tire a foto novamente.';
                header('Location: ' . $_ENV['URL_ADM'] . 'timeline');
                exit;
            }
        }

        if ($content === '' && $imagePath === null && $videoPath === null) {
            $_SESSION['msg_warning'] = 'Escreva algo ou anexe uma imagem ou vídeo.';
            header('Location: ' . $_ENV['URL_ADM'] . 'timeline');
            exit;
        }

        $repo = new TimelineRepository();
        $postId = $repo->createPost((int)$_SESSION['user_id'], $content !== '' ? $content : ' ', $imagePath, $videoPath);

        $mentionIds = TimelineMentionHelper::extractMentionedUserIds($content, $userRepo);
        $validIds = array_keys($userRepo->getIdNameMapForIds($mentionIds));
        $repo->replaceMentions('post', $postId, $validIds);

        $_SESSION['success'] = 'Publicação enviada.';
        header('Location: ' . $_ENV['URL_ADM'] . 'timeline');
        exit;
    }

    private function uploadImage(array $file): ?string
    {
        if ((int)($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
            return null;
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_readable($tmp)) {
            return null;
        }
        $basePath = dirname(__DIR__, 4);
        $folder = 'timeline';
        $uploadDir = $basePath . '/public/adms/uploads/' . $folder . '/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            return null;
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = 'jpg';
        }
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return null;
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            return null;
        }
        $filename = uniqid('tl_', true) . '.' . $ext;
        $pathFs = $uploadDir . $filename;
        if (!$this->moveUploadedOrCopy($tmp, $pathFs)) {
            return null;
        }

        return $folder . '/' . $filename;
    }

    private function uploadVideo(array $file): ?string
    {
        if ((int)($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
            return null;
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_readable($tmp)) {
            return null;
        }
        $basePath = dirname(__DIR__, 4);
        $folder = 'timeline';
        $uploadDir = $basePath . '/public/adms/uploads/' . $folder . '/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            return null;
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['mp4', 'webm'], true)) {
            return null;
        }
        if ($file['size'] > 50 * 1024 * 1024) {
            return null;
        }
        $filename = uniqid('tlv_', true) . '.' . $ext;
        $pathFs = $uploadDir . $filename;
        if (!$this->moveUploadedOrCopy($tmp, $pathFs)) {
            return null;
        }

        return $folder . '/' . $filename;
    }

    /**
     * move_uploaded_file falha em alguns envios via fetch/FormData; copy do tmp do PHP como fallback.
     */
    private function moveUploadedOrCopy(string $tmpName, string $destinationPath): bool
    {
        if (is_uploaded_file($tmpName)) {
            return move_uploaded_file($tmpName, $destinationPath);
        }
        if (!@copy($tmpName, $destinationPath)) {
            return false;
        }
        @unlink($tmpName);

        return true;
    }
}
