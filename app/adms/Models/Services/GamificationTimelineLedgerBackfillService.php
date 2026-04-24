<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\GamificationLedgerRepository;
use App\adms\Models\Repository\GamificationProgramRepository;
use App\adms\Models\Repository\GamificationTimelineRulesRepository;
use PDO;

/**
 * Backfill do ledger a partir da timeline, usando created_at real de cada registo.
 * Não dispara missões semanais nem badges (apenas INSERT no ledger).
 *
 * Limitações documentadas:
 * - Não reproduz max_awards_per_user_per_day / max_awards_per_user_total ao longo do tempo.
 * - Respeita tamanho mínimo de comentário e bloqueio de reação no próprio post (settings anti-fraude).
 */
final class GamificationTimelineLedgerBackfillService extends DbConnection
{
    /** @var array<string, array{points:int,is_active:bool}> */
    private array $ruleByEvent = [];

    public function __construct(
        private readonly GamificationLedgerRepository $ledgerRepo = new GamificationLedgerRepository(),
        private readonly GamificationTimelineRulesRepository $rulesRepo = new GamificationTimelineRulesRepository(),
        private readonly GamificationProgramRepository $programRepo = new GamificationProgramRepository()
    ) {
    }

    /**
     * @return array{
     *   dry_run:bool,
     *   include_inactive_rules:bool,
     *   since:?string,
     *   inserted:int,
     *   skipped_existing:int,
     *   skipped_no_rule:int,
     *   skipped_zero_points:int,
     *   skipped_short_comment:int,
     *   skipped_self_reaction:int,
     *   by_event:array<string,int>
     * }
     */
    public function run(bool $dryRun = false, bool $includeInactiveRules = false, ?string $sinceYmdHis = null): array
    {
        $this->loadRules($includeInactiveRules);
        $pdo = $this->getConnection();
        $sinceParam = null;
        if ($sinceYmdHis !== null && $sinceYmdHis !== '') {
            $norm = $this->normalizeInputDate($sinceYmdHis);
            if ($norm !== null) {
                $sinceParam = $norm;
            }
        }
        $sincePosts = $sinceParam !== null ? ' AND p.created_at >= :since ' : '';
        $sinceComments = $sinceParam !== null ? ' AND c.created_at >= :since ' : '';
        $sinceLikes = $sinceParam !== null ? ' AND l.created_at >= :since ' : '';
        $sinceVotes = $sinceParam !== null ? ' AND v.created_at >= :since ' : '';

        $minCommentChars = max(0, $this->programRepo->getIntSetting('anti_fraud_min_comment_chars', 15));
        $blockSelfReaction = $this->programRepo->getIntSetting('anti_fraud_block_self_reaction', 1) === 1;

        $stats = [
            'dry_run' => $dryRun,
            'include_inactive_rules' => $includeInactiveRules,
            'since' => $sinceParam,
            'inserted' => 0,
            'skipped_existing' => 0,
            'skipped_no_rule' => 0,
            'skipped_zero_points' => 0,
            'skipped_short_comment' => 0,
            'skipped_self_reaction' => 0,
            'by_event' => [],
        ];

        $tryInsert = function (
            int $userId,
            string $eventKey,
            string $refType,
            int $refId,
            int $points,
            ?array $meta,
            string $createdAt
        ) use ($dryRun, &$stats): void {
            if ($userId <= 0 || $points <= 0) {
                $stats['skipped_zero_points']++;

                return;
            }
            if ($this->ledgerRowExists($userId, $eventKey, $refType, $refId)) {
                $stats['skipped_existing']++;

                return;
            }
            $metaJson = null;
            if ($meta !== null && $meta !== []) {
                try {
                    $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                } catch (\Throwable) {
                    $metaJson = null;
                }
            }
            if ($dryRun) {
                $stats['inserted']++;
                $stats['by_event'][$eventKey] = ($stats['by_event'][$eventKey] ?? 0) + 1;

                return;
            }
            if ($this->ledgerRepo->insertIfNotExistsAt($userId, 'timeline', $eventKey, $refType, $refId, $points, $metaJson, $createdAt)) {
                $stats['inserted']++;
                $stats['by_event'][$eventKey] = ($stats['by_event'][$eventKey] ?? 0) + 1;
            } else {
                $stats['skipped_existing']++;
            }
        };

        // --- Posts (original vs repost)
        $sqlPosts = 'SELECT p.id, p.user_id, p.shared_from_post_id, p.created_at
                     FROM adms_timeline_posts p
                     INNER JOIN adms_users u ON u.id = p.user_id AND u.status = 1
                     WHERE p.status = \'active\' AND p.user_id > 0 ' . $sincePosts . '
                     ORDER BY p.id ASC';
        $st = $pdo->prepare($sqlPosts);
        if ($sinceParam !== null) {
            $st->bindValue(':since', $sinceParam, PDO::PARAM_STR);
        }
        $st->execute();
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $postId = (int)($row['id'] ?? 0);
            $authorId = (int)($row['user_id'] ?? 0);
            $sharedFrom = $row['shared_from_post_id'] ?? null;
            $createdAt = (string)($row['created_at'] ?? '');
            if ($postId <= 0 || $authorId <= 0 || $createdAt === '') {
                continue;
            }
            if ($sharedFrom !== null && (int)$sharedFrom > 0) {
                $pts = $this->pointsFor('timeline_share_created');
                if ($pts === null) {
                    $stats['skipped_no_rule']++;
                    continue;
                }
                if ($pts <= 0) {
                    $stats['skipped_zero_points']++;
                    continue;
                }
                $tryInsert($authorId, 'timeline_share_created', 'timeline_post', $postId, $pts, [
                    'shared_from_post_id' => (int)$sharedFrom,
                    'backfill' => true,
                ], $createdAt);
            } else {
                $pts = $this->pointsFor('timeline_post_created');
                if ($pts === null) {
                    $stats['skipped_no_rule']++;
                    continue;
                }
                if ($pts <= 0) {
                    $stats['skipped_zero_points']++;
                    continue;
                }
                $tryInsert($authorId, 'timeline_post_created', 'timeline_post', $postId, $pts, ['backfill' => true], $createdAt);
            }
        }

