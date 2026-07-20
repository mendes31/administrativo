<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\PerformanceFeedbacksRepository;
use App\adms\Models\Repository\UsersRepository;

/**
 * Feedback contínuo global (fora do PDI) — Expand Fase 5.
 */
class PerformanceFeedbackService
{
    public const TYPES = ['general', 'performance', 'recognition', 'improvement'];

    public function __construct(
        private readonly ?PerformanceFeedbacksRepository $repository = null,
        private readonly ?UsersRepository $usersRepo = null,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, error?: string, data?: array<string, mixed>}
     */
    public function validatePayload(array $input, bool $requireEmployee = true): array
    {
        $employeeId = (int) ($input['employee_id'] ?? 0);
        $type = (string) ($input['feedback_type'] ?? 'general');
        $text = trim((string) ($input['feedback_text'] ?? ''));
        $isAnonymous = !empty($input['is_anonymous']) && in_array((string) $input['is_anonymous'], ['1', 'on', 'true'], true);
        $isPublic = !empty($input['is_public']) && in_array((string) $input['is_public'], ['1', 'on', 'true'], true);
        $relatedReviewId = !empty($input['related_review_id']) ? (int) $input['related_review_id'] : null;
        $relatedGoalId = !empty($input['related_goal_id']) ? (int) $input['related_goal_id'] : null;

        if ($requireEmployee && $employeeId <= 0) {
            return ['ok' => false, 'error' => 'Colaborador é obrigatório.'];
        }
        if ($text === '') {
            return ['ok' => false, 'error' => 'Texto do feedback é obrigatório.'];
        }
        if (!in_array($type, self::TYPES, true)) {
            return ['ok' => false, 'error' => 'Tipo de feedback inválido.'];
        }

        return [
            'ok' => true,
            'data' => [
                'employee_id' => $employeeId,
                'feedback_type' => $type,
                'feedback_text' => $text,
                'is_anonymous' => $isAnonymous,
                'is_public' => $isPublic,
                'related_review_id' => $relatedReviewId,
                'related_goal_id' => $relatedGoalId,
            ],
        ];
    }

    /**
     * Destinatários válidos para o formulário de criação.
     *
     * @return list<array<string, mixed>>
     */
    public function listEligibleRecipients(int $actorId, bool $fullAccess): array
    {
        if ($actorId <= 0) {
            return [];
        }
        if ($fullAccess) {
            return $this->users()->getAllUsersForSelect();
        }

        return $this->users()->getSubordinates($actorId);
    }

    public function canTargetEmployee(int $employeeId, int $actorId, bool $fullAccess): bool
    {
        if ($employeeId <= 0 || $actorId <= 0) {
            return false;
        }
        if ($fullAccess) {
            return true;
        }
        foreach ($this->users()->getSubordinates($actorId) as $sub) {
            if ((int) ($sub['id'] ?? 0) === $employeeId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $feedback
     */
    public function canView(array $feedback, int $actorId, bool $fullAccess): bool
    {
        if ($actorId <= 0) {
            return false;
        }
        if ($fullAccess) {
            return true;
        }
        if ((int) ($feedback['employee_id'] ?? 0) === $actorId) {
            return true;
        }
        if ((int) ($feedback['given_by'] ?? 0) === $actorId) {
            return true;
        }
        if (!(int) ($feedback['is_public'] ?? 0)) {
            return false;
        }

        $employeeId = (int) ($feedback['employee_id'] ?? 0);
        $employee = $this->users()->getUser($employeeId);
        if (!is_array($employee)) {
            return false;
        }

        return (int) ($employee['immediate_supervisor_id'] ?? 0) === $actorId;
    }

    /**
     * @param array<string, mixed> $feedback
     */
    public function canEdit(array $feedback, int $actorId, bool $fullAccess): bool
    {
        if ($fullAccess) {
            return true;
        }

        return (int) ($feedback['given_by'] ?? 0) === $actorId;
    }

    /**
     * Nome do autor para exibição (respeita anonimato).
     *
     * @param array<string, mixed> $feedback
     */
    public function displayAuthorName(array $feedback, int $actorId, bool $fullAccess): string
    {
        $name = trim((string) ($feedback['given_by_name'] ?? ''));
        if (!(int) ($feedback['is_anonymous'] ?? 0)) {
            return $name !== '' ? $name : '—';
        }
        if ($fullAccess || (int) ($feedback['given_by'] ?? 0) === $actorId) {
            return ($name !== '' ? $name : '—') . ' (anônimo para outros)';
        }

        return 'Anônimo';
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, error?: string, id?: int}
     */
    public function create(array $input, int $actorId, bool $fullAccess): array
    {
        if ($actorId <= 0) {
            return ['ok' => false, 'error' => 'Usuário não autenticado.'];
        }

        $validated = $this->validatePayload($input, true);
        if (!$validated['ok']) {
            return $validated;
        }
        /** @var array<string, mixed> $data */
        $data = $validated['data'];

        if (!$this->canTargetEmployee((int) $data['employee_id'], $actorId, $fullAccess)) {
            return ['ok' => false, 'error' => 'Você não pode enviar feedback para este colaborador.'];
        }

        $data['given_by'] = $actorId;
        $id = $this->repo()->create($data);
        if ($id <= 0) {
            return ['ok' => false, 'error' => 'Erro ao criar feedback.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, error?: string, id?: int}
     */
    public function update(int $id, array $input, int $actorId, bool $fullAccess): array
    {
        $current = $this->repo()->getById($id);
        if (!$current) {
            return ['ok' => false, 'error' => 'Feedback não encontrado.'];
        }
        if (!$this->canView($current, $actorId, $fullAccess)) {
            return ['ok' => false, 'error' => 'Você não tem permissão para ver este feedback.'];
        }
        if (!$this->canEdit($current, $actorId, $fullAccess)) {
            return ['ok' => false, 'error' => 'Somente o autor (ou acesso total) pode editar.'];
        }

        $validated = $this->validatePayload(array_merge($current, $input), false);
        if (!$validated['ok']) {
            return $validated;
        }
        /** @var array<string, mixed> $data */
        $data = $validated['data'];
        unset($data['employee_id']);

        if (!$this->repo()->update($id, $data)) {
            return ['ok' => false, 'error' => 'Erro ao atualizar feedback.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    private function repo(): PerformanceFeedbacksRepository
    {
        return $this->repository ?? new PerformanceFeedbacksRepository();
    }

    private function users(): UsersRepository
    {
        return $this->usersRepo ?? new UsersRepository();
    }
}
