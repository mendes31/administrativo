<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Planning;

use App\adms\Models\Services\AiAnalyst\Tools\AiToolDefinition;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolRegistry;

/** Valida o plano do LLM como dado não confiável antes de qualquer execução. */
final class AiPlanValidator
{
    public function __construct(
        private readonly AiToolRegistry $registry,
        private readonly int $maxSteps = 5
    ) {
    }

    /** @param array<string, mixed> $plan */
    public function validate(array $plan, string $profile): AiPlanValidationResult
    {
        $errors = [];
        $steps = $plan['steps'] ?? null;
        if (!is_array($steps) || !array_is_list($steps)) {
            return new AiPlanValidationResult(false, ['steps deve ser uma lista.']);
        }
        if (count($steps) > $this->maxSteps) {
            $errors[] = 'O plano excede o limite de ' . $this->maxSteps . ' passos.';
        }
        if ($this->containsForbiddenContent($plan)) {
            $errors[] = 'O plano contém SQL, URL, classe PHP ou argumento técnico proibido.';
        }

        $ids = [];
        $positions = [];
        $dependencies = [];
        foreach ($steps as $index => $step) {
            if (!is_array($step)) {
                $errors[] = 'Passo ' . ($index + 1) . ' inválido.';
                continue;
            }

            $stepId = (string) ($step['step_id'] ?? '');
            if (!preg_match('/^[a-z][a-z0-9_-]{0,30}$/', $stepId)) {
                $errors[] = 'step_id inválido no passo ' . ($index + 1) . '.';
            } elseif (isset($ids[$stepId])) {
                $errors[] = 'step_id duplicado: ' . $stepId . '.';
            } else {
                $ids[$stepId] = true;
                $positions[$stepId] = $index;
            }

            $toolName = (string) ($step['tool'] ?? '');
            $tool = $this->registry->get($toolName);
            if ($tool === null) {
                $errors[] = 'Tool não registrada no passo ' . ($index + 1) . '.';
            } else {
                $definition = $tool->definition();
                if (!in_array($profile, $definition->profiles, true)) {
                    $errors[] = 'Tool não permitida para o perfil no passo ' . ($index + 1) . '.';
                }
                $arguments = $step['arguments'] ?? [];
                if (!is_array($arguments) || array_is_list($arguments) && $arguments !== []) {
                    $errors[] = 'Argumentos inválidos no passo ' . ($index + 1) . '.';
                } else {
                    $this->validateArguments($definition, $arguments, $index, $errors);
                }
            }

            $dependsOn = $step['depends_on'] ?? [];
            if (!is_array($dependsOn) || !array_is_list($dependsOn)) {
                $errors[] = 'depends_on inválido no passo ' . ($index + 1) . '.';
                $dependsOn = [];
            }
            $dependencies[$stepId] = array_values(array_filter($dependsOn, 'is_string'));
        }

        foreach ($dependencies as $stepId => $dependsOn) {
            foreach ($dependsOn as $dependency) {
                if (!isset($ids[$dependency])) {
                    $errors[] = 'Dependência inexistente em ' . $stepId . ': ' . $dependency . '.';
                }
                if (isset($positions[$dependency], $positions[$stepId]) && $positions[$dependency] >= $positions[$stepId]) {
                    $errors[] = 'A dependência deve apontar para um passo anterior em ' . $stepId . '.';
                }
                if ($dependency === $stepId) {
                    $errors[] = 'Autodependência no passo ' . $stepId . '.';
                }
            }
        }
        if ($this->hasCycle($dependencies)) {
            $errors[] = 'O plano contém ciclo de dependências.';
        }

        return new AiPlanValidationResult($errors === [], array_values(array_unique($errors)));
    }

    /**
     * @param array<string, mixed> $arguments
     * @param list<string> $errors
     */
    private function validateArguments(
        AiToolDefinition $definition,
        array $arguments,
        int $index,
        array &$errors
    ): void {
        foreach ($definition->requiredArguments as $name => $type) {
            if (!array_key_exists($name, $arguments)) {
                $errors[] = 'Argumento obrigatório ausente no passo ' . ($index + 1) . ': ' . $name . '.';
                continue;
            }
            if (!$this->matchesType($arguments[$name], $type)) {
                $errors[] = 'Tipo inválido para ' . $name . ' no passo ' . ($index + 1) . '.';
            }
        }

        $accepted = array_flip($definition->acceptedArguments());
        foreach ($arguments as $name => $value) {
            if (!is_string($name) || !isset($accepted[$name])) {
                $errors[] = 'Argumento não declarado no passo ' . ($index + 1) . '.';
                continue;
            }
            $type = $definition->requiredArguments[$name]
                ?? $definition->optionalArguments[$name]
                ?? '';
            if ($type !== '' && !$this->matchesType($value, $type)) {
                $errors[] = 'Tipo inválido para ' . $name . ' no passo ' . ($index + 1) . '.';
            }
        }
    }

    private function matchesType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            'array' => is_array($value) && array_is_list($value),
            'object' => is_array($value) && !array_is_list($value),
            default => false,
        };
    }

    private function containsForbiddenContent(mixed $value, ?string $key = null): bool
    {
        if ($key !== null && preg_match('/^(sql|statement|url|endpoint|class|service|php_class)$/i', $key)) {
            return true;
        }
        if (is_array($value)) {
            foreach ($value as $childKey => $child) {
                if ($this->containsForbiddenContent($child, is_string($childKey) ? $childKey : null)) {
                    return true;
                }
            }
            return false;
        }
        if (!is_string($value)) {
            return false;
        }

        return preg_match('~https?://~i', $value) === 1
            || preg_match('/(?:SELECT\s+.+\s+FROM|WITH\s+.+\s+AS\s*\(|INSERT\s+INTO|UPDATE\s+.+\s+SET|DELETE\s+FROM|DROP\s+(?:TABLE|VIEW)|ALTER\s+TABLE|TRUNCATE\s+TABLE)/is', $value) === 1
            || preg_match('/\\\\(?:App|Routes)\\\\[A-Za-z0-9_\\\\]+/', $value) === 1;
    }

    /** @param array<string, list<string>> $graph */
    private function hasCycle(array $graph): bool
    {
        $visiting = [];
        $visited = [];
        $visit = function (string $node) use (&$visit, &$visiting, &$visited, $graph): bool {
            if (isset($visiting[$node])) {
                return true;
            }
            if (isset($visited[$node])) {
                return false;
            }
            $visiting[$node] = true;
            foreach ($graph[$node] ?? [] as $dependency) {
                if (isset($graph[$dependency]) && $visit($dependency)) {
                    return true;
                }
            }
            unset($visiting[$node]);
            $visited[$node] = true;
            return false;
        };

        foreach (array_keys($graph) as $node) {
            if ($visit($node)) {
                return true;
            }
        }
        return false;
    }
}
