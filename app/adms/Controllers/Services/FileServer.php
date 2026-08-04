<?php

namespace App\adms\Controllers\Services;

class FileServer
{
    private array $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'zip', 'rar'
    ];
    
    private string $uploadBasePath = 'public/adms/uploads/';

    /**
     * Arquivos binários grandes (vídeo, PDF): Range (206), cache e 304.
     * O visualizador de PDF no iframe depende de Range para exibir sem baixar o arquivo inteiro.
     */
    private function serveStreamableWithRange(
        string $fullPath,
        string $mimeType,
        int $fileSize,
        string $dispositionFilename,
        bool $inline = true
    ): void {
        while (ob_get_level()) {
            ob_end_clean();
        }

        $lastmod = (int) filemtime($fullPath);
        $etag = '"' . md5($fullPath . $lastmod . $fileSize) . '"';

        header('Accept-Ranges: bytes');
        header('Content-Type: ' . $mimeType);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastmod) . ' GMT');
        header('ETag: ' . $etag);
        header('Cache-Control: private, max-age=86400');
        header(
            'Content-Disposition: '
            . ($inline ? 'inline' : 'attachment')
            . '; filename="' . str_replace(['"', "\r", "\n"], '', $dispositionFilename) . '"'
            . "; filename*=UTF-8''" . rawurlencode($dispositionFilename)
        );

        $rangeHeader = (string) ($_SERVER['HTTP_RANGE'] ?? '');
        $rangeMatches = [];
        $hasRange = $rangeHeader !== ''
            && preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $rangeMatches) === 1;

        if (!$hasRange) {
            if (!empty($_SERVER['HTTP_IF_NONE_MATCH']) && trim((string) $_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
                http_response_code(304);
                exit;
            }
            if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
                $ifModifiedSince = strtotime((string) $_SERVER['HTTP_IF_MODIFIED_SINCE']);
                if ($ifModifiedSince !== false && $lastmod <= $ifModifiedSince) {
                    http_response_code(304);
                    exit;
                }
            }
        }

        $start = 0;
        $end = $fileSize - 1;

        if ($hasRange) {
            if ($rangeMatches[1] !== '') {
                $start = (int) $rangeMatches[1];
            }
            if ($rangeMatches[2] !== '') {
                $end = (int) $rangeMatches[2];
            }
            if ($start > $end || $start >= $fileSize) {
                http_response_code(416);
                header("Content-Range: bytes */{$fileSize}");
                exit;
            }
            $end = min($end, $fileSize - 1);
            $length = $end - $start + 1;

            http_response_code(206);
            header("Content-Range: bytes {$start}-{$end}/{$fileSize}");
            header('Content-Length: ' . $length);

            $handle = fopen($fullPath, 'rb');
            if ($handle === false) {
                $this->sendError('Erro ao ler arquivo', 500);

                return;
            }
            fseek($handle, $start);
            $remaining = $length;
            $chunkSize = 65536;
            while ($remaining > 0 && !feof($handle)) {
                $read = (int) min($chunkSize, $remaining);
                $buffer = fread($handle, $read);
                if ($buffer === false) {
                    break;
                }
                echo $buffer;
                $remaining -= strlen($buffer);
            }
            fclose($handle);
            exit;
        }

        header('Content-Length: ' . $fileSize);
        if (readfile($fullPath) === false) {
            $this->sendError('Erro ao ler arquivo', 500);
        }
        exit;
    }

    public function serveFile(string $path): void
    {
        // Desabilitar exibição de erros para produção
        ini_set('display_errors', 0);
        error_reporting(0);

        if (headers_sent($file, $line)) {
            error_log("Headers already sent in $file on line $line");
            $this->sendError('Erro interno do servidor', 500);
            return;
        }

        // Limpar o caminho de caracteres perigosos
        $path = $this->sanitizePath($path);
        
        if (empty($path)) {
            $this->sendError('Caminho inválido', 400);
            return;
        }

        // Currículos: sessão + autorização por objeto; dual-read (privado/legado).
        $normalizedForAuth = str_replace('\\', '/', strtolower(ltrim($path, '/')));
        if (str_starts_with($normalizedForAuth, 'rh_candidatos/')) {
            if (empty($_SESSION['user_id'])) {
                $this->sendError('Acesso não autorizado', 401);
                return;
            }

            $candidatoId = \App\adms\Models\Services\RhCandidatoPermissionService::extractCandidatoIdFromAnexoPath($path);
            if ($candidatoId === null
                || !\App\adms\Models\Services\RhCandidatoPermissionService::canDownloadAnexo($candidatoId)
            ) {
                $this->sendError('Acesso não autorizado a este currículo', 403);
                return;
            }

            $fullPath = \App\adms\Models\Services\RhCandidatoAnexoService::resolvePhysicalPath($path);
            if ($fullPath === null || !is_readable($fullPath)) {
                $this->sendError('Arquivo não encontrado', 404);
                return;
            }

            $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            if (!in_array($extension, $this->allowedExtensions, true)) {
                $this->sendError('Tipo de arquivo não permitido', 403);
                return;
            }

            $mimeTypes = [
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ];
            $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
            $disposition = $extension === 'pdf' ? 'inline' : 'attachment';
            $filename = $this->resolveDispositionFilename($path, $fullPath, $extension);
            $fileSize = (int) filesize($fullPath);

            $anexoRow = (new \App\adms\Models\Repository\RhCandidatosRepository())
                ->findAnexoByArquivoCaminho($path);
            (new \App\adms\Models\Repository\RhCandidatoAnexoAccessLogRepository())->logDownload([
                'rh_candidato_anexo_id' => $anexoRow['id'] ?? null,
                'rh_candidato_id' => $candidatoId,
                'actor_user_id' => (int) ($_SESSION['user_id'] ?? 0),
                'action' => 'download',
                'delivery_mode' => $disposition,
                'source' => 'legacy_file_server',
                'anexo_tipo' => $anexoRow['tipo'] ?? null,
                'path' => $path,
                'dedup_seconds' => 60,
            ]);

            if ($extension === 'pdf') {
                $this->serveStreamableWithRange($fullPath, $mimeType, $fileSize, $filename, true);
                return;
            }

            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . $fileSize);
            header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
            header('Cache-Control: private, no-store');
            readfile($fullPath);
            exit;
        }

        // Ajuste para imagem padrão de usuário:
        // se vier apenas "icon_user.png", redirecionar para o caminho correto "users/icon_user.png"
        $basename = basename($path);
        if ($basename === 'icon_user.png' && ($path === 'icon_user.png' || $path === '/icon_user.png')) {
            $path = 'users/icon_user.png';
        }

        // Raiz do projeto: index.php define APP_ROOT; evita falhar se __DIR__ divergir (symlink, deploy).
        $uploadsDir = (defined('APP_ROOT') && APP_ROOT !== '')
            ? APP_ROOT . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'adms' . DIRECTORY_SEPARATOR . 'uploads'
            : dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'adms' . DIRECTORY_SEPARATOR . 'uploads';
        $base = realpath($uploadsDir);

        if (!$base) {
            error_log('Base path não encontrado: ' . $uploadsDir);
            $this->sendError('Erro de configuração do servidor', 500);
            return;
        }

        $fullPath = realpath($base . DIRECTORY_SEPARATOR . $path);

        // Log para debug (remover em produção)
        if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true') {
            error_log("DEBUG - Base: $base, Path: $path, Full: " . ($fullPath ?: 'false'));
        }

        // Validar o caminho; se for inválido ou o arquivo não existir,
        // tentar cair no ícone padrão de usuário antes de retornar erro.
        if (!$fullPath || strpos($fullPath, $base) !== 0 || !file_exists($fullPath)) {
            $fallback = realpath($base . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . 'icon_user.png');

            if ($fallback && file_exists($fallback)) {
                $fullPath = $fallback;
                $path = 'users/icon_user.png';
            } else {
                $this->sendError('Arquivo não encontrado', 404);
                return;
            }
        }

        if (!is_readable($fullPath)) {
            $this->sendError('Arquivo sem permissão de leitura', 403);
            return;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            $this->sendError('Tipo de arquivo não permitido', 403);
            return;
        }

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed'
        ];

        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

        $fileSize = filesize($fullPath);
        $lastmod = filemtime($fullPath);
        $dispositionName = $this->resolveDispositionFilename($path, $fullPath, $extension);

        if (in_array($extension, ['mp4', 'webm', 'pdf'], true)) {
            $this->serveStreamableWithRange(
                $fullPath,
                $mimeType,
                $fileSize,
                $dispositionName,
                true
            );

            return;
        }

        // Para imagens: cache + 304 quando possível (menos bytes na rede em revisita)
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastmod) . ' GMT');
            $etag = '"' . md5($fullPath . $lastmod . $fileSize) . '"';
            header('ETag: ' . $etag);

            if (!empty($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
                http_response_code(304);
                exit;
            }
            if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
                $ifModifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
                if ($ifModifiedSince !== false && $lastmod <= $ifModifiedSince) {
                    http_response_code(304);
                    exit;
                }
            }

            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . $fileSize);
            header('Cache-Control: public, max-age=31536000, immutable');
            header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000));
        } else {
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . $fileSize);
        }

        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
        }

        // Limpar buffers antes de enviar arquivo binário
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Enviar o arquivo
        if (readfile($fullPath) === false) {
            $this->sendError('Erro ao ler arquivo', 500);
            return;
        }
        
        exit;
    }

    /**
     * Nome amigável para Content-Disposition (evita o browser rotular só como "serve-file").
     */
    private function resolveDispositionFilename(string $path, string $fullPath, string $extension): string
    {
        $requested = trim((string) ($_GET['name'] ?? $_GET['filename'] ?? ''));
        if ($requested !== '') {
            $requested = basename(str_replace(["\0", '\\', '/'], '', $requested));
            if ($requested !== '') {
                if ($extension !== '' && !str_ends_with(strtolower($requested), '.' . strtolower($extension))) {
                    $requested .= '.' . $extension;
                }
                return $requested;
            }
        }

        $base = basename($path !== '' ? $path : $fullPath);
        return $base !== '' ? $base : ('arquivo.' . ($extension !== '' ? $extension : 'bin'));
    }

    /**
     * Sanitiza o caminho do arquivo removendo caracteres perigosos
     */
    private function sanitizePath(string $path): string
    {
        // Remover caracteres perigosos
        $path = str_replace(['..', '\\'], '', $path);
        
        // Remover barras duplas
        $path = preg_replace('#/+#', '/', $path);
        
        // Remover barra inicial se existir
        $path = ltrim($path, '/');
        
        return $path;
    }

    private function sendError(string $message, int $code): void
    {
        http_response_code($code);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Erro $code: $message";
        exit;
    }
} 