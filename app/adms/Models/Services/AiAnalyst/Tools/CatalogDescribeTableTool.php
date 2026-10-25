<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Tools;

use App\adms\Models\Services\AiAnalyst\Auth\AiActorContext;
use App\adms\Models\Services\AiAnalyst\Catalog\AiTechnicalCatalogService;
use App\adms\Models\Services\AiAnalyst\Catalog\TableColumnsResolverInterface;
use App\adms\Models\Services\AiAnalyst\Policy\PolicyDecision;

/** Descreve uma tabela e seus campos conhecidos. Sem SQL de negócio. */
final class CatalogDescribeTableTool implements AiToolInterface
{
    public function __construct(
        private readonly AiTechnicalCatalogService $catalog,
        private readonly TableColumnsResolverInterface $columns
    ) {
    }

    public function definition(): AiToolDefinition
    {
        return new AiToolDefinition(
            'catalog.describe_table',
            'Descreve uma tabela SAP B1/BEAS e lista campos conhecidos (incluindo UDF quando constarem no catálogo).',
            ['technical'],
            ['table_name' => 'string']
        );
    }

    public function authorize(AiActorContext $actor, string $profile, array $arguments): PolicyDecision
    {
        if (!in_array($profile, $this->definition()->profiles, true)) {
            return PolicyDecision::deny('Perfil sem permissão para descrever tabelas.');
        }
        if ($actor->userId < 1 || !$actor->fullSystemAccess) {
            return PolicyDecision::deny('Descrição de tabelas disponível apenas com acesso total ao sistema.');
        }

        $table = strtoupper(trim((string) ($arguments['table_name'] ?? '')));
        if ($table === '' || !preg_match('/^[A-Z][A-Z0-9_]{1,60}$/', $table)) {
            return PolicyDecision::deny('Nome de tabela inválido.');
        }

        return PolicyDecision::allow();
    }

    public function execute(AiToolExecutionContext $context, array $arguments): AiToolResult
    {
        $table = strtoupper(trim((string) $arguments['table_name']));
        $entry = $this->catalog->findByTableName($table);
        if ($entry === null) {
            return new AiToolResult(false, 'Não encontrei a tabela ' . $table . ' no catálogo técnico local.');
        }

        $resolved = $this->columns->resolve($table);
        $columns = $resolved['columns'];
        $rows = [];
        foreach (array_slice($columns, 0, 40) as $column) {
            $rows[] = [
                'Campo' => $column['name'],
                'Descrição' => $column['description'] !== '' ? $column['description'] : '—',
                'UDF' => !empty($column['is_udf']) ? 'sim' : 'não',
            ];
        }

        $title = (string) ($entry['title'] ?? $table);
        $summary = trim((string) ($entry['summary'] ?? ''));
        $objectType = trim((string) ($entry['sap_object_type'] ?? ''));
        $related = is_array($entry['related'] ?? null) ? $entry['related'] : [];

        $message = $table . ' — ' . $title . '.';
        if ($summary !== '') {
            $message .= ' ' . rtrim($summary, '.') . '.';
        }
        if ($objectType !== '') {
            $message .= ' Object Type (ObjType): ' . $objectType . '.';
        }
        if ($columns === []) {
            $message .= ' ' . $resolved['message'];
        } else {
            $udfCount = count(array_filter($columns, static fn (array $c): bool => !empty($c['is_udf'])));
            $message .= ' Campos conhecidos: ' . count($columns)
                . ($udfCount > 0 ? ' (incluindo ' . $udfCount . ' UDF/userfield).' : '.');
            if (count($columns) > 40) {
                $message .= ' Exibindo os 40 primeiros.';
            }
        }
        if ($related !== []) {
            $message .= ' Relacionados: ' . implode('; ', $related) . '.';
        }
        $message .= ' Fonte: ' . $resolved['source'] . '; não foi executado SQL de negócio'
            . ($this->columns->usesLiveHana() ? '.' : '; HANA não foi consultado.');

        return new AiToolResult(true, $message, [
            'name' => $title,
            'source_type' => 'technical_catalog',
            'table_name' => $table,
            'columns_source' => $resolved['source'],
            'rows' => $rows,
            'evidence' => [[
                'label' => $table,
                'source_type' => 'technical_catalog',
                'source_id' => (string) ($entry['id'] ?? $table),
            ]],
        ]);
    }
}
