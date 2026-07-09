<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Nome de arquivo para download de anexos (extensão correta mesmo se metadado cifrado ilegível).
 */
final class WhistleblowingAttachmentFilenameHelper
{
    /** @var array<string, string> */
    private const MIME_EXT = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'audio/mpeg' => 'mp3',
        'audio/mp3' => 'mp3',
        'audio/wav' => 'wav',
        'audio/x-wav' => 'wav',
        'video/mp4' => 'mp4',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    /**
     * @param array<string, mixed> $attachment
     */
    public static function resolve(array $attachment): string
    {
        $name = trim((string) ($attachment['original_name'] ?? ''));
        if ($name === '' || $name === 'arquivo') {
            $name = '';
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext !== '') {
            return self::sanitizeFilename($name);
        }

        $fromStored = strtolower(pathinfo((string) ($attachment['stored_name'] ?? ''), PATHINFO_EXTENSION));
        if ($fromStored !== '' && $fromStored !== 'enc') {
            $ext = $fromStored;
        } else {
            $ext = self::extensionFromMime((string) ($attachment['mime_type'] ?? ''));
        }

        $base = $name !== '' ? pathinfo($name, PATHINFO_FILENAME) : 'arquivo';
        if ($ext === '') {
            return self::sanitizeFilename($base !== '' ? $base : 'arquivo.bin');
        }

        return self::sanitizeFilename($base . '.' . $ext);
    }

    public static function contentDispositionHeader(string $filename): string
    {
        $filename = self::sanitizeFilename($filename);
        $ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
        if ($ascii === '' || $ascii === null) {
            $ascii = 'arquivo.bin';
        }

        return 'attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($filename);
    }

    public static function extensionFromMime(string $mime): string
    {
        $mime = strtolower(trim($mime));

        return self::MIME_EXT[$mime] ?? '';
    }

    public static function legacyFallbackKeyMaterial(): string
    {
        return ($_ENV['APP_NAME'] ?? 'app') . '|' . ($_ENV['DB_NAME'] ?? 'db') . '|whistleblowing';
    }

    private static function sanitizeFilename(string $name): string
    {
        $name = str_replace(["\0", '/', '\\'], '', $name);
        $name = trim($name);

        return $name !== '' ? $name : 'arquivo.bin';
    }
}
