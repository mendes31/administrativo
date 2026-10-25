<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Routing;

final class AiModeDecision
{
    private function __construct(
        public readonly bool $allowed,
        public readonly string $effectiveMode,
        public readonly string $reason
    ) {
    }

    public static function allow(string $mode, string $reason = ''): self
    {
        return new self(true, $mode, $reason);
    }

    public static function deny(string $reason): self
    {
        return new self(false, 'legacy', $reason);
    }
}
