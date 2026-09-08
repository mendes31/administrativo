<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\SstCidsRepository;

final class SstCidsImportProfile extends AbstractSstCatalogImportProfile
{
    public function key(): string
    {
        return 'sst_cids';
    }

    public function label(): string
    {
        return 'SST — CIDs';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID (chave)',
            'codigo' => 'Código CID (chave)',
            'descricao' => 'Descrição',
            'categoria' => 'Categoria',
            'frequente' => 'Frequente (Sim/Não)',
            'status' => 'Status',
        ];
    }

    public function keyFields(): array
    {
        return ['codigo', 'id'];
    }

    public function defaultKeyField(): string
    {
        return 'codigo';
    }

    public function sampleRow(): array
    {
        return ['', 'S61.0', 'Ferimento de dedo(s) da mão sem lesão da unha', '', 'Não', 'Ativo'];
    }

    protected function findExisting(array $mapped, string $keyField): ?array
    {
        $raw = SstImportValues::v($mapped, $keyField) ?: SstImportValues::v($mapped, 'codigo');

        return (new SstImportLookup())->catalog('adms_sst_cids', $raw, true, 'descricao');
    }

    protected function buildCreatePayload(array $mapped): array
    {
        $codigo = SstImportValues::v($mapped, 'codigo');
        $descricao = SstImportValues::v($mapped, 'descricao');
        if ($codigo === '' || $descricao === '') {
            throw new \RuntimeException('Para criar CID, informe codigo e descricao.');
        }

        return [
            'codigo' => $codigo,
            'descricao' => $descricao,
            'categoria' => SstImportValues::v($mapped, 'categoria') ?: null,
            'frequente' => SstImportValues::bool01(SstImportValues::v($mapped, 'frequente')) ?? 0,
            'status' => SstImportValues::status(SstImportValues::v($mapped, 'status')) ?? 'Ativo',
        ];
    }

    protected function buildUpdatePayload(array $mapped, array $existing, string $emptyPolicy): array
    {
        $payload = [
            'codigo' => SstImportValues::v($mapped, 'codigo') ?: ($existing['codigo'] ?? ''),
            'descricao' => SstImportValues::v($mapped, 'descricao'),
            'categoria' => SstImportValues::v($mapped, 'categoria'),
            'frequente' => SstImportValues::bool01(SstImportValues::v($mapped, 'frequente')),
            'status' => SstImportValues::status(SstImportValues::v($mapped, 'status')),
        ];
        $merged = SstImportValues::mergeSkipEmpty($payload, $existing, $emptyPolicy, [
            'codigo', 'descricao', 'categoria', 'frequente', 'status',
        ]);
        $merged['frequente'] = !empty($merged['frequente']);

        return $merged;
    }

    protected function createRow(array $payload): int|false
    {
        return (new SstCidsRepository())->create($payload);
    }

    protected function updateRow(int $id, array $payload): bool
    {
        return (new SstCidsRepository())->update($id, $payload);
    }
}
