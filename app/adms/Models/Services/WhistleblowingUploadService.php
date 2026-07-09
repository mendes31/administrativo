<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Upload de anexos do canal de denúncias — validação forte, sem metadados EXIF, cifrado em disco.
 */
final class WhistleblowingUploadService
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB

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
        'mp4' => ['video/mp4'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    ];

    private WhistleblowingEncryptionService $encryption;

    public function __construct()
    {
        $this->encryption = new WhistleblowingEncryptionService();
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
     * @param array<string, mixed> $file $_FILES item
     * @return array{stored_name: string, original_name: string, mime_type: string, size_bytes: int}|null
     */
    public function handleFile(array $file): ?array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_FILE_SIZE) {
            return null;
        }

        $originalName = basename((string) ($file['name'] ?? 'arquivo'));
        if ($this->isSuspiciousFilename($originalName)) {
            return null;
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === '' || !isset(self::MIME_BY_EXTENSION[$ext])) {
            return null;
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return null;
        }

        if ($this->containsEmbeddedScript($tmpPath)) {
            return null;
        }

        $detectedMime = $this->detectMimeType($tmpPath);
        if (!$this->mimeMatchesExtension($ext, $detectedMime)) {
            return null;
        }

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

    /**
     * @param array<int, array<string, mixed>>|null $files
     * @return list<array{stored_name: string, original_name: string, mime_type: string, size_bytes: int}>
     */
    public function handleMultiple(?array $files): array
    {
        if ($files === null || !isset($files['name']) || !is_array($files['name'])) {
            return [];
        }

        $uploaded = [];
        $count = count($files['name']);

        for ($i = 0; $i < $count; $i++) {
            $item = [
                'name' => $files['name'][$i] ?? '',
                'type' => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$i] ?? 0,
            ];

            if (($item['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $result = $this->handleFile($item);
            if ($result !== null) {
                $uploaded[] = $result;
            }
        }

        return $uploaded;
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

    private function mimeMatchesExtension(string $ext, string $mime): bool
    {
        $allowed = self::MIME_BY_EXTENSION[$ext] ?? [];

        return in_array($mime, $allowed, true);
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

        return substr_count($lower, '.') > 1;
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
