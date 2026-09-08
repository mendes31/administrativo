<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\SstGheRepository;

final class SstGheImportProfile extends AbstractSstCatalogImportProfile
{
    public function key(): string
    {
        return 'sst_ghe';
    }

    public function label(): string
    {
        return 'SST — GHE / ambientes';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID (chave)',
            'codigo' => 'Código (chave)',
            'nome' => 'Nome (chave)',
            'descricao' => 'Descrição',
            'ambiente_local' => 'Local do ambiente',
            'department' => 'Departamento (nome ou ID)',
            'status' => 'Status',
        ];
    }

    public function keyFields(): array
    {
        return ['codigo', 'nome', 'id'];
    }

    public function defaultKeyField(): string
    {
        return 'codigo';
    }

    public function sampleRow(): array
    {
        return ['', 'GHE-SUP', 'Almoxarifado', '', 'Pátio', 'Suprimentos', 'Ativo'];
    }

    protected function findExisting(array $mapped, string $keyField): ?array
    {
        $raw = SstImportValues::v($mapped, $keyField) ?: SstImportValues::v($mapped, 'codigo') ?: SstImportValues::v($mapped, 'nome');

        return (new SstImportLookup())->catalog('adms_sst_ghe', $raw, true);
    }

    protected function buildCreatePayload(array $mapped): array
    {
        $nome = SstImportValues::v($mapped, 'nome');
        if ($nome === '') {
            throw new \RuntimeException('Para criar GHE, informe o nome.');
        }
        $dept = SstImportValues::v($mapped, 'department');
        $deptId = null;
        if ($dept !== '') {
            $deptId = (new SstImportLookup())->department($dept);
            if ($deptId === null) {
                throw new \RuntimeException('Departamento não encontrado: ' . $dept);
            }
        }

        return [
            'codigo' => SstImportValues::v($mapped, 'codigo') ?: null,
            'nome' => $nome,
            'descricao' => SstImportValues::v($mapped, 'descricao') ?: null,
            'ambiente_local' => SstImportValues::v($mapped, 'ambiente_local') ?: null,
            'adms_department_id' => $deptId,
            'status' => SstImportValues::status(SstImportValues::v($mapped, 'status')) ?? 'Ativo',
        ];
    }

    protected function buildUpdatePayload(array $mapped, array $existing, string $emptyPolicy): array
    {
        $payload = $this->buildCreatePayload(array_merge(['nome' => (string) ($existing['nome'] ?? '')], $mapped));
        if ($emptyPolicy === 'skip' && SstImportValues::v($mapped, 'department') === '') {
            $payload['adms_department_id'] = $existing['adms_department_id'] ?? null;
        }

        return SstImportValues::mergeSkipEmpty($payload, $existing, $emptyPolicy, [
            'codigo', 'nome', 'descricao', 'ambiente_local', 'status',
        ]);
    }

    protected function createRow(array $payload): int|false
    {
        return (new SstGheRepository())->create($payload);
    }

    protected function updateRow(int $id, array $payload): bool
    {
        return (new SstGheRepository())->update($id, $payload);
    }
}
