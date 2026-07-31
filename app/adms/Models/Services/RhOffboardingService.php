<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\UserFormHelper;
use App\adms\Models\Repository\RhOffboardingRepository;
use App\adms\Models\Repository\UsersRepository;
use Exception;

/**
 * Offboarding (Expand Fase 4) — checklist + desligamento em adms_users.
 */
final class RhOffboardingService
{
    /**
     * @param array<string, mixed> $input
     * @return array{plano_id: int}
     */
    public function iniciar(array $input, int $actorId): array
    {
        $userId = (int) ($input['adms_user_id'] ?? 0);
        if ($userId <= 0) {
            throw new Exception('Informe o colaborador.');
        }

        $tipo = (string) ($input['tipo'] ?? '');
        if (!in_array($tipo, RhOffboardingRepository::TIPOS, true)) {
            throw new Exception('Tipo de offboarding inválido.');
        }

        $motivo = trim((string) ($input['motivo'] ?? ''));
        if ($motivo === '') {
            throw new Exception('Informe o motivo do desligamento.');
        }

        $tipoImpacto = UserFormHelper::normalizeTipoImpactoDesligamento(
            $input['tipo_impacto'] ?? 'nao_classificado'
        );

        $usersRepo = new UsersRepository();
        $user = $usersRepo->getUser($userId);
        if (!$user || !is_array($user)) {
            throw new Exception('Colaborador não encontrado.');
        }
        if (!empty($user['data_desligamento'])) {
            throw new Exception('Colaborador já possui data de desligamento.');
        }

        $repo = new RhOffboardingRepository();
        if ($repo->getPlanoEmAndamentoByUser($userId) !== null) {
            throw new Exception('Já existe um offboarding em andamento para este colaborador.');
        }

        $pdo = $repo->getConnection();
        $pdo->beginTransaction();

        try {
            $planoId = $repo->createPlano([
                'adms_user_id' => $userId,
                'tipo' => $tipo,
                'status' => RhOffboardingRepository::STATUS_EM_ANDAMENTO,
                'data_prevista' => trim((string) ($input['data_prevista'] ?? '')) ?: null,
                'motivo' => $motivo,
                'tipo_impacto' => $tipoImpacto,
                'observacoes' => trim((string) ($input['observacoes'] ?? '')) ?: null,
                'created_by_user_id' => $actorId > 0 ? $actorId : null,
            ]);
            if (!$planoId) {
                throw new Exception('Não foi possível criar o plano de offboarding.');
            }
            if (!$repo->seedItens($planoId, RhOffboardingItemCatalog::defaults())) {
                throw new Exception('Não foi possível gerar os itens de offboarding.');
            }

            $pdo->commit();

            return ['plano_id' => $planoId];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            GenerateLog::generateLog('error', 'Falha ao iniciar offboarding.', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e instanceof Exception ? $e : new Exception($e->getMessage(), 0, $e);
        }
    }

    public function atualizarItem(
        int $planoId,
        int $itemId,
        string $status,
        ?string $observacoes,
        int $actorId
    ): void {
        $repo = new RhOffboardingRepository();
        $plano = $repo->getPlanoById($planoId);
        if ($plano === null) {
            throw new Exception('Plano de offboarding não encontrado.');
        }
        if (($plano['status'] ?? '') !== RhOffboardingRepository::STATUS_EM_ANDAMENTO) {
            throw new Exception('Somente planos em andamento podem ser alterados.');
        }

        $itens = $repo->listItens($planoId);
        $item = null;
        foreach ($itens as $row) {
            if ((int) ($row['id'] ?? 0) === $itemId) {
                $item = $row;
                break;
            }
        }
        if ($item === null) {
            throw new Exception('Item de offboarding não encontrado.');
        }

        if (
            ($item['codigo'] ?? '') === 'revogar_acessos'
            && $status === RhOffboardingRepository::ITEM_CONCLUIDO
        ) {
            $ativos = (new \App\adms\Models\Repository\TiAcessoRepository())
                ->countAtivosByUser((int) $plano['adms_user_id']);
            if ($ativos > 0) {
                throw new Exception(
                    "Há {$ativos} acesso(s) TI ainda ativo(s). Revogue-os no mapa antes de concluir este item "
                    . '(ou marque como dispensado com observação).'
                );
            }
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
    }

    public function cancelar(int $planoId): void
    {
        $repo = new RhOffboardingRepository();
        $plano = $repo->getPlanoById($planoId);
        if ($plano === null) {
            throw new Exception('Plano de offboarding não encontrado.');
        }
        if (($plano['status'] ?? '') !== RhOffboardingRepository::STATUS_EM_ANDAMENTO) {
            throw new Exception('Somente planos em andamento podem ser cancelados.');
        }
        if (!$repo->updatePlanoStatus($planoId, RhOffboardingRepository::STATUS_CANCELADO)) {
            throw new Exception('Falha ao cancelar o plano.');
        }
    }

    /**
     * @return array{plano_id: int}
     */
    public function concluir(int $planoId, ?string $dataDesligamento, int $actorId): array
    {
        $repo = new RhOffboardingRepository();
        $plano = $repo->getPlanoById($planoId);
        if ($plano === null) {
            throw new Exception('Plano de offboarding não encontrado.');
        }
        if (($plano['status'] ?? '') !== RhOffboardingRepository::STATUS_EM_ANDAMENTO) {
            throw new Exception('Somente planos em andamento podem ser concluídos.');
        }
        if ($repo->obrigatoriosPendentes($planoId) > 0) {
            throw new Exception('Conclua ou dispense todos os itens obrigatórios antes de finalizar.');
        }

        $data = trim((string) ($dataDesligamento ?: ($plano['data_prevista'] ?? '') ?: date('Y-m-d')));
        if ($data === '') {
            throw new Exception('Informe a data de desligamento.');
        }

        $userId = (int) $plano['adms_user_id'];
        $usersRepo = new UsersRepository();
        $user = $usersRepo->getUser($userId);
        if (!$user || !is_array($user)) {
            throw new Exception('Colaborador não encontrado.');
        }
        if (!empty($user['data_desligamento'])) {
            throw new Exception('Colaborador já possui data de desligamento.');
        }

        $pdo = $repo->getConnection();
        $pdo->beginTransaction();

        try {
            if (!$repo->marcarConcluido($planoId, $data, $actorId > 0 ? $actorId : null)) {
                throw new Exception('Falha ao marcar o plano como concluído.');
            }
            if (!$repo->aplicarDesligamentoUsuario(
                $userId,
                $data,
                (string) $plano['motivo'],
                $plano['tipo_impacto'] !== null ? (string) $plano['tipo_impacto'] : null
            )) {
                throw new Exception('Falha ao aplicar o desligamento no usuário.');
            }

            $pdo->commit();

            (new RhIdentidadeSyncService())->tentarSincronizar($userId, 'offboarding');
            (new RhJornadaIntegracaoService())->aposDesligamento(
                $userId,
                $planoId,
                $actorId,
                [
                    'data_desligamento' => $data,
                    'tipo' => $plano['tipo'] ?? null,
                ]
            );

            return ['plano_id' => $planoId];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            GenerateLog::generateLog('error', 'Falha ao concluir offboarding.', [
                'plano_id' => $planoId,
                'error' => $e->getMessage(),
            ]);
            throw $e instanceof Exception ? $e : new Exception($e->getMessage(), 0, $e);
        }
    }
}
