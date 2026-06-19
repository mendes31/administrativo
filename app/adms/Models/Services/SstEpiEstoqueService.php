<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SstEpiMovimentoHelper;
use App\adms\Models\Repository\SstEpiMovimentosRepository;
use App\adms\Models\Repository\SstEpisRepository;

/**
 * Registra movimentações de estoque EPI e mantém estoque_atual sincronizado (cache).
 */
class SstEpiEstoqueService
{
    private SstEpiMovimentosRepository $movRepo;

    private SstEpisRepository $epiRepo;

    public function __construct()
    {
        $this->movRepo = new SstEpiMovimentosRepository();
        $this->epiRepo = new SstEpisRepository();
    }

    /**
     * @param array<string, mixed> $data
     * @return array{ok:bool, id?:int, error?:string}
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

        if ($tipo === 'Ajuste') {
            $saldoNovo = (int) ($data['saldo_novo'] ?? -1);
            if ($saldoNovo < 0) {
                return ['ok' => false, 'error' => 'Informe o saldo contado para o ajuste.'];
            }
            $diff = $saldoNovo - $saldoAtual;
            if ($diff === 0) {
                return ['ok' => false, 'error' => 'Saldo informado é igual ao saldo atual. Nenhum ajuste necessário.'];
            }
            $data['quantidade'] = $diff;
            $data['tipo_movimento'] = 'Ajuste';
            $data['observacoes'] = trim(
                (($data['observacoes'] ?? '') !== '' ? (string) $data['observacoes'] . ' ' : '')
                . '[Inventário: ' . $saldoAtual . ' → ' . $saldoNovo . ']'
            );
            $tipo = 'Ajuste';
        } else {
            $qty = max(1, (int) ($data['quantidade'] ?? 1));
            $data['quantidade'] = $qty;
            if (in_array($tipo, SstEpiMovimentosRepository::TIPOS_SAIDA, true)) {
                if ($qty > $saldoAtual) {
                    return ['ok' => false, 'error' => 'Saldo insuficiente. Disponível: ' . $saldoAtual . '.'];
                }
                $ca = (string) ($data['ca_numero'] ?? '');
                if ($ca !== '') {
                    $saldoCa = $this->movRepo->getSaldoCa($epiId, $ca);
                    if ($qty > $saldoCa) {
                        return [
                            'ok' => false,
                            'error' => 'Saldo insuficiente para o CA ' . $ca . '. Disponível neste lote: ' . $saldoCa . '.',
                        ];
                    }
                }
            }
        }

        $novoSaldo = $saldoAtual + SstEpiMovimentosRepository::impactoSaldo($tipo, (int) $data['quantidade']);
        $data['saldo_apos'] = max(0, $novoSaldo);

        $movId = $this->movRepo->create($data);
        if (!$movId) {
            return ['ok' => false, 'error' => 'Falha ao registrar movimentação.'];
        }

        $this->syncEstoqueCache($epiId);

        return ['ok' => true, 'id' => (int) $movId];
    }

    /** Saídas automáticas ao assinar ficha de entrega. */
    public function registrarSaidaPorFicha(
        int $fichaId,
        int $epiId,
        int $quantidade,
        string $dataMovimento,
        ?string $caNumero = null
    ): void {
        if (!$this->movRepo->hasTable() || $fichaId <= 0 || $epiId <= 0 || $quantidade <= 0) {
            return;
        }
        $refId = $fichaId * 100000 + $epiId;
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
            'observacoes' => 'Saída automática — ficha de entrega #' . $fichaId,
        ];
        if ($ca !== '') {
            $payload['ca_numero'] = $ca;
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

    /** @return list<array<string, mixed>> */
    public function listarPosicaoEstoque(array $filters = []): array
    {
        $epis = $this->epiRepo->getAll(1, 500, $filters);
        foreach ($epis as &$ep) {
            $id = (int) ($ep['id'] ?? 0);
            if ($this->movRepo->hasTable()) {
                $calc = $this->movRepo->getSaldoCalculado($id);
                $ep['estoque_calculado'] = $calc;
            } else {
                $ep['estoque_calculado'] = (int) ($ep['estoque_atual'] ?? 0);
            }
            $min = (int) ($ep['estoque_minimo'] ?? 0);
            $ep['estoque_baixo'] = $min > 0 && (int) $ep['estoque_calculado'] <= $min;
        }
        unset($ep);

        if (!empty($filters['estoque_baixo'])) {
            $epis = array_values(array_filter($epis, static fn(array $e): bool => !empty($e['estoque_baixo'])));
        }

        return $epis;
    }
}
