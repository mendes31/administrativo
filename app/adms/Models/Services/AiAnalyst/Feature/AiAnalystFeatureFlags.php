<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Feature;

/**
 * Feature flags do novo núcleo do Tiarajuzinho.
 *
 * Todas as flags são fail-closed: valor ausente ou inválido equivale a false.
 */
final class AiAnalystFeatureFlags
{
    /** @param array<string, mixed> $values */
    public function __construct(private readonly array $values = [])
    {
    }

    public static function fromEnvironment(): self
    {
        return new self(array_merge($_SERVER, $_ENV));
    }

    public function enabled(): bool
    {
        return $this->flag('AI_ANALYST_ENABLED');
    }

    public function businessEnabled(): bool
    {
        return $this->enabled() && $this->flag('AI_ANALYST_BUSINESS_ENABLED');
    }

    public function technicalEnabled(): bool
    {
        return $this->enabled() && $this->flag('AI_ANALYST_TECHNICAL_ENABLED');
    }

    public function plannerEnabled(): bool
    {
        return $this->enabled() && $this->flag('AI_ANALYST_PLANNER_ENABLED');
    }

    public function toolExecutionEnabled(): bool
    {
        return $this->enabled() && $this->flag('AI_ANALYST_TOOL_EXECUTION_ENABLED');
    }

    public function realReportsEnabled(): bool
    {
        return $this->enabled() && $this->flag('AI_ANALYST_REAL_REPORTS_ENABLED');
    }

    /**
     * Seletor de modo no chat. Desligado por padrão: o servidor decide sozinho.
     * Só para diagnóstico local; o usuário final não deve escolher perfil.
     */
    public function modeSelectorEnabled(): bool
    {
        return $this->enabled() && $this->flag('AI_ANALYST_MODE_SELECTOR_ENABLED');
    }

    /**
     * Discovery de colunas via metadados HANA. Desligado por padrão.
     * Mesmo ligado, o stub atual não envia SQL ao SAP até homologação.
     */
    public function hanaMetadataEnabled(): bool
    {
        return $this->enabled() && $this->flag('AI_ANALYST_HANA_METADATA_ENABLED');
    }

    private function flag(string $name): bool
    {
        $value = $this->values[$name] ?? null;
        if (is_bool($value)) {
            return $value;
        }

        if (!is_scalar($value)) {
            return false;
        }

        return filter_var((string) $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true;
    }
}
