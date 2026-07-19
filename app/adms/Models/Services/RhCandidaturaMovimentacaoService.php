<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\DomainEventOutboxRepository;
use App\adms\Models\Repository\RhCandidaturaHistoricoRepository;
use App\adms\Models\Repository\RhEntrevistaComunicacoesRepository;
use App\adms\Models\Repository\RhEntrevistaReagendamentosRepository;
use App\adms\Models\Repository\RhEntrevistasRepository;
use App\adms\Models\Repository\RhVagasRepository;
use Exception;

/**
 * Casos de uso de movimentação de candidatura (Fase 1+).
 * Fronteira transacional: entrevista, vínculo, histórico, outbox e intenção de comunicação.
 */
final class RhCandidaturaMovimentacaoService
{
    /**
     * Cria entrevista e, se não for provisória (pendente sem confirmação),
     * registra EntrevistaAgendada + intenção de e-mail (sem envio).
     *
     * @param array<string, mixed> $form
     * @throws Exception
     */
    public function criarEntrevista(array $form): int
    {
        $entrevistasRepo = new RhEntrevistasRepository();
        $pdo = $entrevistasRepo->getConnection();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $resultado = trim((string) ($form['resultado'] ?? ''));
            if ($resultado === '' || $resultado === 'pendente') {
                $form['resultado'] = 'agendado';
            }
            if (!empty($form['data_hora'])) {
                $form['data_hora'] = self::normalizeDateTime((string) $form['data_hora']);
            }

            $id = $entrevistasRepo->create($form);
            if (!$id) {
                throw new Exception('Erro ao cadastrar entrevista.');
            }

            $entrevista = $entrevistasRepo->getById((int) $id);
            if (!$entrevista) {
                throw new Exception('Entrevista criada não encontrada.');
            }

            $this->registrarComunicacaoAgendamento($entrevista);

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return (int) $id;
        } catch (Exception $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            GenerateLog::generateLog('error', 'Falha ao criar entrevista com outbox.', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza a entrevista e, se o resultado for aprovado/reprovado,
     * reflete no pipeline na mesma transação.
     * Alteração de data/hora em entrevista já agendada exige motivo e gera histórico.
     * Emite outbox + intenção de comunicação (sem SMTP).
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
            $antes = $entrevistasRepo->lockById($entrevistaId);
            if (!$antes) {
                throw new Exception('Entrevista não encontrada.');
            }

            $dataAnterior = self::normalizeDateTime((string) ($antes['data_hora'] ?? ''));
            $dataNova = self::normalizeDateTime((string) ($form['data_hora'] ?? $dataAnterior));
            $resultadoAntes = trim((string) ($antes['resultado'] ?? ''));
            $resultadoForm = trim((string) ($form['resultado'] ?? $resultadoAntes));
            $dataMudou = $dataAnterior !== '' && $dataNova !== '' && $dataAnterior !== $dataNova;
            $jaAgendada = !in_array($resultadoAntes, ['', 'pendente'], true);
            $motivoReagendamento = trim((string) ($form['motivo_reagendamento'] ?? ''));

            if ($dataMudou && $jaAgendada && $motivoReagendamento === '') {
                throw new Exception('Informe o motivo do reagendamento ao alterar a data/hora.');
            }

            unset($form['motivo_reagendamento']);
            if ($dataNova !== '') {
                $form['data_hora'] = $dataNova;
            }

            if (!$entrevistasRepo->update($entrevistaId, $form)) {
                throw new Exception('Erro ao atualizar entrevista.');
            }

            $depois = $entrevistasRepo->getById($entrevistaId);
            if (!$depois) {
                throw new Exception('Entrevista atualizada não encontrada.');
            }

            $reagendamentoId = null;
            if ($dataMudou && $jaAgendada) {
                $reagendamentoId = (new RhEntrevistaReagendamentosRepository())->append($entrevistaId, [
                    'data_hora_anterior' => $dataAnterior,
                    'data_hora_nova' => $dataNova,
                    'motivo' => $motivoReagendamento,
                    'reagendado_por' => (int) ($_SESSION['user_id'] ?? 0),
                ]);
                $this->registrarComunicacaoReagendamento($depois, $dataAnterior, $reagendamentoId);
            } else {
                $primeiroAgendamento = in_array($resultadoAntes, ['', 'pendente'], true)
                    && !in_array($resultadoForm, ['', 'pendente'], true);
                if ($primeiroAgendamento) {
                    $this->registrarComunicacaoAgendamento($depois);
                }
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

    /**
     * @param array<string, mixed> $entrevista
     */
    private function registrarComunicacaoAgendamento(array $entrevista): void
    {
        $entrevistaId = (int) ($entrevista['id'] ?? 0);
        if ($entrevistaId <= 0) {
            return;
        }

        $payload = [
            'entrevista_id' => $entrevistaId,
            'candidato_id' => (int) ($entrevista['rh_candidato_id'] ?? 0),
            'vaga_id' => !empty($entrevista['rh_vaga_id']) ? (int) $entrevista['rh_vaga_id'] : null,
            'data_hora' => (string) ($entrevista['data_hora'] ?? ''),
        ];

        $outboxId = (new DomainEventOutboxRepository())->enqueue([
            'event_name' => 'EntrevistaAgendada',
            'event_version' => 1,
            'aggregate_type' => 'rh_entrevista',
            'aggregate_id' => $entrevistaId,
            'idempotency_key' => "talentos.entrevista.{$entrevistaId}.agendada.v1",
            'correlation_id' => "talentos:entrevista:{$entrevistaId}",
            'payload' => $payload,
            'privacy_classification' => 'pessoal',
        ]);

        $tpl = RhEntrevistaEmailTemplateCatalog::render(
            RhEntrevistaEmailTemplateCatalog::KEY_AGENDADA,
            [
                'candidato_nome' => (string) ($entrevista['candidato_nome'] ?? ''),
                'vaga_titulo' => $entrevista['vaga_titulo'] ?? null,
                'data_hora' => (string) ($entrevista['data_hora'] ?? ''),
                'local' => $entrevista['local'] ?? null,
                'tipo' => $entrevista['tipo'] ?? null,
            ]
        );

        (new RhEntrevistaComunicacoesRepository())->record([
            'rh_entrevista_id' => $entrevistaId,
            'outbox_event_id' => $outboxId,
            'purpose' => RhEntrevistaComunicacoesRepository::PURPOSE_AGENDAMENTO,
            'template_key' => $tpl['key'],
            'template_version' => $tpl['version'],
            'recipient_name' => $entrevista['candidato_nome'] ?? null,
            'recipient_address' => $entrevista['candidato_email'] ?? null,
            'subject_snapshot' => $tpl['subject'],
            'body_html_snapshot' => $tpl['body_html'],
            'body_text_snapshot' => $tpl['body_text'],
        ]);
    }

    /**
     * @param array<string, mixed> $entrevista
     */
    private function registrarComunicacaoReagendamento(
        array $entrevista,
        string $dataAnterior,
        int $reagendamentoId
    ): void {
        $entrevistaId = (int) ($entrevista['id'] ?? 0);
        if ($entrevistaId <= 0 || $reagendamentoId <= 0) {
            return;
        }

        $payload = [
            'entrevista_id' => $entrevistaId,
            'candidato_id' => (int) ($entrevista['rh_candidato_id'] ?? 0),
            'vaga_id' => !empty($entrevista['rh_vaga_id']) ? (int) $entrevista['rh_vaga_id'] : null,
            'data_hora_anterior' => $dataAnterior,
            'data_hora_nova' => (string) ($entrevista['data_hora'] ?? ''),
            'reagendamento_id' => $reagendamentoId,
        ];

        $outboxId = (new DomainEventOutboxRepository())->enqueue([
            'event_name' => 'EntrevistaReagendada',
            'event_version' => 1,
            'aggregate_type' => 'rh_entrevista',
            'aggregate_id' => $entrevistaId,
            'idempotency_key' => "talentos.entrevista.{$entrevistaId}.reagendamento.{$reagendamentoId}.v1",
            'correlation_id' => "talentos:entrevista:{$entrevistaId}",
            'payload' => $payload,
            'privacy_classification' => 'pessoal',
        ]);

        $tpl = RhEntrevistaEmailTemplateCatalog::render(
            RhEntrevistaEmailTemplateCatalog::KEY_REAGENDADA,
            [
                'candidato_nome' => (string) ($entrevista['candidato_nome'] ?? ''),
                'vaga_titulo' => $entrevista['vaga_titulo'] ?? null,
                'data_hora' => (string) ($entrevista['data_hora'] ?? ''),
                'data_hora_anterior' => $dataAnterior,
                'local' => $entrevista['local'] ?? null,
                'tipo' => $entrevista['tipo'] ?? null,
            ]
        );

        (new RhEntrevistaComunicacoesRepository())->record([
            'rh_entrevista_id' => $entrevistaId,
            'rh_entrevista_reagendamento_id' => $reagendamentoId,
            'outbox_event_id' => $outboxId,
            'purpose' => RhEntrevistaComunicacoesRepository::PURPOSE_REAGENDAMENTO,
            'template_key' => $tpl['key'],
            'template_version' => $tpl['version'],
            'recipient_name' => $entrevista['candidato_nome'] ?? null,
            'recipient_address' => $entrevista['candidato_email'] ?? null,
            'subject_snapshot' => $tpl['subject'],
            'body_html_snapshot' => $tpl['body_html'],
            'body_text_snapshot' => $tpl['body_text'],
        ]);
    }

    private static function normalizeDateTime(string $value): string
    {
        $value = trim(str_replace('T', ' ', $value));
        if ($value === '') {
            return '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value) === 1) {
            return $value . ':00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $value) === 1) {
            return substr($value, 0, 19);
        }

        return $value;
    }
}
