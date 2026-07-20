<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class PerformanceCyclesContractTest extends TestCase
{
    public function testMigrationCreatesCyclesAndOptionalFks(): void
    {
        $migration = $this->readProjectFile(
            'database/migrations/20260719250000_create_adms_performance_cycles.php'
        );
        self::assertStringContainsString('adms_performance_cycles', $migration);
        self::assertStringContainsString('performance_cycle_id', $migration);
        self::assertStringContainsString('ListPerformanceCycles', $migration);
        self::assertStringContainsString('ListPerformanceGoals', $migration);
    }

    public function testGoalsRepositoryPersistsCycle(): void
    {
        $repo = $this->readProjectFile('app/adms/Models/Repository/PerformanceGoalsRepository.php');
        self::assertStringContainsString('performance_cycle_id', $repo);
        self::assertStringContainsString('adms_performance_cycles', $repo);
    }

    public function testControllersExist(): void
    {
        foreach ([
            'app/adms/Controllers/performance/ListPerformanceCycles.php',
            'app/adms/Controllers/performance/CreatePerformanceCycle.php',
            'app/adms/Controllers/performance/ViewPerformanceCycle.php',
            'app/adms/Controllers/performance/UpdatePerformanceCycle.php',
        ] as $file) {
            self::assertFileExists(PROJECT_ROOT . '/' . $file);
        }
    }

    public function testServiceBlocksClosedCycleLink(): void
    {
        $service = $this->readProjectFile('app/adms/Models/Services/PerformanceCycleService.php');
        self::assertStringContainsString('assertMayLink', $service);
        self::assertStringContainsString('closed', $service);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
