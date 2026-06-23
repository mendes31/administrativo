<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\SstTreinamentoAplicacoesRepository;
use App\adms\Models\Repository\SstTreinamentosRepository;
use App\adms\Models\Repository\SstTreinamentoVinculosRepository;

/** Recalcula status dos vínculos de treinamento SST (alerta 30 dias). */
class SstTreinamentoStatusService
{
    public const ALERTA_DIAS = 30;

    /**
     * @param array<string, mixed> $vinculo
     */
    public function calculateStatus(array $vinculo): string
    {
        $today = new \DateTimeImmutable('today');

        if (!empty($vinculo['data_agendada']) && empty($vinculo['data_realizacao'])) {
            $agendada = new \DateTimeImmutable((string) $vinculo['data_agendada']);
            if ($agendada >= $today) {
                return 'agendado';
            }
        }

        if (empty($vinculo['data_realizacao'])) {
            return 'pendente';
        }

        if (!empty($vinculo['data_validade'])) {
            $validade = new \DateTimeImmutable((string) $vinculo['data_validade']);
            if ($validade < $today) {
                return 'vencido';
            }
            $limiteAlerta = $today->modify('+' . self::ALERTA_DIAS . ' days');
            if ($validade <= $limiteAlerta) {
                return 'proximo_vencimento';
            }

            return 'dentro_do_prazo';
        }

        return 'concluido';
    }

    public function recalculateVinculo(int $vinculoId): bool
    {
        $repo = new SstTreinamentoVinculosRepository();
        $vinculo = $repo->getById($vinculoId);
        if (!$vinculo) {
            return false;
        }
        $novoStatus = $this->calculateStatus($vinculo);
        if (($vinculo['status'] ?? '') === $novoStatus) {
            return true;
        }

        return $repo->update($vinculoId, array_merge($vinculo, ['status' => $novoStatus]));
    }

    public function recalculateAll(?int $userId = null): int
    {
        $repo = new SstTreinamentoVinculosRepository();
        $filters = $userId !== null && $userId > 0 ? ['adms_user_id' => $userId] : [];
        $page = 1;
        $updated = 0;
        do {
            $rows = $repo->getAll($page, 200, $filters);
            foreach ($rows as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $novo = $this->calculateStatus($row);
                if (($row['status'] ?? '') !== $novo) {
                    if ($repo->update($id, array_merge($row, ['status' => $novo]))) {
                        $updated++;
                    }
                }
            }
            $page++;
        } while (count($rows) === 200);

        return $updated;
    }

    public static function calcularDataValidade(?string $dataRealizacao, ?int $validadeMeses): ?string
    {
        if ($dataRealizacao === null || $dataRealizacao === '' || empty($validadeMeses) || $validadeMeses < 1) {
            return null;
        }
        $dt = new \DateTimeImmutable($dataRealizacao);

        return $dt->modify('+' . $validadeMeses . ' months')->format('Y-m-d');
    }

    public function hasConclusaoAnterior(int $userId, int $treinamentoId): bool
    {
        $vinculo = (new SstTreinamentoVinculosRepository())->getByUserAndTreinamento($userId, $treinamentoId);
        if (!$vinculo) {
            return false;
        }
        $aplicacoes = (new SstTreinamentoAplicacoesRepository())->getByVinculoId((int) $vinculo['id'], 1);

        return $aplicacoes !== [] || !empty($vinculo['data_realizacao']);
    }
}
