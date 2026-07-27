<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\ApprovalDelegationsRepository;
use App\adms\Models\Repository\EmployeeRequestsRepository;
use App\adms\Models\Repository\RequestTypeStagesRepository;
use App\adms\Models\Repository\RequestTypesRepository;
use App\adms\Models\Repository\UsersAccessLevelsRepository;
use App\adms\Models\Repository\UsersRepository;

/**
 * Workflow de solicitações: etapas ordenadas por tipo, delegação e escalação.
 */
class EmployeeRequestWorkflowService
{
    public const STAGE_IMMEDIATE = 'immediate';
    public const STAGE_HR = 'hr';
    public const STAGE_FIXED = 'fixed_user';

    public function __construct(
        private ?RequestTypeStagesRepository $stagesRepo = null,
        private ?ApprovalDelegationsRepository $delegationsRepo = null,
        private ?UsersRepository $usersRepo = null,
        private ?EmployeeRequestsRepository $requestsRepo = null,
        private ?RequestTypesRepository $typesRepo = null,
        private ?UsersAccessLevelsRepository $userLevelsRepo = null,
    ) {
        $this->stagesRepo ??= new RequestTypeStagesRepository();
        $this->delegationsRepo ??= new ApprovalDelegationsRepository();
        $this->usersRepo ??= new UsersRepository();
        $this->requestsRepo ??= new EmployeeRequestsRepository();
        $this->typesRepo ??= new RequestTypesRepository();
        $this->userLevelsRepo ??= new UsersAccessLevelsRepository();
    }

    /**
     * Resolve a primeira etapa aplicável do fluxo configurado no tipo.
     *
     * @return array{
     *   status: string,
     *   requires_manager_approval: bool,
     *   current_stage_code: string,
     *   current_approver_user_id: ?int,
     *   original_approver_user_id: ?int,
     *   escalate_after_hours: int,
     *   max_escalation_levels: int,
     *   escalation_count: int,
     *   escalate_policy: string,
     *   via_delegation: bool
     * }
     */
    public function resolveInitialAssignment(int $employeeId, string $requestTypeCode): array
    {
        $stages = $this->stagesRepo->listByTypeCode($requestTypeCode);
        if ($stages === []) {
            $type = $this->typesRepo->getByCode($requestTypeCode);
            if ($type && (int) ($type['id'] ?? 0) > 0) {
                $this->stagesRepo->syncDefaultStagesForType(
                    (int) $type['id'],
                    !empty($type['requires_manager_approval']),
                    72,
                    1,
                    'next_level',
                    !isset($type['requires_hr_approval']) || !empty($type['requires_hr_approval'])
                );
                $stages = $this->stagesRepo->listByTypeCode($requestTypeCode);
            }
        }

        $type = $this->typesRepo->getByCode($requestTypeCode) ?: [];
        $requiresManager = $this->flowHasManagerLikeStage($stages);

        foreach ($stages as $index => $stage) {
            $assignment = $this->tryAssignStage($stage, $employeeId, $type, $stages, $index);
            if ($assignment !== null) {
                $assignment['requires_manager_approval'] = $requiresManager;

                return $assignment;
            }
        }

        return $this->hrAssignment($requiresManager);
    }

    /**
     * @return array{approver_id: int, via_delegation: bool, delegator_id: ?int}
     */
    public function resolveEffectiveApprover(int $primaryApproverId): array
    {
        if ($primaryApproverId <= 0) {
            return ['approver_id' => 0, 'via_delegation' => false, 'delegator_id' => null];
        }

        $delegation = $this->delegationsRepo->findActiveDelegateFor($primaryApproverId);
        if ($delegation) {
            $delegateId = (int) ($delegation['delegate_user_id'] ?? 0);
            if ($delegateId > 0 && $this->isUserActiveApprover($delegateId)) {
                return [
                    'approver_id' => $delegateId,
                    'via_delegation' => true,
                    'delegator_id' => $primaryApproverId,
                ];
            }
        }

        return [
            'approver_id' => $primaryApproverId,
            'via_delegation' => false,
            'delegator_id' => null,
        ];
    }

