<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\PerformanceCyclesRepository;

/**
 * Regras de ciclo de desempenho (Fase 5 Expand).
 */
class PerformanceCycleService
{
    public const STATUSES = ['draft', 'open', 'closed'];

    public function __construct(
        private readonly ?PerformanceCyclesRepository $repository = null
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, error?: string, data?: array<string, mixed>}
     */
    public function validatePayload(array $input, ?array $current = null): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $year = (int) ($input['year'] ?? 0);
        $periodStart = trim((string) ($input['period_start'] ?? ''));
        $periodEnd = trim((string) ($input['period_end'] ?? ''));
        $status = (string) ($input['status'] ?? ($current['status'] ?? 'draft'));
        $description = trim((string) ($input['description'] ?? ''));

        if ($name === '') {
            return ['ok' => false, 'error' => 'Nome do ciclo é obrigatório.'];
        }
        if ($year < 2000 || $year > 2100) {
            return ['ok' => false, 'error' => 'Ano inválido.'];
        }
        if ($periodStart === '' || $periodEnd === '') {
            return ['ok' => false, 'error' => 'Período inicial e final são obrigatórios.'];
        }
        if (strtotime($periodStart) === false || strtotime($periodEnd) === false) {
            return ['ok' => false, 'error' => 'Datas do período inválidas.'];
        }
        if ($periodEnd < $periodStart) {
            return ['ok' => false, 'error' => 'A data final deve ser maior ou igual à inicial.'];
        }
        if (!in_array($status, self::STATUSES, true)) {
            return ['ok' => false, 'error' => 'Status inválido.'];
        }

        $currentStatus = $current['status'] ?? null;
        if ($currentStatus === 'closed' && $status !== 'closed') {
            return ['ok' => false, 'error' => 'Ciclo fechado não pode voltar para rascunho ou aberto.'];
        }

        return [
            'ok' => true,
            'data' => [
                'name' => $name,
                'year' => $year,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => $status,
                'description' => $description !== '' ? $description : null,
            ],
        ];
    }

    public function create(array $input, int $createdBy): array
    {
        $validated = $this->validatePayload($input);
        if (!$validated['ok']) {
            return $validated;
        }

        $data = $validated['data'];
        $data['created_by'] = $createdBy;
        $id = $this->repo()->create($data);

        if ($id <= 0) {
            return ['ok' => false, 'error' => 'Erro ao criar ciclo.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    public function update(int $id, array $input): array
    {
        $current = $this->repo()->getById($id);
        if (!$current) {
            return ['ok' => false, 'error' => 'Ciclo não encontrado.'];
        }

        $validated = $this->validatePayload($input, $current);
        if (!$validated['ok']) {
            return $validated;
        }

        if (!$this->repo()->update($id, $validated['data'])) {
            return ['ok' => false, 'error' => 'Erro ao atualizar ciclo.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    /**
     * Valida vínculo de meta a um ciclo (null permitido).
     */
    public function assertGoalMayLink(?int $cycleId): array
    {
        if ($cycleId === null || $cycleId <= 0) {
            return ['ok' => true, 'cycle_id' => null];
        }

        $cycle = $this->repo()->getById($cycleId);
        if (!$cycle) {
            return ['ok' => false, 'error' => 'Ciclo informado não existe.'];
        }
        if (($cycle['status'] ?? '') === 'closed') {
            return ['ok' => false, 'error' => 'Não é possível vincular meta a ciclo fechado.'];
        }

        return ['ok' => true, 'cycle_id' => $cycleId];
    }

    private function repo(): PerformanceCyclesRepository
    {
        return $this->repository ?? new PerformanceCyclesRepository();
    }
}
