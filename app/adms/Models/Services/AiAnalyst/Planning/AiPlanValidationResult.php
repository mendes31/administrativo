<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Planning;

final class AiPlanValidationResult
{
    /** @param list<string> $errors */
    public function __construct(
        public readonly bool $valid,
        public readonly array $errors = []
    ) {
    }
}
