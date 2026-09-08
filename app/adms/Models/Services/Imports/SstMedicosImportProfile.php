<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\SstMedicosRepository;

final class SstMedicosImportProfile extends AbstractSstCatalogImportProfile
{
    public function key(): string
    {
        return 'sst_medicos';
    }

    public function label(): string
    {
        return 'SST — Médicos';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID (chave)',
            'nome' => 'Nome (chave)',
            'crm' => 'CRM (chave)',
            'crm_uf' => 'UF do CRM',
            'clinica' => 'Clínica',
            'telefone' => 'Telefone',
            'email' => 'E-mail',
            'status' => 'Status',
        ];
    }

    public function keyFields(): array
    {
        return ['crm', 'nome', 'id'];
    }

    public function defaultKeyField(): string
    {
        return 'crm';
    }

    public function sampleRow(): array
    {
        return ['', 'Dr. João Silva', '12345', 'RS', 'Clínica Ocupacional', '', 'joao@clinica.com', 'Ativo'];
    }

    protected function findExisting(array $mapped, string $keyField): ?array
    {
        $raw = SstImportValues::v($mapped, $keyField)
            ?: SstImportValues::v($mapped, 'crm')
            ?: SstImportValues::v($mapped, 'nome');

        return (new SstImportLookup())->medico($raw);
    }

    protected function buildCreatePayload(array $mapped): array
    {
        $nome = SstImportValues::v($mapped, 'nome');
        if ($nome === '') {
            throw new \RuntimeException('Para criar médico, informe o nome.');
        }

        return [
            'nome' => $nome,
            'crm' => SstImportValues::v($mapped, 'crm') ?: null,
            'crm_uf' => strtoupper(SstImportValues::v($mapped, 'crm_uf')) ?: null,
            'clinica' => SstImportValues::v($mapped, 'clinica') ?: null,
            'telefone' => SstImportValues::v($mapped, 'telefone') ?: null,
            'email' => SstImportValues::v($mapped, 'email') ?: null,
            'status' => SstImportValues::status(SstImportValues::v($mapped, 'status')) ?? 'Ativo',
        ];
    }

    protected function buildUpdatePayload(array $mapped, array $existing, string $emptyPolicy): array
    {
        $payload = [
            'nome' => SstImportValues::v($mapped, 'nome') ?: ($existing['nome'] ?? ''),
            'crm' => SstImportValues::v($mapped, 'crm'),
            'crm_uf' => strtoupper(SstImportValues::v($mapped, 'crm_uf')),
            'clinica' => SstImportValues::v($mapped, 'clinica'),
            'telefone' => SstImportValues::v($mapped, 'telefone'),
            'email' => SstImportValues::v($mapped, 'email'),
            'status' => SstImportValues::status(SstImportValues::v($mapped, 'status')),
        ];

        return SstImportValues::mergeSkipEmpty($payload, $existing, $emptyPolicy, [
            'nome', 'crm', 'crm_uf', 'clinica', 'telefone', 'email', 'status',
        ]);
    }

    protected function createRow(array $payload): int|false
    {
        return (new SstMedicosRepository())->create($payload);
    }

    protected function updateRow(int $id, array $payload): bool
    {
        return (new SstMedicosRepository())->update($id, $payload);
    }
}
