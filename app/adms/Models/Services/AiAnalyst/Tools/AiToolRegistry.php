<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Tools;

/** Registro fechado: somente instâncias adicionadas pelo servidor podem ser executadas. */
final class AiToolRegistry
{
    /** @var array<string, AiToolInterface> */
    private array $tools = [];

    public function register(AiToolInterface $tool): void
    {
        $name = $tool->definition()->name;
        if (isset($this->tools[$name])) {
            throw new \LogicException('Tool duplicada: ' . $name);
        }
        $this->tools[$name] = $tool;
    }

    public function get(string $name): ?AiToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    /** @return list<AiToolDefinition> */
    public function definitionsForProfile(string $profile): array
    {
        $definitions = [];
        foreach ($this->tools as $tool) {
            $definition = $tool->definition();
            if (in_array($profile, $definition->profiles, true)) {
                $definitions[] = $definition;
            }
        }

        return $definitions;
    }
}
