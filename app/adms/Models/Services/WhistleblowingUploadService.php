<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Upload de anexos do canal de denúncias — validação forte, sem metadados EXIF, cifrado em disco.
 */
final class WhistleblowingUploadService
{
    private const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50 MB

    /** @var array<string, list<string>> */
    private const MIME_BY_EXTENSION = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'mp3' => ['audio/mpeg', 'audio/mp3'],
        'wav' => ['audio/wav', 'audio/x-wav'],
        'ogg' => ['audio/ogg', 'application/ogg', 'audio/x-ogg', 'audio/opus'],
        'opus' => ['audio/opus', 'audio/ogg'],
        'mp4' => ['video/mp4'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    ];

    private WhistleblowingEncryptionService $encryption;

    public function __construct()
    {
        $this->encryption = new WhistleblowingEncryptionService();
    }

    public static function maxFileSizeBytes(): int
    {
        return self::MAX_FILE_SIZE;
    }

    public static function maxFileSizeLabel(): string
    {
        $mb = (int) round(self::MAX_FILE_SIZE / (1024 * 1024));

        return $mb . ' MB';
    }

    /**
     * @return list<string>
     */
    public static function allowedExtensions(): array
    {
        return array_keys(self::MIME_BY_EXTENSION);
    }

    public static function allowedExtensionsLabel(): string
    {
        return implode(', ', array_map('strtoupper', self::allowedExtensions()));
    }

    public static function uploadDir(): string
    {
        $dir = dirname(__DIR__, 3) . '/public/adms/uploads/whistleblowing';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        self::ensureHtaccessDeny($dir);

        return $dir;
    }

    /**
     * Processa anexos e devolve erros legíveis (não silencia rejeição).
     * Valida todos antes de gravar; se houver erro, não salva nenhum.
     *
     * @param array<string, mixed>|null $files $_FILES['attachments']
     * @return array{
     *   uploads: list<array{stored_name: string, original_name: string, mime_type: string, size_bytes: int}>,
     *   errors: list<string>,
     *   attempted: bool
     * }
     */
    public function processMultiple(?array $files): array
    {
        $result = ['uploads' => [], 'errors' => [], 'attempted' => false];

        if ($files === null || !isset($files['name']) || !is_array($files['name'])) {
            return $result;
        }

        $items = [];
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            $item = [
                'name' => $files['name'][$i] ?? '',
                'type' => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$i] ?? 0,
            ];

