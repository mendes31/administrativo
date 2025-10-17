<?php

namespace App\adms\Controllers\Services;

class RequestHelper
{
    public static function getClientIp(): string
    {
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
        ];

        // 1) Priorizar cabeçalhos de proxy, validando e escolhendo o primeiro IP válido
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $value = (string)$_SERVER[$header];
                $parts = array_map('trim', explode(',', $value));
                foreach ($parts as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return self::normalizeLoopbackIp($ip);
                    }
                }
            }
        }

        // 2) Fallback para REMOTE_ADDR
        $remote = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!empty($remote) && filter_var($remote, FILTER_VALIDATE_IP)) {
            return self::normalizeLoopbackIp($remote);
        }

        // 3) Último recurso
        return '0.0.0.0';
    }

    public static function getUserAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    private static function normalizeLoopbackIp(string $ip): string
    {
        // Normalizar IPv6 loopback
        if ($ip === '::1' || $ip === '0:0:0:0:0:0:0:1') {
            return '127.0.0.1';
        }
        return $ip;
    }
}


