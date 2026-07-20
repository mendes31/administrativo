<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\HeadcountPlansRepository;

/**
 * Planejamento de quadro (headcount) — Fase 6 1º incremento.
 */
class HeadcountPlanService
{
    public const STATUSES = ['draft', 'active', 'closed'];

    public function __construct(
        private readonly ?HeadcountPlansRepository $plans = null,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, error?: string, id?: int}
     */
    public function create(array $input, int $createdBy): array
    {
        $departmentId = (int) ($input['department_id'] ?? 0);
        $positionRaw = $input['position_id'] ?? null;
        $positionId = ($positionRaw === null || $positionRaw === '' || (int) $positionRaw <= 0)
            ? null
            : (int) $positionRaw;
        $year = (int) ($input['period_year'] ?? date('Y'));
        $month = (int) ($input['period_month'] ?? date('n'));
        $planned = (int) ($input['planned_count'] ?? 0);

        if ($departmentId <= 0) {
            return ['ok' => false, 'error' => 'Departamento é obrigatório.'];
        }
        if ($year < 2000 || $year > 2100) {
            return ['ok' => false, 'error' => 'Ano inválido.'];
        }
        if ($month < 1 || $month > 12) {
            return ['ok' => false, 'error' => 'Mês inválido.'];
        }
        if ($planned < 0) {
            return ['ok' => false, 'error' => 'Quantidade planejada não pode ser negativa.'];
        }
        if ($this->plans()->findDuplicate($departmentId, $positionId, $year, $month)) {
            return ['ok' => false, 'error' => 'Já existe linha de quadro para este departamento/cargo/período.'];
        }

        $id = $this->plans()->create([
            'department_id' => $departmentId,
            'position_id' => $positionId,
            'period_year' => $year,
            'period_month' => $month,
            'planned_count' => $planned,
            'status' => 'draft',
            'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
            'created_by' => $createdBy,
        ]);
        if ($id <= 0) {
            return ['ok' => false, 'error' => 'Erro ao criar linha de quadro.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, error?: string, id?: int}
     */
    public function update(int $id, array $input): array
    {
        $current = $this->plans()->getById($id);
        if (!$current) {
            return ['ok' => false, 'error' => 'Linha de quadro não encontrada.'];
        }

        $status = (string) ($input['status'] ?? $current['status']);
        if (!in_array($status, self::STATUSES, true)) {
            return ['ok' => false, 'error' => 'Status inválido.'];
        }
        if (($current['status'] ?? '') === 'closed' && $status !== 'closed') {
            return ['ok' => false, 'error' => 'Linha fechada não reabre neste incremento.'];
        }

        $planned = array_key_exists('planned_count', $input)
            ? (int) $input['planned_count']
            : (int) $current['planned_count'];
        if ($planned < 0) {
            return ['ok' => false, 'error' => 'Quantidade planejada não pode ser negativa.'];
        }
        if (($current['status'] ?? '') === 'closed') {
            $planned = (int) $current['planned_count'];
        }

        if (!$this->plans()->update($id, [
            'planned_count' => $planned,
            'status' => $status,
            'notes' => array_key_exists('notes', $input)
                ? (trim((string) $input['notes']) ?: null)
                : $current['notes'],
        ])) {
            return ['ok' => false, 'error' => 'Erro ao atualizar linha de quadro.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    /**
     * @param array<string, mixed> $plan
     * @return array{actual_count: int, gap: int}
     */
    public function withActual(array $plan): array
    {
        $departmentId = (int) ($plan['department_id'] ?? 0);
        $positionId = isset($plan['position_id']) && $plan['position_id'] !== null && $plan['position_id'] !== ''
            ? (int) $plan['position_id']
            : null;
        $actual = $this->plans()->countActual($departmentId, $positionId);
        $planned = (int) ($plan['planned_count'] ?? 0);

        return [
            'actual_count' => $actual,
            'gap' => $planned - $actual,
        ];
    }

    private function plans(): HeadcountPlansRepository
    {
        return $this->plans ??= new HeadcountPlansRepository();
    }
}