        // --- Comentários
        $sqlComments = 'SELECT c.id, c.post_id, c.user_id, c.content, c.created_at
                        FROM adms_timeline_comments c
                        INNER JOIN adms_timeline_posts p ON p.id = c.post_id AND p.status = \'active\'
                        INNER JOIN adms_users u ON u.id = c.user_id AND u.status = 1
                        WHERE c.status = \'active\' AND c.user_id > 0 ' . $sinceComments . '
                        ORDER BY c.id ASC';
        $stc = $pdo->prepare($sqlComments);
        if ($sinceParam !== null) {
            $stc->bindValue(':since', $sinceParam, PDO::PARAM_STR);
        }
        $stc->execute();
        while ($row = $stc->fetch(PDO::FETCH_ASSOC)) {
            $cid = (int)($row['id'] ?? 0);
            $uid = (int)($row['user_id'] ?? 0);
            $content = trim((string)($row['content'] ?? ''));
            $createdAt = (string)($row['created_at'] ?? '');
            if ($cid <= 0 || $uid <= 0 || $createdAt === '') {
                continue;
            }
            $pts = $this->pointsFor('timeline_comment_created');
            if ($pts === null) {
                $stats['skipped_no_rule']++;
                continue;
            }
            if ($pts <= 0) {
                $stats['skipped_zero_points']++;
                continue;
            }
            if ($content !== '' && mb_strlen($content) < $minCommentChars) {
                $stats['skipped_short_comment']++;
                continue;
            }
            $tryInsert($uid, 'timeline_comment_created', 'timeline_comment', $cid, $pts, ['content' => $content, 'backfill' => true], $createdAt);
        }

        // --- Reações (uma linha por post+utilizador na timeline_likes)
        $sqlLikes = 'SELECT l.post_id, l.user_id, l.created_at, l.reaction_type, p.user_id AS post_owner_id
                     FROM adms_timeline_likes l
                     INNER JOIN adms_timeline_posts p ON p.id = l.post_id AND p.status = \'active\'
                     INNER JOIN adms_users ua ON ua.id = l.user_id AND ua.status = 1
                     WHERE l.user_id > 0 ' . $sinceLikes . '
                     ORDER BY l.post_id ASC, l.user_id ASC';
        $stl = $pdo->prepare($sqlLikes);
        if ($sinceParam !== null) {
            $stl->bindValue(':since', $sinceParam, PDO::PARAM_STR);
        }
        $stl->execute();
        while ($row = $stl->fetch(PDO::FETCH_ASSOC)) {
            $postId = (int)($row['post_id'] ?? 0);
            $actorId = (int)($row['user_id'] ?? 0);
            $ownerId = (int)($row['post_owner_id'] ?? 0);
            $createdAt = (string)($row['created_at'] ?? '');
            if ($postId <= 0 || $actorId <= 0 || $createdAt === '') {
                continue;
            }
            if ($blockSelfReaction && $ownerId > 0 && $actorId === $ownerId) {
                $stats['skipped_self_reaction']++;
                continue;
            }
            $pts = $this->pointsFor('timeline_reaction_created');
            if ($pts === null) {
                $stats['skipped_no_rule']++;
                continue;
            }
            if ($pts <= 0) {
                $stats['skipped_zero_points']++;
                continue;
            }
            $tryInsert($actorId, 'timeline_reaction_created', 'timeline_post', $postId, $pts, [
                'reaction' => (string)($row['reaction_type'] ?? ''),
                'target_user_id' => $ownerId,
                'backfill' => true,
            ], $createdAt);
        }

