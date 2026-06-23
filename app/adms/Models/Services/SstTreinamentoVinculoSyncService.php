<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\SstTreinamentosRepository;
use App\adms\Models\Repository\SstTreinamentoVinculosRepository;
use PDO;

/** Sincroniza vínculos obrigatórios de treinamento SST com a matriz. */
class SstTreinamentoVinculoSyncService extends DbConnection
{
    /**
     * @return array{criados: int, existentes: int}
     */
    public function syncForUser(int $userId): array
    {
        if ($userId <= 0) {
            return ['criados' => 0, 'existentes' => 0];
        }

        $resolver = new SstTreinamentosObrigatoriosResolver();
        $vinculoRepo = new SstTreinamentoVinculosRepository();
        $treinamentoRepo = new SstTreinamentosRepository();
        $statusService = new SstTreinamentoStatusService();

        $criados = 0;
        $existentes = 0;

        foreach ($resolver->resolveForUser($userId) as $row) {
            $treinamentoId = (int) ($row['adms_sst_treinamento_id'] ?? 0);
            if ($treinamentoId <= 0) {
                continue;
            }

            $existente = $vinculoRepo->getByUserAndTreinamento($userId, $treinamentoId);
            if ($existente) {
                $existentes++;
                continue;
            }

            $treinamento = $treinamentoRepo->getById($treinamentoId);
            $motivo = $statusService->hasConclusaoAnterior($userId, $treinamentoId) ? 'reciclagem' : 'primeiro';
            $dataLimite = null;
            if ($motivo === 'primeiro' && !empty($treinamento['prazo_primeiro_dias'])) {
                $dataLimite = (new \DateTimeImmutable('today'))
                    ->modify('+' . (int) $treinamento['prazo_primeiro_dias'] . ' days')
                    ->format('Y-m-d');
            }

            $novoId = $vinculoRepo->create([
                'adms_user_id' => $userId,
                'adms_sst_treinamento_id' => $treinamentoId,
                'motivo' => $motivo,
                'data_limite_primeiro' => $dataLimite,
                'status' => 'pendente',
            ]);
            if ($novoId) {
                $criados++;
            }
        }

        $statusService->recalculateAll($userId);
        SstPendenciasService::invalidateDashboardCache();

        return ['criados' => $criados, 'existentes' => $existentes];
    }

    /**
     * @return array{usuarios: int, criados: int, existentes: int}
     */
    public function syncForAllActiveUsers(): array
    {
        $sql = "SELECT u.id FROM adms_users u
                WHERE u.status = 'Ativo' AND (u.data_desligamento IS NULL)
                ORDER BY u.id";
        $stmt = $this->getConnection()->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $totalCriados = 0;
        $totalExistentes = 0;
        foreach ($users as $user) {
            $uid = (int) ($user['id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            $result = $this->syncForUser($uid);
            $totalCriados += $result['criados'];
            $totalExistentes += $result['existentes'];
        }

        return [
            'usuarios' => count($users),
            'criados' => $totalCriados,
            'existentes' => $totalExistentes,
        ];
    }
}
