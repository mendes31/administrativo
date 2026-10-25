<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Planning;

use App\adms\Models\Services\AiAnalyst\Tools\AiToolDefinition;

interface AiPlannerInterface
{
    /** @param list<AiToolDefinition> $tools */
    public function plan(string $message, string $profile, array $tools): AiPlannerResult;
}
