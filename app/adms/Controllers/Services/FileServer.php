<?php

namespace App\adms\Controllers\Services;

class FileServer
{
    private array $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'zip', 'rar'
    ];
    
    private string $uploadBasePath = 'public/adms/uploads/';

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

        // Ajuste para imagem padrão de usuário:
        // se vier apenas "icon_user.png", redirecionar para o caminho correto "users/icon_user.png"
        $basename = basename($path);
        if ($basename === 'icon_user.png' && ($path === 'icon_user.png' || $path === '/icon_user.png')) {
            $path = 'users/icon_user.png';
        }

        // Obter o caminho base correto (subindo 4 níveis a partir do diretório atual)
        $base = realpath(__DIR__ . '/../../../../public/adms/uploads');
        
        if (!$base) {
            error_log("Base path não encontrado: " . __DIR__ . '/../../../../public/adms/uploads');
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
            // PDF e vídeos curtos da timeline: inline no navegador; demais: download
            if ($extension === 'pdf' || in_array($extension, ['mp4', 'webm'], true)) {
                header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
            } else {
                header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
            }
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