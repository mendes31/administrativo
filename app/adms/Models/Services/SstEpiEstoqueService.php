<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SstEpiMovimentoHelper;
use App\adms\Helpers\SstEpiTamanhoHelper;
use App\adms\Models\Repository\SstEpiEstoqueMinTamanhoRepository;
use App\adms\Models\Repository\SstEpiMovimentosRepository;
use App\adms\Models\Repository\SstEpisRepository;

/**
 * Registra movimentações de estoque EPI e mantém estoque_atual sincronizado (cache).
 * Custo: média ponderada por CA. DOCNUM por série (EM/SM/ET/DS/AS).
 */
class SstEpiEstoqueService
{
    private SstEpiMovimentosRepository $movRepo;

    private SstEpisRepository $epiRepo;

    private SstEpiEstoqueMinTamanhoRepository $minRepo;

    public function __construct()
    {
        $this->movRepo = new SstEpiMovimentosRepository();
        $this->epiRepo = new SstEpisRepository();
        $this->minRepo = new SstEpiEstoqueMinTamanhoRepository();
    }

    /**
     * @param array<string, mixed> $data
     * @return array{ok:bool, id?:int, doc_codigo?:string, error?:string}
     */
    public function registrarMovimento(array $data): array
    {
        if (!$this->movRepo->hasTable()) {
            return ['ok' => false, 'error' => 'Módulo de movimentações não instalado. Execute a migration SST EPI movimentos.'];
        }

        $epiId = (int) ($data['adms_sst_epi_id'] ?? 0);
        $tipo = (string) ($data['tipo_movimento'] ?? '');
        if ($epiId <= 0 || $tipo === '') {
            return ['ok' => false, 'error' => 'EPI e tipo de movimento são obrigatórios.'];
        }

        $data = SstEpiMovimentoHelper::normalize($data);
        $validationError = SstEpiMovimentoHelper::validate($data);
        if ($validationError !== null) {
            return ['ok' => false, 'error' => $validationError];
        }

        $epi = $this->epiRepo->getById($epiId);
        if (!$epi) {
            return ['ok' => false, 'error' => 'EPI não encontrado.'];
        }

        $saldoAtual = $this->movRepo->getSaldoCalculado($epiId);
        $ca = isset($data['ca_numero']) ? (string) $data['ca_numero'] : '';
        $controlaTamanho = $this->epiControlaTamanho($epi);
        $grade = SstEpiTamanhoHelper::parseGrade((string) ($epi['grade_tamanhos'] ?? ''));
        $tamanho = SstEpiTamanhoHelper::normalize((string) ($data['tamanho'] ?? ''));

        if ($controlaTamanho && in_array($tipo, ['Entrada', 'Saída', 'Entrega', 'Devolução', 'Ajuste'], true)) {
            if ($tamanho === '') {
                return ['ok' => false, 'error' => 'Informe o tamanho/numeração do lote.'];
            }
            if ($grade !== [] && !SstEpiTamanhoHelper::isAllowed($tamanho, $grade)) {
                return ['ok' => false, 'error' => 'Tamanho "' . $tamanho . '" não está na grade deste EPI (' . implode(', ', $grade) . ').'];
            }
            $data['tamanho'] = $tamanho;
        }

        if ($tipo === 'Ajuste') {
            $saldoNovo = (int) ($data['saldo_novo'] ?? -1);
            if ($saldoNovo < 0) {
                return ['ok' => false, 'error' => 'Informe o saldo contado para o ajuste.'];
            }
            if ($controlaTamanho) {
                if ($ca === '') {
                    return ['ok' => false, 'error' => 'Informe o CA do lote para ajustar por tamanho.'];
                }
                $saldoBase = $this->movRepo->getSaldoLote($epiId, $ca, $tamanho);
            } else {
                $saldoBase = $saldoAtual;
            }
            $diff = $saldoNovo - $saldoBase;
            if ($diff === 0) {
                return ['ok' => false, 'error' => 'Saldo informado é igual ao saldo atual. Nenhum ajuste necessário.'];
            }
            $data['quantidade'] = $diff;
            $data['tipo_movimento'] = 'Ajuste';
            $media = $this->movRepo->getCustoMedioCa($epiId, $ca !== '' ? $ca : null);
            if ($diff < 0 && $media !== null) {
                $data['valor_unitario'] = $media;
                $data['valor_total'] = round($media * abs($diff), 2);
            } else {
                $data['valor_unitario'] = null;
                $data['valor_total'] = null;
            }
            $data['observacoes'] = trim(
                (($data['observacoes'] ?? '') !== '' ? (string) $data['observacoes'] . ' ' : '')
                . '[Inventário' . ($controlaTamanho ? ' ' . $tamanho . '/' . $ca : '') . ': ' . $saldoBase . ' → ' . $saldoNovo . ']'
            );
            $tipo = 'Ajuste';
        } else {
            $qty = max(1, (int) ($data['quantidade'] ?? 1));
            $data['quantidade'] = $qty;
            if (in_array($tipo, SstEpiMovimentosRepository::TIPOS_SAIDA, true)) {
                if ($qty > $saldoAtual) {
                    return ['ok' => false, 'error' => 'Saldo insuficiente. Disponível: ' . $saldoAtual . '.'];
                }
                if ($ca !== '') {
                    if ($controlaTamanho) {
                        $saldoLote = $this->movRepo->getSaldoLote($epiId, $ca, $tamanho);
                        if ($qty > $saldoLote) {
                            return [
                                'ok' => false,
                                'error' => 'Saldo insuficiente para o CA ' . $ca . ' / tamanho ' . $tamanho
                                    . '. Disponível neste lote: ' . $saldoLote . '.',
                            ];
                        }
                    } else {
                        $saldoCa = $this->movRepo->getSaldoCa($epiId, $ca);
                        if ($qty > $saldoCa) {
                            return [
                                'ok' => false,
                                'error' => 'Saldo insuficiente para o CA ' . $ca . '. Disponível neste CA: ' . $saldoCa . '.',
                            ];
                        }
                    }
                }
            }

            if (SstEpiMovimentoHelper::tipoUsaValor($tipo)) {
                $unit = SstEpiMovimentoHelper::parseMoney($data['valor_unitario'] ?? null);
                // Entrada: obrigatório (já validado). Saída/Entrega/Devolução: média do CA.
                if ($tipo !== 'Entrada') {
                    $media = $this->movRepo->getCustoMedioCa($epiId, $ca !== '' ? $ca : null);
                    // Saída/Entrega sempre usam média (não editável no custo real de estoque)
                    if (in_array($tipo, ['Saída', 'Entrega'], true)) {
                        $unit = $media;
                    } elseif ($unit === null) {
                        $unit = $media;
                    }
                }
                $data['valor_unitario'] = $unit;
                $data['valor_total'] = ($unit !== null) ? round($unit * $qty, 2) : null;
            }
        }

        $serie = SstEpiMovimentoHelper::seriePorTipo($tipo);
        if ($serie !== null && $this->movRepo->hasSeriesTable()) {
            $doc = $this->movRepo->allocateNextDoc($serie);
            if ($doc === null) {
                return ['ok' => false, 'error' => 'Não foi possível gerar o número do documento (' . $serie . ').'];
            }
            $data['doc_serie'] = $doc['serie'];
            $data['doc_numero'] = $doc['numero'];
            $data['doc_codigo'] = $doc['codigo'];
        }

        $novoSaldo = $saldoAtual + SstEpiMovimentosRepository::impactoSaldo($tipo, (int) $data['quantidade']);
        $data['saldo_apos'] = max(0, $novoSaldo);

        $movId = $this->movRepo->create($data);
        if (!$movId) {
            return ['ok' => false, 'error' => 'Falha ao registrar movimentação.'];
        }

        $this->syncEstoqueCache($epiId);

        return [
            'ok' => true,
            'id' => (int) $movId,
            'doc_codigo' => (string) ($data['doc_codigo'] ?? ''),
        ];
    }

