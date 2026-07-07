<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * URL pública do canal (raiz do site, sem /administrativo/).
 */
final class WhistleblowingPublicUrlHelper
{
    public static function baseUrl(): string
    {
        $fromEnv = trim((string) ($_ENV['URL_CANAL_DENUNCIA'] ?? ''));
        if ($fromEnv !== '') {
            return rtrim($fromEnv, '/') . '/';
        }

        $adm = rtrim((string) ($_ENV['URL_ADM'] ?? '/'), '/');

        return $adm . '/canaldenuncia/';
    }

    public static function path(string $suffix = ''): string
    {
        $suffix = trim($suffix, '/');

        return self::baseUrl() . $suffix;
    }
}
