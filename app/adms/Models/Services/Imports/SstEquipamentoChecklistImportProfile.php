<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\SstEquipamentoTiposRepository;

final class SstEquipamentoChecklistImportProfile extends AbstractSstLinkImportProfile
{
    public function key(): string
    {
        return 'sst_equipamento_checklist';
    }

    public function label(): string
    {
        return 'SST — Checklist de tipo';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID do item',
            'tipo' => 'Tipo de equipamento (código, nome ou ID)',
            'descricao' => 'Descrição do item do checklist',
            'ordem' => 'Ordem',
            'obrigatorio' => 'Obrigatório (Sim/Não)',
            'ativo' => 'Ativo (Sim/Não)',
        ];
    }

    public function sampleRow(): array
    {
        return ['', 'EXTINTOR', 'Manômetro na faixa verde', '1', 'Sim', 'Sim'];
    }

    protected function resolve(array $mapped, string $emptyPolicy): array
    {
        $tipo = $this->requireCatalog('adms_sst_equipamento_tipos', SstImportValues::v($mapped, 'tipo'), true, 'Tipo de equipamento');
        $descricao = SstImportValues::v($mapped, 'descricao');
        if ($descricao === '') {
            throw new \RuntimeException('Informe a descrição do item do checklist.');
        }
        $existing = $this->existingByIdOrLink('adms_sst_equipamento_checklist_itens', $mapped, [
            'adms_sst_equipamento_tipo_id' => (int) $tipo['id'],
            'descricao' => $descricao,
        ]);
        $obr = SstImportValues::bool01(SstImportValues::v($mapped, 'obrigatorio'));
        $ativo = SstImportValues::bool01(SstImportValues::v($mapped, 'ativo'));
        $ordemRaw = SstImportValues::v($mapped, 'ordem');
        $payload = [
            'adms_sst_equipamento_tipo_id' => (int) $tipo['id'],
            'descricao' => $descricao,
            'ordem' => $ordemRaw !== '' ? (int) $ordemRaw : (int) ($existing['ordem'] ?? 0),
            'obrigatorio' => $obr ?? ($existing['obrigatorio'] ?? 1),
            'ativo' => $ativo ?? ($existing['ativo'] ?? 1),
        ];
        if ($emptyPolicy === 'skip' && $ordemRaw === '' && $existing !== null) {
            $payload['ordem'] = (int) ($existing['ordem'] ?? 0);
        }

        return [
            'existing' => $existing,
            'payload' => $payload,
            'key' => ($tipo['codigo'] ?? $tipo['nome']) . ' × ' . $descricao,
        ];
    }

    protected function createRow(array $payload): int|false
    {
        return (new SstEquipamentoTiposRepository())->addChecklistItem(
            (int) $payload['adms_sst_equipamento_tipo_id'],
            $payload
        );
    }

    protected function updateRow(int $id, array $payload): bool
    {
        return (new SstEquipamentoTiposRepository())->updateChecklistItem($id, $payload);
    }
}
