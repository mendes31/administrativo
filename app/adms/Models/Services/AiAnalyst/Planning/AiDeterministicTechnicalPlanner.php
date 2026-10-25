<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Planning;

use App\adms\Models\Services\AiAnalyst\Tools\AiToolDefinition;

/**
 * Resolve no PHP pesquisas e descrições do catálogo técnico.
 * Sem SQL, sem HANA e sem inventar tabelas fora do catálogo.
 */
final class AiDeterministicTechnicalPlanner implements AiPlannerInterface
{
    public function __construct(private readonly AiPlannerInterface $fallback)
    {
    }

    public function plan(string $message, string $profile, array $tools): AiPlannerResult
    {
        if ($profile !== 'technical') {
            return $this->fallback->plan($message, $profile, $tools);
        }

        $normalized = trim($message);
        if ($normalized === '') {
            return $this->clarify('Descreva a tabela, campo ou conceito SAP/HANA/BEAS que deseja consultar.');
        }

        if ($this->looksLikeFreeSql($normalized)) {
            return $this->clarify(
                'Nesta etapa o modo técnico não gera nem executa SQL. Posso explicar tabelas e campos do catálogo curado (ex.: OITM, OCRD, OINV, BEAS_PERS).'
            );
        }

        $tableName = $this->extractTableName($normalized);
        if (
            $tableName !== null
            && $this->wantsFieldDescription($normalized)
            && $this->hasTool($tools, 'catalog.describe_table')
        ) {
            return new AiPlannerResult([
                'goal' => 'technical_describe_table',
                'steps' => [[
                    'step_id' => 's1',
                    'tool' => 'catalog.describe_table',
                    'arguments' => ['table_name' => $tableName],
                    'depends_on' => [],
                ]],
            ], 'local-catalog');
        }

        if ($this->hasTool($tools, 'catalog.technical_search')) {
            return new AiPlannerResult([
                'goal' => 'technical_catalog_search',
                'steps' => [[
                    'step_id' => 's1',
                    'tool' => 'catalog.technical_search',
                    'arguments' => ['query' => mb_substr($normalized, 0, 400)],
                    'depends_on' => [],
                ]],
            ], 'local-catalog');
        }

        return $this->fallback->plan($message, $profile, $tools);
    }

    private function clarify(string $message): AiPlannerResult
    {
        return new AiPlannerResult([
            'goal' => 'clarify',
            'steps' => [],
            'clarification' => $message,
        ], 'local-catalog');
    }

    /** @param list<AiToolDefinition> $tools */
    private function hasTool(array $tools, string $name): bool
    {
        foreach ($tools as $tool) {
            if ($tool->name === $name) {
                return true;
            }
        }

        return false;
    }

    private function wantsFieldDescription(string $message): bool
    {
        return preg_match(
            '/\b(?:campos?|colunas?|udf|user\s*field|userfield|estrutura|descreva|descrever|detalhe|detalhes)\b/iu',
            $message
        ) === 1;
    }

    private function extractTableName(string $message): ?string
    {
        if (preg_match('/\b(BEAS_[A-Z0-9_]{2,60}|[O][A-Z]{3}|[A-Z]{4}\d?)\b/', mb_strtoupper($message), $match) === 1) {
            return $match[1];
        }

        return null;
    }

    private function looksLikeFreeSql(string $message): bool
    {
        return preg_match(
            '/(?:SELECT\s+.+\s+FROM|WITH\s+.+\s+AS\s*\(|INSERT\s+INTO|UPDATE\s+.+\s+SET|DELETE\s+FROM)/is',
            $message
        ) === 1;
    }
}
