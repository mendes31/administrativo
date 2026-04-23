<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\GamificationLedgerRepository;
use App\adms\Models\Repository\GamificationTimelineRulesRepository;

class GamificationAwardService
{
    public function __construct(
        private readonly GamificationTimelineRulesRepository $rulesRepo = new GamificationTimelineRulesRepository(),
        private readonly GamificationLedgerRepository $ledgerRepo = new GamificationLedgerRepository()
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

        return $ok ? $points : 0;
    }
}
