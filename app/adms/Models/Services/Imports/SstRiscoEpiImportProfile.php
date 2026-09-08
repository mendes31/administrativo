<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\SstRiscoEpiRepository;

final class SstRiscoEpiImportProfile extends AbstractSstLinkImportProfile
{
    public function key(): string
    {
        return 'sst_risco_epi';
    }

    public function label(): string
    {
        return 'SST — Risco × EPI';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID do vínculo',
            'risco' => 'Risco (código, nome ou ID)',
            'epi' => 'EPI (nome ou ID)',
            'obrigatorio' => 'Obrigatório (Sim/Não)',
            'observacoes' => 'Observações',
        ];
    }

    public function sampleRow(): array
    {
        return ['', 'RIS001', 'Capacete de segurança', 'Sim', ''];
    }

    protected function resolve(array $mapped, string $emptyPolicy): array
    {
        $risco = $this->requireCatalog('adms_sst_riscos', SstImportValues::v($mapped, 'risco'), true, 'Risco');
        $epi = $this->requireCatalog('adms_sst_epis', SstImportValues::v($mapped, 'epi'), false, 'EPI');
        $existing = $this->existingByIdOrLink('adms_sst_risco_epi', $mapped, [
            'adms_sst_risco_id' => (int) $risco['id'],
            'adms_sst_epi_id' => (int) $epi['id'],
        ]);
        $obr = SstImportValues::bool01(SstImportValues::v($mapped, 'obrigatorio'));
        $payload = [
            'adms_sst_risco_id' => (int) $risco['id'],
            'adms_sst_epi_id' => (int) $epi['id'],
            'obrigatorio' => $obr ?? ($existing['obrigatorio'] ?? 1),
            'observacoes' => SstImportValues::v($mapped, 'observacoes') ?: ($emptyPolicy === 'skip' ? ($existing['observacoes'] ?? null) : null),
        ];

        return [
            'existing' => $existing,
            'payload' => $payload,
            'key' => ($risco['codigo'] ?? $risco['nome']) . ' × ' . $epi['nome'],
        ];
    }

    protected function createRow(array $payload): int|false
    {
        return (new SstRiscoEpiRepository())->create($payload);
    }

    protected function updateRow(int $id, array $payload): bool
    {
        return (new SstRiscoEpiRepository())->update($id, $payload);
    }
}
