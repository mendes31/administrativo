<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\PerformanceCalibrationsRepository;
use App\adms\Models\Repository\PerformanceCyclesRepository;

/**
 * Regras de sessão de calibração.
 */
class PerformanceCalibrationService
{
    public const STATUSES = ['draft', 'open', 'locked'];

    public function __construct(
        private readonly ?PerformanceCalibrationsRepository $repository = null,
        private readonly ?PerformanceCyclesRepository $cyclesRepository = null
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, error?: string, id?: int, data?: array<string, mixed>}
     */
    public function create(array $input, int $createdBy): array
    {
        $cycleId = (int) ($input['performance_cycle_id'] ?? 0);
        if ($cycleId <= 0) {
            return ['ok' => false, 'error' => 'Selecione o ciclo.'];
        }

        $cycle = $this->cycles()->getById($cycleId);
        if (!$cycle) {
            return ['ok' => false, 'error' => 'Ciclo não encontrado.'];
        }
        if (($cycle['status'] ?? '') === 'closed') {
            return ['ok' => false, 'error' => 'Não é possível abrir calibração em ciclo fechado.'];
        }

        if ($this->repo()->getByCycleId($cycleId)) {
            return ['ok' => false, 'error' => 'Já existe calibração para este ciclo.'];
        }

        $status = (string) ($input['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'open'], true)) {
            $status = 'draft';
        }

        $id = $this->repo()->create([
            'performance_cycle_id' => $cycleId,
            'status' => $status,
            'session_notes' => trim((string) ($input['session_notes'] ?? '')) ?: null,
            'created_by' => $createdBy,
        ]);

        if ($id <= 0) {
            return ['ok' => false, 'error' => 'Erro ao criar calibração.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, error?: string, id?: int}
     */
    public function update(int $id, array $input, int $actorId): array
    {
        $current = $this->repo()->getById($id);
        if (!$current) {
            return ['ok' => false, 'error' => 'Calibração não encontrada.'];
        }

        $status = (string) ($input['status'] ?? $current['status']);
        if (!in_array($status, self::STATUSES, true)) {
            return ['ok' => false, 'error' => 'Status inválido.'];
        }

        if (($current['status'] ?? '') === 'locked') {
            return ['ok' => false, 'error' => 'Calibração travada (somente leitura).'];
        }

        if (($current['status'] ?? '') === 'locked' && $status !== 'locked') {
            return ['ok' => false, 'error' => 'Calibração travada não pode reabrir neste incremento.'];
        }

        $payload = [
            'session_notes' => trim((string) ($input['session_notes'] ?? '')) ?: null,
            'status' => $status,
        ];

        if ($status === 'locked') {
            $payload['locked_at'] = date('Y-m-d H:i:s');
            $payload['locked_by'] = $actorId;
        }

        if (!$this->repo()->update($id, $payload)) {
            return ['ok' => false, 'error' => 'Erro ao atualizar calibração.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    private function repo(): PerformanceCalibrationsRepository
    {
        return $this->repository ?? new PerformanceCalibrationsRepository();
    }

    private function cycles(): PerformanceCyclesRepository
    {
        return $this->cyclesRepository ?? new PerformanceCyclesRepository();
    }
}
