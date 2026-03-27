<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Normaliza textos que podem chegar com entidades HTML salvas no banco.
 */
final class TextEncodingHelper
{
    public static function decodeEntities(?string $value): string
    {
        $text = (string)($value ?? '');
        // Alguns registros antigos podem estar codificados duas vezes.
        for ($i = 0; $i < 2; $i++) {
            $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $text) {
                break;
            }
            $text = $decoded;
        }
        return $text;
    }

    public static function escape(?string $value): string
    {
        return htmlspecialchars(self::decodeEntities($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
