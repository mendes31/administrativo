<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\SstEpiNecessidadeRepository;

final class SstEpiNecessidadeImportProfile extends AbstractSstLinkImportProfile
{
    public function key(): string
    {
        return 'sst_epi_necessidade';
    }

    public function label(): string
    {
        return 'SST — Necessidade de EPI';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID do vínculo',
            'epi' => 'EPI (nome ou ID)',
            'position' => 'Cargo (nome ou ID, opcional)',
            'department' => 'Departamento (nome ou ID, opcional)',
            'risco' => 'Risco (código, nome ou ID, opcional)',
            'obrigatorio' => 'Obrigatório (Sim/Não)',
            'observacoes' => 'Observações',
        ];
    }

    public function sampleRow(): array
    {
        return ['', 'Capacete de segurança', 'Gerente de Suprimentos', 'Suprimentos', '', 'Sim', ''];
    }

    protected function resolve(array $mapped, string $emptyPolicy): array
    {
        $epi = $this->requireCatalog('adms_sst_epis', SstImportValues::v($mapped, 'epi'), false, 'EPI');
        $pos = $this->optionalPosition(SstImportValues::v($mapped, 'position'));
        $dep = $this->optionalDepartment(SstImportValues::v($mapped, 'department'));
        $riscoId = $this->optionalRiscoId(SstImportValues::v($mapped, 'risco'));
        $existing = $this->existingByIdOrLink('adms_sst_epi_necessidade', $mapped, [
            'adms_sst_epi_id' => (int) $epi['id'],
            'adms_position_id' => $pos,
            'adms_department_id' => $dep,
            'adms_sst_risco_id' => $riscoId,
        ]);
        $payload = [
            'adms_sst_epi_id' => (int) $epi['id'],
            'adms_position_id' => $pos,
            'adms_department_id' => $dep,
            'adms_sst_risco_id' => $riscoId,
            'obrigatorio' => SstImportValues::bool01(SstImportValues::v($mapped, 'obrigatorio')) ?? ($existing['obrigatorio'] ?? 1),
            'observacoes' => SstImportValues::v($mapped, 'observacoes') ?: ($emptyPolicy === 'skip' ? ($existing['observacoes'] ?? null) : null),
        ];

        return [
            'existing' => $existing,
            'payload' => $payload,
            'key' => $epi['nome'] . ' / ' . (SstImportValues::v($mapped, 'position') ?: '-') . ' / ' . (SstImportValues::v($mapped, 'department') ?: '-'),
        ];
    }

    protected function createRow(array $payload): int|false
    {
        return (new SstEpiNecessidadeRepository())->create($payload);
    }

    protected function updateRow(int $id, array $payload): bool
    {
        return (new SstEpiNecessidadeRepository())->update($id, $payload);
    }
}
