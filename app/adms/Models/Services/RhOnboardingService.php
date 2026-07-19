<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhOnboardingRepository;
use Exception;

/**
 * Onboarding pós-conversão (Expand Fase 4).
 */
final class RhOnboardingService
{
    /**
     * @return array{plano_id: int}
     */
    public function criarAPartirDaConversao(
        int $conversaoId,
        int $userId,
        int $candidatoId,
        int $actorId
    ): array {
        $repo = new RhOnboardingRepository();
        if ($repo->getPlanoByConversaoId($conversaoId) !== null) {
            throw new Exception('Já existe onboarding para esta conversão.');
        }

        $limite = (new \DateTimeImmutable('today'))->modify('+30 days')->format('Y-m-d');
        $planoId = $repo->createPlano([
            'rh_conversao_id' => $conversaoId,
            'adms_user_id' => $userId,
            'rh_candidato_id' => $candidatoId > 0 ? $candidatoId : null,
            'status' => RhOnboardingRepository::STATUS_EM_ANDAMENTO,
            'data_inicio' => date('Y-m-d'),
            'data_limite' => $limite,
            'created_by_user_id' => $actorId > 0 ? $actorId : null,
        ]);
        if (!$planoId) {
            throw new Exception('Não foi possível criar o plano de onboarding.');
        }
        if (!$repo->seedItens($planoId, RhOnboardingItemCatalog::defaults())) {
            throw new Exception('Não foi possível gerar os itens de onboarding.');
        }

        return ['plano_id' => $planoId];
    }

    public function atualizarItem(
        int $planoId,
        int $itemId,
        string $status,
        ?string $observacoes,
        int $actorId
    ): void {
        $repo = new RhOnboardingRepository();
        $plano = $repo->getPlanoById($planoId);
        if ($plano === null) {
            throw new Exception('Plano de onboarding não encontrado.');
        }
        if (($plano['status'] ?? '') === RhOnboardingRepository::STATUS_CANCELADO) {
            throw new Exception('Plano cancelado não pode ser alterado.');
        }

        if (!$repo->updateItemStatus(
            $itemId,
            $planoId,
            $status,
            $observacoes,
            $actorId > 0 ? $actorId : null
        )) {
            throw new Exception('Não foi possível atualizar o item.');
        }

        $this->recalcularStatusPlano($planoId);
    }

    public function cancelarPlano(int $planoId): void
    {
        $repo = new RhOnboardingRepository();
        $plano = $repo->getPlanoById($planoId);
        if ($plano === null) {
            throw new Exception('Plano de onboarding não encontrado.');
        }
        if (($plano['status'] ?? '') === RhOnboardingRepository::STATUS_CONCLUIDO) {
            throw new Exception('Plano já concluído não pode ser cancelado.');
        }
        if (!$repo->updatePlanoStatus($planoId, RhOnboardingRepository::STATUS_CANCELADO)) {
            throw new Exception('Falha ao cancelar o plano.');
        }
    }

    private function recalcularStatusPlano(int $planoId): void
    {
        $repo = new RhOnboardingRepository();
        $plano = $repo->getPlanoById($planoId);
        if ($plano === null || ($plano['status'] ?? '') === RhOnboardingRepository::STATUS_CANCELADO) {
            return;
        }

        $itens = $repo->listItens($planoId);
        $obrigatoriosOk = true;
        foreach ($itens as $item) {
            if (empty($item['obrigatorio'])) {
                continue;
            }
            $st = (string) ($item['status'] ?? '');
            if (!in_array($st, [
                RhOnboardingRepository::ITEM_CONCLUIDO,
                RhOnboardingRepository::ITEM_DISPENSADO,
            ], true)) {
                $obrigatoriosOk = false;
                break;
            }
        }

        $novo = $obrigatoriosOk
            ? RhOnboardingRepository::STATUS_CONCLUIDO
            : RhOnboardingRepository::STATUS_EM_ANDAMENTO;

        if (($plano['status'] ?? '') !== $novo) {
            $repo->updatePlanoStatus($planoId, $novo);
            GenerateLog::generateLog('info', 'Status do onboarding recalculado.', [
                'plano_id' => $planoId,
                'status' => $novo,
            ]);
        }
    }
}
