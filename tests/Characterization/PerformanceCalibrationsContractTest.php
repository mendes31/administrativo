<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class PerformanceCalibrationsContractTest extends TestCase
{
    public function testMigrationAndPages(): void
    {
        $m = $this->read('database/migrations/20260720200000_create_adms_performance_calibrations.php');
        self::assertStringContainsString('adms_performance_calibrations', $m);
        self::assertStringContainsString('ListPerformanceCalibrations', $m);
        self::assertStringContainsString('uq_perf_calibrations_cycle', $m);
    }

    public function testNineBoxFiltersByCycle(): void
    {
        $repo = $this->read('app/adms/Models/Repository/PerformanceReviewsRepository.php');
        self::assertStringContainsString('performance_cycle_id', $repo);
        $ctrl = $this->read('app/adms/Controllers/performance/NineBoxMatrix.php');
        self::assertStringContainsString('performance_cycle_id', $ctrl);
    }

    public function testControllersExist(): void
    {
        foreach ([
            'ListPerformanceCalibrations.php',
            'CreatePerformanceCalibration.php',
            'ViewPerformanceCalibration.php',
            'UpdatePerformanceCalibration.php',
        ] as $file) {
            self::assertFileExists(PROJECT_ROOT . '/app/adms/Controllers/performance/' . $file);
        }
    }

    private function read(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source);

        return $source;
    }
}
