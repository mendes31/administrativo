<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\AiAnalyst\Adapters\ApprovedReportAdapter;
use App\adms\Models\Services\AiAnalyst\Adapters\FakeReportRunner;
use App\adms\Models\Services\AiAnalyst\Auth\AiActorContext;
use App\adms\Models\Services\AiAnalyst\Tools\RunApprovedReportTool;
use PHPUnit\Framework\TestCase;

final class RunApprovedReportToolPolicyTest extends TestCase
{
    public function testExecutionIsDeniedWhileDataScopeIsNotExplicitlyResolved(): void
    {
        $tool = $this->tool();
        $actor = new AiActorContext(10, false, ['report.run'], ['scope_mode' => 'none']);

        $decision = $tool->authorize($actor, 'business', ['report_binding' => 'faturamento']);

        self::assertFalse($decision->allowed);
        self::assertStringContainsString('acesso total', $decision->reason);
    }

    public function testExplicitAllScopeStillRequiresValidBindingAndPeriod(): void
    {
        $tool = $this->tool();
        $actor = new AiActorContext(10, false, ['report.run'], ['scope_mode' => 'all']);

        self::assertTrue($tool->authorize(
            $actor,
            'business',
            ['report_binding' => 'faturamento', 'month' => 9, 'year' => 2026]
        )->allowed);
        self::assertFalse($tool->authorize(
            $actor,
            'business',
            ['report_binding' => '#42']
        )->allowed);
        self::assertFalse($tool->authorize(
            $actor,
            'business',
            ['report_binding' => 'faturamento', 'month' => 13, 'year' => 2026]
        )->allowed);
        self::assertTrue($tool->authorize(
            $actor,
            'business',
            ['report_binding' => 'faturamento', 'year' => 2025]
        )->allowed);
        self::assertFalse($tool->authorize(
            $actor,
            'business',
            ['report_binding' => 'faturamento', 'month' => 6]
        )->allowed);
    }

    public function testSimulatedSeriesDoesNotRequireBranchScope(): void
    {
        $tool = new RunApprovedReportTool(new FakeReportRunner());
        $actor = new AiActorContext(10, false, ['report.run'], ['scope_mode' => 'none']);

        $decision = $tool->authorize($actor, 'business', [
            'report_binding' => 'sales_summary',
            'month' => 7,
            'year' => 2026,
        ]);

        self::assertTrue($decision->allowed);
    }

    private function tool(): RunApprovedReportTool
    {
        $adapter = (new \ReflectionClass(ApprovedReportAdapter::class))->newInstanceWithoutConstructor();

        return new RunApprovedReportTool($adapter);
    }
}
