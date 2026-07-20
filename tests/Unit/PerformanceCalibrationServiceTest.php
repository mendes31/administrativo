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

    public function testValidateScorePairAcceptsRange(): void
    {
        $service = new PerformanceCalibrationService();
        $ok = $service->validateScorePair('8.5', '7');
        self::assertTrue($ok['ok']);
        self::assertSame(8.5, $ok['overall']);
        self::assertSame(7.0, $ok['potential']);
    }

    public function testValidateScorePairRejectsOutOfRange(): void
    {
        $service = new PerformanceCalibrationService();
        $bad = $service->validateScorePair('11', '5');
        self::assertFalse($bad['ok']);
    }

    public function testValidateScorePairAllowsEmpty(): void
    {
        $service = new PerformanceCalibrationService();
        $ok = $service->validateScorePair('', '');
        self::assertTrue($ok['ok']);
        self::assertNull($ok['overall']);
        self::assertNull($ok['potential']);
    }

    public function testAssertReviewEditableWithoutCycle(): void
    {
        $service = new PerformanceCalibrationService();
        $result = $service->assertReviewEditable(['performance_cycle_id' => null]);
        self::assertTrue($result['ok']);
    }
}
