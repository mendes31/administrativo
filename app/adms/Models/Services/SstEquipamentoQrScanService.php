<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\SstEquipamentosRepository;
use App\adms\Models\Repository\SstEquipamentoVistoriasRepository;

/**
 * Resolve leitura de QR → vistoria executável.
 */
final class SstEquipamentoQrScanService
{
    public function __construct(
        private ?SstEquipamentosRepository $equipamentosRepo = null,
        private ?SstEquipamentoVistoriasRepository $vistoriasRepo = null,
        private ?SstEquipamentoVistoriaGeneratorService $generator = null,
    ) {
        $this->equipamentosRepo = $equipamentosRepo ?? new SstEquipamentosRepository();
        $this->vistoriasRepo = $vistoriasRepo ?? new SstEquipamentoVistoriasRepository();
        $this->generator = $generator ?? new SstEquipamentoVistoriaGeneratorService();
    }

    /**
     * @return array{ok: bool, code: string, message: string, vistoria_id?: int, equipamento?: array}
     */
    public function resolveForUser(string $qrToken, int $userId): array
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
                'ok' => false,
                'code' => 'inactive',
                'message' => 'Equipamento inativo ou baixado — vistoria não permitida.',
                'equipamento' => $equipamento,
            ];
        }

        if (!$this->userCanExecuteOnEquipamento($equipamento, $userId)) {
            return [
                'ok' => false,
                'code' => 'forbidden',
                'message' => 'Você não é o responsável por este equipamento. Solicite acesso ao SESMT.',
                'equipamento' => $equipamento,
            ];
        }

        $vistoria = $this->vistoriasRepo->findOpenForEquipamento((int) $equipamento['id']);
        if ($vistoria && ($vistoria['status'] ?? '') === 'Concluída') {
            $vistoria = null;
        }

        if (!$vistoria) {
            $gen = $this->generator->tryGenerateManual((int) $equipamento['id']);
            if (!empty($gen['vistoria_id'])) {
                $loaded = $this->vistoriasRepo->getById((int) $gen['vistoria_id']);
                if ($gen['code'] === 'completed' || ($loaded['status'] ?? '') === 'Concluída') {
                    return [
                        'ok' => true,
                        'code' => 'completed',
                        'message' => $gen['message'] ?? 'Vistoria desta competência já foi concluída.',
                        'vistoria_id' => (int) $gen['vistoria_id'],
                        'equipamento' => $equipamento,
                    ];
                }
                $vistoria = $loaded;
            } elseif (!$vistoria) {
                return [
                    'ok' => false,
                    'code' => $gen['code'] ?? 'no_vistoria',
                    'message' => $gen['message'] ?? 'Não há vistoria pendente para este equipamento.',
                    'equipamento' => $equipamento,
                ];
            }
        }

        if (!$vistoria) {
            return [
                'ok' => false,
                'code' => 'no_vistoria',
                'message' => 'Não foi possível abrir uma vistoria para este equipamento.',
                'equipamento' => $equipamento,
            ];
        }

        return [
            'ok' => true,
            'code' => 'ready',
            'message' => 'Vistoria localizada.',
            'vistoria_id' => (int) $vistoria['id'],
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
