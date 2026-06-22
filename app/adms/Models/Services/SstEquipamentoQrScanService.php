<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\SstEquipamentosRepository;
use App\adms\Models\Repository\SstEquipamentoVistoriasRepository;

/**
 * Contexto da leitura de QR: equipamento + vistoria aberta (sem redirecionar nem gerar automaticamente).
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
     *   vistoria?: array<string, mixed>
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

        if (($equipamento['status'] ?? '') !== 'Ativo') {
            return [
                'ok' => true,
                'code' => 'inactive',
                'message' => 'Equipamento inativo ou baixado — vistoria não permitida.',
                'equipamento' => $equipamento,
            ];
        }

        if (!$this->userCanExecuteOnEquipamento($equipamento, $userId)) {
            return [
                'ok' => true,
                'code' => 'forbidden',
                'message' => 'Você não é o responsável por este equipamento. Os dados abaixo são apenas para consulta.',
                'equipamento' => $equipamento,
            ];
        }

        $equipamentoId = (int) $equipamento['id'];
        $vistoria = $this->vistoriasRepo->findOpenForEquipamento($equipamentoId);

        if ($vistoria) {
            return [
                'ok' => true,
                'code' => 'ready',
                'message' => 'Vistoria pendente localizada. Confira os dados e inicie quando estiver no local do equipamento.',
                'equipamento' => $equipamento,
                'vistoria' => $vistoria,
            ];
        }

        $competenciaAtual = (new \DateTimeImmutable('today'))->format('Y-m');
        $vistoriaMes = $this->vistoriasRepo->getByEquipamentoCompetencia($equipamentoId, $competenciaAtual);
        if ($vistoriaMes && ($vistoriaMes['status'] ?? '') === 'Concluída') {
            $loaded = $this->vistoriasRepo->getById((int) $vistoriaMes['id']) ?? $vistoriaMes;

            return [
                'ok' => true,
                'code' => 'completed',
                'message' => "A vistoria de {$competenciaAtual} já foi concluída para este equipamento.",
                'equipamento' => $equipamento,
                'vistoria' => $loaded,
            ];
        }

        if ($vistoriaMes) {
            $loaded = $this->vistoriasRepo->getById((int) $vistoriaMes['id']) ?? $vistoriaMes;

            return [
                'ok' => true,
                'code' => 'ready',
                'message' => 'Vistoria localizada para a competência atual.',
                'equipamento' => $equipamento,
                'vistoria' => $loaded,
            ];
        }

        return [
            'ok' => true,
            'code' => 'no_open',
            'message' => 'Não há vistoria aberta para este equipamento no momento. Solicite ao SESMT a geração da vistoria.',
            'equipamento' => $equipamento,
        ];
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
