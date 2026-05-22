<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Evita reenvio automático duplicado de push na mesma publicação (sem usar adms_notifications).
 */
final class PublishPushDedupCache
{
    private const TTL_SECONDS = 86400 * 90;

    public static function scopeForEntity(string $type, int $entityId): string
    {
        return $type . '_' . $entityId;
    }

    public static function wasSentInScope(string $scope, int $userId, string $title, string $url): bool
    {
        $cacheKey = $userId . ':' . md5($title . '|' . $url);
        $data = self::readScopeFile($scope);
        if (!isset($data['keys'][$cacheKey])) {
            return false;
        }
        $ts = (int) ($data['keys'][$cacheKey] ?? 0);

        return $ts > 0 && (time() - $ts) < self::TTL_SECONDS;
    }

    public static function markSentInScope(string $scope, int $userId, string $title, string $url): void
    {
        $cacheKey = $userId . ':' . md5($title . '|' . $url);
        $data = self::readScopeFile($scope);
        $data['keys'][$cacheKey] = time();
        self::writeScopeFile($scope, $data);
    }

    public static function clearScope(string $scope): void
    {
        $path = self::scopePathFromScope($scope);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * @return array{keys: array<string, int>}
     */
    private static function readScopeFile(string $scope): array
    {
        $path = self::scopePathFromScope($scope);
        if (!is_file($path)) {
            return ['keys' => []];
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            return ['keys' => []];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) && isset($decoded['keys']) && is_array($decoded['keys'])
            ? $decoded
            : ['keys' => []];
    }

    /**
     * @param array{keys: array<string, int>} $data
     */
    private static function writeScopeFile(string $scope, array $data): void
    {
        $dir = self::cacheDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents(self::scopePathFromScope($scope), json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    private static function scopePathFromScope(string $scope): string
    {
        return self::cacheDir() . DIRECTORY_SEPARATOR . preg_replace('/[^a-z0-9_\-]/i', '_', $scope) . '.json';
    }

    private static function cacheDir(): string
    {
        return dirname(__DIR__, 3)
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache'
            . DIRECTORY_SEPARATOR . 'push_dedup';
    }
}
