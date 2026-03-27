<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\UsersRepository;

/**
 * Menções no formato @username (texto) e @123 (legado). IDs são persistidos em adms_timeline_mentions.
 */
final class TimelineMentionHelper
{
    /**
     * Extrai IDs de usuários mencionados: @137 (legado) e @username (correspondência exata ao campo username).
     *
     * @return array<int>
     */
    public static function extractMentionedUserIds(string $text, UsersRepository $repo): array
    {
        $ids = [];
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
                $id = $repo->findIdByUsernameExact($tok);
                if ($id !== null) {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique(array_filter($ids, static fn ($v) => $v > 0)));
    }

    /**
     * @param array<int, string> $idToName id => nome (exibir para menções legadas @id)
     */
    public static function renderHtml(string $text, string $urlAdm, array $idToName, UsersRepository $users): string
    {
        $urlAdm = rtrim($urlAdm, '/') . '/';
        $parts = preg_split('/(@\d+|@[a-zA-Z0-9._-]+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        $out = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if (preg_match('/^@(\d+)$/', $part, $mm)) {
                $id = (int) $mm[1];
                $label = $idToName[$id] ?? ('#' . $id);
                $safeLabel = htmlspecialchars('@' . $label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeUrl = htmlspecialchars($urlAdm . 'view-user/' . $id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $out .= '<a href="' . $safeUrl . '" class="timeline-mention">' . $safeLabel . '</a>';
                continue;
            }
            if (preg_match('/^@([a-zA-Z0-9._-]+)$/', $part, $mm) && !ctype_digit($mm[1])) {
                $uname = $mm[1];
                $map = $users->getActiveUsersByUsernames([$uname]);
                if (isset($map[$uname])) {
                    $id = $map[$uname]['id'];
                    $safeUser = htmlspecialchars($uname, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $safeUrl = htmlspecialchars($urlAdm . 'view-user/' . $id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $out .= '<a href="' . $safeUrl . '" class="timeline-mention">@' . $safeUser . '</a>';
                } else {
                    $out .= nl2br(htmlspecialchars($part, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                }
                continue;
            }
            $out .= nl2br(htmlspecialchars($part, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }

        return $out;
    }
}
