<?php

declare(strict_types=1);

namespace App\adms\Controllers\timeline;

use App\adms\Helpers\TimelineMentionHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\NotificationsRepository;
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
        $imagePaths = null; // array<string>
        $videoPath = null;

        $videoProvided = !empty($_FILES['video']['tmp_name']) && (int)($_FILES['video']['error'] ?? 0) === UPLOAD_ERR_OK;
        // Suporta tanto campo `images[]` quanto `image` com multiple.
        $imagesProvided = false;
        $uploadedImageFiles = [];
        if (!empty($_FILES['images']) && is_array($_FILES['images']['tmp_name'] ?? null)) {
            $imagesProvided = true;
            $uploadedImageFiles = $this->normalizeUploadedFilesArray($_FILES['images']);
        } elseif (!empty($_FILES['image']) && is_array($_FILES['image']['tmp_name'] ?? null)) {
            $imagesProvided = true;
            $uploadedImageFiles = $this->normalizeUploadedFilesArray($_FILES['image']);
        } elseif (!empty($_FILES['image']['tmp_name'] ?? null) && (int)($_FILES['image']['error'] ?? 0) === UPLOAD_ERR_OK) {
            $imagesProvided = true;
            $uploadedImageFiles = [$_FILES['image']];
        }

        if ($videoProvided && $imagesProvided && $uploadedImageFiles !== []) {
            $_SESSION['msg_warning'] = 'Não é permitido enviar vídeo e fotos no mesmo post.';
            header('Location: ' . $_ENV['URL_ADM'] . 'timeline');
            exit;
        }

        if ($videoProvided) {
            $videoPath = $this->uploadVideo($_FILES['video']);
            if ($videoPath === null) {
                $_SESSION['msg_warning'] = 'Vídeo inválido (use MP4 ou WebM, máx. 50MB).';
                header('Location: ' . $_ENV['URL_ADM'] . 'timeline');
                exit;
            }
        } elseif ($imagesProvided && $uploadedImageFiles !== []) {
            $maxPhotos = 6;
            if (count($uploadedImageFiles) > $maxPhotos) {
                $_SESSION['msg_warning'] = 'Você pode enviar até ' . $maxPhotos . ' fotos por post.';
                header('Location: ' . $_ENV['URL_ADM'] . 'timeline');
                exit;
            }

            $imagePaths = [];
            foreach ($uploadedImageFiles as $file) {
                $img = $this->uploadImage($file);
                if ($img === null) {
                    $_SESSION['msg_warning'] = 'Não foi possível salvar uma das imagens. Verifique o formato/tamanho e tente novamente.';
                    header('Location: ' . $_ENV['URL_ADM'] . 'timeline');
                    exit;
                }
                $imagePaths[] = $img;
            }
        }

        if ($content === '' && ($imagePaths === null || $imagePaths === []) && $videoPath === null) {
            $_SESSION['msg_warning'] = 'Escreva algo ou anexe uma imagem ou vídeo.';
            header('Location: ' . $_ENV['URL_ADM'] . 'timeline');
            exit;
        }

        $repo = new TimelineRepository();
        $postId = $repo->createPost((int)$_SESSION['user_id'], $content !== '' ? $content : ' ', $imagePaths, $videoPath);

        $mentionIds = TimelineMentionHelper::extractMentionedUserIds($content, $userRepo);
        $validIds = array_keys($userRepo->getIdNameMapForIds($mentionIds));
        $repo->replaceMentions('post', $postId, $validIds);

        // Notifica usuários mencionados no post.
        $authorId = (int)($_SESSION['user_id'] ?? 0);
        $authorName = (string)($_SESSION['user_name'] ?? 'Alguém');
        if ($validIds !== []) {
            $notifRepo = new NotificationsRepository();
            $base = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
            foreach ($validIds as $mentionedUserId) {
                $mentionedUserId = (int)$mentionedUserId;
                if ($mentionedUserId <= 0 || $mentionedUserId === $authorId) {
                    continue;
                }
                $notifRepo->create([
                    'user_id' => $mentionedUserId,
                    'type' => 'timeline_mention',
                    'title' => $authorName . ' mencionou você em uma publicação',
                    'message' => mb_substr($content, 0, 180),
                    'link_url' => $base . 'timeline?post=' . $postId,
                    'entity_type' => 'timeline_post',
                    'entity_id' => $postId,
                ]);
            }
        }

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

    /**
     * Normaliza estrutura $_FILES[field] (multiple) para uma lista de arquivos no formato esperado por uploadImage().
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeUploadedFilesArray(array $files): array
    {
        $names = $files['name'] ?? [];
        $tmpNames = $files['tmp_name'] ?? [];
        $errors = $files['error'] ?? [];
        $sizes = $files['size'] ?? [];
        $types = $files['type'] ?? [];

        if (!is_array($names) || !is_array($tmpNames)) {
            return [];
        }

        $out = [];
        foreach ($names as $idx => $name) {
            $out[] = [
                'name' => $name,
                'tmp_name' => $tmpNames[$idx] ?? '',
                'error' => $errors[$idx] ?? UPLOAD_ERR_NO_FILE,
                'size' => $sizes[$idx] ?? 0,
                'type' => $types[$idx] ?? '',
            ];
        }

        return $out;
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
