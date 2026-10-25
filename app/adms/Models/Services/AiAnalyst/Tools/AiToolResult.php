<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Tools;

final class AiToolResult
{
    /** @param array<string, mixed> $data @param list<string> $warnings */
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly array $data = [],
        public readonly array $warnings = []
    ) {
    }
}
