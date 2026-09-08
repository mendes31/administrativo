<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\SstRiscoExameRepository;

final class SstRiscoExameImportProfile extends AbstractSstLinkImportProfile
{
    public function key(): string
    {
        return 'sst_risco_exame';
    }

    public function label(): string
    {
        return 'SST — Risco × exame';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID do vínculo',
            'risco' => 'Risco (código, nome ou ID)',
            'exame' => 'Exame (código, nome ou ID)',
            'categoria_aso' => 'Categoria ASO (Admissional/Periódico/…)',
            'periodicidade_meses' => 'Periodicidade (meses)',
            'obrigatorio' => 'Obrigatório (Sim/Não)',
            'observacoes' => 'Observações',
        ];
    }

    public function sampleRow(): array
    {
        return ['', 'RIS001', 'EX0001', 'Periódico', '12', 'Sim', ''];
    }

    protected function resolve(array $mapped, string $emptyPolicy): array
    {
        $risco = $this->requireCatalog('adms_sst_riscos', SstImportValues::v($mapped, 'risco'), true, 'Risco');
        $exame = $this->requireCatalog('adms_sst_exames', SstImportValues::v($mapped, 'exame'), true, 'Exame');
        $existing = $this->existingByIdOrLink('adms_sst_risco_exame', $mapped, [
            'adms_sst_risco_id' => (int) $risco['id'],
            'adms_sst_exame_id' => (int) $exame['id'],
        ]);
        $payload = [
            'adms_sst_risco_id' => (int) $risco['id'],
            'adms_sst_exame_id' => (int) $exame['id'],
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
            'key' => ($risco['codigo'] ?? $risco['nome']) . ' × ' . ($exame['codigo'] ?? $exame['nome']),
        ];
    }

    protected function createRow(array $payload): int|false
    {
        return (new SstRiscoExameRepository())->create($payload);
    }

    protected function updateRow(int $id, array $payload): bool
    {
        return (new SstRiscoExameRepository())->update($id, $payload);
    }
}
