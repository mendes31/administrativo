<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class PerformanceReviewsCycleContractTest extends TestCase
{
    public function testReviewsRepositoryPersistsCycle(): void
    {
        $repo = $this->readProjectFile('app/adms/Models/Repository/PerformanceReviewsRepository.php');
        self::assertStringContainsString('performance_cycle_id', $repo);
        self::assertStringContainsString('adms_performance_cycles', $repo);
    }

    public function testCreateAndUpdateValidateCycle(): void
    {
        $create = $this->readProjectFile('app/adms/Controllers/performance/CreatePerformanceReview.php');
        $update = $this->readProjectFile('app/adms/Controllers/performance/UpdatePerformanceReview.php');
        self::assertStringContainsString('assertMayLink', $create);
        self::assertStringContainsString('performance_cycle_id', $create);
        self::assertStringContainsString('assertMayLink', $update);
        self::assertStringContainsString('listLinkable', $update);
    }

    public function testListFiltersByCycle(): void
    {
        $list = $this->readProjectFile('app/adms/Controllers/performance/ListPerformanceReviews.php');
        self::assertStringContainsString('performance_cycle_id', $list);
        self::assertStringContainsString('PerformanceCyclesRepository', $list);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
