<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Helpers\SstEpiTamanhoHelper;
use App\adms\Models\Repository\SstEpiEstoqueMinTamanhoRepository;
use App\adms\Models\Repository\SstEpisRepository;

final class SstEpisImportProfile extends AbstractSstCatalogImportProfile
{
    public function key(): string
    {
        return 'sst_epis';
    }

    public function label(): string
    {
        return 'SST — EPIs';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID (chave)',
            'nome' => 'Nome (chave)',
            'descricao' => 'Descrição',
            'categoria' => 'Categoria',
            'estoque_minimo' => 'Estoque mínimo (sem grade: total; com grade: padrão por tamanho)',
            'grade_tamanhos' => 'Grade (vazio, calcado, vestuario ou lista 36,37,38)',
            'min_tamanhos' => 'Mínimos extras (ex.: 38=8, 42=5; vazio = só o padrão)',
            'periodicidade_troca_dias' => 'Troca (dias)',
            'status' => 'Status',
        ];
    }

    public function keyFields(): array
    {
        return ['nome', 'id'];
    }

    public function defaultKeyField(): string
    {
        return 'nome';
    }

    public function sampleRow(): array
    {
        return $this->sampleRows()[0];
    }

    /**
     * @return list<list<string>>
     */
    public function sampleRows(): array
    {
        return [
            ['', 'Capacete de segurança', 'Casco classe B', 'Proteção de Cabeça', '5', '', '', '365', 'Ativo'],
            ['', 'Calçado de segurança', 'Bico de PVC', 'Proteção de Pés e Pernas', '3', 'calcado', '38=8, 42=5', '180', 'Ativo'],
            ['', 'Camisa manga longa', 'Brim', 'Proteção do Tronco', '3', 'vestuario', 'GG=6', '365', 'Ativo'],
            ['', 'Luva nitrílica', 'Caixa mista', 'Proteção de Mãos e Braços', '10', 'P, M, G, GG', '', '90', 'Ativo'],
        ];
    }

    protected function findExisting(array $mapped, string $keyField): ?array
    {
        return (new SstImportLookup())->catalog(
            'adms_sst_epis',
            SstImportValues::v($mapped, $keyField) ?: SstImportValues::v($mapped, 'nome'),
            false
        );
    }

    protected function buildCreatePayload(array $mapped): array
    {
        $nome = SstImportValues::v($mapped, 'nome');
        if ($nome === '') {
            throw new \RuntimeException('Para criar EPI, informe o nome.');
        }

        $gradeRaw = SstImportValues::v($mapped, 'grade_tamanhos');
        $grade = $gradeRaw !== '' ? SstEpiTamanhoHelper::parseGrade($gradeRaw) : [];

        return [
            'nome' => $nome,
            'descricao' => SstImportValues::v($mapped, 'descricao') ?: null,
            'categoria' => \App\adms\Helpers\SstEpiCategoriaHelper::canonicalize(SstImportValues::v($mapped, 'categoria'))
                ?: (SstImportValues::v($mapped, 'categoria') ?: null),
            'estoque_minimo' => SstImportValues::v($mapped, 'estoque_minimo') !== '' ? (int) SstImportValues::v($mapped, 'estoque_minimo') : 0,
            'controla_tamanho' => $grade !== [] ? 1 : 0,
            'grade_tamanhos' => $grade !== [] ? SstEpiTamanhoHelper::serializeGrade($grade) : null,
            '_minimos_tamanho' => SstEpiTamanhoHelper::parseMinimosLista(
                SstImportValues::v($mapped, 'min_tamanhos'),
                $grade
            ),
            'periodicidade_troca_dias' => SstImportValues::v($mapped, 'periodicidade_troca_dias') !== '' ? (int) SstImportValues::v($mapped, 'periodicidade_troca_dias') : null,
            'status' => SstImportValues::status(SstImportValues::v($mapped, 'status')) ?? 'Ativo',
        ];
    }

    protected function buildUpdatePayload(array $mapped, array $existing, string $emptyPolicy): array
    {
        $payload = [];
        foreach (['nome', 'descricao', 'categoria', 'estoque_minimo', 'periodicidade_troca_dias', 'status'] as $f) {
            if (!SstImportValues::has($mapped, $f)) {
                continue;
            }
            $payload[$f] = SstImportValues::v($mapped, $f);
        }
        if (isset($payload['status'])) {
            $payload['status'] = SstImportValues::status((string) $payload['status']) ?? $existing['status'] ?? 'Ativo';
        }
        if (SstImportValues::has($mapped, 'grade_tamanhos')) {
            $grade = SstEpiTamanhoHelper::parseGrade(SstImportValues::v($mapped, 'grade_tamanhos'));
            $payload['controla_tamanho'] = $grade !== [] ? 1 : 0;
            $payload['grade_tamanhos'] = $grade !== [] ? SstEpiTamanhoHelper::serializeGrade($grade) : null;
        }
        if (SstImportValues::has($mapped, 'min_tamanhos')) {
            $rawMins = SstImportValues::v($mapped, 'min_tamanhos');
            if ($rawMins !== '' || $emptyPolicy === 'clear') {
                $grade = SstEpiTamanhoHelper::parseGrade((string) (
                    $payload['grade_tamanhos'] ?? $existing['grade_tamanhos'] ?? ''
                ));
                $payload['_minimos_tamanho'] = SstEpiTamanhoHelper::parseMinimosLista($rawMins, $grade);
            }
        }

        return SstImportValues::mergeSkipEmpty($payload, $existing, $emptyPolicy, [
            'nome', 'descricao', 'categoria', 'estoque_minimo', 'periodicidade_troca_dias', 'status',
        ]);
    }

    protected function createRow(array $payload): int|false
    {
        $mins = $payload['_minimos_tamanho'] ?? [];
        unset($payload['_minimos_tamanho']);
        $id = (new SstEpisRepository())->create($payload);
        if ($id) {
            $this->persistMinimos((int) $id, $payload, is_array($mins) ? $mins : []);
        }

        return $id;
    }

    protected function updateRow(int $id, array $payload): bool
    {
        $hasMins = array_key_exists('_minimos_tamanho', $payload);
        $mins = $hasMins && is_array($payload['_minimos_tamanho']) ? $payload['_minimos_tamanho'] : [];
        unset($payload['_minimos_tamanho']);
        $ok = (new SstEpisRepository())->update($id, $payload);
        if ($ok && ($hasMins || (array_key_exists('controla_tamanho', $payload) && empty($payload['controla_tamanho'])))) {
            $this->persistMinimos($id, $payload, $hasMins ? $mins : []);
        }

        return $ok;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, int> $mins
     */
    private function persistMinimos(int $epiId, array $payload, array $mins): void
    {
        $controla = array_key_exists('controla_tamanho', $payload)
            ? !empty($payload['controla_tamanho'])
            : null;
        if ($controla === false) {
            (new SstEpiEstoqueMinTamanhoRepository())->replaceForEpi($epiId, []);

            return;
        }
        $grade = SstEpiTamanhoHelper::parseGrade((string) ($payload['grade_tamanhos'] ?? ''));
        if ($grade === []) {
            $epi = (new SstEpisRepository())->getById($epiId);
            $grade = SstEpiTamanhoHelper::parseGrade((string) ($epi['grade_tamanhos'] ?? ''));
            $controla = !empty($epi['controla_tamanho']);
        }
        if ($controla === false || $grade === []) {
            (new SstEpiEstoqueMinTamanhoRepository())->replaceForEpi($epiId, []);

            return;
        }
        (new SstEpiEstoqueMinTamanhoRepository())->replaceForEpi($epiId, $mins);
    }
}
