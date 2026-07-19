<?php

declare(strict_types=1);

// Reenvio FTP experiencia/movimentacoes (controllers ausentes no servidor).

namespace App\adms\Models\Services;

use App\adms\Models\Repository\RhPeriodosExperienciaRepository;
use App\adms\Models\Repository\UsersRepository;
use Exception;

/**
 * Período de experiência pós-conversão (Expand Fase 4).
 */
final class RhExperienciaService
{
    public const DIAS_PADRAO = 90;
    public const DIAS_PRORROGACAO_PADRAO = 45;

    /**
     * @return array{experiencia_id: int}
     */
    public function criarAPartirDaConversao(
        int $conversaoId,
        int $userId,
        int $candidatoId,
        ?int $onboardingPlanoId,
        int $actorId,
        ?string $dataAdmissao = null
    ): array {
        $repo = new RhPeriodosExperienciaRepository();
        if ($repo->getByConversaoId($conversaoId) !== null) {
            throw new Exception('Já existe período de experiência para esta conversão.');
        }

        $inicio = $dataAdmissao;
        if ($inicio === null || $inicio === '') {
            $user = (new UsersRepository())->getUser($userId);
            $inicio = is_array($user) ? trim((string) ($user['data_admissao'] ?? '')) : '';
        }
        if ($inicio === '') {
            $inicio = date('Y-m-d');
        }

        $fim = (new \DateTimeImmutable($inicio))
            ->modify('+' . self::DIAS_PADRAO . ' days')
            ->format('Y-m-d');

        $id = $repo->create([
            'rh_conversao_id' => $conversaoId,
            'adms_user_id' => $userId,
            'rh_candidato_id' => $candidatoId > 0 ? $candidatoId : null,
            'rh_onboarding_plano_id' => $onboardingPlanoId,
            'status' => RhPeriodosExperienciaRepository::STATUS_EM_ANDAMENTO,
            'data_inicio' => $inicio,
            'data_fim_prevista' => $fim,
            'created_by_user_id' => $actorId > 0 ? $actorId : null,
        ]);
        if (!$id) {
            throw new Exception('Não foi possível criar o período de experiência.');
        }

        return ['experiencia_id' => $id];
    }

    public function aprovar(int $id, ?string $observacoes, int $actorId): void
    {
        $this->avaliarFinal(
            $id,
            RhPeriodosExperienciaRepository::STATUS_APROVADO,
            'aprovado',
            $observacoes,
            $actorId
        );
    }

    public function reprovar(int $id, ?string $observacoes, int $actorId): void
    {
        $obs = trim((string) $observacoes);
        if ($obs === '') {
            throw new Exception('Informe o motivo da reprovação no período de experiência.');
        }
        $this->avaliarFinal(
            $id,
            RhPeriodosExperienciaRepository::STATUS_REPROVADO,
            'reprovado',
            $obs,
            $actorId
        );
    }

    public function prorrogar(int $id, ?string $observacoes, int $actorId, ?int $dias = null): void
    {
        $repo = new RhPeriodosExperienciaRepository();
        $periodo = $repo->getById($id);
        if ($periodo === null) {
            throw new Exception('Período de experiência não encontrado.');
        }
        $status = (string) ($periodo['status'] ?? '');
        if (!in_array($status, [
            RhPeriodosExperienciaRepository::STATUS_EM_ANDAMENTO,
            RhPeriodosExperienciaRepository::STATUS_PRORROGADO,
        ], true)) {
            throw new Exception('Este período não pode ser prorrogado.');
        }
        if ((int) ($periodo['dias_prorrogacao'] ?? 0) > 0) {
            throw new Exception('O período já foi prorrogado uma vez.');
        }

        $diasAdd = $dias !== null && $dias > 0 ? $dias : self::DIAS_PRORROGACAO_PADRAO;
        $novaFim = (new \DateTimeImmutable((string) $periodo['data_fim_prevista']))
            ->modify('+' . $diasAdd . ' days')
            ->format('Y-m-d');

        if (!$repo->registrarAvaliacao(
            $id,
            RhPeriodosExperienciaRepository::STATUS_PRORROGADO,
            'prorrogado',
            $observacoes,
            $actorId > 0 ? $actorId : null,
            $novaFim,
            $diasAdd
        )) {
            throw new Exception('Falha ao prorrogar o período.');
        }
    }

    public function cancelar(int $id): void
    {
        $repo = new RhPeriodosExperienciaRepository();
        $periodo = $repo->getById($id);
        if ($periodo === null) {
            throw new Exception('Período de experiência não encontrado.');
        }
        if (in_array((string) ($periodo['status'] ?? ''), [
            RhPeriodosExperienciaRepository::STATUS_APROVADO,
            RhPeriodosExperienciaRepository::STATUS_REPROVADO,
            RhPeriodosExperienciaRepository::STATUS_CANCELADO,
        ], true)) {
            throw new Exception('Período já finalizado.');
        }
        if (!$repo->cancelar($id)) {
            throw new Exception('Falha ao cancelar o período.');
        }
    }

    private function avaliarFinal(
        int $id,
        string $status,
        string $resultado,
        ?string $observacoes,
        int $actorId
    ): void {
        $repo = new RhPeriodosExperienciaRepository();
        $periodo = $repo->getById($id);
        if ($periodo === null) {
            throw new Exception('Período de experiência não encontrado.');
        }
        if (!in_array((string) ($periodo['status'] ?? ''), [
            RhPeriodosExperienciaRepository::STATUS_EM_ANDAMENTO,
            RhPeriodosExperienciaRepository::STATUS_PRORROGADO,
        ], true)) {
            throw new Exception('Este período não pode ser avaliado no status atual.');
        }

        if (!$repo->registrarAvaliacao(
            $id,
            $status,
            $resultado,
            $observacoes,
            $actorId > 0 ? $actorId : null
        )) {
            throw new Exception('Falha ao registrar a avaliação.');
        }
    }
}
