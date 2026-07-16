<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * URL de retorno pós-login (ex.: QR do equipamento).
 * Evita concatenar URL_ADM + REQUEST_URI (duplicava /administrativo/...).
 */
final class ReturnUrlHelper
{
    public static function fromCurrentRequest(): ?string
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        if ($uri === '' || str_contains(strtolower($uri), 'login')) {
            return null;
        }

        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        if ($host === '') {
            return null;
        }

        $https = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
        $scheme = $https ? 'https' : 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $fwd = strtolower(trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
            if ($fwd === 'https' || $fwd === 'http') {
                $scheme = $fwd;
            }
        }

        return self::sanitize($scheme . '://' . $host . $uri);
    }

    public static function consume(): ?string
    {
        $fromSession = self::sanitize($_SESSION['return_url'] ?? null);
        unset($_SESSION['return_url']);

        if ($fromSession !== null) {
            return $fromSession;
        }

        return self::sanitize($_POST['return_url'] ?? null);
    }

    public static function sanitize(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $url = str_replace(['"', "'"], '', $url);
        $parsed = parse_url($url);
        if (!is_array($parsed) || empty($parsed['host']) || empty($parsed['path'])) {
            return null;
        }

        $adm = parse_url((string) ($_ENV['URL_ADM'] ?? ''));
        if (!is_array($adm) || empty($adm['host'])) {
            return null;
        }

        $path = (string) $parsed['path'];
        if (str_contains(strtolower($path), '/login')) {
            return null;
        }

        $admPath = rtrim((string) ($adm['path'] ?? ''), '/');
        if ($admPath !== '') {
            $normalized = rtrim($path, '/');
            if ($normalized !== $admPath && !str_starts_with($path, $admPath . '/')) {
                return null;
            }
        }

        $requestHost = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $requestHost = strtolower((string) (preg_replace('/:\d+$/', '', $requestHost) ?: $requestHost));
        $urlHost = strtolower((string) $parsed['host']);
        $admHost = strtolower((string) $adm['host']);

        $hostOk = strcasecmp($urlHost, $admHost) === 0
            || ($requestHost !== '' && strcasecmp($urlHost, $requestHost) === 0);
        if (!$hostOk) {
            return null;
        }

        $query = isset($parsed['query']) && $parsed['query'] !== '' ? '?' . $parsed['query'] : '';

        // Normalizar para URL_ADM (mesmo host do .env)
        $scheme = (string) ($adm['scheme'] ?? $parsed['scheme'] ?? 'http');
        $finalHost = (string) $adm['host'];
        $port = isset($adm['port']) ? ':' . $adm['port'] : '';

        return $scheme . '://' . $finalHost . $port . $path . $query;
    }

    public static function storeFromCurrentRequest(): void
    {
        $url = self::fromCurrentRequest();
        if ($url !== null) {
            $_SESSION['return_url'] = $url;
        }
    }

    public static function dashboardFallback(): string
    {
        return rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/dashboard';
    }
}
