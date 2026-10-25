<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Planning;

final class AiPlannerResult
{
    /** @param array<string, mixed>|null $plan */
    public function __construct(
        public readonly ?array $plan,
        public readonly string $provider,
        public readonly ?string $error = null
    ) {
    }
}
