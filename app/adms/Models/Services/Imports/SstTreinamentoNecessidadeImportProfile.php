<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\SstTreinamentoNecessidadeRepository;

final class SstTreinamentoNecessidadeImportProfile extends AbstractSstLinkImportProfile
{
    public function key(): string
    {
        return 'sst_treinamento_necessidade';
    }

    public function label(): string
    {
        return 'SST — Necessidade de treinamento';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID do vínculo',
            'treinamento' => 'Treinamento (código, nome ou ID)',
            'position' => 'Cargo (nome ou ID, opcional)',
            'department' => 'Departamento (nome ou ID, opcional)',
            'risco' => 'Risco (código, nome ou ID, opcional)',
            'validade_meses' => 'Validade (meses)',
            'obrigatorio' => 'Obrigatório (Sim/Não)',
            'observacoes' => 'Observações',
        ];
    }

    public function sampleRow(): array
    {
        return ['', 'TR0001', 'Gerente de Suprimentos', '', '', '24', 'Sim', 'Matriz cargo × treinamento'];
    }

    protected function resolve(array $mapped, string $emptyPolicy): array
    {
        $tr = $this->requireCatalog('adms_sst_treinamentos', SstImportValues::v($mapped, 'treinamento'), true, 'Treinamento');
        $pos = $this->optionalPosition(SstImportValues::v($mapped, 'position'));
        $dep = $this->optionalDepartment(SstImportValues::v($mapped, 'department'));
        $riscoId = $this->optionalRiscoId(SstImportValues::v($mapped, 'risco'));
        $existing = $this->existingByIdOrLink('adms_sst_treinamento_necessidade', $mapped, [
            'adms_sst_treinamento_id' => (int) $tr['id'],
            'adms_position_id' => $pos,
            'adms_department_id' => $dep,
            'adms_sst_risco_id' => $riscoId,
        ]);
        $payload = [
            'adms_sst_treinamento_id' => (int) $tr['id'],
            'adms_position_id' => $pos,
            'adms_department_id' => $dep,
            'adms_sst_risco_id' => $riscoId,
            'validade_meses' => SstImportValues::v($mapped, 'validade_meses') !== ''
                ? (int) SstImportValues::v($mapped, 'validade_meses')
                : ($existing['validade_meses'] ?? null),
            'obrigatorio' => SstImportValues::bool01(SstImportValues::v($mapped, 'obrigatorio')) ?? ($existing['obrigatorio'] ?? 1),
            'observacoes' => SstImportValues::v($mapped, 'observacoes') ?: ($emptyPolicy === 'skip' ? ($existing['observacoes'] ?? null) : null),
        ];

        return [
            'existing' => $existing,
            'payload' => $payload,
            'key' => ($tr['codigo'] ?? $tr['nome']) . ' / ' . (SstImportValues::v($mapped, 'position') ?: '-') . ' / ' . (SstImportValues::v($mapped, 'department') ?: '-'),
        ];
    }

    protected function createRow(array $payload): int|false
    {
        return (new SstTreinamentoNecessidadeRepository())->create($payload);
    }

    protected function updateRow(int $id, array $payload): bool
    {
        return (new SstTreinamentoNecessidadeRepository())->update($id, $payload);
    }
}
