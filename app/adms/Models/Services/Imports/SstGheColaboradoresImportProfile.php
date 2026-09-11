<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\SstGheColaboradoresRepository;

final class SstGheColaboradoresImportProfile extends AbstractSstLinkImportProfile
{
    public function key(): string
    {
        return 'sst_ghe_colaboradores';
    }

    public function label(): string
    {
        return 'SST — GHE × colaborador';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID do vínculo',
            'ghe' => 'GHE (código, nome ou ID)',
            'colaborador' => 'Colaborador (login, e-mail, CPF, nome ou ID)',
            'data_inicio' => 'Data de início',
            'observacoes' => 'Observações',
        ];
    }

    public function sampleRow(): array
    {
        return ['', 'GHE-SUP', 'fulano.silva', '01/03/2026', ''];
    }

    protected function resolve(array $mapped, string $emptyPolicy): array
    {
        $ghe = $this->requireCatalog('adms_sst_ghe', SstImportValues::v($mapped, 'ghe'), true, 'GHE');
        $userId = $this->requireUser(SstImportValues::v($mapped, 'colaborador'));
        $existing = $this->existingByIdOrLink('adms_sst_ghe_colaboradores', $mapped, [
            'adms_sst_ghe_id' => (int) $ghe['id'],
            'adms_user_id' => $userId,
            'data_fim' => null,
        ]);
        $inicio = SstImportValues::date(SstImportValues::v($mapped, 'data_inicio'));
        if ($inicio === null && $existing !== null && $emptyPolicy === 'skip') {
            $inicio = (string) ($existing['data_inicio'] ?? date('Y-m-d'));
        }
        $obs = SstImportValues::v($mapped, 'observacoes');
        if ($obs === '' && $emptyPolicy === 'skip') {
            $obs = (string) ($existing['observacoes'] ?? '');
        }

        return [
            'existing' => $existing,
            'payload' => [
                'adms_sst_ghe_id' => (int) $ghe['id'],
                'adms_user_id' => $userId,
                'data_inicio' => $inicio ?: date('Y-m-d'),
                'observacoes' => $obs !== '' ? $obs : null,
            ],
            'key' => ($ghe['codigo'] ?? $ghe['nome']) . ' × ' . $userId,
        ];
    }

    protected function createRow(array $payload): int|false
    {
        return (new SstGheColaboradoresRepository())->create($payload);
    }

    protected function updateRow(int $id, array $payload): bool
    {
        return (new SstGheColaboradoresRepository())->update($id, $payload);
    }
}
