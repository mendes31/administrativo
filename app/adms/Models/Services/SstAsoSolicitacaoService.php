<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SstAsoStatusHelper;
use App\adms\Helpers\SstCategoriaAsoHelper;
use App\adms\Models\Repository\SstAsoExamesRepository;
use App\adms\Models\Repository\SstAsosRepository;

/**
 * Abre solicitação de ASO (aguardando exames) a partir do pacote sugerido.
 */
class SstAsoSolicitacaoService
{
    /**
     * Gera solicitações ASO (status aguardando exames) para eventos pendentes pelos vínculos.
     *
     * @return array{criados: int, ids: list<int>}
     */
    public function sincronizarSolicitacoesPendentes(?int $onlyUserId = null): array
    {
        $pendSvc = new SstPendenciasService();
        $userIds = $onlyUserId !== null && $onlyUserId > 0
            ? [$onlyUserId]
            : $pendSvc->getUserIdsAtivosComObrigacaoExame();

        $situacoesGerar = [
            'aso_evento_sem_registro',
            'aso_evento_vencido',
            'aso_evento_a_vencer',
        ];

        $ids = [];
        foreach ($userIds as $uid) {
            foreach ($pendSvc->getPendenciasAsoEventoPorUsuario($uid) as $pendencia) {
                $sit = (string) ($pendencia['situacao'] ?? '');
                if ($sit === 'aso_aguardando_resultados' || !in_array($sit, $situacoesGerar, true)) {
                    continue;
                }
                $categoria = (string) ($pendencia['categoria_aso'] ?? '');
                if ($categoria === '') {
                    continue;
                }
                try {
                    $repo = new SstAsosRepository();
                    if ($repo->findAguardando($uid, $categoria) !== null) {
                        continue;
                    }
                    $asoId = $this->abrirFromPacote($uid, $categoria);
                    if ($asoId > 0) {
                        $ids[] = $asoId;
                    }
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        if ($ids !== []) {
            SstPendenciasService::invalidateDashboardCache();
        }

        return ['criados' => count($ids), 'ids' => $ids];
    }

    /**
     * Cria ASO aguardando exames com complementares do pacote (obrigatórios + recomendados).
     *
     * @return int ID do ASO criado
     */
    public function abrirFromPacote(int $userId, string $categoriaAso): int
    {
        if ($userId <= 0 || !SstCategoriaAsoHelper::isValid($categoriaAso)) {
            throw new \InvalidArgumentException('Colaborador ou categoria ASO inválidos.');
        }

        $repo = new SstAsosRepository();
        $existente = $repo->findAguardando($userId, $categoriaAso);
        if ($existente !== null) {
            return (int) $existente['id'];
        }

        $pacote = (new SstExamesObrigatoriosResolver())->resolvePacoteCompleto($userId, $categoriaAso);
        $complementares = $this->montarLinhasComplementares($pacote);

        $asoId = $repo->create([
            'adms_user_id' => $userId,
            'tipo' => $categoriaAso,
            'status' => SstAsoStatusHelper::AGUARDANDO_EXAMES,
            'data_realizacao' => null,
            'data_validade' => null,
            'resultado' => null,
        ]);

        if (!$asoId) {
            throw new \RuntimeException('Não foi possível criar o ASO.');
        }

        (new SstAsoExamesRepository())->syncForAso((int) $asoId, $complementares);
        SstPendenciasService::invalidateDashboardCache();

        return (int) $asoId;
    }

    /**
     * @param array{obrigatorios: list<array<string, mixed>>, recomendados: list<array<string, mixed>>} $pacote
     * @return list<array<string, mixed>>
     */
    private function montarLinhasComplementares(array $pacote): array
    {
        $linhas = [];
        $vistos = [];

        foreach ($pacote['obrigatorios'] ?? [] as $regra) {
            $exameId = (int) ($regra['adms_sst_exame_id'] ?? 0);
            if ($exameId <= 0 || isset($vistos[$exameId])) {
                continue;
            }
            $vistos[$exameId] = true;
            $linhas[] = [
                'adms_sst_exame_id' => $exameId,
                'exigencia' => 'obrigatorio',
                'data_realizacao' => null,
                'resultado' => null,
            ];
        }

        foreach ($pacote['recomendados'] ?? [] as $regra) {
            $exameId = (int) ($regra['adms_sst_exame_id'] ?? 0);
            if ($exameId <= 0 || isset($vistos[$exameId])) {
                continue;
            }
            $vistos[$exameId] = true;
            $linhas[] = [
                'adms_sst_exame_id' => $exameId,
                'exigencia' => 'recomendado',
                'data_realizacao' => null,
                'resultado' => null,
            ];
        }

        return $linhas;
    }
}
