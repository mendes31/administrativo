<?php

declare(strict_types=1);

namespace App\adms\Controllers\timeline;

use App\adms\Helpers\TimelineMentionHelper;
use App\adms\Helpers\TextEncodingHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\NotificationsRepository;
use App\adms\Models\Repository\TimelineRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\GamificationAwardService;
use App\adms\Helpers\CSRFHelper;

class CreateTimelinePost
{
    public function index(string|int|null $routeParam = null): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->failAndExit('Requisição inválida.', 'error');
        }

        $permRepo = new ButtonPermissionUserRepository();
        $perms = $permRepo->buttonPermission(['CreateTimelinePost', 'TimelineShare']);
        $canCreate = is_array($perms) && in_array('CreateTimelinePost', $perms, true);
        $canShare = is_array($perms) && in_array('TimelineShare', $perms, true);
        if (!$canCreate) {
            $this->failAndExit('Sem permissão para publicar na timeline.', 'error');
        }

        if (!CSRFHelper::validateCSRFToken('timeline_create_post', $_POST['csrf_token'] ?? '')) {
            if ($this->isAjaxRequest() && !empty($_SESSION['user_id'])) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Token CSRF inválido ou sessão expirou. Atualize a página e tente novamente.',
                    'csrf_expired' => true,
                    'csrf_token' => CSRFHelper::generateCSRFToken('timeline_create_post'),
                ]);
                exit;
            }
            $this->failAndExit('Token CSRF inválido ou sessão expirou. Atualize a página e tente novamente.', 'error');
        }

        $userRepo = new UsersRepository();
        $content = trim(TextEncodingHelper::decodeEntities((string)($_POST['content'] ?? '')));
        $postType = (string)($_POST['post_type'] ?? 'regular');
        $postType = $postType === 'poll' ? 'poll' : 'regular';
        $sharedFromPostId = (int)($_POST['shared_from_post_id'] ?? 0);
        if ($sharedFromPostId <= 0) {
            $sharedFromPostId = null;
        }
        if ($sharedFromPostId !== null && !$canShare) {
            $this->failAndExit('Sem permissão para repostar publicações.', 'error');
        }
        $imagePaths = null; // array<string>
        $videoPath = null;

        $videoFile = (isset($_FILES['video']) && is_array($_FILES['video'])) ? $_FILES['video'] : null;
        $videoErrorCode = (int)($videoFile['error'] ?? UPLOAD_ERR_NO_FILE);
        $videoProvided = !empty($videoFile['tmp_name']) && $videoErrorCode === UPLOAD_ERR_OK;
        if ($videoErrorCode !== UPLOAD_ERR_NO_FILE && !$videoProvided) {
            $this->failAndExit($this->mapUploadErrorToMessage($videoErrorCode), 'msg_warning');
        }
        // Suporta tanto campo `images[]` quanto `image` com multiple.
        $imagesProvided = false;
        $uploadedImageFiles = [];
        if (!empty($_FILES['images']) && is_array($_FILES['images']['tmp_name'] ?? null)) {
            $normalized = $this->normalizeUploadedFilesArray($_FILES['images']);
            foreach ($normalized as $f) {
                $err = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($err === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if ($err !== UPLOAD_ERR_OK) {
                    $this->failAndExit($this->mapUploadErrorToMessage($err), 'msg_warning');
                }
                $uploadedImageFiles[] = $f;
            }
            $imagesProvided = $uploadedImageFiles !== [];
        } elseif (!empty($_FILES['image']) && is_array($_FILES['image']['tmp_name'] ?? null)) {
            $normalized = $this->normalizeUploadedFilesArray($_FILES['image']);
            foreach ($normalized as $f) {
                $err = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($err === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if ($err !== UPLOAD_ERR_OK) {
                    $this->failAndExit($this->mapUploadErrorToMessage($err), 'msg_warning');
                }
                $uploadedImageFiles[] = $f;
            }
            $imagesProvided = $uploadedImageFiles !== [];
        } elseif (!empty($_FILES['image']['tmp_name'] ?? null) && (int)($_FILES['image']['error'] ?? 0) === UPLOAD_ERR_OK) {
            $imagesProvided = true;
            $uploadedImageFiles = [$_FILES['image']];
        }

        if ($videoProvided && $imagesProvided && $uploadedImageFiles !== []) {
            $this->failAndExit('Não é permitido enviar vídeo e fotos no mesmo post.', 'msg_warning');
        }
        if ($postType === 'poll' && ($videoProvided || ($imagesProvided && $uploadedImageFiles !== []))) {
            $this->failAndExit('Enquete não aceita mídia anexada neste momento.', 'msg_warning');
        }
        if ($postType === 'poll' && $sharedFromPostId !== null) {
            $this->failAndExit('Enquete não pode ser criada como repost.', 'msg_warning');
        }
        if ($sharedFromPostId !== null && ($videoProvided || ($imagesProvided && $uploadedImageFiles !== []))) {
            $this->failAndExit('Compartilhamento aceita apenas comentário (sem nova mídia).', 'msg_warning');
        }

        if ($videoProvided) {
            $videoError = null;
            $videoPath = $this->uploadVideo($videoFile, $videoError);
            if ($videoPath === null) {
                $this->failAndExit($videoError ?? 'Vídeo inválido (use MP4 ou WebM, máx. 50MB).', 'msg_warning');
            }
        } elseif ($imagesProvided && $uploadedImageFiles !== []) {
            $maxPhotos = 6;
            if (count($uploadedImageFiles) > $maxPhotos) {
                $this->failAndExit('Você pode enviar até ' . $maxPhotos . ' fotos por post.', 'msg_warning');
            }

            $imagePaths = [];
            foreach ($uploadedImageFiles as $file) {
                $img = $this->uploadImage($file);
                if ($img === null) {
                    $this->failAndExit('Não foi possível salvar uma das imagens. Verifique o formato/tamanho e tente novamente.', 'msg_warning');
                }
                $imagePaths[] = $img;
            }
        }

        if ($content === '' && ($imagePaths === null || $imagePaths === []) && $videoPath === null && $sharedFromPostId === null && $postType !== 'poll') {
            $this->failAndExit('Escreva algo ou anexe uma imagem ou vídeo.', 'msg_warning');
        }

        $pollQuestion = trim(TextEncodingHelper::decodeEntities((string)($_POST['poll_question'] ?? '')));
        $pollStartsAt = trim((string)($_POST['poll_starts_at'] ?? ''));
        $pollEndsAt = trim((string)($_POST['poll_ends_at'] ?? ''));
        $pollOptionsRaw = $_POST['poll_options'] ?? [];
        $pollOptions = [];
        if (is_array($pollOptionsRaw)) {
            foreach ($pollOptionsRaw as $op) {
                $t = trim(TextEncodingHelper::decodeEntities((string)$op));
                if ($t !== '') {
                    $pollOptions[] = $t;
                }
            }
            $pollOptions = array_values(array_unique($pollOptions));
        }
        if ($postType === 'poll') {
            if ($pollQuestion === '') {
                $this->failAndExit('Informe a pergunta da enquete.', 'msg_warning');
            }
            if (count($pollOptions) < 2 || count($pollOptions) > 5) {
                $this->failAndExit('A enquete deve ter entre 2 e 5 opções.', 'msg_warning');
            }
            if ($pollEndsAt === '') {
                $this->failAndExit('Informe o período de encerramento da enquete.', 'msg_warning');
            }
            $endTs = strtotime(str_replace('T', ' ', $pollEndsAt) . ':00');
            if ($endTs === false) {
                $this->failAndExit('Data/hora de encerramento inválida.', 'msg_warning');
            }
            $startTs = null;
            if ($pollStartsAt !== '') {
                $startTs = strtotime(str_replace('T', ' ', $pollStartsAt) . ':00');
                if ($startTs === false) {
                    $this->failAndExit('Data/hora de início inválida.', 'msg_warning');
                }
            }
            if ($startTs !== null && $startTs >= $endTs) {
                $this->failAndExit('A data de início deve ser menor que a data de encerramento.', 'msg_warning');
            }
        }

        $repo = new TimelineRepository();
        $authorId = (int)($_SESSION['user_id'] ?? 0);
        $contextType = isset($_POST['context_type']) ? (string)$_POST['context_type'] : '';
        $contextTargetUserId = isset($_POST['context_target_user_id']) && is_numeric($_POST['context_target_user_id'])
            ? (int)$_POST['context_target_user_id']
            : 0;
        $contextYears = isset($_POST['context_years']) && is_numeric($_POST['context_years'])
            ? (int)$_POST['context_years']
            : null;
        $sharedPost = null;
        if ($sharedFromPostId !== null) {
            $sharedPost = $repo->getPostById($sharedFromPostId);
            if (!$sharedPost || (string)($sharedPost['status'] ?? '') !== 'active') {
                $this->failAndExit('A publicação que você tentou compartilhar não está mais disponível.', 'msg_warning');
            }
        }
        if ($contextType === 'tenure' && $authorId > 0 && $contextTargetUserId > 0) {
            if ($repo->hasTenureCongratsPostToday($authorId, $contextTargetUserId, $contextYears)) {
                $this->failAndExit('Você já publicou uma mensagem de tempo de empresa para este colaborador hoje.', 'msg_warning');
            }
        }
        $postId = $repo->createPost($authorId, $content !== '' ? $content : ' ', $imagePaths, $videoPath, $sharedFromPostId, $postType);
        if ($postType === 'poll') {
            $starts = $pollStartsAt !== '' ? str_replace('T', ' ', $pollStartsAt) . ':00' : null;
            $ends = str_replace('T', ' ', $pollEndsAt) . ':00';
            $repo->createPollForPost($postId, $pollQuestion, $pollOptions, $starts, $ends);
        }

        $mentionIds = TimelineMentionHelper::extractMentionedUserIds($content, $userRepo, $authorId);
        $validIds = array_keys($userRepo->getIdNameMapForIds($mentionIds));
        $repo->replaceMentions('post', $postId, $validIds);
        $repo->syncPostTags($postId, $content);

        // Notifica usuários mencionados no post.
        $authorName = (string)($_SESSION['user_name'] ?? 'Alguém');
        $sharedAuthorIdForMention = $sharedPost !== null ? (int)($sharedPost['user_id'] ?? 0) : 0;
        if ($validIds !== []) {
            $notifRepo = new NotificationsRepository();
            $base = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
            foreach ($validIds as $mentionedUserId) {
                $mentionedUserId = (int)$mentionedUserId;
                if ($mentionedUserId <= 0 || $mentionedUserId === $authorId) {
                    continue;
                }
                $isMentionToOriginalAuthor = $sharedAuthorIdForMention > 0 && $mentionedUserId === $sharedAuthorIdForMention;
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
        if ($sharedPost !== null) {
            $sharedAuthorId = (int)($sharedPost['user_id'] ?? 0);
            // Se o autor do post original foi @mencionado no repost, prioriza só a menção (evita duplicata).
            $sharedAuthorMentioned = $sharedAuthorId > 0 && in_array($sharedAuthorId, $validIds, true);
            if ($sharedAuthorId > 0 && $sharedAuthorId !== $authorId && !$sharedAuthorMentioned) {
                $notifRepo = $notifRepo ?? new NotificationsRepository();
                $base = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
                $notifRepo->create([
                    'user_id' => $sharedAuthorId,
                    'type' => 'timeline_share',
                    'title' => $authorName . ' fez repost da sua publicação',
                    'message' => mb_substr($content !== '' ? $content : 'Seu post recebeu um repost na timeline.', 0, 180),
                    'link_url' => $base . 'timeline?post=' . $postId,
                    'entity_type' => 'timeline_post',
                    'entity_id' => $postId,
                ]);
            }
        }

        try {
            $award = new GamificationAwardService();
            if ($sharedFromPostId !== null) {
                $award->awardTimelineEvent($authorId, 'timeline_share_created', 'timeline_post', $postId, ['shared_from_post_id' => $sharedFromPostId]);
            } else {
                $award->awardTimelineEvent($authorId, 'timeline_post_created', 'timeline_post', $postId);
            }
        } catch (\Throwable) {
        }

        $this->successAndExit('Publicação enviada.');
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

    private function uploadVideo(array $file, ?string &$errorMessage = null): ?string
    {
        if ((int)($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
            $errorMessage = $this->mapUploadErrorToMessage((int)($file['error'] ?? UPLOAD_ERR_NO_FILE));
            return null;
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_readable($tmp)) {
            $errorMessage = 'Não foi possível ler o arquivo de vídeo enviado.';
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
            $errorMessage = 'Formato de vídeo não suportado. Use MP4 ou WebM.';
            return null;
        }
        if ((int)($file['size'] ?? 0) > 50 * 1024 * 1024) {
            $errorMessage = 'Vídeo muito grande. Limite de 50MB.';
            return null;
        }
        $filename = uniqid('tlv_', true) . '.' . $ext;
        $pathFs = $uploadDir . $filename;
        if (!$this->moveUploadedOrCopy($tmp, $pathFs)) {
            $errorMessage = 'Falha ao salvar o vídeo no servidor.';
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

    private function isAjaxRequest(): bool
    {
        $requestedWith = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        return $requestedWith === 'xmlhttprequest' || str_contains($accept, 'application/json');
    }

    private function mapUploadErrorToMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Vídeo excede o limite de upload do servidor/formulário.',
            UPLOAD_ERR_PARTIAL => 'Upload do vídeo foi interrompido. Tente novamente.',
            UPLOAD_ERR_NO_TMP_DIR => 'Servidor sem pasta temporária para upload.',
            UPLOAD_ERR_CANT_WRITE => 'Servidor não conseguiu gravar o vídeo em disco.',
            UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão do PHP no servidor.',
            default => 'Falha no upload do vídeo. Tente novamente.',
        };
    }

    private function failAndExit(string $message, string $sessionKey = 'msg_warning'): void
    {
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => $message,
            ]);
            exit;
        }
        $_SESSION[$sessionKey] = $message;
        header('Location: ' . $_ENV['URL_ADM'] . 'timeline');
        exit;
    }

    private function successAndExit(string $message): void
    {
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => $message,
                'redirect' => rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/timeline',
            ]);
            exit;
        }
        $_SESSION['success'] = $message;
        header('Location: ' . $_ENV['URL_ADM'] . 'timeline');
        exit;
    }
}
