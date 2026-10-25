<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Planning;

use App\adms\Models\Services\AiAnalyst\Tools\AiToolDefinition;

/**
 * No perfil business, o LLM interpreta a pergunta e monta o plano.
 * O caminho determinístico só entra se o LLM não estiver configurado ou falhar.
 * A execução continua restrita às tools validadas (sem SQL livre).
 */
final class AiPreferLlmPlanner implements AiPlannerInterface
{
    /**
     * @param \Closure(): bool $llmConfigured
     */
    public function __construct(
        private readonly AiPlannerInterface $llm,
        private readonly AiPlannerInterface $fallback,
        private readonly \Closure $llmConfigured
    ) {
    }

    /** @param list<AiToolDefinition> $tools */
    public function plan(string $message, string $profile, array $tools): AiPlannerResult
    {
        if ($profile !== 'business') {
            return $this->fallback->plan($message, $profile, $tools);
        }

        // Série anual («12 meses de 2025») é determinística: o LLM costuma omitir year
        // e a execução cai no relatório completo (meses de outros anos).
        $deterministic = $this->fallback->plan($message, $profile, $tools);
        if (($deterministic->plan['goal'] ?? null) === 'year_series') {
            return $deterministic;
        }

        if (($this->llmConfigured)()) {
            $planned = $this->llm->plan($message, $profile, $tools);
            if ($planned->plan !== null) {
                return $planned;
            }
        }

        return $deterministic;
    }
}
