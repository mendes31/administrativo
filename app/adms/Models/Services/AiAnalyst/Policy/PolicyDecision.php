<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Policy;

final class PolicyDecision
{
    private function __construct(
        public readonly bool $allowed,
        public readonly string $reason,
        public readonly bool $requiresRedaction = false
    ) {
    }

    public static function allow(string $reason = ''): self
    {
        return new self(true, $reason);
    }

    public static function allowWithRedaction(string $reason): self
    {
        return new self(true, $reason, true);
    }

    public static function deny(string $reason): self
    {
        return new self(false, $reason);
    }
}
