<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\NotificationsRepository;

/**
 * Marca notificação interna como lida ao abrir o destino (parâmetro mark_notification na URL).
 */
final class NotificationOpenHelper
{
    public static function markFromRequestIfPresent(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        $notificationId = self::notificationIdFromRequest();
        if ($notificationId <= 0) {
            return;
        }

        try {
            $repo = new NotificationsRepository();
            if ($repo->notificationOwnerState($notificationId, $userId) !== 'self') {
                return;
            }
            if ($repo->markAsRead($notificationId, $userId)) {
                NavbarLayoutCacheHelper::clear();
            }
        } catch (\Throwable $e) {
            error_log('NotificationOpenHelper::markFromRequestIfPresent error: ' . $e->getMessage());
        }
    }

    /**
     * Abertura vinda de outro host, ou de uma notificação de outra conta, não reaproveita a sessão local.
     * A página não é renderizada: a sessão é encerrada e o login deste endereço é exigido.
     */
    public static function discardForeignNotificationSession(): void
    {
        $notificationId = self::notificationIdFromRequest();
        if ($notificationId <= 0) {
            return;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        $foreign = self::isForeignReferer(
            (string) ($_SERVER['HTTP_REFERER'] ?? ''),
            (string) ($_SERVER['HTTP_HOST'] ?? '')
        );

        $owner = 'missing';
        try {
            $owner = (new NotificationsRepository())->notificationOwnerState($notificationId, $userId);
        } catch (\Throwable $e) {
            error_log('NotificationOpenHelper::discardForeignNotificationSession error: ' . $e->getMessage());
        }

        if (!$foreign && $owner !== 'other') {
            return;
        }

        $message = $foreign
            ? 'Este aviso veio de outro ambiente. A sessão deste endereço não foi usada. Entre com o seu usuário aqui.'
            : 'Esta notificação não pertence à conta desta sessão. Entre com o seu usuário.';

        self::discardSessionAndRequireLogin($message);
    }

    public static function isForeignReferer(?string $referer, ?string $currentHost): bool
    {
        $current = self::normalizeHost($currentHost);
        if ($current === '') {
            return false;
        }

        $referer = trim((string) $referer);
        if ($referer === '' || !preg_match('#^https?://#i', $referer)) {
            return false;
        }

        $parts = parse_url($referer);
        if (!is_array($parts)) {
            return false;
        }

        $refHost = self::normalizeHost((string) ($parts['host'] ?? ''));
        if ($refHost === '') {
            return false;
        }

        $path = strtolower((string) ($parts['path'] ?? ''));
        if (!str_contains($path, '/administrativo')) {
            return false;
        }

        return $refHost !== $current;
    }

    public static function currentRequestAppBase(): string
    {
        $env = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        $rawHost = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($rawHost === '' || !preg_match('/^[a-z0-9.\-:\[\]]+$/i', $rawHost)) {
            return $env;
        }

        $https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        $scheme = $https ? 'https' : 'http';

        $path = '';
        $parts = parse_url($env !== '' ? $env : '');
        if (is_array($parts) && !empty($parts['path'])) {
            $path = rtrim((string) $parts['path'], '/');
        }

        return $scheme . '://' . $rawHost . $path;
    }

    /**
     * Mantém o destino da notificação no endereço desta instalação.
     * Links gravados com outro host (ex.: produção) não podem abrir outra sessão.
     */
    public static function urlOnCurrentHost(?string $linkUrl, ?string $appBase = null): string
    {
        $base = rtrim($appBase ?? self::currentRequestAppBase(), '/');
        $fallback = $base !== '' ? $base . '/notificacoes' : '/notificacoes';
        $link = trim((string) $linkUrl);
        if ($link === '' || $link === '#') {
            return $fallback;
        }

        $appPath = '';
        $baseParts = parse_url($base);
        if (is_array($baseParts) && !empty($baseParts['path'])) {
            $appPath = rtrim((string) $baseParts['path'], '/');
        }

        $query = '';
        $fragment = '';
        $path = $link;

        if (preg_match('#^https?://#i', $link)) {
            $parts = parse_url($link);
            if (!is_array($parts)) {
                return $fallback;
            }
            $path = (string) ($parts['path'] ?? '/');
            $query = isset($parts['query']) ? '?' . $parts['query'] : '';
            $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        } elseif (str_contains($link, '?') || str_contains($link, '#')) {
            $hashPos = strpos($link, '#');
            if ($hashPos !== false) {
                $fragment = substr($link, $hashPos);
                $link = substr($link, 0, $hashPos);
            }
            $qPos = strpos($link, '?');
            if ($qPos !== false) {
                $query = substr($link, $qPos);
                $path = substr($link, 0, $qPos);
            } else {
                $path = $link;
            }
        }

        if ($appPath !== '') {
            $pos = stripos($path, $appPath);
            if ($pos !== false) {
                $path = substr($path, $pos + strlen($appPath));
            }
        }

        $path = '/' . ltrim($path, '/');
        if ($path === '/') {
            return $base . $query . $fragment;
        }

        return ($base !== '' ? $base : '') . $path . $query . $fragment;
    }

    private static function notificationIdFromRequest(): int
    {
        if (!isset($_GET['mark_notification']) || !is_numeric($_GET['mark_notification'])) {
            return 0;
        }

        return max(0, (int) $_GET['mark_notification']);
    }

    private static function normalizeHost(?string $host): string
    {
        $host = strtolower(trim((string) $host));
        if ($host === '') {
            return '';
        }
        if (str_starts_with($host, '[')) {
            $end = strpos($host, ']');

            return $end === false ? $host : substr($host, 0, $end + 1);
        }

        return (string) (preg_replace('/:\d+$/', '', $host) ?: $host);
    }

    private static function discardSessionAndRequireLogin(string $message): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }
        session_destroy();
        session_start();

        $_SESSION['error'] = $message;

        header('Location: ' . rtrim(self::currentRequestAppBase(), '/') . '/login');
        exit;
    }
}
