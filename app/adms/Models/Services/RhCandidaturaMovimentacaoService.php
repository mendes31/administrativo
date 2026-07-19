<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhCandidaturaHistoricoRepository;
use App\adms\Models\Repository\RhEntrevistasRepository;
use App\adms\Models\Repository\RhVagasRepository;
use Exception;

/**
 * Casos de uso de movimentação de candidatura (Fase 1).
 * Garante fronteira transacional única entre entrevista, vínculo e histórico.
 */
final class RhCandidaturaMovimentacaoService
{
    /**
     * Atualiza a entrevista e, se o resultado for aprovado/reprovado,
     * reflete no pipeline na mesma transação.
     *
     * @param array<string, mixed> $form
     * @throws Exception
     */
    public function atualizarEntrevistaComReflexoPipeline(int $entrevistaId, array $form): void
    {
        if ($entrevistaId <= 0) {
            throw new Exception('Entrevista inválida.');
        }

        $entrevistasRepo = new RhEntrevistasRepository();
        $vagasRepo = new RhVagasRepository();
        $pdo = $entrevistasRepo->getConnection();

        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $antes = $entrevistasRepo->getById($entrevistaId);
            if (!$antes) {
                throw new Exception('Entrevista não encontrada.');
            }

            if (!$entrevistasRepo->update($entrevistaId, $form)) {
                throw new Exception('Erro ao atualizar entrevista.');
            }

            $resultado = trim((string) ($form['resultado'] ?? ''));
            $candidatoId = (int) ($antes['rh_candidato_id'] ?? 0);
            $vagaId = !empty($form['rh_vaga_id'])
                ? (int) $form['rh_vaga_id']
                : (int) ($antes['rh_vaga_id'] ?? 0);

            if (
                $candidatoId > 0
                && $vagaId > 0
                && in_array($resultado, ['aprovado', 'reprovado'], true)
            ) {
                $vagasRepo->atualizarStatusVinculo(
                    $vagaId,
                    $candidatoId,
                    $resultado,
                    null,
                    RhCandidaturaHistoricoRepository::ORIGEM_ENTREVISTA,
                    $entrevistaId,
                    RhCandidaturaMotivoCatalog::forEntrevistaResultado($resultado)
                );
            }

            if ($ownsTransaction) {
                $pdo->commit();
            }
        } catch (Exception $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            GenerateLog::generateLog('error', 'Falha na movimentação transacional entrevista/pipeline.', [
                'entrevista_id' => $entrevistaId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