    /** Entrega automática ao assinar ficha. */
    public function registrarSaidaPorFicha(
        int $fichaId,
        int $epiId,
        int $quantidade,
        string $dataMovimento,
        ?string $caNumero = null,
        ?string $tamanho = null,
        ?int $fichaItemId = null
    ): void {
        if (!$this->movRepo->hasTable() || $fichaId <= 0 || $epiId <= 0 || $quantidade <= 0) {
            return;
        }
        $refId = ($fichaItemId !== null && $fichaItemId > 0)
            ? $fichaItemId
            : ($fichaId * 100000 + $epiId);
        if ($this->movRepo->existsReferencia('ficha_epi_item', $refId)) {
            return;
        }

        $ca = SstEpiMovimentoHelper::normalizeCa((string) ($caNumero ?? ''));
        $payload = [
            'adms_sst_epi_id' => $epiId,
            'tipo_movimento' => 'Entrega',
            'quantidade' => $quantidade,
            'data_movimento' => $dataMovimento,
            'referencia_tipo' => 'ficha_epi_item',
            'referencia_id' => $refId,
            'observacoes' => 'Entrega automática — ficha #' . $fichaId,
        ];
        if ($ca !== '') {
            $payload['ca_numero'] = $ca;
        }
        $tam = SstEpiTamanhoHelper::normalize((string) $tamanho);
        if ($tam !== '') {
            $payload['tamanho'] = $tam;
        }

        $this->registrarMovimento($payload);
    }

