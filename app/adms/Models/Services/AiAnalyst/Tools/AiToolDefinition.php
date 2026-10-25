<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Tools;

final class AiToolDefinition
{
    /**
     * @param list<string> $profiles
     * @param array<string, string> $requiredArguments argumento => tipo
     * @param array<string, string> $optionalArguments argumento => tipo
     */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly array $profiles,
        public readonly array $requiredArguments = [],
        public readonly array $optionalArguments = []
    ) {
        if (!preg_match('/^[a-z][a-z0-9_.-]{2,80}$/', $name)) {
            throw new \InvalidArgumentException('Nome de tool inválido.');
        }
    }

    /** @return list<string> */
    public function acceptedArguments(): array
    {
        return array_values(array_unique(array_merge(
            array_keys($this->requiredArguments),
            array_keys($this->optionalArguments)
        )));
    }

    /** @return array<string, mixed> */
    public function toPlannerSchema(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'profiles' => $this->profiles,
            'required_arguments' => $this->requiredArguments,
            'optional_arguments' => $this->optionalArguments,
        ];
    }
}
