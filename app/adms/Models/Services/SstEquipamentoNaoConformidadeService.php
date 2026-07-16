<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\SstEquipamentoAcoesCorretivasRepository;
use App\adms\Models\Repository\SstEquipamentoNaoConformidadesRepository;
use App\adms\Models\Repository\SstEquipamentoVistoriasRepository;
use App\adms\Models\Repository\SstEquipamentosRepository;
use PDO;

/**
 * Gera NCs a partir de vistoria Não conforme e gerencia bloqueio do equipamento.
 * A vistoria permanece com resultado "Não conforme" mesmo após encerrar a NC.
 */
class SstEquipamentoNaoConformidadeService
{
    /**
     * Cria uma NC por item "Não conforme" da vistoria (idempotente por resposta).
     *
     * @return list<int> IDs das NCs criadas
     */
    public function createFromVistoria(int $vistoriaId): array
    {
        $vistoriaRepo = new SstEquipamentoVistoriasRepository();
        $vistoria = $vistoriaRepo->getById($vistoriaId);
        if (!$vistoria || ($vistoria['resultado'] ?? '') !== 'Não conforme') {
            return [];
        }

        $equipamentoId = (int) ($vistoria['adms_sst_equipamento_id'] ?? 0);
        if ($equipamentoId <= 0) {
            return [];
        }

        $ncRepo = new SstEquipamentoNaoConformidadesRepository();
        $created = [];
        foreach ($vistoriaRepo->getRespostas($vistoriaId) as $r) {
            if (($r['resposta'] ?? '') !== 'Não conforme') {
                continue;
            }
            $respId = (int) ($r['id'] ?? 0);
            if ($respId > 0 && $ncRepo->existsForResposta($respId)) {
                continue;
            }
            $newId = $ncRepo->create([
                'adms_sst_equipamento_vistoria_id' => $vistoriaId,
                'adms_sst_equipamento_id' => $equipamentoId,
                'adms_sst_equipamento_vistoria_resposta_id' => $respId > 0 ? $respId : null,
                'descricao' => (string) ($r['descricao_snapshot'] ?? 'Item não conforme'),
                'observacao' => $r['observacao'] ?? null,
                'status' => 'Aberta',
            ]);
            if ($newId) {
                $created[] = $newId;
            }
        }

        if ($created !== []) {
            $this->bloquearEquipamentoSeAtivo($equipamentoId);
        }

        return $created;
    }

    public function afterAcaoCriadaOuAtualizada(int $ncId): void
    {
        $ncRepo = new SstEquipamentoNaoConformidadesRepository();
        $nc = $ncRepo->getById($ncId);
        if (!$nc || !in_array($nc['status'] ?? '', ['Aberta', 'Em tratamento'], true)) {
            return;
        }
        $acoes = (new SstEquipamentoAcoesCorretivasRepository())->getByNaoConformidadeId($ncId);
        $temAberta = false;
        foreach ($acoes as $a) {
            if (in_array($a['status'] ?? '', ['Pendente', 'Em andamento'], true)) {
                $temAberta = true;
                break;
            }
        }
        if ($temAberta || $acoes !== []) {
            $ncRepo->markEmTratamento($ncId);
        }
    }

    /**
     * Encerra NC vinculando à AC concluída. Não altera o resultado da vistoria.
     */
    public function encerrarComAcao(int $ncId, int $acaoId, int $userId): array
    {
        $ncRepo = new SstEquipamentoNaoConformidadesRepository();
        $acRepo = new SstEquipamentoAcoesCorretivasRepository();
        $nc = $ncRepo->getById($ncId);
        $acao = $acRepo->getById($acaoId);

        if (!$nc || !$acao) {
            return ['ok' => false, 'message' => 'NC ou ação corretiva não encontrada.'];
        }
        if ((int) ($acao['adms_sst_equipamento_nao_conformidade_id'] ?? 0) !== $ncId) {
            return ['ok' => false, 'message' => 'A ação corretiva não pertence a esta NC.'];
        }
        if (($acao['status'] ?? '') !== 'Concluído') {
            return ['ok' => false, 'message' => 'Só é possível encerrar a NC com uma ação corretiva Concluída.'];
        }
        if (!in_array($nc['status'] ?? '', ['Aberta', 'Em tratamento'], true)) {
            return ['ok' => false, 'message' => 'Esta NC já está encerrada ou cancelada.'];
        }

        if (!$ncRepo->encerrar($ncId, $userId, $acaoId)) {
            return ['ok' => false, 'message' => 'Não foi possível encerrar a NC.'];
        }

        $equipamentoId = (int) ($nc['adms_sst_equipamento_id'] ?? 0);
        $this->desbloquearSeSemNcAberta($equipamentoId);

        $codigoNc = (string) ($nc['codigo'] ?? '');
        $codigoAc = (string) ($acao['codigo'] ?? '');

        return [
            'ok' => true,
            'message' => "NC {$codigoNc} encerrada mediante ação corretiva {$codigoAc}. "
                . 'A vistoria permanece com resultado Não conforme (histórico).',
        ];
    }

    private function bloquearEquipamentoSeAtivo(int $equipamentoId): void
    {
        $repo = new SstEquipamentosRepository();
        $eq = $repo->getById($equipamentoId);
        if (!$eq || ($eq['status'] ?? '') !== 'Ativo') {
            return;
        }
        $this->updateEquipamentoStatus($equipamentoId, 'Bloqueado');
    }

    private function desbloquearSeSemNcAberta(int $equipamentoId): void
    {
        if ($equipamentoId <= 0) {
            return;
        }
        $abertas = (new SstEquipamentoNaoConformidadesRepository())->countAbertasByEquipamento($equipamentoId);
        if ($abertas > 0) {
            return;
        }
        $eq = (new SstEquipamentosRepository())->getById($equipamentoId);
        if (!$eq || ($eq['status'] ?? '') !== 'Bloqueado') {
            return;
        }
        $this->updateEquipamentoStatus($equipamentoId, 'Ativo');
    }

    private function updateEquipamentoStatus(int $equipamentoId, string $status): void
    {
        $conn = (new SstEquipamentosRepository())->getConnection();
        $stmt = $conn->prepare(
            'UPDATE adms_sst_equipamentos SET status = :st, updated_at = NOW() WHERE id = :id'
        );
        $stmt->bindValue(':st', $status);
        $stmt->bindValue(':id', $equipamentoId, PDO::PARAM_INT);
        $stmt->execute();
    }
}