    public function syncEstoqueCache(int $epiId): void
    {
        if ($epiId <= 0) {
            return;
        }
        $saldo = $this->movRepo->hasTable()
            ? $this->movRepo->getSaldoCalculado($epiId)
            : (int) ($this->epiRepo->getById($epiId)['estoque_atual'] ?? 0);

        $this->epiRepo->updateEstoqueAtual($epiId, max(0, $saldo));
    }

    /**
     * Grava mínimos específicos por numeração (vazio = usa o padrão do cadastro).
     *
     * @param array<string, mixed> $post
     * @param array<string, mixed> $epiData
     */
    public function salvarMinimosDoPost(int $epiId, array $post, array $epiData): void
    {
        $grade = SstEpiTamanhoHelper::parseGrade((string) ($epiData['grade_tamanhos'] ?? ''));
        if ($epiId <= 0 || empty($epiData['controla_tamanho']) || $grade === []) {
            $this->minRepo->replaceForEpi($epiId, []);

            return;
        }
        $this->minRepo->replaceForEpi($epiId, SstEpiTamanhoHelper::parseMinimosPost($post, $grade));
    }

    /**
     * @param list<array<string, mixed>> $epis
     * @return list<array<string, mixed>>
     */
    public function anotarPosicao(array $epis): array
    {
        $ids = [];
        foreach ($epis as $ep) {
            $id = (int) ($ep['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        $mins = $this->minRepo->getMapsByEpiIds($ids);

        foreach ($epis as &$ep) {
            $id = (int) ($ep['id'] ?? 0);
            $padrao = (int) ($ep['estoque_minimo'] ?? 0);
            $controla = $this->epiControlaTamanho($ep);
            if ($this->movRepo->hasTable()) {
                $ep['estoque_calculado'] = $this->movRepo->getSaldoCalculado($id);
                $saldos = $controla ? $this->movRepo->getSaldoPorTamanhoPorEpi($id, false) : [];
            } else {
                $ep['estoque_calculado'] = (int) ($ep['estoque_atual'] ?? 0);
                $saldos = [];
            }
            $ep['minimos_tamanho'] = $mins[$id] ?? [];
            if ($controla) {
                $grade = SstEpiTamanhoHelper::parseGrade((string) ($ep['grade_tamanhos'] ?? ''));
                $linhas = SstEpiTamanhoHelper::linhasEstoquePorTamanho(
                    $grade,
                    $saldos,
                    $padrao,
                    $ep['minimos_tamanho']
                );
                $ep['saldos_tamanho'] = $linhas;
                $ep['estoque_baixo'] = false;
                foreach ($linhas as $linha) {
                    if (!empty($linha['estoque_baixo'])) {
                        $ep['estoque_baixo'] = true;
                        break;
                    }
                }
            } else {
                $ep['saldos_tamanho'] = [];
                $ep['estoque_baixo'] = $padrao > 0 && (int) $ep['estoque_calculado'] <= $padrao;
            }
        }
        unset($ep);

        return $epis;
    }

    /** @return list<array<string, mixed>> */
    public function listarPosicaoEstoque(array $filters = []): array
    {
        $repoFilters = $filters;
        unset($repoFilters['estoque_baixo']);
        $epis = $this->anotarPosicao($this->epiRepo->getAll(1, 500, $repoFilters));
        if (!empty($filters['estoque_baixo'])) {
            $epis = array_values(array_filter($epis, static fn (array $e): bool => !empty($e['estoque_baixo'])));
        }

        return $epis;
    }

    /**
     * Metadados para formulários (grade + lotes CA/tamanho).
     *
     * @param list<array<string, mixed>> $epis
     * @return array<int, array{controla_tamanho: bool, grade: list<string>, lotes: list<array<string, mixed>>, cas: list<array<string, mixed>>}>
     */
    public function metaEstoqueParaFormulario(array $epis): array
    {
        $out = [];
        foreach ($epis as $ep) {
            $id = (int) ($ep['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $controla = $this->epiControlaTamanho($ep);
            $out[$id] = [
                'controla_tamanho' => $controla,
                'grade' => SstEpiTamanhoHelper::parseGrade((string) ($ep['grade_tamanhos'] ?? '')),
                'lotes' => $this->movRepo->hasTable() ? $this->movRepo->getSaldoPorLotePorEpi($id, true) : [],
                'cas' => $this->movRepo->hasTable() ? $this->movRepo->getSaldoPorCaPorEpi($id, true) : [],
            ];
        }

        return $out;
    }

    /** @param array<string, mixed> $epi */
    private function epiControlaTamanho(array $epi): bool
    {
        return !empty($epi['controla_tamanho']);
    }
}
