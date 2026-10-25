<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Catalog;

/**
 * Resolve colunas de uma tabela sem executar SQL de negócio.
 */
interface TableColumnsResolverInterface
{
    public function sourceLabel(): string;

    public function usesLiveHana(): bool;

    /**
     * @return array{
     *   ok: bool,
     *   message: string,
     *   columns: list<array{name: string, description: string, is_udf?: bool}>,
     *   source: string
     * }
     */
    public function resolve(string $tableName): array;
}
