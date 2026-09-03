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

    /**
     * Prepara HTML rico para exibição: absolutiza href e marca âncoras
     * para o CSS de destaque (remove estilos inline que escondem o link).
     */
    public static function prepareRichHtml(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $html = self::absolutizeAppLinks($html);

        $rewritten = preg_replace_callback(
            '/<a\b([^>]*)>/i',
            static function (array $m): string {
                $attrs = (string) $m[1];

                // Remove style inline (TinyMCE/Word costumam forçar cor preta / sem sublinhado).
                $attrs = preg_replace('/\sstyle\s*=\s*(["\'])(.*?)\1/is', '', $attrs) ?? $attrs;

                if (preg_match('/\bclass\s*=\s*(["\'])([^"\']*)\1/i', $attrs, $cm)) {
                    $classes = trim((string) $cm[2]);
                    if (!preg_match('/(?:^|\s)adms-rich-link(?:\s|$)/', $classes)) {
                        $classes = trim($classes . ' adms-rich-link');
                    }
                    $attrs = preg_replace(
                        '/\bclass\s*=\s*(["\'])([^"\']*)\1/i',
                        'class=' . $cm[1] . $classes . $cm[1],
                        $attrs,
                        1
                    ) ?? $attrs;
                } else {
                    $attrs .= ' class="adms-rich-link"';
                }

                return '<a' . $attrs . '>';
            },
            $html
        );

        return is_string($rewritten) ? $rewritten : $html;
    }
}
