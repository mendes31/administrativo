<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Helpers\SstTreinamentoAplicacaoHelper;
use App\adms\Models\Repository\SstTreinamentosRepository;

final class SstTreinamentosImportProfile extends AbstractSstCatalogImportProfile
{
    public function key(): string
    {
        return 'sst_treinamentos';
    }

    public function label(): string
    {
        return 'SST — Treinamentos';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID (chave)',
            'codigo' => 'Código (chave, ex. TR0001)',
            'nome' => 'Nome (chave)',
            'descricao' => 'Descrição',
            'nr_referencia' => 'NR (ex. NR-35)',
            'aplicacao_momentos' => 'Momentos (admissional, reciclagem, periódico…)',
            'modalidade' => 'Modalidade',
            'carga_horaria_minutos' => 'Carga horária (minutos)',
            'validade_meses' => 'Validade (meses)',
            'prazo_primeiro_dias' => 'Prazo 1º treinamento (dias)',
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
        return ['', 'TR0001', 'NR-35 Trabalho em altura', '', 'NR-35', 'admissional,reciclagem', 'Presencial', '480', '24', '30', 'Ativo'];
    }

    protected function findExisting(array $mapped, string $keyField): ?array
    {
        $raw = SstImportValues::v($mapped, $keyField) ?: SstImportValues::v($mapped, 'codigo') ?: SstImportValues::v($mapped, 'nome');

        return (new SstImportLookup())->catalog('adms_sst_treinamentos', $raw, true);
    }

    protected function buildCreatePayload(array $mapped): array
    {
        $nome = SstImportValues::v($mapped, 'nome');
        if ($nome === '') {
            throw new \RuntimeException('Para criar treinamento, informe o nome.');
        }
        $repo = new SstTreinamentosRepository();
        $codigo = strtoupper(SstImportValues::v($mapped, 'codigo'));
        if ($codigo === '') {
            $codigo = $repo->getProximoCodigo();
        }
        $momentos = SstImportValues::parseMomentos(SstImportValues::v($mapped, 'aplicacao_momentos'));
        if ($momentos === []) {
            $momentos = SstTreinamentoAplicacaoHelper::fromLegacyTipo('Ambos');
        }

        return [
            'codigo' => $codigo,
            'nome' => $nome,
            'descricao' => SstImportValues::v($mapped, 'descricao') ?: null,
            'nr_referencia' => SstImportValues::v($mapped, 'nr_referencia') ?: null,
            'tipo' => SstTreinamentoAplicacaoHelper::toLegacyTipo($momentos),
            'aplicacao_momentos' => $momentos,
            'modalidade' => SstImportValues::v($mapped, 'modalidade') ?: 'Presencial',
            'carga_horaria_minutos' => SstImportValues::v($mapped, 'carga_horaria_minutos') !== '' ? (int) SstImportValues::v($mapped, 'carga_horaria_minutos') : null,
            'validade_meses' => SstImportValues::v($mapped, 'validade_meses') !== '' ? (int) SstImportValues::v($mapped, 'validade_meses') : null,
            'prazo_primeiro_dias' => SstImportValues::v($mapped, 'prazo_primeiro_dias') !== '' ? (int) SstImportValues::v($mapped, 'prazo_primeiro_dias') : null,
            'status' => SstImportValues::status(SstImportValues::v($mapped, 'status')) ?? 'Ativo',
        ];
    }

    protected function buildUpdatePayload(array $mapped, array $existing, string $emptyPolicy): array
    {
        $payload = [
            'codigo' => strtoupper(SstImportValues::v($mapped, 'codigo')) ?: ($existing['codigo'] ?? null),
            'nome' => SstImportValues::v($mapped, 'nome') ?: ($existing['nome'] ?? ''),
            'descricao' => SstImportValues::v($mapped, 'descricao'),
            'nr_referencia' => SstImportValues::v($mapped, 'nr_referencia'),
            'modalidade' => SstImportValues::v($mapped, 'modalidade'),
            'carga_horaria_minutos' => SstImportValues::v($mapped, 'carga_horaria_minutos') !== ''
                ? (int) SstImportValues::v($mapped, 'carga_horaria_minutos')
                : null,
            'validade_meses' => SstImportValues::v($mapped, 'validade_meses') !== ''
                ? (int) SstImportValues::v($mapped, 'validade_meses')
                : null,
            'prazo_primeiro_dias' => SstImportValues::v($mapped, 'prazo_primeiro_dias') !== ''
                ? (int) SstImportValues::v($mapped, 'prazo_primeiro_dias')
                : null,
            'status' => SstImportValues::status(SstImportValues::v($mapped, 'status')),
        ];
        $momentos = SstImportValues::parseMomentos(SstImportValues::v($mapped, 'aplicacao_momentos'));
        if ($momentos !== []) {
            $payload['aplicacao_momentos'] = $momentos;
            $payload['tipo'] = SstTreinamentoAplicacaoHelper::toLegacyTipo($momentos);
        } elseif ($emptyPolicy === 'skip' && !empty($existing['aplicacao_momentos'])) {
            $payload['aplicacao_momentos'] = is_array($existing['aplicacao_momentos'])
                ? $existing['aplicacao_momentos']
                : SstTreinamentoAplicacaoHelper::decodeFromDb($existing['aplicacao_momentos']);
            $payload['tipo'] = SstTreinamentoAplicacaoHelper::toLegacyTipo($payload['aplicacao_momentos']);
        } else {
            $payload['aplicacao_momentos'] = SstTreinamentoAplicacaoHelper::fromLegacyTipo((string) ($existing['tipo'] ?? 'Ambos'));
            $payload['tipo'] = $existing['tipo'] ?? 'Ambos';
        }

        return SstImportValues::mergeSkipEmpty($payload, $existing, $emptyPolicy, [
            'codigo', 'nome', 'descricao', 'nr_referencia', 'modalidade', 'carga_horaria_minutos', 'validade_meses', 'prazo_primeiro_dias', 'status',
        ]);
    }

    protected function createRow(array $payload): int|false
    {
        return (new SstTreinamentosRepository())->create($payload);
    }

    protected function updateRow(int $id, array $payload): bool
    {
        return (new SstTreinamentosRepository())->update($id, $payload);
    }
}
