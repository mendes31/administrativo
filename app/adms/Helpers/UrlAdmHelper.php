<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Monta URLs do módulo administrativo a partir de URL_ADM.
 *
 * Se URL_ADM apontar só para a raiz do site (ex.: WordPress em tiaraju.com.br) sem o
 * segmento /administrativo/, os redirects viram https://domínio/import-payroll-documents
 * e o CMS responde 404 — daí a tela "Epic 404" após processar o PDF.
 */
final class UrlAdmHelper
{
    public static function base(): string
    {
        $b = trim((string)($_ENV['URL_ADM'] ?? ''), " \t\n\r");
        if ($b === '') {
            return '/';
        }
        $b = rtrim($b, '/') . '/';

        if (preg_match('#/administrativo/#i', $b)) {
            return $b;
        }

        $host = strtolower((string)(parse_url($b, PHP_URL_HOST) ?? ''));
        if ($host === '') {
            return $b;
        }

        $needsAdminPath =
            str_ends_with($host, 'tiaraju.com.br')
            || str_ends_with($host, 'administrativotiaraju.kinghost.net');
        if ($needsAdminPath) {
            return rtrim($b, '/') . '/administrativo/';
        }

        return $b;
    }

    public static function to(string $path): string
    {
        return self::base() . ltrim($path, '/');
    }
}
