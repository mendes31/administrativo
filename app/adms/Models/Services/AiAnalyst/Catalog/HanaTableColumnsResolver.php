<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Catalog;

/**
 * Stub do discovery HANA.
 * Não consulta SAP nesta fase; existe para plugar metadados read-only no futuro.
 */
final class HanaTableColumnsResolver implements TableColumnsResolverInterface
{
    public function __construct(private readonly TableColumnsResolverInterface $fallback)
    {
    }

    public function sourceLabel(): string
    {
        return 'hana-metadata-stub';
    }

    public function usesLiveHana(): bool
    {
        return true;
    }

    public function resolve(string $tableName): array
    {
        // Fail-closed: mesmo com a flag ligada, ainda não há cliente de metadados homologado.
        $fallback = $this->fallback->resolve($tableName);
        $fallback['message'] = 'Discovery HANA ainda não está homologado. '
            . ($fallback['message'] !== '' ? $fallback['message'] . ' ' : '')
            . 'Nenhuma consulta foi enviada ao HANA.';
        $fallback['source'] = $this->sourceLabel();

        return $fallback;
    }
}