        // --- Votos em enquete (ref_id = post_id, igual ao fluxo em TimelineComment)
        if ($this->tableExists($pdo, 'adms_timeline_poll_votes')) {
            $sqlVotes = 'SELECT v.user_id, v.created_at, pl.post_id AS post_id
                          FROM adms_timeline_poll_votes v
                          INNER JOIN adms_timeline_polls pl ON pl.id = v.poll_id
                          INNER JOIN adms_timeline_posts p ON p.id = pl.post_id AND p.status = \'active\'
                          INNER JOIN adms_users u ON u.id = v.user_id AND u.status = 1
                          WHERE v.user_id > 0 ' . $sinceVotes . '
                          ORDER BY v.poll_id ASC, v.user_id ASC';
            $stv = $pdo->prepare($sqlVotes);
            if ($sinceParam !== null) {
                $stv->bindValue(':since', $sinceParam, PDO::PARAM_STR);
            }
            $stv->execute();
            while ($row = $stv->fetch(PDO::FETCH_ASSOC)) {
                $postId = (int)($row['post_id'] ?? 0);
                $uid = (int)($row['user_id'] ?? 0);
                $createdAt = (string)($row['created_at'] ?? '');
                if ($postId <= 0 || $uid <= 0 || $createdAt === '') {
                    continue;
                }
                $pts = $this->pointsFor('timeline_poll_vote');
                if ($pts === null) {
                    $stats['skipped_no_rule']++;
                    continue;
                }
                if ($pts <= 0) {
                    $stats['skipped_zero_points']++;
                    continue;
                }
                $tryInsert($uid, 'timeline_poll_vote', 'timeline_post', $postId, $pts, ['backfill' => true], $createdAt);
            }
        }

        return $stats;
    }

    private function tableExists(PDO $pdo, string $table): bool
    {
        $db = (string)($_ENV['DB_NAME'] ?? '');
        if ($db === '') {
            return false;
        }
        $q = $pdo->prepare(
            'SELECT 1 FROM information_schema.tables WHERE table_schema = :db AND table_name = :t LIMIT 1'
        );
        $q->execute([':db' => $db, ':t' => $table]);

        return (bool)$q->fetchColumn();
    }

    private function ledgerRowExists(int $userId, string $eventKey, string $refType, int $refId): bool
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT 1 FROM adms_gamification_point_ledger
             WHERE user_id = :u AND event_key = :e AND ref_type = :r AND ref_id = :i LIMIT 1'
        );
        $stmt->execute([':u' => $userId, ':e' => $eventKey, ':r' => $refType, ':i' => $refId]);

        return (bool)$stmt->fetchColumn();
    }

    private function loadRules(bool $includeInactive): void
    {
        $this->ruleByEvent = [];
        foreach ($this->rulesRepo->listAll() as $r) {
            $ek = (string)($r['event_key'] ?? '');
            if ($ek === '') {
                continue;
            }
            $active = !empty($r['is_active']);
            if (!$includeInactive && !$active) {
                continue;
            }
            $this->ruleByEvent[$ek] = [
                'points' => max(0, (int)($r['points'] ?? 0)),
                'is_active' => $active,
            ];
        }
    }

    /** @return int|null null = regra em falta (ou filtrada) */
    private function pointsFor(string $eventKey): ?int
    {
        if (!isset($this->ruleByEvent[$eventKey])) {
            return null;
        }

        return $this->ruleByEvent[$eventKey]['points'];
    }

    private function normalizeInputDate(string $s): ?string
    {
        $s = trim($s);
        if ($s === '') {
            return null;
        }
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $s)
            ?: \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $s);
        if ($dt === false) {
            try {
                $dt = new \DateTimeImmutable($s);
            } catch (\Throwable) {
                return null;
            }
        }

        return $dt->format('Y-m-d H:i:s');
    }
}
