<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Upload de anexos do canal de denúncias (armazenamento isolado).
 */
final class WhistleblowingUploadService
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB

    /** @var list<string> */
    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'mp3', 'wav', 'mp4', 'doc', 'docx'];

    public static function uploadDir(): string
    {
        $dir = dirname(__DIR__, 3) . '/public/adms/uploads/whistleblowing';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

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
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return null;
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = self::uploadDir() . '/' . $storedName;

        if (!move_uploaded_file((string) $file['tmp_name'], $destPath)) {
            return null;
        }

        $mime = mime_content_type($destPath) ?: (string) ($file['type'] ?? 'application/octet-stream');

        return [
            'stored_name' => $storedName,
            'original_name' => $originalName,
            'mime_type' => $mime,
            'size_bytes' => $size,
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
}
