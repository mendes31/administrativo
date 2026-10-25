<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Auth;

final class AiActorContext
{
    /**
     * @param list<string> $allowedTools
     * @param array<string, mixed> $dataScopes
     */
    public function __construct(
        public readonly int $userId,
        public readonly bool $fullSystemAccess,
        public readonly array $allowedTools,
        public readonly array $dataScopes = []
    ) {
    }

    public function canUseLegacyTool(string $tool): bool
    {
        return in_array($tool, $this->allowedTools, true);
    }
}
