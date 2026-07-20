<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\CompetenciesRepository;
use App\adms\Models\Repository\PdiActionsRepository;
use App\adms\Models\Repository\PdiCompetenciesRepository;
use App\adms\Models\Repository\PdiPlansRepository;
use App\adms\Models\Repository\TrainingsRepository;

/**
 * Regras de PDI operacional (Fase 5 Expand).
 */
class PdiPlanService
{
    public const PLAN_STATUSES = ['draft', 'active', 'completed', 'cancelled'];
    public const ACTION_TYPES = ['training', 'course', 'mentoring', 'project', 'reading', 'other'];
    public const ACTION_STATUSES = ['pending', 'in_progress', 'completed', 'cancelled'];
    public const PRIORITIES = ['low', 'medium', 'high'];
    public const COMPETENCY_TYPES = ['technical', 'behavioral', 'leadership'];

    public function __construct(
        private readonly ?PdiPlansRepository $plans = null,
        private readonly ?PdiActionsRepository $actions = null,
        private readonly ?PdiCompetenciesRepository $competencies = null,
        private readonly ?PerformanceCycleService $cycleService = null,
        private readonly ?CompetenciesRepository $catalogCompetencies = null,
        private readonly ?TrainingsRepository $trainings = null,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, error?: string, data?: array<string, mixed>}
     */
    public function validatePlanPayload(array $input): array
    {
        $userId = (int) ($input['user_id'] ?? 0);
        $title = trim((string) ($input['title'] ?? ''));
        $periodStart = trim((string) ($input['period_start'] ?? ''));
        $periodEnd = trim((string) ($input['period_end'] ?? ''));
        $status = (string) ($input['status'] ?? 'draft');
        $managerId = !empty($input['manager_id']) ? (int) $input['manager_id'] : null;
        $cycleId = !empty($input['performance_cycle_id']) ? (int) $input['performance_cycle_id'] : null;

        if ($userId <= 0) {
            return ['ok' => false, 'error' => 'Colaborador é obrigatório.'];
        }
        if ($title === '') {
            return ['ok' => false, 'error' => 'Título do PDI é obrigatório.'];
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
        if (!in_array($status, self::PLAN_STATUSES, true)) {
            return ['ok' => false, 'error' => 'Status do plano inválido.'];
        }

        $cycleCheck = $this->cycles()->assertMayLink($cycleId);
        if (!$cycleCheck['ok']) {
            return $cycleCheck;
        }

        return [
            'ok' => true,
            'data' => [
                'user_id' => $userId,
                'manager_id' => ($managerId !== null && $managerId > 0) ? $managerId : null,
                'title' => $title,
                'description' => trim((string) ($input['description'] ?? '')) ?: null,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => $status,
                'current_level' => trim((string) ($input['current_level'] ?? '')) ?: null,
                'target_level' => trim((string) ($input['target_level'] ?? '')) ?: null,
                'career_goal' => trim((string) ($input['career_goal'] ?? '')) ?: null,
                'performance_cycle_id' => $cycleCheck['cycle_id'],
            ],
        ];
    }

    public function create(array $input, int $createdBy): array
    {
        $validated = $this->validatePlanPayload($input);
        if (!$validated['ok']) {
            return $validated;
        }

        $data = $validated['data'];
        $data['created_by'] = $createdBy;
        $id = $this->plansRepo()->create($data);

        if ($id <= 0) {
            return ['ok' => false, 'error' => 'Erro ao criar PDI.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    public function update(int $id, array $input): array
    {
        $current = $this->plansRepo()->getById($id);
        if (!$current) {
            return ['ok' => false, 'error' => 'PDI não encontrado.'];
        }

        $validated = $this->validatePlanPayload($input);
        if (!$validated['ok']) {
            return $validated;
        }

        $newCycleId = $validated['data']['performance_cycle_id'];
        $oldCycleId = !empty($current['performance_cycle_id']) ? (int) $current['performance_cycle_id'] : null;
        if ($newCycleId !== $oldCycleId) {
            $cycleCheck = $this->cycles()->assertMayLink($newCycleId);
            if (!$cycleCheck['ok']) {
                return $cycleCheck;
            }
            $validated['data']['performance_cycle_id'] = $cycleCheck['cycle_id'];
        }

        if (!$this->plansRepo()->update($id, $validated['data'])) {
            return ['ok' => false, 'error' => 'Erro ao atualizar PDI.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, error?: string, data?: array<string, mixed>}
     */
    public function validateActionPayload(array $input, int $planId): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $actionType = (string) ($input['action_type'] ?? 'other');
        $status = (string) ($input['status'] ?? 'pending');
        $priority = (string) ($input['priority'] ?? 'medium');
        $progress = (int) ($input['progress_percentage'] ?? 0);
        $trainingId = !empty($input['training_id']) ? (int) $input['training_id'] : null;

        if ($title === '') {
            return ['ok' => false, 'error' => 'Título da ação é obrigatório.'];
        }
        if (!in_array($actionType, self::ACTION_TYPES, true)) {
            return ['ok' => false, 'error' => 'Tipo de ação inválido.'];
        }
        if (!in_array($status, self::ACTION_STATUSES, true)) {
            return ['ok' => false, 'error' => 'Status da ação inválido.'];
        }
        if (!in_array($priority, self::PRIORITIES, true)) {
            return ['ok' => false, 'error' => 'Prioridade inválida.'];
        }
        if ($progress < 0 || $progress > 100) {
            return ['ok' => false, 'error' => 'Progresso deve estar entre 0 e 100.'];
        }

        if ($trainingId !== null && $trainingId > 0) {
            $exists = false;
            foreach ($this->trainingsRepo()->getAllTrainingsSelect() as $t) {
                if ((int) $t['id'] === $trainingId) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                return ['ok' => false, 'error' => 'Treinamento informado não existe.'];
            }
        } else {
            $trainingId = null;
        }

        $completedAt = null;
        if ($status === 'completed') {
            $completedAt = !empty($input['completed_at'])
                ? (string) $input['completed_at']
                : date('Y-m-d H:i:s');
            if ($progress < 100) {
                $progress = 100;
            }
        }

        return [
            'ok' => true,
            'data' => [
                'pdi_plan_id' => $planId,
                'title' => $title,
                'description' => trim((string) ($input['description'] ?? '')) ?: null,
                'action_type' => $actionType,
                'category' => trim((string) ($input['category'] ?? '')) ?: null,
                'priority' => $priority,
                'start_date' => !empty($input['start_date']) ? (string) $input['start_date'] : null,
                'end_date' => !empty($input['end_date']) ? (string) $input['end_date'] : null,
                'expected_hours' => $input['expected_hours'] !== '' && isset($input['expected_hours'])
                    ? $input['expected_hours']
                    : null,
                'actual_hours' => $input['actual_hours'] ?? 0,
                'status' => $status,
                'progress_percentage' => $progress,
                'training_id' => $trainingId,
                'resource_url' => trim((string) ($input['resource_url'] ?? '')) ?: null,
                'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
                'completed_at' => $completedAt,
            ],
        ];
    }

    public function addAction(int $planId, array $input): array
    {
        if (!$this->plansRepo()->getById($planId)) {
            return ['ok' => false, 'error' => 'PDI não encontrado.'];
        }

        $validated = $this->validateActionPayload($input, $planId);
        if (!$validated['ok']) {
            return $validated;
        }

        $id = $this->actionsRepo()->create($validated['data']);
        if ($id <= 0) {
            return ['ok' => false, 'error' => 'Erro ao adicionar ação.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    public function updateAction(int $actionId, int $planId, array $input): array
    {
        $action = $this->actionsRepo()->getById($actionId);
        if (!$action || (int) $action['pdi_plan_id'] !== $planId) {
            return ['ok' => false, 'error' => 'Ação não encontrada neste PDI.'];
        }

        $validated = $this->validateActionPayload($input, $planId);
        if (!$validated['ok']) {
            return $validated;
        }

        if (!$this->actionsRepo()->update($actionId, $validated['data'])) {
            return ['ok' => false, 'error' => 'Erro ao atualizar ação.'];
        }

        return ['ok' => true, 'id' => $actionId];
    }

    public function addCompetency(int $planId, array $input): array
    {
        if (!$this->plansRepo()->getById($planId)) {
            return ['ok' => false, 'error' => 'PDI não encontrado.'];
        }

        $competencyId = !empty($input['competency_id']) ? (int) $input['competency_id'] : null;
        $name = trim((string) ($input['competency_name'] ?? ''));
        $type = (string) ($input['competency_type'] ?? 'behavioral');
        $current = (int) ($input['current_level'] ?? 1);
        $target = (int) ($input['target_level'] ?? 3);

        if ($competencyId !== null && $competencyId > 0) {
            $catalog = $this->catalogRepo()->getById($competencyId);
            if (!$catalog) {
                return ['ok' => false, 'error' => 'Competência do catálogo não encontrada.'];
            }
            $name = (string) $catalog['name'];
            $type = (string) ($catalog['competency_type'] ?? $type);
        }

        if ($name === '') {
            return ['ok' => false, 'error' => 'Informe a competência (catálogo ou nome).'];
        }
        if (!in_array($type, self::COMPETENCY_TYPES, true)) {
            return ['ok' => false, 'error' => 'Tipo de competência inválido.'];
        }
        if ($current < 1 || $current > 5 || $target < 1 || $target > 5) {
            return ['ok' => false, 'error' => 'Níveis devem estar entre 1 e 5.'];
        }
        if ($target < $current) {
            return ['ok' => false, 'error' => 'Nível alvo deve ser maior ou igual ao atual.'];
        }

        $id = $this->competenciesRepo()->create([
            'pdi_plan_id' => $planId,
            'competency_id' => $competencyId,
            'competency_name' => $name,
            'competency_type' => $type,
            'current_level' => $current,
            'target_level' => $target,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
        ]);

        if ($id <= 0) {
            return ['ok' => false, 'error' => 'Erro ao vincular competência.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    public function removeCompetency(int $competencyRowId, int $planId): array
    {
        $row = $this->competenciesRepo()->getById($competencyRowId);
        if (!$row || (int) $row['pdi_plan_id'] !== $planId) {
            return ['ok' => false, 'error' => 'Competência não encontrada neste PDI.'];
        }

        if (!$this->competenciesRepo()->delete($competencyRowId, $planId)) {
            return ['ok' => false, 'error' => 'Erro ao remover competência.'];
        }

        return ['ok' => true];
    }

    private function plansRepo(): PdiPlansRepository
    {
        return $this->plans ?? new PdiPlansRepository();
    }

    private function actionsRepo(): PdiActionsRepository
    {
        return $this->actions ?? new PdiActionsRepository();
    }

    private function competenciesRepo(): PdiCompetenciesRepository
    {
        return $this->competencies ?? new PdiCompetenciesRepository();
    }

    private function cycles(): PerformanceCycleService
    {
        return $this->cycleService ?? new PerformanceCycleService();
    }

    private function catalogRepo(): CompetenciesRepository
    {
        return $this->catalogCompetencies ?? new CompetenciesRepository();
    }

    private function trainingsRepo(): TrainingsRepository
    {
        return $this->trainings ?? new TrainingsRepository();
    }
}
