<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\UsersRepository;

/**
 * Menções: @username, @123 (legado), @todos / @everyone, departamento por @depto-{id} ou slug (ex.: @financeiro).
 * IDs são persistidos em adms_timeline_mentions.
 */
final class TimelineMentionHelper
{
    /** Tokens que expandem para todos os usuários (comparação sem acento de maiúsculas). */
    private const EVERYONE_TOKENS = ['todos', 'everyone'];

    /**
     * Extrai IDs de usuários mencionados: @137 (legado), @username, @todos/@everyone, menções por departamento.
     *
     * @param bool $expandGroupMentions Quando false, @todos, @everyone e @depto-id não geram lista de IDs (ex.: mapa na view).
     * @return array<int>
     */
    public static function extractMentionedUserIds(
        string $text,
        UsersRepository $repo,
        ?int $excludeActorId = null,
        bool $expandGroupMentions = true
    ): array {
        $ids = [];
        $hasEveryoneToken = false;
        $departmentIds = [];

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
                $userId = $repo->findIdByUsernameExact($tok);
                if ($userId !== null) {
                    $ids[] = $userId;

                    continue;
                }
                $depId = $repo->resolveTimelineDepartmentMention($tok);
                if ($depId !== null && $depId > 0) {
                    $departmentIds[] = $depId;
                }
            }
        }

        $exclude = $excludeActorId ?? 0;
        if ($expandGroupMentions && $hasEveryoneToken) {
            $ids = array_merge($ids, $repo->getAllActiveUserIdsForTimelineMentions($exclude));
        }
        if ($expandGroupMentions && $departmentIds !== []) {
            foreach (array_unique($departmentIds) as $did) {
                $ids = array_merge($ids, $repo->getActiveUserIdsByDepartmentForTimelineMentions((int) $did, $exclude));
            }
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

        $deptNameMap = [];
        if (preg_match_all('/@([a-zA-Z0-9._-]+)/u', $text, $dm)) {
            $depIds = [];
            foreach ($dm[1] as $tok) {
                if (ctype_digit($tok)) {
                    continue;
                }
                $i = $users->resolveTimelineDepartmentMention($tok);
                if ($i !== null && $i > 0) {
                    $depIds[] = $i;
                }
            }
            if ($depIds !== []) {
                $deptNameMap = $users->getDepartmentNamesByIds(array_values(array_unique($depIds)));
            }
        }

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
                $safeUrl = TextEncodingHelper::escape($urlAdm . 'timeline-profile/' . $id);
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
                    $safeUrl = TextEncodingHelper::escape($urlAdm . 'timeline-profile/' . $id);
                    $out .= '<a href="' . $safeUrl . '" class="timeline-mention">@' . $safeUser . '</a>';
                    continue;
                }
                $depId = $users->resolveTimelineDepartmentMention($uname);
                if ($depId !== null && $depId > 0) {
                    $dname = $deptNameMap[$depId] ?? null;
                    if ($dname !== null && $dname !== '') {
                        $title = TextEncodingHelper::escape('@' . $uname);
                        $out .= '<span class="timeline-mention timeline-mention-dept" title="' . $title . '">@' . TextEncodingHelper::escape($dname) . '</span>';
                    } else {
                        $out .= TimelineHashtagHelper::renderWithLinks($part, $urlAdm);
                    }
                    continue;
                }
                $out .= TimelineHashtagHelper::renderWithLinks($part, $urlAdm);
                continue;
            }
            $out .= TimelineHashtagHelper::renderWithLinks($part, $urlAdm);
        }

        return $out;
    }
}
