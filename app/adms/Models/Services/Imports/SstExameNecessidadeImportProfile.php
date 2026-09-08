<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\SstExameNecessidadeRepository;

final class SstExameNecessidadeImportProfile extends AbstractSstLinkImportProfile
{
    public function key(): string
    {
        return 'sst_exame_necessidade';
    }

    public function label(): string
    {
        return 'SST — Necessidade de exame';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID do vínculo',
            'exame' => 'Exame (código, nome ou ID)',
            'position' => 'Cargo (nome ou ID, opcional)',
            'department' => 'Departamento (nome ou ID, opcional)',
            'risco' => 'Risco (código, nome ou ID, opcional)',
            'categoria_aso' => 'Categoria ASO (Admissional/Periódico/…)',
            'periodicidade_meses' => 'Periodicidade (meses)',
            'obrigatorio' => 'Obrigatório (Sim/Não)',
            'observacoes' => 'Observações',
        ];
    }

    public function sampleRow(): array
    {
        return ['', 'EX0001', 'Gerente de Suprimentos', 'Suprimentos', '', 'Periódico', '12', 'Sim', ''];
    }

    protected function resolve(array $mapped, string $emptyPolicy): array
    {
        $exame = $this->requireCatalog('adms_sst_exames', SstImportValues::v($mapped, 'exame'), true, 'Exame');
        $pos = $this->optionalPosition(SstImportValues::v($mapped, 'position'));
        $dep = $this->optionalDepartment(SstImportValues::v($mapped, 'department'));
        $riscoId = $this->optionalRiscoId(SstImportValues::v($mapped, 'risco'));
        $existing = $this->existingByIdOrLink('adms_sst_exame_necessidade', $mapped, [
            'adms_sst_exame_id' => (int) $exame['id'],
            'adms_position_id' => $pos,
            'adms_department_id' => $dep,
            'adms_sst_risco_id' => $riscoId,
        ]);
        $payload = [
            'adms_sst_exame_id' => (int) $exame['id'],
            'adms_position_id' => $pos,
            'adms_department_id' => $dep,
            'adms_sst_risco_id' => $riscoId,
            'categoria_aso' => SstImportValues::v($mapped, 'categoria_aso') ?: ($existing['categoria_aso'] ?? null),
            'periodicidade_meses' => SstImportValues::v($mapped, 'periodicidade_meses') !== ''
                ? (int) SstImportValues::v($mapped, 'periodicidade_meses')
                : ($existing['periodicidade_meses'] ?? null),
            'obrigatorio' => SstImportValues::bool01(SstImportValues::v($mapped, 'obrigatorio')) ?? ($existing['obrigatorio'] ?? 1),
            'observacoes' => SstImportValues::v($mapped, 'observacoes') ?: ($emptyPolicy === 'skip' ? ($existing['observacoes'] ?? null) : null),
        ];

        return [
            'existing' => $existing,
            'payload' => $payload,
            'key' => ($exame['codigo'] ?? $exame['nome']) . ' / ' . (SstImportValues::v($mapped, 'position') ?: '-') . ' / ' . (SstImportValues::v($mapped, 'department') ?: '-'),
        ];
    }

    protected function createRow(array $payload): int|false
    {
        return (new SstExameNecessidadeRepository())->create($payload);
    }

    protected function updateRow(int $id, array $payload): bool
    {
        return (new SstExameNecessidadeRepository())->update($id, $payload);
    }
}
