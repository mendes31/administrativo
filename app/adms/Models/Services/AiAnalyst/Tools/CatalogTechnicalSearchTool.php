<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Tools;

use App\adms\Models\Services\AiAnalyst\Auth\AiActorContext;
use App\adms\Models\Services\AiAnalyst\Catalog\AiTechnicalCatalogService;
use App\adms\Models\Services\AiAnalyst\Policy\PolicyDecision;

/** Pesquisa lexical no catálogo curado. Não consulta HANA. */
final class CatalogTechnicalSearchTool implements AiToolInterface
{
    public function __construct(private readonly AiTechnicalCatalogService $catalog)
    {
    }

    public function definition(): AiToolDefinition
    {
        return new AiToolDefinition(
            'catalog.technical_search',
            'Pesquisa tabelas, campos e conceitos SAP B1/HANA/BEAS no catálogo técnico curado.',
            ['technical'],
            ['query' => 'string']
        );
    }

    public function authorize(AiActorContext $actor, string $profile, array $arguments): PolicyDecision
    {
        if (!in_array($profile, $this->definition()->profiles, true)) {
            return PolicyDecision::deny('Perfil sem permissão para o catálogo técnico.');
        }
        if ($actor->userId < 1 || !$actor->fullSystemAccess) {
            return PolicyDecision::deny('Catálogo técnico disponível apenas com acesso total ao sistema.');
        }

        $query = trim((string) ($arguments['query'] ?? ''));
        if ($query === '' || mb_strlen($query) > 400) {
            return PolicyDecision::deny('Consulta do catálogo inválida.');
        }

        return PolicyDecision::allow();
    }

    public function execute(AiToolExecutionContext $context, array $arguments): AiToolResult
    {
        $query = trim((string) $arguments['query']);
        $hits = $this->catalog->search($query, 5);
        if ($hits === []) {
            return new AiToolResult(
                true,
                'Não encontrei essa tabela ou conceito no catálogo técnico. '
                . 'Pode perguntar em português (ex.: «tabela de itens», «tabela de vendas», «object type 4»). '
                . 'Ainda não consulto o HANA para listar campos em tempo real.',
                [
                    'source_type' => 'technical_catalog',
                    'query' => $query,
                    'rows' => [],
                ]
            );
        }

        $primary = $hits[0];
        $rows = [];
        foreach ($hits as $hit) {
            $rows[] = [
                'Tabela' => (string) ($hit['table_name'] ?? ''),
                'Título' => (string) ($hit['title'] ?? ''),
                'ObjType' => (string) ($hit['sap_object_type'] ?? ''),
                'Módulo' => (string) ($hit['module'] ?? ''),
            ];
        }

        $table = (string) ($primary['table_name'] ?? '');
        $title = (string) ($primary['title'] ?? '');
        $summary = trim((string) ($primary['summary'] ?? ''));
        $objectType = trim((string) ($primary['sap_object_type'] ?? ''));
        $fields = is_array($primary['key_fields'] ?? null) ? $primary['key_fields'] : [];
        $related = is_array($primary['related'] ?? null) ? $primary['related'] : [];

        $message = $table . ' — ' . $title . '.';
        if ($summary !== '') {
            $message .= ' ' . $summary;
            if (!str_ends_with($summary, '.')) {
                $message .= '.';
            }
        }
        if ($objectType !== '' && !str_contains(mb_strtolower($message), 'object type')) {
            $message .= ' Object Type (ObjType): ' . $objectType . '.';
        }
        if ($fields !== []) {
            $message .= ' Chave/campos principais: ' . implode(', ', $fields) . '.';
        }
        if ($related !== []) {
            $message .= ' Relacionados: ' . implode('; ', $related) . '.';
        }
        if (count($hits) > 1) {
            $others = [];
            foreach (array_slice($hits, 1) as $hit) {
                $others[] = (string) ($hit['table_name'] ?? '') . ' (' . (string) ($hit['title'] ?? '') . ')';
            }
            $message .= ' Outras coincidências: ' . implode('; ', $others) . '.';
        }
        $message .= ' Fonte: planilha homologada SAP B1 9.3 + nomes em português locais; não foi consultado o HANA.';

        return new AiToolResult(true, $message, [
            'name' => 'Catálogo técnico SAP',
            'source_type' => 'technical_catalog',
            'query' => $query,
            'rows' => $rows,
            'evidence' => [[
                'label' => $table !== '' ? $table : $title,
                'source_type' => 'technical_catalog',
                'source_id' => (string) ($primary['id'] ?? ''),
            ]],
        ]);
    }
}
