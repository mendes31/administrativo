<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Planning;

/** Adia a criação do planejador seguinte até existir uma pergunta fora do caminho local. */
final class AiLazyPlanner implements AiPlannerInterface
{
    private ?AiPlannerInterface $planner = null;

    public function __construct(private readonly \Closure $factory)
    {
    }

    public function plan(string $message, string $profile, array $tools): AiPlannerResult
    {
        if ($this->planner === null) {
            $planner = ($this->factory)();
            if (!$planner instanceof AiPlannerInterface) {
                return new AiPlannerResult(null, 'local-policy', 'Planejador de fallback inválido.');
            }
            $this->planner = $planner;
        }

        return $this->planner->plan($message, $profile, $tools);
    }
}
