<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Repository\ApprovalDelegationsRepository;
use App\adms\Models\Repository\EmployeeRequestsRepository;
use App\adms\Models\Repository\RequestTypeStagesRepository;
use App\adms\Models\Repository\RequestTypesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\EmployeeRequestWorkflowService;
use PHPUnit\Framework\TestCase;

final class EmployeeRequestWorkflowServiceTest extends TestCase
{
    public function testResolveEffectiveApproverUsesActiveDelegation(): void
    {
        $delegations = new class extends ApprovalDelegationsRepository {
            public function findActiveDelegateFor(int $delegatorUserId, ?\DateTimeInterface $at = null): ?array
            {
                return [
                    'delegator_user_id' => $delegatorUserId,
                    'delegate_user_id' => 20,
                ];
            }
        };

        $users = new class extends UsersRepository {
            public function getUser(int $id): array|bool
            {
                return ['id' => $id, 'status' => 'Ativo', 'data_desligamento' => null, 'immediate_supervisor_id' => null];
            }
        };

        $service = new EmployeeRequestWorkflowService(
            new RequestTypeStagesRepository(),
            $delegations,
            $users,
            new EmployeeRequestsRepository(),
            new RequestTypesRepository()
        );

        $resolved = $service->resolveEffectiveApprover(10);
        self::assertSame(20, $resolved['approver_id']);
        self::assertTrue($resolved['via_delegation']);
        self::assertSame(10, $resolved['delegator_id']);
    }

    public function testResolveInitialAssignmentFallsBackToHrWithoutImmediate(): void
    {
        $stages = new class extends RequestTypeStagesRepository {
            public function listByTypeCode(string $typeCode): array
            {
                return [[
                    'stage_code' => 'immediate',
                    'approver_kind' => 'immediate',
                    'escalate_after_hours' => 72,
                ]];
            }
        };

        $users = new class extends UsersRepository {
            public function getUser(int $id): array|bool
            {
                return [
                    'id' => $id,
                    'status' => 'Ativo',
                    'immediate_supervisor_id' => null,
                    'data_desligamento' => null,
                ];
            }
        };

        $service = new EmployeeRequestWorkflowService(
            $stages,
            new ApprovalDelegationsRepository(),
            $users,
            new EmployeeRequestsRepository(),
            new RequestTypesRepository()
        );

        $assignment = $service->resolveInitialAssignment(5, 'vacation');
        self::assertSame('pending_hr_approval', $assignment['status']);
        self::assertSame('hr', $assignment['current_stage_code']);
        self::assertNull($assignment['current_approver_user_id']);
    }

    public function testResolveInitialAssignmentUsesFirstApplicableStage(): void
    {
        $stages = new class extends RequestTypeStagesRepository {
            public function listByTypeCode(string $typeCode): array
            {
                return [
                    [
                        'stage_code' => 'immediate_1',
                        'approver_kind' => 'immediate',
                        'escalate_after_hours' => 48,
                        'max_escalation_levels' => 1,
                        'escalate_policy' => 'next_level',
                    ],
                    [
                        'stage_code' => 'hr_2',
                        'approver_kind' => 'hr',
                    ],
                ];
            }
        };

        $users = new class extends UsersRepository {
            public function getUser(int $id): array|bool
            {
                if ($id === 5) {
                    return [
                        'id' => 5,
                        'status' => 'Ativo',
                        'immediate_supervisor_id' => 10,
                        'data_desligamento' => null,
                    ];
                }

                return [
                    'id' => $id,
                    'status' => 'Ativo',
                    'immediate_supervisor_id' => null,
                    'data_desligamento' => null,
                ];
            }
        };

        $types = new class extends RequestTypesRepository {
            public function getByCode(string $code): ?array
            {
                return ['id' => 1, 'code' => $code, 'requires_manager_approval' => 1];
            }

            public function getSkipImmediateRequesterLevelIds(array $type): array
            {
                return [];
            }

            public function getSkipImmediateSupervisorLevelIds(array $type): array
            {
                return [];
            }
        };

        $service = new EmployeeRequestWorkflowService(
            $stages,
            new ApprovalDelegationsRepository(),
            $users,
            new EmployeeRequestsRepository(),
            $types
        );

        $assignment = $service->resolveInitialAssignment(5, 'vacation');
        self::assertSame('pending_manager_approval', $assignment['status']);
        self::assertSame('immediate_1', $assignment['current_stage_code']);
        self::assertSame(10, $assignment['current_approver_user_id']);
        self::assertSame(48, $assignment['escalate_after_hours']);
    }

    public function testResolveInitialAssignmentUsesHierarchyLevelTwo(): void
    {
        $stages = new class extends RequestTypeStagesRepository {
            public function listByTypeCode(string $typeCode): array
            {
                return [[
                    'stage_code' => 'hierarchy_2_1',
                    'approver_kind' => 'immediate',
                    'hierarchy_level' => 2,
                    'escalate_after_hours' => 24,
                    'max_escalation_levels' => 0,
                    'escalate_policy' => 'none',
                ]];
            }
        };

        $users = new class extends UsersRepository {
            public function getUser(int $id): array|bool
            {
                // 5 (solicitante) → 10 (André) → 20 (Nathiele)
                $map = [
                    5 => ['id' => 5, 'status' => 'Ativo', 'immediate_supervisor_id' => 10, 'data_desligamento' => null],
                    10 => ['id' => 10, 'status' => 'Ativo', 'immediate_supervisor_id' => 20, 'data_desligamento' => null],
                    20 => ['id' => 20, 'status' => 'Ativo', 'immediate_supervisor_id' => null, 'data_desligamento' => null],
                ];

                return $map[$id] ?? false;
            }
        };

        $types = new class extends RequestTypesRepository {
            public function getByCode(string $code): ?array
            {
                return ['id' => 1, 'code' => $code];
            }

            public function getSkipImmediateRequesterLevelIds(array $type): array
            {
                return [];
            }

            public function getSkipImmediateSupervisorLevelIds(array $type): array
            {
                return [];
            }
        };

        $service = new EmployeeRequestWorkflowService(
            $stages,
            new ApprovalDelegationsRepository(),
            $users,
            new EmployeeRequestsRepository(),
            $types
        );

        $assignment = $service->resolveInitialAssignment(5, 'vacation');
        self::assertSame('pending_manager_approval', $assignment['status']);
        self::assertSame(20, $assignment['current_approver_user_id']);
        self::assertSame(20, $assignment['original_approver_user_id']);
    }

    public function testCanActAsManagerAllowsCurrentApprover(): void
    {
        $service = new EmployeeRequestWorkflowService(
            new RequestTypeStagesRepository(),
            new class extends ApprovalDelegationsRepository {
                public function listDelegatorIdsForDelegate(int $delegateUserId, ?\DateTimeInterface $at = null): array
                {
                    return [];
                }
            },
            new UsersRepository(),
            new EmployeeRequestsRepository(),
            new RequestTypesRepository()
        );

        $ok = $service->canActAsManager([
            'status' => 'pending_manager_approval',
            'current_approver_user_id' => 33,
            'immediate_supervisor_id' => 10,
        ], 33);

        self::assertTrue($ok);
    }
}
