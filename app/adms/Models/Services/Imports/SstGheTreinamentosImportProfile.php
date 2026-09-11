<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\SstGheTreinamentosRepository;

final class SstGheTreinamentosImportProfile extends AbstractSstLinkImportProfile
{
    public function key(): string
    {
        return 'sst_ghe_treinamentos';
    }

    public function label(): string
    {
        return 'SST — GHE × treinamento';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID do vínculo',
            'ghe' => 'GHE (código, nome ou ID)',
            'treinamento' => 'Treinamento (código, nome ou ID)',
            'obrigatorio' => 'Obrigatório (Sim/Não)',
            'validade_meses' => 'Validade (meses)',
            'observacoes' => 'Observações',
        ];
    }

    public function sampleRow(): array
    {
        return ['', 'GHE-SUP', 'TR0001', 'Sim', '12', ''];
    }

    protected function resolve(array $mapped, string $emptyPolicy): array
    {
        $ghe = $this->requireCatalog('adms_sst_ghe', SstImportValues::v($mapped, 'ghe'), true, 'GHE');
        $treinamento = $this->requireCatalog(
            'adms_sst_treinamentos',
            SstImportValues::v($mapped, 'treinamento'),
            true,
            'Treinamento'
        );
        $existing = $this->existingByIdOrLink('adms_sst_ghe_treinamentos', $mapped, [
            'adms_sst_ghe_id' => (int) $ghe['id'],
            'adms_sst_treinamento_id' => (int) $treinamento['id'],
        ]);
        $obr = SstImportValues::bool01(SstImportValues::v($mapped, 'obrigatorio'));
        $validadeRaw = SstImportValues::v($mapped, 'validade_meses');
        $validade = $validadeRaw !== '' ? (int) $validadeRaw : ($existing['validade_meses'] ?? null);
        $obs = SstImportValues::v($mapped, 'observacoes');
        if ($obs === '' && $emptyPolicy === 'skip') {
            $obs = (string) ($existing['observacoes'] ?? '');
        }

        return [
            'existing' => $existing,
            'payload' => [
                'adms_sst_ghe_id' => (int) $ghe['id'],
                'adms_sst_treinamento_id' => (int) $treinamento['id'],
                'obrigatorio' => $obr ?? ($existing['obrigatorio'] ?? 1),
                'validade_meses' => $validade,
                'observacoes' => $obs !== '' ? $obs : null,
            ],
            'key' => ($ghe['codigo'] ?? $ghe['nome']) . ' × ' . ($treinamento['codigo'] ?? $treinamento['nome']),
        ];
    }

    protected function createRow(array $payload): int|false
    {
        return (new SstGheTreinamentosRepository())->create($payload);
    }

    protected function updateRow(int $id, array $payload): bool
    {
        return (new SstGheTreinamentosRepository())->update($id, $payload);
    }
}
