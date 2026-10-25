<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Tools;

use App\adms\Models\Services\AiAnalyst\Auth\AiActorContext;

final class AiToolExecutionContext
{
    /** @var array<string, AiToolResult> */
    private array $stepResults = [];

    public function __construct(
        public readonly string $correlationId,
        public readonly string $profile,
        public readonly AiActorContext $actor
    ) {
    }

    public function remember(string $stepId, AiToolResult $result): void
    {
        if ($stepId !== '') {
            $this->stepResults[$stepId] = $result;
        }
    }

    public function resultOf(string $stepId): ?AiToolResult
    {
        return $this->stepResults[$stepId] ?? null;
    }
}