            if (($item['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE
                && trim((string) ($item['name'] ?? '')) === '') {
                continue;
            }

            $result['attempted'] = true;
            $label = trim((string) ($item['name'] ?? '')) !== ''
                ? basename((string) $item['name'])
                : ('arquivo #' . ($i + 1));

            $error = $this->validateFileError($item, $label);
            if ($error !== null) {
                $result['errors'][] = $error;
                continue;
            }

            $items[] = $item;
        }

        if ($result['errors'] !== []) {
            return $result;
        }

        foreach ($items as $item) {
            $label = basename((string) ($item['name'] ?? 'arquivo'));
            $upload = $this->storeValidatedFile($item);
            if ($upload === null) {
                foreach ($result['uploads'] as $saved) {
                    self::deleteFile((string) ($saved['stored_name'] ?? ''));
                }
                $result['uploads'] = [];
                $result['errors'][] = "«{$label}»: não foi possível salvar o arquivo. Tente novamente ou use outro formato.";

                return $result;
            }
            $result['uploads'][] = $upload;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $file $_FILES item
     * @return array{stored_name: string, original_name: string, mime_type: string, size_bytes: int}|null
     */
    public function handleFile(array $file): ?array
    {
        $label = basename((string) ($file['name'] ?? 'arquivo'));
        if ($this->validateFileError($file, $label) !== null) {
            return null;
        }

        return $this->storeValidatedFile($file);
    }

    /**
     * @param array<int, array<string, mixed>>|null $files
     * @return list<array{stored_name: string, original_name: string, mime_type: string, size_bytes: int}>
     */
    public function handleMultiple(?array $files): array
    {
        return $this->processMultiple($files)['uploads'];
    }

    /**
     * @param array<string, mixed> $file
     */
    private function validateFileError(array $file, string $label): ?string
    {
        $phpError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($phpError === UPLOAD_ERR_NO_FILE) {
            return "«{$label}»: nenhum arquivo enviado.";
        }
        if ($phpError === UPLOAD_ERR_INI_SIZE || $phpError === UPLOAD_ERR_FORM_SIZE) {
            return "«{$label}»: arquivo maior que o permitido (máximo "
                . self::maxFileSizeLabel() . '). Reduza o tamanho do vídeo/arquivo e tente novamente.';
        }
        if ($phpError === UPLOAD_ERR_PARTIAL) {
            return "«{$label}»: envio incompleto. Selecione o arquivo novamente.";
        }
        if ($phpError !== UPLOAD_ERR_OK) {
            return "«{$label}»: falha no envio (código {$phpError}). Tente novamente.";
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            return "«{$label}»: arquivo vazio.";
        }
        if ($size > self::MAX_FILE_SIZE) {
            $sentMb = round($size / (1024 * 1024), 1);

            return "«{$label}»: tamanho {$sentMb} MB ultrapassa o máximo de "
                . self::maxFileSizeLabel() . '. Comprima o vídeo ou envie um arquivo menor.';
        }

        $originalName = basename((string) ($file['name'] ?? 'arquivo'));
        if ($this->isSuspiciousFilename($originalName)) {
            return "«{$label}»: nome de arquivo não permitido por segurança.";
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === '' || !isset(self::MIME_BY_EXTENSION[$ext])) {
            return "«{$label}»: tipo não permitido. Use: " . self::allowedExtensionsLabel() . '.';
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return "«{$label}»: upload inválido. Selecione o arquivo novamente.";
        }

        if ($this->containsEmbeddedScript($tmpPath)) {
            return "«{$label}»: conteúdo do arquivo rejeitado por segurança.";
        }

        $detectedMime = $this->detectMimeType($tmpPath);
        if (!$this->mimeMatchesExtension($ext, $detectedMime, $tmpPath)) {
            return "«{$label}»: o conteúdo não corresponde à extensão .{$ext} (tipo detectado: {$detectedMime}).";
        }

        return null;
    }

    /**
     * @param array<string, mixed> $file
     * @return array{stored_name: string, original_name: string, mime_type: string, size_bytes: int}|null
     */
    private function storeValidatedFile(array $file): ?array
    {
        $originalName = basename((string) ($file['name'] ?? 'arquivo'));
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $detectedMime = $this->detectMimeType($tmpPath);
        $detectedMime = $this->normalizeMimeForExtension($ext, $detectedMime, $tmpPath);

        $plainPath = $tmpPath;
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $stripped = $this->stripImageMetadataToTemp($tmpPath, $ext);
            if ($stripped !== null) {
                $plainPath = $stripped;
            }
        }

        $binary = file_get_contents($plainPath);
        if ($binary === false || $binary === '') {
            if ($plainPath !== $tmpPath && is_file($plainPath)) {
                @unlink($plainPath);
            }

            return null;
        }

        if ($plainPath !== $tmpPath && is_file($plainPath)) {
            @unlink($plainPath);
        }

        try {
            $encrypted = $this->encryption->encryptBinary($binary);
        } catch (\Throwable) {
            return null;
        }

        $storedName = bin2hex(random_bytes(16)) . '.enc';
        $destPath = self::uploadDir() . '/' . $storedName;

        if (file_put_contents($destPath, $encrypted) === false) {
            return null;
        }

        return [
            'stored_name' => $storedName,
            'original_name' => $originalName,
            'mime_type' => $detectedMime,
            'size_bytes' => strlen($binary),
        ];
    }

    public static function getFilePath(string $storedName): string
    {
        $safe = basename($storedName);

        return self::uploadDir() . '/' . $safe;
    }

    /** Lê e descriptografa o anexo (compatível com arquivos legados em texto plano). */
    public function readFileContents(string $storedName): ?string
    {
        return $this->readFileContentsWithEncryption($storedName, $this->encryption);
    }

    public function readFileContentsWithEncryption(string $storedName, WhistleblowingEncryptionService $encryption): ?string
    {
        $path = self::getFilePath($storedName);
        if (!is_readable($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        if (str_ends_with(strtolower($storedName), '.enc')) {
            try {
                return $encryption->decryptBinary($raw);
            } catch (\Throwable) {
                return null;
            }
        }

        return $raw;
    }

    /**
     * Grava bytes cifrados em disco e retorna o nome armazenado (.enc).
     */
    public function writeEncryptedFile(string $plaintext, WhistleblowingEncryptionService $encryption): ?array
    {
        try {
            $encrypted = $encryption->encryptBinary($plaintext);
        } catch (\Throwable) {
            return null;
        }

        $storedName = bin2hex(random_bytes(16)) . '.enc';
        $destPath = self::uploadDir() . '/' . $storedName;
        if (file_put_contents($destPath, $encrypted) === false) {
            return null;
        }

        return [
            'stored_name' => $storedName,
            'size_bytes' => strlen($plaintext),
        ];
    }

    public static function deleteFile(string $storedName): bool
    {
        $path = self::getFilePath($storedName);

        return is_file($path) && @unlink($path);
    }

    private function detectMimeType(string $path): string
    {
        if (class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($path);
            if (is_string($mime) && $mime !== '') {
                return strtolower($mime);
            }
        }

        $fallback = mime_content_type($path);

        return is_string($fallback) && $fallback !== '' ? strtolower($fallback) : 'application/octet-stream';
    }

    private function mimeMatchesExtension(string $ext, string $mime, ?string $path = null): bool
    {
        $allowed = self::MIME_BY_EXTENSION[$ext] ?? [];

        if (in_array($mime, $allowed, true)) {
            return true;
        }

        // Áudios do WhatsApp (.ogg) costumam vir como application/octet-stream no Windows.
        if (
            $path !== null
            && $mime === 'application/octet-stream'
            && in_array($ext, ['ogg', 'opus'], true)
            && $this->isOggContainer($path)
        ) {
            return true;
        }

        return false;
    }

    private function normalizeMimeForExtension(string $ext, string $mime, string $path): string
    {
        if (
            $mime === 'application/octet-stream'
            && in_array($ext, ['ogg', 'opus'], true)
            && $this->isOggContainer($path)
        ) {
            return $ext === 'opus' ? 'audio/opus' : 'audio/ogg';
        }

        return $mime;
    }

    private function isOggContainer(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }

        $header = fread($handle, 4);
        fclose($handle);

        return $header === 'OggS';
    }

    private function isSuspiciousFilename(string $name): bool
    {
        $lower = strtolower($name);

        if (preg_match('/\.(php|phtml|phar|htaccess|cgi|asp|aspx|jsp)(\.|$)/', $lower)) {
            return true;
        }

        if (str_contains($lower, '..') || str_contains($lower, "\0")) {
            return true;
        }

        // Bloqueia extensão dupla perigosa (ex.: arquivo.php.mp4), mas permite nomes com pontos (video.final.mp4).
        if (preg_match('/\.(php|phtml|phar|cgi|asp|aspx|jsp)\.[a-z0-9]+$/i', $lower)) {
            return true;
        }

        return false;
    }

    private function containsEmbeddedScript(string $path): bool
    {
        $head = file_get_contents($path, false, null, 0, 512);
        if ($head === false) {
            return true;
        }

        $snippet = strtolower($head);

        return str_contains($snippet, '<?php')
            || str_contains($snippet, '<?=')
            || str_contains($snippet, '<script');
    }

    private function stripImageMetadataToTemp(string $sourcePath, string $ext): ?string
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        $image = match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($sourcePath),
            'png' => @imagecreatefrompng($sourcePath),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            'gif' => @imagecreatefromgif($sourcePath),
            default => false,
        };

        if ($image === false) {
            return null;
        }

        $temp = tempnam(sys_get_temp_dir(), 'wb_');
        if ($temp === false) {
            imagedestroy($image);

            return null;
        }

        $saved = match ($ext) {
            'jpg', 'jpeg' => imagejpeg($image, $temp, 90),
            'png' => imagepng($image, $temp),
            'webp' => function_exists('imagewebp') ? imagewebp($image, $temp, 90) : false,
            'gif' => imagegif($image, $temp),
            default => false,
        };

        imagedestroy($image);

        if (!$saved) {
            @unlink($temp);

            return null;
        }

        return $temp;
    }

    private static function ensureHtaccessDeny(string $dir): void
    {
        $htaccess = $dir . DIRECTORY_SEPARATOR . '.htaccess';
        if (is_file($htaccess)) {
            return;
        }

        $content = <<<'HTA'
# Bloqueia acesso direto — download somente via controller autenticado.
Require all denied
HTA;

        @file_put_contents($htaccess, $content);
    }
}
