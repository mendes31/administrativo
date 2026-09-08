<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\SstRiscoCargoRepository;

final class SstRiscosCargoImportProfile extends AbstractSstLinkImportProfile
{
    public function key(): string
    {
        return 'sst_riscos_cargo';
    }

    public function label(): string
    {
        return 'SST — Risco × cargo/setor';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID do vínculo',
            'risco' => 'Risco (código, nome ou ID)',
            'position' => 'Cargo (nome ou ID)',
            'department' => 'Departamento (nome ou ID)',
            'nivel' => 'Nível / classificação',
            'observacoes' => 'Observações',
        ];
    }

    public function sampleRow(): array
    {
        return ['', 'RIS001', 'Gerente de Suprimentos', 'Suprimentos', '', ''];
    }

    protected function resolve(array $mapped, string $emptyPolicy): array
    {
        $risco = $this->requireCatalog('adms_sst_riscos', SstImportValues::v($mapped, 'risco'), true, 'Risco');
        $pos = $this->optionalPosition(SstImportValues::v($mapped, 'position'));
        $dep = $this->optionalDepartment(SstImportValues::v($mapped, 'department'));
        if ($pos === null && $dep === null) {
            throw new \RuntimeException('Informe cargo e/ou departamento.');
        }
        $existing = $this->existingByIdOrLink('adms_sst_riscos_cargo', $mapped, [
            'adms_sst_risco_id' => (int) $risco['id'],
            'adms_position_id' => $pos,
            'adms_department_id' => $dep,
        ]);
        $payload = [
            'adms_sst_risco_id' => (int) $risco['id'],
            'adms_position_id' => $pos,
            'adms_department_id' => $dep,
            'nivel' => SstImportValues::v($mapped, 'nivel') ?: ($existing['nivel'] ?? null),
            'observacoes' => SstImportValues::v($mapped, 'observacoes') ?: ($emptyPolicy === 'skip' ? ($existing['observacoes'] ?? null) : null),
        ];
        $key = ($risco['codigo'] ?? $risco['nome']) . ' / ' . (SstImportValues::v($mapped, 'position') ?: '-') . ' / ' . (SstImportValues::v($mapped, 'department') ?: '-');

        return ['existing' => $existing, 'payload' => $payload, 'key' => $key];
    }

    protected function createRow(array $payload): int|false
    {
        return (new SstRiscoCargoRepository())->create($payload);
    }

    protected function updateRow(int $id, array $payload): bool
    {
        return (new SstRiscoCargoRepository())->update($id, $payload);
    }
}
