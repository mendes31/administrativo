<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\PerformanceCalibrationService;
use PHPUnit\Framework\TestCase;

final class PerformanceCalibrationServiceTest extends TestCase
{
    public function testRejectsMissingCycle(): void
    {
        $service = new PerformanceCalibrationService();
        $result = $service->create(['performance_cycle_id' => 0], 1);
        self::assertFalse($result['ok']);
        self::assertStringContainsString('ciclo', strtolower($result['error'] ?? ''));
    }
}
