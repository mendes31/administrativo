<?php

namespace App\adms\Helpers;

/**
 * Upload e remoção de arquivos de eventos corporativos.
 */
class CompanyEventUploadHelper
{
    public const MAX_EVENT_IMAGES = 6;

    private const MAX_SIZE = 20 * 1024 * 1024;

    /**
     * @param array<string, mixed> $file
     */
    public static function uploadFile(array $file, string $folder, ?string $oldFile = null): ?string
    {
        if (!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        if (empty($file['tmp_name']) || !is_uploaded_file((string)$file['tmp_name'])) {
            return null;
        }

        $basePath = dirname(__DIR__, 3);
        $uploadDir = $basePath . '/public/adms/uploads/' . $folder . '/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            return null;
        }
        if (!is_writable($uploadDir)) {
            return null;
        }

        $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (str_contains($folder, 'imagens')) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($extension, $allowed, true)) {
                return null;
            }
        }

        if ((int)($file['size'] ?? 0) > self::MAX_SIZE) {
            return null;
        }

        $filename = uniqid('', true) . '_' . time() . ($extension !== '' ? '.' . $extension : '');
        $filepath = $uploadDir . $filename;
        if (!move_uploaded_file((string)$file['tmp_name'], $filepath)) {
            return null;
        }

        if ($oldFile !== null && $oldFile !== '') {
            self::deleteStoredFile($oldFile);
        }

        return $folder . '/' . $filename;
    }

    /**
     * Coleta arquivos de imagem do request (campo imagens[] ou imagens).
     *
     * @return array<int, array<string, mixed>>|false
     */
    public static function collectImageFilesFromRequest(): array|false
    {
        if (!empty($_FILES['imagens']) && is_array($_FILES['imagens']['tmp_name'] ?? null)) {
            return self::normalizeUploadedFilesArray($_FILES['imagens']);
        }
        if (!empty($_FILES['imagens']['tmp_name'] ?? null) && !is_array($_FILES['imagens']['tmp_name'])) {
            $err = (int)($_FILES['imagens']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_NO_FILE) {
                return [];
            }
            if ($err !== UPLOAD_ERR_OK) {
                return false;
            }

            return [$_FILES['imagens']];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $files
     * @return array<int, array<string, mixed>>|false
     */
    public static function normalizeUploadedFilesArray(array $files): array|false
    {
        $out = [];
        $names = $files['name'] ?? [];
        if (!is_array($names)) {
            $err = (int)($files['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_NO_FILE) {
                return [];
            }
            if ($err !== UPLOAD_ERR_OK) {
                return false;
            }

            return [$files];
        }

        $count = count($names);
        for ($i = 0; $i < $count; $i++) {
            $err = (int)($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($err !== UPLOAD_ERR_OK) {
                return false;
            }
            $out[] = [
                'name' => $files['name'][$i] ?? '',
                'type' => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error' => $err,
                'size' => $files['size'][$i] ?? 0,
            ];
        }

        return $out;
    }

    public static function deleteStoredFile(?string $relativePath): void
    {
        $relativePath = trim((string)$relativePath);
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return;
        }
        $basePath = dirname(__DIR__, 3);
        $full = $basePath . '/public/adms/uploads/' . ltrim($relativePath, '/');
        if (is_file($full)) {
            @unlink($full);
        }
    }

    public static function serveUrl(string $relativePath): string
    {
        return ($_ENV['URL_ADM'] ?? '') . 'serve-file?path=' . rawurlencode($relativePath);
    }
}
