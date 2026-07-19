<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\PerformanceCycleService;
use PHPUnit\Framework\TestCase;

final class PerformanceCycleServiceTest extends TestCase
{
    public function testRejectsInvertedPeriod(): void
    {
        $service = new PerformanceCycleService();
        $result = $service->validatePayload([
            'name' => 'Ciclo teste',
            'year' => 2026,
            'period_start' => '2026-12-31',
            'period_end' => '2026-01-01',
            'status' => 'draft',
        ]);

        self::assertFalse($result['ok']);
        self::assertStringContainsString('data final', strtolower($result['error'] ?? ''));
    }

    public function testClosedCannotReopen(): void
    {
        $service = new PerformanceCycleService();
        $result = $service->validatePayload(
            [
                'name' => 'Ciclo fechado',
                'year' => 2026,
                'period_start' => '2026-01-01',
                'period_end' => '2026-12-31',
                'status' => 'open',
            ],
            ['status' => 'closed']
        );

        self::assertFalse($result['ok']);
        self::assertStringContainsString('fechado', strtolower($result['error'] ?? ''));
    }

    public function testAcceptsValidOpenCycle(): void
    {
        $service = new PerformanceCycleService();
        $result = $service->validatePayload([
            'name' => 'Ciclo 2026 H1',
            'year' => 2026,
            'period_start' => '2026-01-01',
            'period_end' => '2026-06-30',
            'status' => 'open',
        ]);

        self::assertTrue($result['ok']);
        self::assertSame('open', $result['data']['status']);
    }
}
