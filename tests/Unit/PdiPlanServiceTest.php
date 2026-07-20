<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\PdiPlanService;
use PHPUnit\Framework\TestCase;

final class PdiPlanServiceTest extends TestCase
{
    public function testRejectsMissingCollaborator(): void
    {
        $service = new PdiPlanService();
        $result = $service->validatePlanPayload([
            'title' => 'Plano teste',
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
        ]);

        self::assertFalse($result['ok']);
        self::assertStringContainsString('Colaborador', $result['error'] ?? '');
    }

    public function testRejectsInvertedPeriod(): void
    {
        $service = new PdiPlanService();
        $result = $service->validatePlanPayload([
            'user_id' => 1,
            'title' => 'Plano teste',
            'period_start' => '2026-12-31',
            'period_end' => '2026-01-01',
        ]);

        self::assertFalse($result['ok']);
        self::assertStringContainsString('final', strtolower($result['error'] ?? ''));
    }

    public function testRejectsInvalidActionType(): void
    {
        $service = new PdiPlanService();
        $result = $service->validateActionPayload([
            'title' => 'Ação',
            'action_type' => 'invalid',
        ], 1);

        self::assertFalse($result['ok']);
        self::assertStringContainsString('Tipo', $result['error'] ?? '');
    }

    public function testAcceptsValidPlanWithoutCycle(): void
    {
        $service = new PdiPlanService();
        $result = $service->validatePlanPayload([
            'user_id' => 10,
            'title' => 'Desenvolvimento 2026',
            'period_start' => '2026-01-01',
            'period_end' => '2026-06-30',
            'status' => 'draft',
        ]);

        self::assertTrue($result['ok']);
        self::assertSame(10, $result['data']['user_id']);
        self::assertNull($result['data']['performance_cycle_id']);
    }

    public function testRejectsGoalWithoutTitle(): void
    {
        $service = new PdiPlanService();
        $result = $service->validateGoalPayload(['status' => 'pending'], 1);
        self::assertFalse($result['ok']);
        self::assertStringContainsString('Título', $result['error'] ?? '');
    }

    public function testProgressSummary(): void
    {
        $service = new PdiPlanService();
        $summary = $service->progressSummary(
            [
                ['progress_percentage' => 50],
                ['progress_percentage' => 100],
            ],
            [
                ['status' => 'achieved'],
                ['status' => 'pending'],
            ]
        );

        self::assertSame(75, $summary['actions_avg']);
        self::assertSame(50, $summary['goals_achieved_pct']);
        self::assertSame(2, $summary['goals_count']);
    }
}
