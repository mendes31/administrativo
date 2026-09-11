<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Helpers\SstEquipamentoCodigoHelper;
use App\adms\Models\Repository\SstEquipamentoTiposRepository;

final class SstEquipamentoTiposImportProfile extends AbstractSstCatalogImportProfile
{
    public function key(): string
    {
        return 'sst_equipamento_tipos';
    }

    public function label(): string
    {
        return 'SST — Tipos de equipamento';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID (chave)',
            'codigo' => 'Código (chave, ex. EXTINTOR)',
            'nome' => 'Nome (chave)',
            'prefixo' => 'Prefixo (3 caracteres, ex. EXT)',
            'controla_recarga' => 'Controla recarga (Sim/Não)',
            'validade_recarga_meses' => 'Validade da recarga (meses)',
            'descricao' => 'Descrição',
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
        return ['', 'EXTINTOR', 'Extintor', 'EXT', 'Sim', '12', 'Extintores portáteis', 'Ativo'];
    }

    protected function findExisting(array $mapped, string $keyField): ?array
    {
        $raw = SstImportValues::v($mapped, $keyField)
            ?: SstImportValues::v($mapped, 'codigo')
            ?: SstImportValues::v($mapped, 'nome');

        return (new SstImportLookup())->catalog('adms_sst_equipamento_tipos', $raw, true);
    }

    protected function buildCreatePayload(array $mapped): array
    {
        $nome = SstImportValues::v($mapped, 'nome');
        if ($nome === '') {
            throw new \RuntimeException('Para criar tipo de equipamento, informe o nome.');
        }
        $codigo = strtoupper(SstImportValues::v($mapped, 'codigo'));
        if ($codigo === '') {
            $codigo = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $nome) ?? '') ?: 'TIPO';
        }
        $prefixo = SstEquipamentoCodigoHelper::normalizePrefixo(SstImportValues::v($mapped, 'prefixo'));
        if (!SstEquipamentoCodigoHelper::isValidPrefixo($prefixo)) {
            throw new \RuntimeException('Prefixo inválido. Informe exatamente 3 caracteres (A–Z / 0–9).');
        }
        $repo = new SstEquipamentoTiposRepository();
        if ($repo->prefixoExists($prefixo)) {
            throw new \RuntimeException('Já existe um tipo com o prefixo ' . $prefixo . '.');
        }

        return [
            'nome' => $nome,
            'codigo' => $codigo,
            'prefixo' => $prefixo,
            'controla_recarga' => SstImportValues::bool01(SstImportValues::v($mapped, 'controla_recarga')) ?? 0,
            'validade_recarga_meses' => SstImportValues::v($mapped, 'validade_recarga_meses') !== ''
                ? max(1, (int) SstImportValues::v($mapped, 'validade_recarga_meses'))
                : 12,
            'descricao' => SstImportValues::v($mapped, 'descricao') ?: null,
            'status' => SstImportValues::status(SstImportValues::v($mapped, 'status')) ?? 'Ativo',
        ];
    }

    protected function buildUpdatePayload(array $mapped, array $existing, string $emptyPolicy): array
    {
        $payload = [
            'nome' => SstImportValues::v($mapped, 'nome') ?: ($existing['nome'] ?? ''),
            'codigo' => strtoupper(SstImportValues::v($mapped, 'codigo')) ?: ($existing['codigo'] ?? ''),
            'prefixo' => SstImportValues::v($mapped, 'prefixo') !== ''
                ? SstEquipamentoCodigoHelper::normalizePrefixo(SstImportValues::v($mapped, 'prefixo'))
                : (string) ($existing['prefixo'] ?? ''),
            'controla_recarga' => SstImportValues::bool01(SstImportValues::v($mapped, 'controla_recarga')),
            'validade_recarga_meses' => SstImportValues::v($mapped, 'validade_recarga_meses'),
            'descricao' => SstImportValues::v($mapped, 'descricao'),
            'status' => SstImportValues::status(SstImportValues::v($mapped, 'status')),
        ];
        $payload = SstImportValues::mergeSkipEmpty($payload, $existing, $emptyPolicy, [
            'nome', 'codigo', 'prefixo', 'controla_recarga', 'validade_recarga_meses', 'descricao', 'status',
        ]);
        $prefixo = SstEquipamentoCodigoHelper::normalizePrefixo((string) ($payload['prefixo'] ?? ''));
        if (!SstEquipamentoCodigoHelper::isValidPrefixo($prefixo)) {
            throw new \RuntimeException('Prefixo inválido. Informe exatamente 3 caracteres (A–Z / 0–9).');
        }
        $repo = new SstEquipamentoTiposRepository();
        if ($repo->prefixoExists($prefixo, (int) $existing['id'])) {
            throw new \RuntimeException('Já existe um tipo com o prefixo ' . $prefixo . '.');
        }
        $payload['prefixo'] = $prefixo;
        $payload['controla_recarga'] = !empty($payload['controla_recarga']);
        $payload['validade_recarga_meses'] = max(1, (int) ($payload['validade_recarga_meses'] ?? 12));
        $payload['status'] = SstImportValues::status((string) ($payload['status'] ?? '')) ?? ($existing['status'] ?? 'Ativo');

        return $payload;
    }

    protected function createRow(array $payload): int|false
    {
        return (new SstEquipamentoTiposRepository())->create($payload);
    }

    protected function updateRow(int $id, array $payload): bool
    {
        return (new SstEquipamentoTiposRepository())->update($id, $payload);
    }
}
