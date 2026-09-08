<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\SstRiscoTreinamentoRepository;

final class SstRiscoTreinamentoImportProfile extends AbstractSstLinkImportProfile
{
    public function key(): string
    {
        return 'sst_risco_treinamento';
    }

    public function label(): string
    {
        return 'SST — Risco × treinamento';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID do vínculo',
            'risco' => 'Risco (código, nome ou ID)',
            'treinamento' => 'Treinamento (código, nome ou ID)',
            'validade_meses' => 'Validade (meses)',
            'obrigatorio' => 'Obrigatório (Sim/Não)',
            'observacoes' => 'Observações',
        ];
    }

    public function sampleRow(): array
    {
        return ['', 'RIS001', 'TR0001', '24', 'Sim', ''];
    }

    protected function resolve(array $mapped, string $emptyPolicy): array
    {
        $risco = $this->requireCatalog('adms_sst_riscos', SstImportValues::v($mapped, 'risco'), true, 'Risco');
        $tr = $this->requireCatalog('adms_sst_treinamentos', SstImportValues::v($mapped, 'treinamento'), true, 'Treinamento');
        $existing = $this->existingByIdOrLink('adms_sst_risco_treinamento', $mapped, [
            'adms_sst_risco_id' => (int) $risco['id'],
            'adms_sst_treinamento_id' => (int) $tr['id'],
        ]);
        $payload = [
            'adms_sst_risco_id' => (int) $risco['id'],
            'adms_sst_treinamento_id' => (int) $tr['id'],
            'validade_meses' => SstImportValues::v($mapped, 'validade_meses') !== ''
                ? (int) SstImportValues::v($mapped, 'validade_meses')
                : ($existing['validade_meses'] ?? null),
            'obrigatorio' => SstImportValues::bool01(SstImportValues::v($mapped, 'obrigatorio')) ?? ($existing['obrigatorio'] ?? 1),
            'observacoes' => SstImportValues::v($mapped, 'observacoes') ?: ($emptyPolicy === 'skip' ? ($existing['observacoes'] ?? null) : null),
        ];

        return [
            'existing' => $existing,
            'payload' => $payload,
            'key' => ($risco['codigo'] ?? $risco['nome']) . ' × ' . ($tr['codigo'] ?? $tr['nome']),
        ];
    }

    protected function createRow(array $payload): int|false
    {
        return (new SstRiscoTreinamentoRepository())->create($payload);
    }

    protected function updateRow(int $id, array $payload): bool
    {
        return (new SstRiscoTreinamentoRepository())->update($id, $payload);
    }
}
