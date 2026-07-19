<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\DomainEventOutboxRepository;
use App\adms\Models\Repository\RhEntrevistaComunicacoesRepository;
use App\adms\Models\Repository\RhEntrevistaReagendamentosRepository;
use App\adms\Models\Repository\RhEntrevistasRepository;
use Exception;

/**
 * Reenvio manual de comunicação failed/blocked.
 * Cria nova intenção + outbox; preserva o registro original no histórico.
 */
final class RhEntrevistaComunicacaoReenvioService
{
    /**
     * @return array{new_comunicacao_id: int, entrevista_id: int}
     */
    public function reenviar(int $comunicacaoId, int $actorUserId): array
    {
        if ($comunicacaoId <= 0) {
            throw new Exception('Comunicação inválida.');
        }

        $comRepo = new RhEntrevistaComunicacoesRepository();
        $source = $comRepo->getById($comunicacaoId);
        if ($source === null) {
            throw new Exception('Comunicação não encontrada.');
        }

        $status = (string) ($source['status'] ?? '');
        if (!in_array($status, [
            RhEntrevistaComunicacoesRepository::STATUS_FAILED,
            RhEntrevistaComunicacoesRepository::STATUS_BLOCKED,
        ], true)) {
            throw new Exception(
                'Só é possível reenviar comunicações com status falhou ou bloqueada.'
            );
        }

        if ($comRepo->hasOpenResendFrom((int) $source['id'])) {
            throw new Exception(
                'Já existe um reenvio em andamento para esta comunicação. Aguarde o preflight/worker.'
            );
        }

        if (!$comRepo->supportsResendTracking()) {
            throw new Exception(
                'Reenvio indisponível neste ambiente: execute a migration de source_comunicacao_id.'
            );
        }

        $entrevistaId = (int) ($source['rh_entrevista_id'] ?? 0);
        $entrevista = (new RhEntrevistasRepository())->getById($entrevistaId);
        if ($entrevista === null) {
            throw new Exception('Entrevista vinculada não encontrada.');
        }

        if (!RhPermissionService::canManageEntrevista($entrevista)) {
            throw new Exception('Você não tem permissão para reenviar esta comunicação.');
        }

        $purpose = (string) ($source['purpose'] ?? '');
        $templateKey = RhEntrevistaEmailTemplateCatalog::KEY_AGENDADA;
        if ($purpose === RhEntrevistaComunicacoesRepository::PURPOSE_REAGENDAMENTO) {
            $templateKey = RhEntrevistaEmailTemplateCatalog::KEY_REAGENDADA;
        }

        $dataAnterior = $this->resolveDataHoraAnterior($entrevistaId, (int) ($source['rh_entrevista_reagendamento_id'] ?? 0));
        $reagendamentoId = (int) ($source['rh_entrevista_reagendamento_id'] ?? 0);

        $tpl = RhEntrevistaEmailTemplateCatalog::render($templateKey, [
            'candidato_nome' => (string) ($entrevista['candidato_nome'] ?? ''),
            'vaga_titulo' => $entrevista['vaga_titulo'] ?? null,
            'data_hora' => (string) ($entrevista['data_hora'] ?? ''),
            'data_hora_anterior' => $dataAnterior !== '' ? $dataAnterior : null,
            'local' => $entrevista['local'] ?? null,
            'tipo' => $entrevista['tipo'] ?? null,
        ]);

        $eventName = trim((string) ($source['event_name'] ?? ''));
        if ($eventName === '') {
            $eventName = $purpose === RhEntrevistaComunicacoesRepository::PURPOSE_REAGENDAMENTO
                ? 'EntrevistaReagendada'
                : 'EntrevistaAgendada';
        }

        $started = !$comRepo->inTransaction();
        if ($started) {
            $comRepo->beginTransaction();
        }

        try {
            if (!$comRepo->claimSourceForResend((int) $source['id'])) {
                throw new Exception(
                    'A comunicação mudou de status ou já possui reenvio em andamento.'
                );
            }

            $seq = $comRepo->countResendsFrom((int) $source['id']) + 1;
            $outboxId = (new DomainEventOutboxRepository())->enqueue([
                'event_name' => $eventName,
                'event_version' => 1,
                'aggregate_type' => 'rh_entrevista',
                'aggregate_id' => $entrevistaId,
                'idempotency_key' => sprintf(
                    'talentos.entrevista.%d.reenvio.%d.%d.v1',
                    $entrevistaId,
                    (int) $source['id'],
                    $seq
                ),
                'correlation_id' => "talentos:entrevista:{$entrevistaId}",
                'payload' => [
                    'entrevista_id' => $entrevistaId,
                    'candidato_id' => (int) ($entrevista['rh_candidato_id'] ?? 0),
                    'vaga_id' => !empty($entrevista['rh_vaga_id']) ? (int) $entrevista['rh_vaga_id'] : null,
                    'data_hora' => (string) ($entrevista['data_hora'] ?? ''),
                    'source_comunicacao_id' => (int) $source['id'],
                    'reenvio' => true,
                    'reenvio_seq' => $seq,
                    'actor_user_id' => $actorUserId,
                    'purpose' => $purpose,
                ],
                'privacy_classification' => 'pessoal',
            ]);

            $newId = $comRepo->record([
                'rh_entrevista_id' => $entrevistaId,
                'rh_entrevista_reagendamento_id' => $reagendamentoId > 0 ? $reagendamentoId : null,
                'outbox_event_id' => $outboxId,
                'source_comunicacao_id' => (int) $source['id'],
                'purpose' => $purpose !== ''
                    ? $purpose
                    : RhEntrevistaComunicacoesRepository::PURPOSE_AGENDAMENTO,
                'template_key' => $tpl['key'],
                'template_version' => $tpl['version'],
                'recipient_name' => $entrevista['candidato_nome'] ?? null,
                'recipient_address' => $entrevista['candidato_email'] ?? null,
                'subject_snapshot' => $tpl['subject'],
                'body_html_snapshot' => $tpl['body_html'],
                'body_text_snapshot' => $tpl['body_text'],
            ]);

            if ($started) {
                $comRepo->commit();
            }

            return [
                'new_comunicacao_id' => $newId,
                'entrevista_id' => $entrevistaId,
            ];
        } catch (\Throwable $e) {
            if ($started) {
                $comRepo->rollBack();
            }
            throw $e;
        }
    }

    private function resolveDataHoraAnterior(int $entrevistaId, int $reagendamentoId): string
    {
        if ($reagendamentoId <= 0 || $entrevistaId <= 0) {
            return '';
        }

        try {
            $rows = (new RhEntrevistaReagendamentosRepository())->listByEntrevista($entrevistaId);
            foreach ($rows as $row) {
                if ((int) ($row['id'] ?? 0) === $reagendamentoId) {
                    return (string) ($row['data_hora_anterior'] ?? '');
                }
            }
        } catch (\Throwable) {
            return '';
        }

        return '';
    }
}
