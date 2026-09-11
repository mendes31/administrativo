<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/** Upload, URL e exclusão da foto do cadastro de EPI. */
final class SstEpiImagemHelper
{
    public const FOLDER = 'sst/epis';

    public const MAX_BYTES = 5 * 1024 * 1024;

    /**
     * @param array<string, mixed>|null $file Campo $_FILES['imagem']
     * @return array{ok: bool, path: ?string, changed: bool, error: ?string}
     */
    public static function fromRequest(?array $file, ?string $current, bool $remove): array
    {
        $current = self::sanitizeStored($current);
        $errorCode = is_array($file) ? (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;

        if ($errorCode !== UPLOAD_ERR_NO_FILE) {
            if ($errorCode !== UPLOAD_ERR_OK || !is_array($file)) {
                return [
                    'ok' => false,
                    'path' => $current,
                    'changed' => false,
                    'error' => 'Erro no envio da imagem. Tente novamente.',
                ];
            }
            if (!ImageHelper::isValidImage($file) || (int) ($file['size'] ?? 0) > self::MAX_BYTES) {
                return [
                    'ok' => false,
                    'path' => $current,
                    'changed' => false,
                    'error' => 'Envie JPG, PNG, GIF ou WEBP de até 5 MB.',
                ];
            }
            $uploaded = self::store($file);
            if ($uploaded === null) {
                return [
                    'ok' => false,
                    'path' => $current,
                    'changed' => false,
                    'error' => 'Não foi possível salvar a imagem.',
                ];
            }
            self::deleteStored($current);

            return ['ok' => true, 'path' => $uploaded, 'changed' => true, 'error' => null];
        }

        if ($remove && $current !== null) {
            self::deleteStored($current);

            return ['ok' => true, 'path' => null, 'changed' => true, 'error' => null];
        }

        return ['ok' => true, 'path' => $current, 'changed' => false, 'error' => null];
    }

    public static function sanitizeStored(?string $path): ?string
    {
        $path = str_replace('\\', '/', trim((string) $path));
        if ($path === '' || str_contains($path, '..')) {
            return null;
        }
        if (!str_starts_with($path, self::FOLDER . '/')) {
            return null;
        }

        return $path;
    }

    public static function url(?string $path): string
    {
        $path = self::sanitizeStored($path);
        if ($path === null) {
            return '';
        }

        return ImageHelper::getImageUrl($path, '', self::FOLDER);
    }

    public static function deleteStored(?string $path): void
    {
        $path = self::sanitizeStored($path);
        if ($path === null) {
            return;
        }
        $full = self::uploadsRoot() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (is_file($full)) {
            @unlink($full);
        }
    }

    /**
     * @param array<string, mixed> $file
     */
    private static function store(array $file): ?string
    {
        $dir = self::uploadsRoot() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::FOLDER);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }
        $ext = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $filename = str_replace('.', '', uniqid('', true)) . '_' . time() . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file((string) $file['tmp_name'], $dest)) {
            return null;
        }

        return self::FOLDER . '/' . $filename;
    }

    private static function uploadsRoot(): string
    {
        $root = (defined('APP_ROOT') && APP_ROOT !== '') ? APP_ROOT : dirname(__DIR__, 3);

        return $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'adms' . DIRECTORY_SEPARATOR . 'uploads';
    }
}
