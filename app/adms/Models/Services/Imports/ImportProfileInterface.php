<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

/**
 * Perfil declarado de importação (não espelha colunas cruas da tabela).
 */
interface ImportProfileInterface
{
    public function key(): string;

    public function label(): string;

    public function permission(): string;

    /**
     * @return array<string, string> campo => rótulo
     */
    public function fields(): array;

    /** @return list<string> */
    public function keyFields(): array;

    public function defaultKeyField(): string;

    /**
     * @param array<string, string> $mapped valores já associados ao campo declarado
     * @return array{action: string, message: string, key: string}
     */
    public function processRow(array $mapped, string $operation, string $emptyPolicy, bool $dryRun): array;
}
