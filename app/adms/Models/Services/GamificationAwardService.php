<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\GamificationLedgerRepository;
use App\adms\Models\Repository\GamificationProgramRepository;
use App\adms\Models\Repository\GamificationTimelineRulesRepository;

class GamificationAwardService
{
    public function __construct(
        private readonly GamificationTimelineRulesRepository $rulesRepo = new GamificationTimelineRulesRepository(),
        private readonly GamificationLedgerRepository $ledgerRepo = new GamificationLedgerRepository(),
        private readonly GamificationProgramRepository $programRepo = new GamificationProgramRepository()
    ) {
    }

    /**
     * Aplica regra de timeline (se ativa) e regista no ledger com idempotência por ref.
     *
     * @return int Pontos efetivamente creditados (0 se bloqueado ou duplicado)
     */
    public function awardTimelineEvent(int $userId, string $eventKey, string $refType, int $refId, ?array $meta = null): int
    {
        if ($userId <= 0 || $refId < 0) {
            return 0;
        }
        $rule = $this->rulesRepo->findByEventKey($eventKey);
        if (!$rule || empty($rule['is_active'])) {
            return 0;
        }
        $points = (int)($rule['points'] ?? 0);
        if ($points <= 0) {
            return 0;
        }
        if ($this->shouldBlockByAntiFraud($userId, $eventKey, $refType, $refId, $meta)) {
            return 0;
        }

        $maxDay = $rule['max_awards_per_user_per_day'] ?? null;
        if ($maxDay !== null && (int)$maxDay > 0) {
            if ($this->ledgerRepo->countAwardsToday($userId, $eventKey) >= (int)$maxDay) {
                return 0;
            }
        }
        $maxTot = $rule['max_awards_per_user_total'] ?? null;
        if ($maxTot !== null && (int)$maxTot > 0) {
            if ($this->ledgerRepo->countAwardsTotal($userId, $eventKey) >= (int)$maxTot) {
                return 0;
            }
        }

        $metaJson = null;
        if ($meta !== null && $meta !== []) {
            try {
                $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                $metaJson = null;
            }
        }
        $ok = $this->ledgerRepo->insertIfNotExists($userId, 'timeline', $eventKey, $refType, $refId, $points, $metaJson);
        if ($ok) {
            $this->processMonthlyMissions($userId, $eventKey);
            $this->processBadges($userId);
        }

        return $ok ? $points : 0;
    }

