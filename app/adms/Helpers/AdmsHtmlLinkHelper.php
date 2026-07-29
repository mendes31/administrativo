<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Corrige links relativos gerados pelo TinyMCE (convert_urls) que quebram
 * em rotas com segmento (ex.: view-informativo/60 + href="vagas-internas/1"
 * → view-informativo/vagas-internas/1).
 */
final class AdmsHtmlLinkHelper
{
    /**
     * Transforma href relativos em URLs absolutas com base em URL_ADM.
     */
    public static function absolutizeAppLinks(string $html): string
    {
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
        if ($html === '' || $base === '/') {
            return $html;
        }

        $rewritten = preg_replace_callback(
            '/\bhref\s*=\s*(["\'])(?!https?:\/\/|\/\/|\/|#|mailto:|tel:|javascript:)([^"\']*)\1/i',
            static function (array $m) use ($base): string {
                $path = ltrim(str_replace('\\', '/', (string) $m[2]), './');
                if ($path === '') {
                    return 'href=' . $m[1] . $base . $m[1];
                }

                return 'href=' . $m[1] . $base . $path . $m[1];
            },
            $html
        );

        return is_string($rewritten) ? $rewritten : $html;
    }
}