    public function canActAsManager(array $request, int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        if (UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }
        if (($request['status'] ?? '') !== 'pending_manager_approval') {
            return false;
        }

        $currentApprover = (int) ($request['current_approver_user_id'] ?? 0);
        if ($currentApprover > 0) {
            if ($currentApprover === $userId) {
                return true;
            }
            $delegators = $this->delegationsRepo->listDelegatorIdsForDelegate($userId);
            if (in_array($currentApprover, $delegators, true)) {
                return true;
            }
        }

        $immediate = (int) ($request['immediate_supervisor_id'] ?? 0);
        if ($currentApprover <= 0 && $immediate === $userId) {
            return true;
        }

        $original = (int) ($request['original_approver_user_id'] ?? 0);
        if ($original === $userId) {
            return true;
        }

        return false;
    }

    public function canViewInTeamTree(array $request, int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        if (UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }

        $employeeId = (int) ($request['employee_id'] ?? 0);
        if ($employeeId === $userId) {
            return true;
        }

        $teamIds = CrmPermissionService::getAllSubordinates($userId);

        return in_array($employeeId, $teamIds, true);
    }

    /**
     * @return array{ok: bool, message: string, via_delegation: bool}
     */
    public function approveManagerStep(int $requestId, int $actorUserId): array
    {
        $request = $this->requestsRepo->getById($requestId);
        if (!$request) {
            return ['ok' => false, 'message' => 'Solicitação não encontrada.', 'via_delegation' => false];
        }
        if (!$this->canActAsManager($request, $actorUserId)) {
            return ['ok' => false, 'message' => 'Sem permissão para aprovar esta solicitação.', 'via_delegation' => false];
        }

        $onBehalfOf = (int) ($request['current_approver_user_id'] ?? $request['original_approver_user_id'] ?? 0);
        $viaDelegation = $onBehalfOf > 0 && $onBehalfOf !== $actorUserId
            && !UserAccessHelper::hasFullSystemAccess();

        $stageCode = (string) ($request['current_stage_code'] ?? self::STAGE_IMMEDIATE);
        $typeCode = (string) ($request['request_type'] ?? '');
        $stages = $this->stagesRepo->listByTypeCode($typeCode);
        $currentIndex = $this->findStageIndex($stages, $stageCode);
        $employeeId = (int) ($request['employee_id'] ?? 0);
        $type = $this->typesRepo->getByCode($typeCode) ?: [];

        $next = $this->resolveNextAssignment($stages, $currentIndex + 1, $employeeId, $type);

        if ($next === null) {
            $ok = $this->requestsRepo->applyStageTransition($requestId, $actorUserId, [
                'status' => 'approved',
                'current_stage_code' => null,
                'current_approver_user_id' => null,
                'original_approver_user_id' => null,
                'escalate_after_hours' => 0,
                'max_escalation_levels' => 0,
                'escalation_count' => 0,
            ], true);

            if (!$ok) {
                return ['ok' => false, 'message' => 'Erro ao finalizar solicitação.', 'via_delegation' => false];
            }

            $this->logEvent(
                $requestId,
                $stageCode,
                $viaDelegation ? 'delegated_act' : 'approved',
                $actorUserId,
                $viaDelegation ? $onBehalfOf : null,
                'Aprovado e finalizado (última etapa do fluxo)'
            );

            EmployeeRequestNotificationService::notifyFinalApproved($requestId, $actorUserId);

            return [
                'ok' => true,
                'message' => 'Solicitação aprovada e finalizada.',
                'via_delegation' => $viaDelegation,
            ];
        }

        $ok = $this->requestsRepo->applyStageTransition($requestId, $actorUserId, [
            'status' => $next['status'],
            'current_stage_code' => $next['current_stage_code'],
            'current_approver_user_id' => $next['current_approver_user_id'],
            'original_approver_user_id' => $next['original_approver_user_id'],
            'stage_started_at' => date('Y-m-d H:i:s'),
            'escalate_after_hours' => (int) ($next['escalate_after_hours'] ?? 0),
            'max_escalation_levels' => (int) ($next['max_escalation_levels'] ?? 0),
            'escalation_count' => (int) ($next['escalation_count'] ?? 0),
        ], true);

        if (!$ok) {
            return ['ok' => false, 'message' => 'Erro ao avançar para a próxima etapa.', 'via_delegation' => false];
        }

        $this->logEvent(
            $requestId,
            $stageCode,
            $viaDelegation ? 'delegated_act' : 'approved',
            $actorUserId,
            $viaDelegation ? $onBehalfOf : null,
            $viaDelegation ? 'Aprovado por delegação' : 'Etapa aprovada; avançou no fluxo'
        );

        EmployeeRequestNotificationService::notifyPendingForRequest($requestId, $actorUserId);

        $msg = ($next['status'] ?? '') === 'pending_hr_approval'
            ? 'Solicitação aprovada! Agora aguarda a etapa de RH.'
            : 'Solicitação aprovada nesta etapa. Seguiu para a próxima aprovação.';

        return [
            'ok' => true,
            'message' => $msg,
            'via_delegation' => $viaDelegation,
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function approveHrStep(int $requestId, int $actorUserId): array
    {
        $request = $this->requestsRepo->getById($requestId);
        if (!$request) {
            return ['ok' => false, 'message' => 'Solicitação não encontrada.'];
        }
        if (($request['status'] ?? '') !== 'pending_hr_approval') {
            return ['ok' => false, 'message' => 'Solicitação não está aguardando RH.'];
        }

        $stageCode = (string) ($request['current_stage_code'] ?? self::STAGE_HR);
        $typeCode = (string) ($request['request_type'] ?? '');
        $stages = $this->stagesRepo->listByTypeCode($typeCode);
        $currentIndex = $this->findStageIndex($stages, $stageCode);
        $employeeId = (int) ($request['employee_id'] ?? 0);
        $type = $this->typesRepo->getByCode($typeCode) ?: [];

        $next = $this->resolveNextAssignment($stages, $currentIndex + 1, $employeeId, $type);
        if ($next === null) {
            if (!$this->requestsRepo->approveByHR($requestId, $actorUserId)) {
                return ['ok' => false, 'message' => 'Erro ao aprovar solicitação.'];
            }
            $this->requestsRepo->updateWorkflowState($requestId, [
                'current_stage_code' => null,
                'current_approver_user_id' => null,
                'original_approver_user_id' => null,
                'stage_started_at' => null,
                'escalate_after_hours' => 0,
            ]);
            $this->logEvent($requestId, $stageCode, 'approved', $actorUserId, null, 'Aprovado pelo RH (fim do fluxo)');

            EmployeeRequestNotificationService::notifyFinalApproved($requestId, $actorUserId);

            return ['ok' => true, 'message' => 'Solicitação aprovada pelo RH!'];
        }

        // RH no meio do fluxo (raro): avança sem finalizar
        $ok = $this->requestsRepo->applyStageTransition($requestId, $actorUserId, [
            'status' => $next['status'],
            'current_stage_code' => $next['current_stage_code'],
            'current_approver_user_id' => $next['current_approver_user_id'],
            'original_approver_user_id' => $next['original_approver_user_id'],
            'stage_started_at' => date('Y-m-d H:i:s'),
            'escalate_after_hours' => (int) ($next['escalate_after_hours'] ?? 0),
            'max_escalation_levels' => (int) ($next['max_escalation_levels'] ?? 0),
            'escalation_count' => (int) ($next['escalation_count'] ?? 0),
        ], false);

        if (!$ok) {
            return ['ok' => false, 'message' => 'Erro ao avançar após RH.'];
        }

        $this->logEvent($requestId, $stageCode, 'approved', $actorUserId, null, 'RH aprovou; avançou no fluxo');

        EmployeeRequestNotificationService::notifyPendingForRequest($requestId, $actorUserId);

        return ['ok' => true, 'message' => 'Etapa de RH aprovada. Seguiu para a próxima.'];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function rejectManagerStep(int $requestId, int $actorUserId, string $reason): array
    {
        $request = $this->requestsRepo->getById($requestId);
        if (!$request) {
            return ['ok' => false, 'message' => 'Solicitação não encontrada.'];
        }
        if (!$this->canActAsManager($request, $actorUserId)) {
            return ['ok' => false, 'message' => 'Sem permissão para rejeitar esta solicitação.'];
        }

        $onBehalfOf = (int) ($request['current_approver_user_id'] ?? 0);
        $viaDelegation = $onBehalfOf > 0 && $onBehalfOf !== $actorUserId
            && !UserAccessHelper::hasFullSystemAccess();

        if (!$this->requestsRepo->rejectByManager($requestId, $actorUserId, $reason)) {
            return ['ok' => false, 'message' => 'Erro ao rejeitar solicitação.'];
        }

        $this->requestsRepo->updateWorkflowState($requestId, [
            'current_stage_code' => null,
            'current_approver_user_id' => null,
            'stage_started_at' => null,
        ]);

        $this->logEvent(
            $requestId,
            (string) ($request['current_stage_code'] ?? self::STAGE_IMMEDIATE),
            'rejected',
            $actorUserId,
            $viaDelegation ? $onBehalfOf : null,
            $reason
        );

        EmployeeRequestNotificationService::notifyRejected($requestId, $actorUserId);

        return ['ok' => true, 'message' => 'Solicitação rejeitada.'];
    }

    /**
     * Escala solicitações com SLA vencido na etapa corrente (tipicamente gestor).
     *
     * @return array{escalated: int, moved_to_hr: int}
     */
    public function escalateDue(?\DateTimeInterface $now = null): array
    {
        $nowStr = ($now ?? new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $due = $this->requestsRepo->listDueForEscalation($nowStr);
        $escalated = 0;
        $movedToHr = 0;

        foreach ($due as $request) {
            $requestId = (int) $request['id'];
            $typeCode = (string) ($request['request_type'] ?? '');
            $stages = $this->stagesRepo->listByTypeCode($typeCode);
            $stageCode = (string) ($request['current_stage_code'] ?? '');
            $currentIndex = $this->findStageIndex($stages, $stageCode);
            $currentStage = $currentIndex >= 0 ? ($stages[$currentIndex] ?? null) : null;

            $policy = (string) ($currentStage['escalate_policy'] ?? $request['escalate_policy'] ?? 'next_level');
            $maxLevels = (int) ($currentStage['max_escalation_levels']
                ?? $request['max_escalation_levels']
                ?? 1);

            if ($policy === 'none') {
                continue;
            }

            $count = (int) ($request['escalation_count'] ?? 0);
            $employeeId = (int) ($request['employee_id'] ?? 0);
            $type = $this->typesRepo->getByCode($typeCode) ?: [];

            if ($policy === 'next_stage' || $policy === 'hr' || $count >= $maxLevels) {
                if ($this->advanceAfterEscalation($requestId, $request, $stages, $currentIndex, $employeeId, $type, $nowStr, $policy)) {
                    $movedToHr++;
                }
                continue;
            }

            // next_level: sobe na hierarquia dentro da etapa
            $current = (int) ($request['current_approver_user_id'] ?? 0);
            $original = (int) ($request['original_approver_user_id'] ?? $current);
            $from = $current > 0 ? $current : $original;
            $next = $this->resolveNextLevelApprover($from > 0 ? $from : $original);

            if ($next === null) {
                if ($this->advanceAfterEscalation($requestId, $request, $stages, $currentIndex, $employeeId, $type, $nowStr, 'next_stage')) {
                    $movedToHr++;
                }
                continue;
            }

            $resolved = $this->resolveEffectiveApprover($next);
            $this->requestsRepo->updateWorkflowState($requestId, [
                'current_approver_user_id' => $resolved['approver_id'],
                'stage_started_at' => $nowStr,
                'escalation_count' => $count + 1,
            ]);
            $this->logEvent(
                $requestId,
                $stageCode !== '' ? $stageCode : self::STAGE_IMMEDIATE,
                'escalated',
                $resolved['approver_id'],
                $from > 0 ? $from : null,
                'Escalado nível ' . ($count + 1) . '/' . $maxLevels
            );
            EmployeeRequestNotificationService::notifyEscalated(
                $requestId,
                (int) $resolved['approver_id']
            );
            $escalated++;
        }

        return ['escalated' => $escalated, 'moved_to_hr' => $movedToHr];
    }

    /**
     * IDs de aprovadores pelos quais o usuário pode agir (ele + delegadores ativos).
     *
     * @return list<int>
     */
    public function approverIdsForActor(int $userId): array
    {
        $ids = [$userId];
        foreach ($this->delegationsRepo->listDelegatorIdsForDelegate($userId) as $delegatorId) {
            $ids[] = $delegatorId;
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * @param list<array<string, mixed>> $stages
     * @return array<string, mixed>|null
     */
    private function tryAssignStage(
        array $stage,
        int $employeeId,
        array $type,
        array $stages,
        int $index
    ): ?array {
        $kind = $this->stageKind($stage);
        $code = $this->stageCode($stage, $index);

        if ($kind === self::STAGE_HR) {
            return [
                'status' => 'pending_hr_approval',
                'requires_manager_approval' => false,
                'current_stage_code' => $code,
                'current_approver_user_id' => null,
                'original_approver_user_id' => null,
                'escalate_after_hours' => 0,
                'max_escalation_levels' => 0,
                'escalation_count' => 0,
                'escalate_policy' => 'none',
                'via_delegation' => false,
            ];
        }

        if ($kind === self::STAGE_FIXED) {
            $fixedId = (int) ($stage['fixed_user_id'] ?? 0);
            if ($fixedId <= 0 || !$this->isUserActiveApprover($fixedId)) {
                return null;
            }
            $resolved = $this->resolveEffectiveApprover($fixedId);

            return [
                'status' => 'pending_manager_approval',
                'requires_manager_approval' => true,
                'current_stage_code' => $code,
                'current_approver_user_id' => $resolved['approver_id'],
                'original_approver_user_id' => $fixedId,
                'escalate_after_hours' => (int) ($stage['escalate_after_hours'] ?? 0),
                'max_escalation_levels' => (int) ($stage['max_escalation_levels'] ?? 0),
                'escalation_count' => 0,
                'escalate_policy' => (string) ($stage['escalate_policy'] ?? 'none'),
                'via_delegation' => $resolved['via_delegation'],
            ];
        }

        // immediate / hierarquia do solicitante (nível 1 = André, 2 = Nathiele, …)
        $hierarchyLevel = max(1, (int) ($stage['hierarchy_level'] ?? 1));

        // Exceções de “pular gestor” valem só para o 1º nível (imediato).
        if ($hierarchyLevel === 1 && $this->shouldSkipImmediateForRequester($employeeId, $type)) {
            return null;
        }

        $targetId = $this->resolveApproverAtHierarchyLevel($employeeId, $hierarchyLevel);
        if ($targetId === null) {
            return null;
        }

        if ($hierarchyLevel === 1 && $this->shouldSkipImmediateForSupervisor($targetId, $type)) {
            return null;
        }

        $escalationCount = 0;
        $originalId = $targetId;
        if (!$this->isUserActiveApprover($targetId)) {
            $escalated = $this->resolveNextLevelApprover($targetId);
            if ($escalated === null) {
                return null;
            }
            $targetId = $escalated;
            $escalationCount = 1;
        }

        $resolved = $this->resolveEffectiveApprover($targetId);

        return [
            'status' => 'pending_manager_approval',
            'requires_manager_approval' => true,
            'current_stage_code' => $code,
            'current_approver_user_id' => $resolved['approver_id'],
            'original_approver_user_id' => $originalId,
            'escalate_after_hours' => (int) ($stage['escalate_after_hours'] ?? 72),
            'max_escalation_levels' => (int) ($stage['max_escalation_levels'] ?? 1),
            'escalation_count' => $escalationCount,
            'escalate_policy' => (string) ($stage['escalate_policy'] ?? 'next_level'),
            'via_delegation' => $resolved['via_delegation'],
        ];
    }

    /**
     * Sobe N níveis no organograma a partir do solicitante.
     * 1 = gestor imediato; 2 = gestor do gestor; etc.
     */
    private function resolveApproverAtHierarchyLevel(int $employeeId, int $level): ?int
    {
        if ($employeeId <= 0 || $level < 1) {
            return null;
        }

        $currentId = $employeeId;
        $approverId = null;
        for ($i = 0; $i < $level; $i++) {
            $user = $this->usersRepo->getUser($currentId);
            if (!$user) {
                return null;
            }
            $supervisorId = (int) ($user['immediate_supervisor_id'] ?? 0);
            if ($supervisorId <= 0 || $supervisorId === $currentId) {
                return null;
            }
            $approverId = $supervisorId;
            $currentId = $supervisorId;
        }

        return $approverId;
    }

    /**
     * @param list<array<string, mixed>> $stages
     * @return array<string, mixed>|null
     */
    private function resolveNextAssignment(array $stages, int $fromIndex, int $employeeId, array $type): ?array
    {
        for ($i = max(0, $fromIndex); $i < count($stages); $i++) {
            $assignment = $this->tryAssignStage($stages[$i], $employeeId, $type, $stages, $i);
            if ($assignment !== null) {
                return $assignment;
            }
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $stages
     */
    private function advanceAfterEscalation(
        int $requestId,
        array $request,
        array $stages,
        int $currentIndex,
        int $employeeId,
        array $type,
        string $nowStr,
        string $policy
    ): bool {
        $fromUserId = (int) ($request['current_approver_user_id'] ?? $request['original_approver_user_id'] ?? 0);
        $actor = $fromUserId > 0 ? $fromUserId : 1;
        $stageCode = (string) ($request['current_stage_code'] ?? self::STAGE_IMMEDIATE);

        $startIndex = $currentIndex + 1;
        if ($policy === 'hr') {
            for ($i = $startIndex; $i < count($stages); $i++) {
                if ($this->stageKind($stages[$i]) === self::STAGE_HR) {
                    $startIndex = $i;
                    break;
                }
            }
        }

        $next = $this->resolveNextAssignment($stages, $startIndex, $employeeId, $type);
        if ($next === null) {
            // Sem próxima etapa: finaliza pela via do gestor (última aprovação implícita por escalação)
            $ok = $this->requestsRepo->applyStageTransition($requestId, $actor, [
                'status' => 'approved',
                'current_stage_code' => null,
                'current_approver_user_id' => null,
                'original_approver_user_id' => null,
                'escalate_after_hours' => 0,
                'max_escalation_levels' => 0,
                'escalation_count' => 0,
            ], true);
            if ($ok) {
                $this->logEvent($requestId, $stageCode, 'escalated', null, $fromUserId > 0 ? $fromUserId : null, 'Escalação sem próxima etapa — finalizado');
                EmployeeRequestNotificationService::notifyFinalApproved($requestId);
            }

            return $ok;
        }

        $ok = $this->requestsRepo->applyStageTransition($requestId, $actor, [
            'status' => $next['status'],
            'current_stage_code' => $next['current_stage_code'],
            'current_approver_user_id' => $next['current_approver_user_id'],
            'original_approver_user_id' => $next['original_approver_user_id'],
            'stage_started_at' => $nowStr,
            'escalate_after_hours' => (int) ($next['escalate_after_hours'] ?? 0),
            'max_escalation_levels' => (int) ($next['max_escalation_levels'] ?? 0),
            'escalation_count' => 0,
        ], true);

        if ($ok) {
            $note = ($next['status'] ?? '') === 'pending_hr_approval'
                ? 'Escalado para etapa RH'
                : 'Escalado para próxima etapa do fluxo';
            $this->logEvent($requestId, $stageCode, 'escalated', null, $fromUserId > 0 ? $fromUserId : null, $note);
            EmployeeRequestNotificationService::notifyPendingForRequest($requestId);
        }

        return $ok;
    }

    /**
     * @param list<array<string, mixed>> $stages
     */
    private function findStageIndex(array $stages, string $stageCode): int
    {
        if ($stageCode === '') {
            return -1;
        }
        foreach ($stages as $i => $stage) {
            if ($this->stageCode($stage, $i) === $stageCode) {
                return $i;
            }
        }
        // Compatibilidade com códigos legados "immediate" / "hr"
        foreach ($stages as $i => $stage) {
            $kind = $this->stageKind($stage);
            if ($stageCode === $kind || $stageCode === ($stage['stage_code'] ?? '')) {
                return $i;
            }
        }

        return -1;
    }

    private function stageKind(array $stage): string
    {
        $kind = (string) ($stage['approver_kind'] ?? '');
        if ($kind !== '') {
            return $kind;
        }
        $code = (string) ($stage['stage_code'] ?? '');
        if (str_starts_with($code, self::STAGE_FIXED)) {
            return self::STAGE_FIXED;
        }
        if (str_starts_with($code, self::STAGE_IMMEDIATE) || $code === self::STAGE_IMMEDIATE) {
            return self::STAGE_IMMEDIATE;
        }
        if (str_starts_with($code, self::STAGE_HR) || $code === self::STAGE_HR) {
            return self::STAGE_HR;
        }

        return $code;
    }

    private function stageCode(array $stage, int $index): string
    {
        $code = trim((string) ($stage['stage_code'] ?? ''));
        if ($code !== '') {
            return $code;
        }

        return $this->stageKind($stage) . '_' . ($index + 1);
    }

    /**
     * @param list<array<string, mixed>> $stages
     */
    private function flowHasManagerLikeStage(array $stages): bool
    {
        foreach ($stages as $stage) {
            $kind = $this->stageKind($stage);
            if ($kind === self::STAGE_IMMEDIATE || $kind === self::STAGE_FIXED) {
                return true;
            }
        }

        return false;
    }

    private function shouldSkipImmediateForRequester(int $employeeId, array $type): bool
    {
        $skipIds = $this->typesRepo->getSkipImmediateRequesterLevelIds($type);
        if ($skipIds === []) {
            return false;
        }

        $userLevels = $this->userLevelsRepo->getUserAccessLevelArray($employeeId);
        if ($userLevels === false || $userLevels === []) {
            return false;
        }

        foreach (array_map('intval', $userLevels) as $levelId) {
            if (in_array($levelId, $skipIds, true)) {
                return true;
            }
        }

        return false;
    }

    private function shouldSkipImmediateForSupervisor(int $supervisorId, array $type): bool
    {
        $skipIds = $this->typesRepo->getSkipImmediateSupervisorLevelIds($type);
        if ($skipIds === [] || $supervisorId <= 0) {
            return false;
        }

        $supervisorLevels = $this->userLevelsRepo->getUserAccessLevelArray($supervisorId);
        if ($supervisorLevels === false || $supervisorLevels === []) {
            return false;
        }

        foreach (array_map('intval', $supervisorLevels) as $levelId) {
            if (in_array($levelId, $skipIds, true)) {
                return true;
            }
        }

        return false;
    }

    private function hrAssignment(bool $requiresManagerFlag): array
    {
        return [
            'status' => 'pending_hr_approval',
            'requires_manager_approval' => $requiresManagerFlag,
            'current_stage_code' => self::STAGE_HR,
            'current_approver_user_id' => null,
            'original_approver_user_id' => null,
            'escalate_after_hours' => 0,
            'max_escalation_levels' => 0,
            'escalation_count' => 0,
            'escalate_policy' => 'none',
            'via_delegation' => false,
        ];
    }

    private function isUserActiveApprover(int $userId): bool
    {
        $user = $this->usersRepo->getUser($userId);
        if (!$user) {
            return false;
        }
        $status = trim((string) ($user['status'] ?? ''));
        if ($status !== '' && strcasecmp($status, 'Ativo') !== 0 && strcasecmp($status, 'ativo') !== 0) {
            return false;
        }
        if (!empty($user['data_desligamento'])) {
            return false;
        }

        return true;
    }

    private function resolveNextLevelApprover(int $userId): ?int
    {
        if ($userId <= 0) {
            return null;
        }
        $user = $this->usersRepo->getUser($userId);
        $supervisorId = (int) ($user['immediate_supervisor_id'] ?? 0);
        if ($supervisorId <= 0 || $supervisorId === $userId) {
            return null;
        }
        if (!$this->isUserActiveApprover($supervisorId)) {
            return $this->resolveNextLevelApprover($supervisorId);
        }

        return $supervisorId;
    }

    private function logEvent(
        int $requestId,
        ?string $stageCode,
        string $action,
        ?int $actorUserId,
        ?int $onBehalfOf,
        ?string $notes
    ): void {
        $this->requestsRepo->addApprovalEvent(
            $requestId,
            $stageCode,
            $action,
            $actorUserId,
            $onBehalfOf,
            $notes
        );
    }
}
