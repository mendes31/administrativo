<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Catalog;

/** Colunas a partir do catálogo local (planilha/PDF/enriquecimento). */
final class LocalCatalogColumnsResolver implements TableColumnsResolverInterface
{
    public function __construct(private readonly AiTechnicalCatalogService $catalog)
    {
    }

    public function sourceLabel(): string
    {
        return 'local-catalog';
    }

    public function usesLiveHana(): bool
    {
        return false;
    }

    public function resolve(string $tableName): array
    {
        $entry = $this->catalog->findByTableName($tableName);
        if ($entry === null) {
            return [
                'ok' => false,
                'message' => 'Tabela não encontrada no catálogo local.',
                'columns' => [],
                'source' => $this->sourceLabel(),
            ];
        }

        $columns = [];
        $seen = [];
        foreach ($entry['columns'] ?? [] as $column) {
            if (!is_array($column)) {
                continue;
            }
            $name = trim((string) ($column['name'] ?? ''));
            if ($name === '' || isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;
            $columns[] = [
                'name' => $name,
                'description' => trim((string) ($column['description'] ?? '')),
                'is_udf' => str_starts_with(strtoupper($name), 'U_') || preg_match('/^UDF\d+$/i', $name) === 1,
            ];
        }

        foreach ($entry['key_fields'] ?? [] as $field) {
            $name = trim((string) $field);
            if ($name === '' || isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;
            $columns[] = [
                'name' => $name,
                'description' => 'Campo/chave documentado no catálogo.',
                'is_udf' => str_starts_with(strtoupper($name), 'U_'),
            ];
        }

        if ($columns === []) {
            return [
                'ok' => true,
                'message' => 'A tabela existe no catálogo, mas ainda não há lista detalhada de colunas localmente. '
                    . 'O discovery HANA (metadados) permanece desligado nesta etapa.',
                'columns' => [],
                'source' => $this->sourceLabel(),
            ];
        }

        return [
            'ok' => true,
            'message' => 'Colunas do catálogo local.',
            'columns' => $columns,
            'source' => $this->sourceLabel(),
        ];
    }
}
