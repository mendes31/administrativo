<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\SstEpisRepository;

final class SstEpisImportProfile extends AbstractSstCatalogImportProfile
{
    public function key(): string
    {
        return 'sst_epis';
    }

    public function label(): string
    {
        return 'SST — EPIs';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID (chave)',
            'nome' => 'Nome (chave)',
            'descricao' => 'Descrição',
            'categoria' => 'Categoria',
            'estoque_minimo' => 'Estoque mínimo',
            'periodicidade_troca_dias' => 'Troca (dias)',
            'status' => 'Status',
        ];
    }

    public function keyFields(): array
    {
        return ['nome', 'id'];
    }

    public function defaultKeyField(): string
    {
        return 'nome';
    }

    public function sampleRow(): array
    {
        return ['', 'Capacete de segurança', 'Proteção da cabeça', 'Classe B', '10', '365', 'Ativo'];
    }

    protected function findExisting(array $mapped, string $keyField): ?array
    {
        return (new SstImportLookup())->catalog(
            'adms_sst_epis',
            SstImportValues::v($mapped, $keyField) ?: SstImportValues::v($mapped, 'nome'),
            false
        );
    }

    protected function buildCreatePayload(array $mapped): array
    {
        $nome = SstImportValues::v($mapped, 'nome');
        if ($nome === '') {
            throw new \RuntimeException('Para criar EPI, informe o nome.');
        }

        return [
            'nome' => $nome,
            'descricao' => SstImportValues::v($mapped, 'descricao') ?: null,
            'categoria' => SstImportValues::v($mapped, 'categoria') ?: null,
            'estoque_minimo' => SstImportValues::v($mapped, 'estoque_minimo') !== '' ? (int) SstImportValues::v($mapped, 'estoque_minimo') : 0,
            'periodicidade_troca_dias' => SstImportValues::v($mapped, 'periodicidade_troca_dias') !== '' ? (int) SstImportValues::v($mapped, 'periodicidade_troca_dias') : null,
            'status' => SstImportValues::status(SstImportValues::v($mapped, 'status')) ?? 'Ativo',
        ];
    }

    protected function buildUpdatePayload(array $mapped, array $existing, string $emptyPolicy): array
    {
        $payload = [];
        foreach (['nome', 'descricao', 'categoria', 'estoque_minimo', 'periodicidade_troca_dias', 'status'] as $f) {
            if (!SstImportValues::has($mapped, $f)) {
                continue;
            }
            $payload[$f] = SstImportValues::v($mapped, $f);
        }
        if (isset($payload['status'])) {
            $payload['status'] = SstImportValues::status((string) $payload['status']) ?? $existing['status'] ?? 'Ativo';
        }

        return SstImportValues::mergeSkipEmpty($payload, $existing, $emptyPolicy, [
            'nome', 'descricao', 'categoria', 'estoque_minimo', 'periodicidade_troca_dias', 'status',
        ]);
    }

    protected function createRow(array $payload): int|false
    {
        return (new SstEpisRepository())->create($payload);
    }

    protected function updateRow(int $id, array $payload): bool
    {
        return (new SstEpisRepository())->update($id, $payload);
    }
}
