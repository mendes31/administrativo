<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\UsersRepository;

/**
 * Menções no formato @username (texto) e @123 (legado). IDs são persistidos em adms_timeline_mentions.
 * @todos / @everyone mencionam todos os colaboradores ativos (exceto o autor da ação).
 */
final class TimelineMentionHelper
{
    /** Tokens que expandem para todos os usuários (comparação sem acento de maiúsculas). */
    private const EVERYONE_TOKENS = ['todos', 'everyone'];

    /**
     * Extrai IDs de usuários mencionados: @137 (legado), @username, e @todos/@everyone (todos ativos exceto $excludeActorId).
     *
     * @param bool $expandEveryone Quando false, @todos/@everyone não geram lista de IDs (ex.: mapa de nomes na view).
     * @return array<int>
     */
    public static function extractMentionedUserIds(
        string $text,
        UsersRepository $repo,
        ?int $excludeActorId = null,
        bool $expandEveryone = true
    ): array {
        $ids = [];
        $hasEveryoneToken = false;

        if (preg_match_all('/@(\d+)/u', $text, $m)) {
            foreach ($m[1] as $d) {
                $ids[] = (int) $d;
            }
        }
        if (preg_match_all('/@([a-zA-Z0-9._-]+)/u', $text, $m2)) {
            foreach ($m2[1] as $tok) {
                if (ctype_digit($tok)) {
                    continue;
                }
                $lower = strtolower($tok);
                if (in_array($lower, self::EVERYONE_TOKENS, true)) {
                    $hasEveryoneToken = true;

                    continue;
                }
                $id = $repo->findIdByUsernameExact($tok);
                if ($id !== null) {
                    $ids[] = $id;
                }
            }
        }

        if ($expandEveryone && $hasEveryoneToken) {
            $exclude = $excludeActorId ?? 0;
            $ids = array_merge($ids, $repo->getAllActiveUserIdsForTimelineMentions($exclude));
        }

        return array_values(array_unique(array_filter($ids, static fn ($v) => $v > 0)));
    }

    /**
     * @param array<int, string> $idToName id => nome (exibir para menções legadas @id)
     */
    public static function renderHtml(string $text, string $urlAdm, array $idToName, UsersRepository $users): string
    {
        $text = TextEncodingHelper::decodeEntities($text);
        $urlAdm = rtrim($urlAdm, '/') . '/';
        $parts = preg_split('/(@\d+|@[a-zA-Z0-9._-]+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return TextEncodingHelper::escape($text);
        }
        $out = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if (preg_match('/^@(\d+)$/', $part, $mm)) {
                $id = (int) $mm[1];
                $label = $idToName[$id] ?? ('#' . $id);
                $safeLabel = TextEncodingHelper::escape('@' . $label);
                $safeUrl = TextEncodingHelper::escape($urlAdm . 'view-user/' . $id);
                $out .= '<a href="' . $safeUrl . '" class="timeline-mention">' . $safeLabel . '</a>';
                continue;
            }
            if (preg_match('/^@([a-zA-Z0-9._-]+)$/', $part, $mm) && !ctype_digit($mm[1])) {
                $uname = $mm[1];
                $lower = strtolower($uname);
                if (in_array($lower, self::EVERYONE_TOKENS, true)) {
                    $display = $lower === 'everyone' ? 'everyone' : 'todos';
                    $out .= '<span class="timeline-mention timeline-mention-everyone">@' . TextEncodingHelper::escape($display) . '</span>';
                    continue;
                }
                $map = $users->getActiveUsersByUsernames([$uname]);
                if (isset($map[$uname])) {
                    $id = $map[$uname]['id'];
                    $safeUser = TextEncodingHelper::escape($uname);
                    $safeUrl = TextEncodingHelper::escape($urlAdm . 'view-user/' . $id);
                    $out .= '<a href="' . $safeUrl . '" class="timeline-mention">@' . $safeUser . '</a>';
                } else {
                    $out .= nl2br(TextEncodingHelper::escape($part));
                }
                continue;
            }
            $out .= nl2br(TextEncodingHelper::escape($part));
        }

        return $out;
    }
}
