<?php

declare(strict_types=1);

namespace App\adms\Helpers;

final class TimelineHashtagHelper
{
    /**
     * @return array<int, string> tags normalizadas (sem #), únicas
     */
    public static function extractNormalizedTags(string $text): array
    {
        if (!preg_match_all('/#([\p{L}\p{N}_-]{2,80})/u', $text, $m)) {
            return [];
        }
        $out = [];
        foreach ($m[1] as $raw) {
            $t = self::normalizeTag((string) $raw);
            if ($t !== '') {
                $out[] = $t;
            }
        }

        return array_values(array_unique($out));
    }

    public static function normalizeTag(string $tag): string
    {
        $tag = trim($tag);
        $tag = ltrim($tag, '#');
        $tag = mb_strtolower($tag, 'UTF-8');
        $tag = preg_replace('/[^\p{L}\p{N}_-]+/u', '-', $tag) ?? '';
        $tag = trim($tag, '-_');
        $tag = preg_replace('/[-_]{2,}/', '-', $tag) ?? '';
        if ($tag === '' || mb_strlen($tag, 'UTF-8') < 2) {
            return '';
        }
        if (mb_strlen($tag, 'UTF-8') > 80) {
            $tag = mb_substr($tag, 0, 80, 'UTF-8');
            $tag = trim($tag, '-_');
        }

        return $tag;
    }

    public static function renderWithLinks(string $plainText, string $urlAdm): string
    {
        $parts = preg_split('/(#[\p{L}\p{N}_-]{2,80})/u', $plainText, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return nl2br(TextEncodingHelper::escape($plainText));
        }
        $urlAdm = rtrim($urlAdm, '/') . '/';
        $out = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if (preg_match('/^#([\p{L}\p{N}_-]{2,80})$/u', $part, $m)) {
                $norm = self::normalizeTag((string) $m[1]);
                if ($norm !== '') {
                    $safeLabel = TextEncodingHelper::escape('#' . $m[1]);
                    $safeUrl = TextEncodingHelper::escape($urlAdm . 'timeline?tag=' . rawurlencode($norm));
                    $out .= '<a href="' . $safeUrl . '" class="timeline-hashtag">' . $safeLabel . '</a>';
                    continue;
                }
            }
            $out .= nl2br(TextEncodingHelper::escape($part));
        }

        return $out;
    }
}