    /**
     * Pontos de conclusão de quiz (configurados no quiz, não na tabela de regras da timeline).
     */
    public function awardQuizLedger(int $userId, int $attemptId, int $points): int
    {
        if ($userId <= 0 || $attemptId <= 0 || $points <= 0) {
            return 0;
        }
        try {
            $metaJson = json_encode(['attempt_id' => $attemptId], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            $metaJson = null;
        }
        $ok = $this->ledgerRepo->insertIfNotExists(
            $userId,
            'quiz',
            'quiz_completion',
            'gamification_quiz_attempt',
            $attemptId,
            $points,
            $metaJson
        );
        if ($ok) {
            $this->processBadges($userId);
        }

        return $ok ? $points : 0;
    }

    private function shouldBlockByAntiFraud(
        int $userId,
        string $eventKey,
        string $refType,
        int $refId,
        ?array $meta
    ): bool {
        $meta = is_array($meta) ? $meta : [];
        $minCommentChars = max(0, $this->programRepo->getIntSetting('anti_fraud_min_comment_chars', 15));
        $maxSamePerMinute = max(1, $this->programRepo->getIntSetting('anti_fraud_max_same_event_per_minute', 3));
        $blockSelfReaction = $this->programRepo->getIntSetting('anti_fraud_block_self_reaction', 1) === 1;

        if ($eventKey === 'timeline_comment_created') {
            $content = trim((string)($meta['content'] ?? ''));
            if ($content !== '' && mb_strlen($content) < $minCommentChars) {
                $this->registerAntiFraud($userId, $eventKey, $refType, $refId, 'Comentario abaixo do minimo configurado', ['len' => mb_strlen($content)]);
                return true;
            }
        }
        if ($blockSelfReaction && $eventKey === 'timeline_reaction_created') {
            $targetUserId = (int)($meta['target_user_id'] ?? 0);
            if ($targetUserId > 0 && $targetUserId === $userId) {
                $this->registerAntiFraud($userId, $eventKey, $refType, $refId, 'Reacao no proprio post', null);
                return true;
            }
        }
        if ($this->ledgerRepo->countAwardsSince($userId, $eventKey, 60) >= $maxSamePerMinute) {
            $this->registerAntiFraud($userId, $eventKey, $refType, $refId, 'Limite por minuto excedido', ['max_per_minute' => $maxSamePerMinute]);
            return true;
        }

        return false;
    }

    private function registerAntiFraud(
        int $userId,
        string $eventKey,
        string $refType,
        int $refId,
        string $reason,
        ?array $details
    ): void {
        try {
            $detailsJson = $details !== null ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : null;
        } catch (\Throwable) {
            $detailsJson = null;
        }
        try {
            $this->programRepo->registerAntiFraudEvent($userId, $eventKey, $refType, $refId, $reason, $detailsJson);
        } catch (\Throwable) {
        }
    }

    /**
     * Progresso das missões por mês civil (chave em adms_gamification_user_mission_progress.week_start_date = Y-m-01).
     */
    private function processMonthlyMissions(int $userId, string $eventKey): void
    {
        $monthStart = (new \DateTimeImmutable('first day of this month'))->format('Y-m-01');
        foreach ($this->programRepo->listActiveWeeklyMissions() as $mission) {
            if ((string)($mission['event_key'] ?? '') !== $eventKey) {
                continue;
            }
            $progress = $this->programRepo->upsertWeeklyMissionProgress(
                (int)$mission['id'],
                $userId,
                $monthStart,
                1
            );
            if ((int)($progress['id'] ?? 0) <= 0) {
                continue;
            }
            $target = max(1, (int)($progress['effective_target'] ?? 1));
            if ((int)($progress['is_completed'] ?? 0) === 1 || (int)($progress['current_value'] ?? 0) < $target) {
                continue;
            }

            $this->programRepo->markMissionCompleted((int)$progress['id']);
            $rewardPoints = max(0, (int)($progress['effective_reward'] ?? 0));
            if ($rewardPoints <= 0) {
                continue;
            }
            $this->ledgerRepo->insertIfNotExists(
                $userId,
                'mission',
                'monthly_mission_completed',
                'gamification_mission_progress',
                (int)$progress['id'],
                $rewardPoints,
                null
            );
        }
    }

    private function processBadges(int $userId): void
    {
        $monthRef = date('Y-m');
        $totalPoints = $this->programRepo->getUserTotalPoints($userId);
        $monthlyPoints = $this->programRepo->getUserMonthlyPoints($userId, $monthRef);
        $completedMissions = $this->programRepo->countCompletedMissions($userId);

        foreach ($this->programRepo->listActiveBadges() as $badge) {
            $badgeId = (int)($badge['id'] ?? 0);
            if ($badgeId <= 0 || $this->programRepo->userHasBadge($userId, $badgeId)) {
                continue;
            }
            $criteria = [];
            if (!empty($badge['criteria_value_json'])) {
                $decoded = json_decode((string)$badge['criteria_value_json'], true);
                if (is_array($decoded)) {
                    $criteria = $decoded;
                }
            }
            $shouldAward = false;
            $criteriaKey = (string)($badge['criteria_key'] ?? '');
            if ($criteriaKey === 'total_points') {
                $shouldAward = $totalPoints >= (int)($criteria['min_points'] ?? 0);
            } elseif ($criteriaKey === 'monthly_points') {
                $shouldAward = $monthlyPoints >= (int)($criteria['min_points'] ?? 0);
            } elseif ($criteriaKey === 'monthly_missions_completed' || $criteriaKey === 'weekly_missions_completed') {
                $shouldAward = $completedMissions >= (int)($criteria['min_missions'] ?? 0);
            }
            if (!$shouldAward) {
                continue;
            }
            $this->programRepo->awardBadge($userId, $badgeId, null);
        }
    }
}
