<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Tools;

use App\adms\Models\Services\AiAnalyst\Auth\AiActorContext;
use App\adms\Models\Services\AiAnalyst\Policy\PolicyDecision;

interface AiToolInterface
{
    public function definition(): AiToolDefinition;

    /** @param array<string, mixed> $arguments */
    public function authorize(AiActorContext $actor, string $profile, array $arguments): PolicyDecision;

    /** @param array<string, mixed> $arguments */
    public function execute(AiToolExecutionContext $context, array $arguments): AiToolResult;
}
