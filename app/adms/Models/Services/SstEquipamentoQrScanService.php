<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SstEquipamentoRecargaHelper;
use App\adms\Models\Repository\SstEquipamentosRepository;
use App\adms\Models\Repository\SstEquipamentoVistoriasRepository;

/**
 * Contexto da leitura de QR: equipamento + status de vistoria/recarga (sem gerar vistoria automaticamente).
 */
final class SstEquipamentoQrScanService
{
    public function __construct(
        private ?SstEquipamentosRepository $equipamentosRepo = null,
        private ?SstEquipamentoVistoriasRepository $vistoriasRepo = null,
    ) {
        $this->equipamentosRepo = $equipamentosRepo ?? new SstEquipamentosRepository();
        $this->vistoriasRepo = $vistoriasRepo ?? new SstEquipamentoVistoriasRepository();
    }

    /**
     * @return array{
     *   ok: bool,
     *   code: string,
     *   message: string,
     *   equipamento?: array<string, mixed>,
     *   vistoria?: array<string, mixed>,
     *   recarga_status?: string|null,
     *   pendencias?: list<string>
     * }
     */
    public function getScanContext(string $qrToken, int $userId): array
    {
        $token = trim($qrToken);
        if ($token === '') {
            return ['ok' => false, 'code' => 'invalid', 'message' => 'QR Code inválido.'];
        }

        $equipamento = $this->equipamentosRepo->getByQrToken($token);
        if (!$equipamento) {
            return ['ok' => false, 'code' => 'not_found', 'message' => 'Equipamento não encontrado para este QR Code.'];
        }

        $recargaStatus = $this->resolveRecargaStatus($equipamento);
        $equipamentoId = (int) $equipamento['id'];
        $vistoriaAberta = $this->vistoriasRepo->findOpenForEquipamento($equipamentoId);

        if (($equipamento['status'] ?? '') !== 'Ativo') {
            return $this->withStatus([
                'ok' => true,
                'code' => 'inactive',
                'message' => 'Equipamento inativo ou baixado — vistoria não permitida.',
                'equipamento' => $equipamento,
                'vistoria' => $vistoriaAberta,
            ], $recargaStatus, $vistoriaAberta);
        }

        if (!$this->userCanExecuteOnEquipamento($equipamento, $userId)) {
            $vistoria = $vistoriaAberta;
            if (!$vistoria) {
                $competenciaAtual = (new \DateTimeImmutable('today'))->format('Y-m');
                $vistoriaMes = $this->vistoriasRepo->getByEquipamentoCompetencia($equipamentoId, $competenciaAtual);
                if ($vistoriaMes) {
                    $vistoria = $this->vistoriasRepo->getById((int) $vistoriaMes['id']) ?? $vistoriaMes;
                }
            }

            return $this->withStatus([
                'ok' => true,
                'code' => 'forbidden',
                'message' => 'Você não é o responsável por este equipamento. Os dados abaixo são apenas para consulta.',
                'equipamento' => $equipamento,
                'vistoria' => $vistoria,
            ], $recargaStatus, $vistoria);
        }

        $vistoria = $vistoriaAberta;

        if ($vistoria) {
            return $this->withStatus([
                'ok' => true,
                'code' => 'ready',
                'message' => 'Vistoria pendente localizada. Confira os dados e inicie quando estiver no local do equipamento.',
                'equipamento' => $equipamento,
                'vistoria' => $vistoria,
            ], $recargaStatus, $vistoria);
        }

        $competenciaAtual = (new \DateTimeImmutable('today'))->format('Y-m');
        $vistoriaMes = $this->vistoriasRepo->getByEquipamentoCompetencia($equipamentoId, $competenciaAtual);
        if ($vistoriaMes && ($vistoriaMes['status'] ?? '') === 'Concluída') {
            $loaded = $this->vistoriasRepo->getById((int) $vistoriaMes['id']) ?? $vistoriaMes;

            return $this->withStatus([
                'ok' => true,
                'code' => 'completed',
                'message' => "A vistoria de {$competenciaAtual} já foi concluída para este equipamento.",
                'equipamento' => $equipamento,
                'vistoria' => $loaded,
            ], $recargaStatus, $loaded);
        }

        if ($vistoriaMes) {
            $loaded = $this->vistoriasRepo->getById((int) $vistoriaMes['id']) ?? $vistoriaMes;

            return $this->withStatus([
                'ok' => true,
                'code' => 'ready',
                'message' => 'Vistoria localizada para a competência atual.',
                'equipamento' => $equipamento,
                'vistoria' => $loaded,
            ], $recargaStatus, $loaded);
        }

        return $this->withStatus([
            'ok' => true,
            'code' => 'no_open',
            'message' => 'Não há vistoria aberta para este equipamento no momento. Solicite ao SESMT a geração da vistoria.',
            'equipamento' => $equipamento,
        ], $recargaStatus, null);
    }

    /** @param array<string, mixed> $equipamento */
    private function resolveRecargaStatus(array $equipamento): ?string
    {
        $controla = (int) ($equipamento['controla_recarga'] ?? 0) === 1;

        return SstEquipamentoRecargaHelper::status(
            isset($equipamento['data_proxima_recarga']) ? (string) $equipamento['data_proxima_recarga'] : null,
            $controla
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed>|null $vistoria
     * @return array<string, mixed>
     */
    private function withStatus(array $payload, ?string $recargaStatus, ?array $vistoria): array
    {
        $payload['recarga_status'] = $recargaStatus;
        $pendencias = [];

        $vistoriaStatus = is_array($vistoria) ? (string) ($vistoria['status'] ?? '') : '';
        if ($vistoriaStatus !== '' && $vistoriaStatus !== 'Concluída') {
            $pendencias[] = $vistoriaStatus === 'Vencida' ? 'Vistoria vencida' : 'Vistoria pendente';
        } elseif (($payload['code'] ?? '') === 'no_open') {
            $pendencias[] = 'Sem vistoria aberta neste período';
        }

        if (in_array($recargaStatus, ['vencido', 'a_vencer', 'sem_data'], true)) {
            $pendencias[] = SstEquipamentoRecargaHelper::statusLabel($recargaStatus);
        }

        $payload['pendencias'] = $pendencias;

        return $payload;
    }

    /** @param array<string, mixed> $equipamento */
    public function userCanExecuteOnEquipamento(array $equipamento, int $userId): bool
    {
        $responsavelId = (int) ($equipamento['responsavel_adms_user_id'] ?? 0);
        if ($responsavelId <= 0) {
            return true;
        }

        return $responsavelId === $userId;
    }
}
