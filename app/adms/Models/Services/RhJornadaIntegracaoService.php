<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\DomainEventOutboxRepository;

/**
 * Fan-out jornada → LNT + outbox (Expand integração DP/SST).
 */
final class RhJornadaIntegracaoService
{
    /**
     * @param array<string, mixed> $ctx
     */
    public function aposAdmissao(int $userId, int $conversaoId, int $actorId, array $ctx = []): void
    {
        $this->safe('admissao', function () use ($userId, $conversaoId, $actorId, $ctx): void {
            (new TrainingLntEventService())->registerNovoColaborador(
                $userId,
                $actorId > 0 ? $actorId : null
            );

            (new DomainEventOutboxRepository())->enqueue([
                'event_name' => 'jornada.ColaboradorAdmitido',
                'event_version' => 1,
                'aggregate_type' => 'adms_users',
                'aggregate_id' => $userId,
                'idempotency_key' => "jornada.user.{$userId}.conversao.{$conversaoId}.v1",
                'correlation_id' => $ctx['correlation_id'] ?? ("conversao-{$conversaoId}"),
                'payload' => [
                    'adms_user_id' => $userId,
                    'rh_conversao_id' => $conversaoId,
                    'data_admissao' => $ctx['data_admissao'] ?? null,
                    'actor_user_id' => $actorId > 0 ? $actorId : null,
                ],
                'privacy_classification' => 'pessoal',
            ]);
        });
    }

    /**
     * @param array<string, mixed> $ctx
     */
    public function aposMovimentacao(
        int $userId,
        int $movimentacaoId,
        ?int $cargoAntes,
        ?int $cargoDepois,
        int $actorId,
        array $ctx = []
    ): void {
        $this->safe('movimentacao', function () use (
            $userId,
            $movimentacaoId,
            $cargoAntes,
            $cargoDepois,
            $actorId,
            $ctx
        ): void {
            if ($cargoAntes !== $cargoDepois) {
                (new TrainingLntEventService())->registerAlteracaoCargo(
                    $userId,
                    $cargoAntes,
                    $cargoDepois,
                    $actorId > 0 ? $actorId : null
                );
            }

            (new DomainEventOutboxRepository())->enqueue([
                'event_name' => 'jornada.LotacaoAlterada',
                'event_version' => 1,
                'aggregate_type' => 'rh_movimentacoes',
                'aggregate_id' => $movimentacaoId,
                'idempotency_key' => "jornada.movimentacao.{$movimentacaoId}.v1",
                'correlation_id' => $ctx['correlation_id'] ?? ("movimentacao-{$movimentacaoId}"),
                'payload' => [
                    'adms_user_id' => $userId,
                    'rh_movimentacao_id' => $movimentacaoId,
                    'tipo' => $ctx['tipo'] ?? null,
                    'departamento_id_depois' => $ctx['departamento_id_depois'] ?? null,
                    'cargo_id_antes' => $cargoAntes,
                    'cargo_id_depois' => $cargoDepois,
                    'gestor_id_depois' => $ctx['gestor_id_depois'] ?? null,
                    'data_vigencia' => $ctx['data_vigencia'] ?? null,
                    'actor_user_id' => $actorId > 0 ? $actorId : null,
                ],
                'privacy_classification' => 'interna',
            ]);
        });
    }

    /**
     * @param array<string, mixed> $ctx
     */
    public function aposDesligamento(int $userId, int $planoId, int $actorId, array $ctx = []): void
    {
        $this->safe('desligamento', function () use ($userId, $planoId, $actorId, $ctx): void {
            (new TrainingLntEventService())->registerColaboradorDesligado(
                $userId,
                $actorId > 0 ? $actorId : null
            );

            (new DomainEventOutboxRepository())->enqueue([
                'event_name' => 'jornada.ColaboradorDesligado',
                'event_version' => 1,
                'aggregate_type' => 'rh_offboarding_planos',
                'aggregate_id' => $planoId,
                'idempotency_key' => "jornada.offboarding.{$planoId}.v1",
                'correlation_id' => $ctx['correlation_id'] ?? ("offboarding-{$planoId}"),
                'payload' => [
                    'adms_user_id' => $userId,
                    'rh_offboarding_plano_id' => $planoId,
                    'data_desligamento' => $ctx['data_desligamento'] ?? null,
                    'tipo' => $ctx['tipo'] ?? null,
                    'actor_user_id' => $actorId > 0 ? $actorId : null,
                ],
                'privacy_classification' => 'pessoal',
            ]);
        });
    }

    /**
     * @param callable():void $fn
     */
    private function safe(string $contexto, callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            GenerateLog::generateLog('warning', 'Falha na integração jornada→DP/SST/LNT.', [
                'contexto' => $contexto,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
