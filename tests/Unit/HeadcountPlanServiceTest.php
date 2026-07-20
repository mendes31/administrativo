<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Repository\HeadcountPlansRepository;
use App\adms\Models\Services\HeadcountPlanService;
use PHPUnit\Framework\TestCase;

final class HeadcountPlanServiceTest extends TestCase
{
    public function testCreateRequiresDepartment(): void
    {
        $result = (new HeadcountPlanService())->create([
            'department_id' => 0,
            'period_year' => 2026,
            'period_month' => 7,
            'planned_count' => 5,
        ], 1);
        self::assertFalse($result['ok']);
        self::assertStringContainsString('Departamento', $result['error'] ?? '');
    }

    public function testCreateRejectsInvalidMonth(): void
    {
        $result = (new HeadcountPlanService())->create([
            'department_id' => 1,
            'period_year' => 2026,
            'period_month' => 13,
            'planned_count' => 5,
        ], 1);
        self::assertFalse($result['ok']);
        self::assertStringContainsString('Mês', $result['error'] ?? '');
    }

    public function testWithActualGap(): void
    {
        $repo = new class extends HeadcountPlansRepository {
            public function __construct()
            {
            }

            public function countActual(int $departmentId, ?int $positionId): int
            {
                return 3;
            }
        };
        $metrics = (new HeadcountPlanService($repo))->withActual([
            'department_id' => 1,
            'position_id' => 2,
            'planned_count' => 10,
        ]);
        self::assertSame(3, $metrics['actual_count']);
        self::assertSame(7, $metrics['gap']);
    }

    public function testClosedDoesNotReopen(): void
    {
        $repo = new class extends HeadcountPlansRepository {
            public function __construct()
            {
            }

            public function getById(int $id): ?array
            {
                return [
                    'id' => 1,
                    'status' => 'closed',
                    'planned_count' => 4,
                    'notes' => null,
                ];
            }
        };
        $result = (new HeadcountPlanService($repo))->update(1, ['status' => 'active', 'planned_count' => 8]);
        self::assertFalse($result['ok']);
        self::assertStringContainsString('fechada', $result['error'] ?? '');
    }
}
