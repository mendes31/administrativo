<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\SstExamesRepository;

final class SstExamesImportProfile extends AbstractSstCatalogImportProfile
{
    public function key(): string
    {
        return 'sst_exames';
    }

    public function label(): string
    {
        return 'SST — Exames';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID (chave)',
            'codigo' => 'Código (chave, ex. EX0001)',
            'nome' => 'Nome (chave)',
            'descricao' => 'Descrição',
            'tipo' => 'Tipo (Clínico/Laboratorial/Imagem/Funcional/Avaliação Médica/Outros)',
            'periodicidade_meses' => 'Periodicidade (meses)',
            'possui_validade' => 'Possui validade (Sim/Não)',
            'validade_meses' => 'Validade (meses)',
            'exige_resultado' => 'Exige resultado (Sim/Não)',
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
        return ['', 'EX0001', 'Audiometria', '', 'Funcional', '12', 'Sim', '12', 'Sim', 'Ativo'];
    }

    protected function findExisting(array $mapped, string $keyField): ?array
    {
        $raw = SstImportValues::v($mapped, $keyField) ?: SstImportValues::v($mapped, 'codigo') ?: SstImportValues::v($mapped, 'nome');

        return (new SstImportLookup())->catalog('adms_sst_exames', $raw, true);
    }

    protected function buildCreatePayload(array $mapped): array
    {
        $nome = SstImportValues::v($mapped, 'nome');
        if ($nome === '') {
            throw new \RuntimeException('Para criar exame, informe o nome.');
        }
        $repo = new SstExamesRepository();
        $codigo = strtoupper(SstImportValues::v($mapped, 'codigo'));
        if ($codigo === '') {
            $codigo = $repo->getProximoCodigo();
        }

        return [
            'codigo' => $codigo,
            'nome' => $nome,
            'descricao' => SstImportValues::v($mapped, 'descricao') ?: null,
            'tipo' => SstImportValues::v($mapped, 'tipo') ?: null,
            'periodicidade_meses' => SstImportValues::v($mapped, 'periodicidade_meses') !== '' ? (int) SstImportValues::v($mapped, 'periodicidade_meses') : null,
            'possui_validade' => SstImportValues::bool01(SstImportValues::v($mapped, 'possui_validade')) ?? 0,
            'validade_meses' => SstImportValues::v($mapped, 'validade_meses') !== '' ? (int) SstImportValues::v($mapped, 'validade_meses') : null,
            'exige_resultado' => SstImportValues::bool01(SstImportValues::v($mapped, 'exige_resultado')) ?? 0,
            'status' => SstImportValues::status(SstImportValues::v($mapped, 'status')) ?? 'Ativo',
        ];
    }

    protected function buildUpdatePayload(array $mapped, array $existing, string $emptyPolicy): array
    {
        $payload = [
            'codigo' => strtoupper(SstImportValues::v($mapped, 'codigo')) ?: ($existing['codigo'] ?? null),
            'nome' => SstImportValues::v($mapped, 'nome') ?: ($existing['nome'] ?? ''),
            'descricao' => SstImportValues::v($mapped, 'descricao'),
            'tipo' => SstImportValues::v($mapped, 'tipo'),
            'periodicidade_meses' => SstImportValues::v($mapped, 'periodicidade_meses') !== ''
                ? (int) SstImportValues::v($mapped, 'periodicidade_meses')
                : null,
            'possui_validade' => SstImportValues::bool01(SstImportValues::v($mapped, 'possui_validade')),
            'validade_meses' => SstImportValues::v($mapped, 'validade_meses') !== ''
                ? (int) SstImportValues::v($mapped, 'validade_meses')
                : null,
            'exige_resultado' => SstImportValues::bool01(SstImportValues::v($mapped, 'exige_resultado')),
            'status' => SstImportValues::status(SstImportValues::v($mapped, 'status')),
            'resultados_permitidos' => $existing['resultados_permitidos'] ?? null,
        ];
        $merged = SstImportValues::mergeSkipEmpty($payload, $existing, $emptyPolicy, [
            'codigo', 'nome', 'descricao', 'tipo', 'periodicidade_meses', 'possui_validade', 'validade_meses', 'exige_resultado', 'status', 'resultados_permitidos',
        ]);
        $merged['possui_validade'] = !empty($merged['possui_validade']);
        $merged['exige_resultado'] = !empty($merged['exige_resultado']);

        return $merged;
    }

    protected function createRow(array $payload): int|false
    {
        return (new SstExamesRepository())->create($payload);
    }

    protected function updateRow(int $id, array $payload): bool
    {
        return (new SstExamesRepository())->update($id, $payload);
    }
}
