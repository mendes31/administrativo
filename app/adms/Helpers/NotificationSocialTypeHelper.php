<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Tipos sociais da Timeline que o "Marcar todas como lidas" pode zerar.
 * Comunicados, políticas, ciência e demais avisos oficiais ficam de fora.
 */
final class NotificationSocialTypeHelper
{
    /** @var list<string> */
    public const TYPES = [
        'timeline_reaction',
        'timeline_comment_reaction',
        'timeline_like',
        'timeline_comment',
        'timeline_share',
        'timeline_mention',
        'comentario_mencao',
    ];

    public static function isSocial(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return self::TYPES;
    }
}
