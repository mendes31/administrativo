<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\SstRiscosRepository;

final class SstRiscosImportProfile extends AbstractSstCatalogImportProfile
{
    public function key(): string
    {
        return 'sst_riscos';
    }

    public function label(): string
    {
        return 'SST — Riscos';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID (chave)',
            'codigo' => 'Código (chave, ex. RIS001)',
            'nome' => 'Nome (chave)',
            'descricao' => 'Descrição',
            'grupo_risco' => 'Grupo (Físico/Químico/Biológico/Ergonômico/Acidente/Mecânico)',
            'necessita_monitoramento_medico' => 'Monitoramento médico (Sim/Não)',
            'necessita_epi' => 'Necessita EPI (Sim/Não)',
            'status' => 'Status',
        ];
    }

    public function keyFields(): array
    {
        return ['codigo', 'nome', 'id'];
    }

    public function defaultKeyField(): string
    {
        return 'codigo';
    }

    public function sampleRow(): array
    {
        return ['', 'RIS001', 'Ruído ocupacional', 'Exposição a ruído', 'Físico', 'Sim', 'Sim', 'Ativo'];
    }

    protected function findExisting(array $mapped, string $keyField): ?array
    {
        $lookup = new SstImportLookup();
        $raw = SstImportValues::v($mapped, $keyField);
        if ($raw === '') {
            $raw = SstImportValues::v($mapped, 'codigo') ?: SstImportValues::v($mapped, 'nome');
        }

        return $lookup->catalog('adms_sst_riscos', $raw, true);
    }

    protected function buildCreatePayload(array $mapped): array
    {
        $nome = SstImportValues::v($mapped, 'nome');
        if ($nome === '') {
            throw new \RuntimeException('Para criar risco, informe o nome.');
        }
        $repo = new SstRiscosRepository();
        $codigo = strtoupper(SstImportValues::v($mapped, 'codigo'));
        if ($codigo === '') {
            $codigo = $repo->getProximoCodigo();
        }

        return [
            'codigo' => $codigo,
            'nome' => $nome,
            'descricao' => SstImportValues::v($mapped, 'descricao') ?: null,
            'grupo_risco' => SstImportValues::v($mapped, 'grupo_risco') ?: null,
            'necessita_monitoramento_medico' => SstImportValues::bool01(SstImportValues::v($mapped, 'necessita_monitoramento_medico')) ?? 0,
            'necessita_epi' => SstImportValues::bool01(SstImportValues::v($mapped, 'necessita_epi')) ?? 0,
            'status' => SstImportValues::status(SstImportValues::v($mapped, 'status')) ?? 'Ativo',
        ];
    }

    protected function buildUpdatePayload(array $mapped, array $existing, string $emptyPolicy): array
    {
        $payload = $this->mappedFields($mapped);
        $payload = SstImportValues::mergeSkipEmpty($payload, $existing, $emptyPolicy, [
            'codigo', 'nome', 'descricao', 'grupo_risco', 'necessita_monitoramento_medico', 'necessita_epi', 'status',
        ]);
        $payload['necessita_monitoramento_medico'] = !empty($payload['necessita_monitoramento_medico']);
        $payload['necessita_epi'] = !empty($payload['necessita_epi']);

        return $payload;
    }

    protected function createRow(array $payload): int|false
    {
        return (new SstRiscosRepository())->create($payload);
    }

    protected function updateRow(int $id, array $payload): bool
    {
        return (new SstRiscosRepository())->update($id, $payload);
    }

    /** @param array<string, string> $mapped */
    private function mappedFields(array $mapped): array
    {
        $out = [];
        if (SstImportValues::has($mapped, 'codigo') && SstImportValues::v($mapped, 'codigo') !== '') {
            $out['codigo'] = strtoupper(SstImportValues::v($mapped, 'codigo'));
        }
        if (SstImportValues::has($mapped, 'nome') && SstImportValues::v($mapped, 'nome') !== '') {
            $out['nome'] = SstImportValues::v($mapped, 'nome');
        }
        if (SstImportValues::has($mapped, 'descricao')) {
            $out['descricao'] = SstImportValues::v($mapped, 'descricao');
        }
        if (SstImportValues::has($mapped, 'grupo_risco') && SstImportValues::v($mapped, 'grupo_risco') !== '') {
            $out['grupo_risco'] = SstImportValues::v($mapped, 'grupo_risco');
        }
        if (SstImportValues::has($mapped, 'necessita_monitoramento_medico')) {
            $b = SstImportValues::bool01(SstImportValues::v($mapped, 'necessita_monitoramento_medico'));
            if ($b !== null) {
                $out['necessita_monitoramento_medico'] = $b;
            }
        }
        if (SstImportValues::has($mapped, 'necessita_epi')) {
            $b = SstImportValues::bool01(SstImportValues::v($mapped, 'necessita_epi'));
            if ($b !== null) {
                $out['necessita_epi'] = $b;
            }
        }
        if (SstImportValues::has($mapped, 'status')) {
            $st = SstImportValues::status(SstImportValues::v($mapped, 'status'));
            if ($st !== null) {
                $out['status'] = $st;
            }
        }

        return $out;
    }
}
