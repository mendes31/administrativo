<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\AiAnalyst\Adapters\AiReportRunnerSelector;
use App\adms\Models\Services\AiAnalyst\Adapters\ApprovedReportRunnerInterface;
use App\adms\Models\Services\AiAnalyst\Adapters\FakeReportRunner;
use App\adms\Models\Services\AiAnalyst\AiAnalystOrchestrator;
use App\adms\Models\Services\AiAnalyst\Auth\AiActorContext;
use App\adms\Models\Services\AiAnalyst\Catalog\AiTechnicalCatalogService;
use App\adms\Models\Services\AiAnalyst\Feature\AiAnalystFeatureFlags;
use App\adms\Models\Services\AiAnalyst\Planning\AiDeterministicBusinessPlanner;
use App\adms\Models\Services\AiAnalyst\Planning\AiPlannerInterface;
use App\adms\Models\Services\AiAnalyst\Planning\AiPlannerResult;
use App\adms\Models\Services\AiAnalyst\Planning\AiPlanValidator;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolExecutionContext;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolRegistry;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolResult;
use App\adms\Models\Services\AiAnalyst\Tools\ComparePeriodsTool;
use App\adms\Models\Services\AiAnalyst\Tools\ListApprovedReportsTool;
use App\adms\Models\Services\AiAnalyst\Tools\RunApprovedReportTool;
use App\adms\Models\Services\InternalChat\ChatDynamicReportService;
use PHPUnit\Framework\TestCase;

final class SimulatedBusinessComparisonTest extends TestCase
{
    public function testJulyAgainstAugustUsesSimulatedSalesAndSkipsTheLlm(): void
    {
        $fallback = new class implements AiPlannerInterface {
            public int $calls = 0;

            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                $this->calls++;

                return new AiPlannerResult(null, 'llm', 'não deveria planejar');
            }
        };
        $result = $this->orchestrator($fallback)->handle(
            'Compare vendas de agosto e julho de 2026',
            'business',
            new AiActorContext(10, false, ['report.list', 'report.run'], ['scope_mode' => 'none'])
        );

        self::assertSame(0, $fallback->calls);
        self::assertSame('analysis.compare', $result['tool']);
        self::assertSame('local-simulated', $result['provider']);
        self::assertSame(['julho/2026', 'agosto/2026'], $result['data']['chart']['labels']);
        self::assertSame([150000.0, 172500.0], $result['data']['chart']['values']);
        self::assertStringContainsString('R$ 22.500,00 acima', $result['resposta']);
        self::assertStringContainsString('15,0%', $result['resposta']);
        self::assertStringContainsString('não vêm do SAP', $result['resposta']);
        self::assertSame('simulated_comparison', $result['data']['source_type']);
        self::assertSame('sales', $result['data']['semantic_hint']['domain'] ?? null);
        self::assertSame(FakeReportRunner::BINDING, $result['data']['semantic_hint']['binding'] ?? null);
        self::assertContains('OINV', $result['data']['semantic_hint']['tables'] ?? []);
        self::assertContains('ORDR', $result['data']['semantic_hint']['tables'] ?? []);
    }

    public function testSessionSalesContextAllowsPeriodOnlyFollowUp(): void
    {
        $fallback = new class implements AiPlannerInterface {
            public int $calls = 0;

            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                $this->calls++;

                return new AiPlannerResult(null, 'llm', 'não deveria planejar');
            }
        };
        $planner = new AiDeterministicBusinessPlanner(
            $fallback,
            2026,
            null,
            false,
            'sales',
            FakeReportRunner::BINDING
        );
        $registry = new AiToolRegistry();
        $registry->register(new ListApprovedReportsTool(new FakeReportRunner()));
        $registry->register(new RunApprovedReportTool(new FakeReportRunner()));
        $registry->register(new ComparePeriodsTool());

        $plan = $planner->plan(
            'compare jan/26, jun/26',
            'business',
            $registry->definitionsForProfile('business')
        );

        self::assertSame(0, $fallback->calls);
        self::assertSame('compare_periods', $plan->plan['goal'] ?? null);
        self::assertCount(3, $plan->plan['steps'] ?? []);
        self::assertSame(1, $plan->plan['steps'][0]['arguments']['month'] ?? null);
        self::assertSame(2026, $plan->plan['steps'][0]['arguments']['year'] ?? null);
        self::assertSame(6, $plan->plan['steps'][1]['arguments']['month'] ?? null);
    }

    public function testYearSeriesUsesSingleApprovedStepWithoutTwelveCompares(): void
    {
        $fallback = new class implements AiPlannerInterface {
            public int $calls = 0;

            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                $this->calls++;

                return new AiPlannerResult(null, 'llm', 'não deveria planejar');
            }
        };
        $planner = new AiDeterministicBusinessPlanner($fallback, 2026, null, false, 'sales', 'sales_summary');
        $registry = new AiToolRegistry();
        $registry->register(new ListApprovedReportsTool(new FakeReportRunner()));
        $registry->register(new RunApprovedReportTool(new FakeReportRunner()));
        $registry->register(new ComparePeriodsTool());

        $plan = $planner->plan(
            'compare os 12 meses de 2025',
            'business',
            $registry->definitionsForProfile('business')
        );

        self::assertSame(0, $fallback->calls);
        self::assertSame('year_series', $plan->plan['goal'] ?? null);
        self::assertCount(1, $plan->plan['steps'] ?? []);
        self::assertSame('report.run_approved', $plan->plan['steps'][0]['tool'] ?? null);
        self::assertSame(2025, $plan->plan['steps'][0]['arguments']['year'] ?? null);
        self::assertArrayNotHasKey('month', $plan->plan['steps'][0]['arguments'] ?? []);
    }

    public function testThreeNamedPeriodsProduceMultiPeriodAnalysis(): void
    {
        $fallback = new class implements AiPlannerInterface {
            public int $calls = 0;

            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                $this->calls++;

                return new AiPlannerResult(null, 'llm', 'não deveria planejar');
            }
        };
        $result = $this->orchestrator($fallback)->handle(
            'compare vendas de janeiro 2026, junho 2026 e agosto de 2026',
            'business',
            new AiActorContext(10, false, ['report.list', 'report.run'], ['scope_mode' => 'none'])
        );

        self::assertSame(0, $fallback->calls);
        self::assertSame('analysis.compare', $result['tool']);
        self::assertSame(
            ['janeiro/2026', 'junho/2026', 'agosto/2026'],
            $result['data']['chart']['labels']
        );
        self::assertSame(3, $result['data']['analysis']['period_count'] ?? null);
        self::assertStringContainsString('3 períodos', $result['resposta']);
        self::assertStringContainsString('Maior valor', $result['resposta']);
        self::assertStringContainsString('Menor valor', $result['resposta']);
        self::assertStringContainsString('Do primeiro ao último', $result['resposta']);
        self::assertStringContainsString('não vêm do SAP', $result['resposta']);
    }

    public function testMonthAbbreviationsAreAcceptedForComparison(): void
    {
        $fallback = new class implements AiPlannerInterface {
            public int $calls = 0;

            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                $this->calls++;

                return new AiPlannerResult(null, 'llm', 'não deveria planejar');
            }
        };
        $result = $this->orchestrator($fallback)->handle(
            'compare venda de janeiro e jun de 2026',
            'business',
            new AiActorContext(10, false, ['report.list', 'report.run'], ['scope_mode' => 'none'])
        );

        self::assertSame(0, $fallback->calls);
        self::assertSame('analysis.compare', $result['tool']);
        self::assertSame(['janeiro/2026', 'junho/2026'], $result['data']['chart']['labels']);
        self::assertSame([120000.0, 139800.0], $result['data']['chart']['values']);
    }

    public function testUnsupportedDomainDoesNotCallTheLlmOrAReport(): void
    {
        $fallback = new class implements AiPlannerInterface {
            public int $calls = 0;

            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                $this->calls++;

                return new AiPlannerResult(null, 'llm', 'não deveria planejar');
            }
        };
        $result = $this->orchestrator($fallback)->handle(
            'Compare compras de agosto e julho de 2026',
            'business',
            new AiActorContext(10, false, ['report.run'], ['scope_mode' => 'none'])
        );

        self::assertSame(0, $fallback->calls);
        self::assertSame('ai.clarification', $result['tool']);
        self::assertStringContainsString('série de vendas', $result['resposta']);
        self::assertStringContainsString('sem SQL livre', $result['resposta']);
    }

    public function testLivePlannerUsesApprovedProviderLabel(): void
    {
        $fallback = new class implements AiPlannerInterface {
            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                return new AiPlannerResult(null, 'llm', 'não deveria planejar');
            }
        };
        $planner = new AiDeterministicBusinessPlanner($fallback, 2026, null, true);
        $registry = new AiToolRegistry();
        $registry->register(new ListApprovedReportsTool(new FakeReportRunner()));
        $registry->register(new RunApprovedReportTool(new FakeReportRunner()));
        $registry->register(new ComparePeriodsTool());

        $plan = $planner->plan(
            'Compare vendas de janeiro e junho de 2026',
            'business',
            $registry->definitionsForProfile('business')
        );

        self::assertSame('local-approved', $plan->provider);
        self::assertSame('compare_periods', $plan->plan['goal'] ?? null);
    }

    public function testSalesPlanAttachesSemanticHintFromCatalogWithoutSql(): void
    {
        $fallback = new class implements AiPlannerInterface {
            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                return new AiPlannerResult(null, 'llm', 'não deveria planejar');
            }
        };
        $result = $this->orchestrator($fallback, new AiTechnicalCatalogService())->handle(
            'Compare vendas de janeiro e junho de 2026',
            'business',
            new AiActorContext(10, false, ['report.list', 'report.run'], ['scope_mode' => 'none'])
        );

        self::assertSame('analysis.compare', $result['tool']);
        $hint = $result['data']['semantic_hint'] ?? null;
        self::assertIsArray($hint);
        self::assertSame('sales', $hint['domain']);
        self::assertSame(FakeReportRunner::BINDING, $hint['binding']);
        self::assertNotEmpty($hint['tables']);
        self::assertStringContainsString('nenhum SQL', (string) ($hint['note'] ?? ''));
        self::assertStringNotContainsString('SELECT', json_encode($result, JSON_THROW_ON_ERROR));
    }

    public function testComparisonOmitsPercentWhenTheBaselineIsZero(): void
    {
        $tool = new ComparePeriodsTool();
        $context = new AiToolExecutionContext('corr', 'business', new AiActorContext(10, false, []));
        $context->remember('s1', new AiToolResult(true, 'base', [
            'metric' => [
                'binding' => 'sales_summary',
                'label' => 'Vendas simuladas',
                'period_label' => 'janeiro/2026',
                'value' => 0,
                'source_type' => 'simulated_report',
            ],
        ]));
        $context->remember('s2', new AiToolResult(true, 'atual', [
            'metric' => [
                'binding' => 'sales_summary',
                'label' => 'Vendas simuladas',
                'period_label' => 'fevereiro/2026',
                'value' => 10,
                'source_type' => 'simulated_report',
            ],
        ]));

        $result = $tool->execute($context, ['left' => 's1', 'right' => 's2']);

        self::assertTrue($result->success);
        self::assertStringNotContainsString('%', $result->message);
        self::assertStringContainsString('R$ 10,00 acima', $result->message);
    }

    public function testDisabledRealReportFlagNeverBuildsTheLiveRunner(): void
    {
        $selector = new AiReportRunnerSelector(static function (): ApprovedReportRunnerInterface {
            throw new \RuntimeException('ChatDynamicReportService não deveria ser construído.');
        });
        $flags = new AiAnalystFeatureFlags([
            'AI_ANALYST_ENABLED' => true,
            'AI_ANALYST_REAL_REPORTS_ENABLED' => false,
        ]);

        $runner = $selector->select($flags);

        self::assertInstanceOf(FakeReportRunner::class, $runner);
        self::assertFalse($runner->usesLiveReports());
    }

    public function testEnabledRealReportFlagUsesTheInjectedLiveRunner(): void
    {
        $live = new class implements ApprovedReportRunnerInterface {
            public function usesLiveReports(): bool
            {
                return true;
            }

            public function listForUser(int $userId): array
            {
                return [];
            }

            public function run(int $userId, string $binding, ?int $month = null, ?int $year = null): array
            {
                return ['ok' => false, 'resposta' => 'não executado'];
            }
        };
        $selector = new AiReportRunnerSelector(static fn (): ApprovedReportRunnerInterface => $live);
        $flags = new AiAnalystFeatureFlags([
            'AI_ANALYST_ENABLED' => true,
            'AI_ANALYST_REAL_REPORTS_ENABLED' => true,
        ]);

        self::assertSame($live, $selector->select($flags));
        self::assertStringContainsString(
            'new ChatDynamicReportService()',
            (string) file_get_contents(
                dirname(__DIR__, 2) . '/app/adms/Models/Services/AiAnalyst/Adapters/AiReportRunnerSelector.php'
            )
        );
        self::assertTrue(class_exists(ChatDynamicReportService::class));
    }

    private function orchestrator(
        AiPlannerInterface $fallback,
        ?AiTechnicalCatalogService $catalog = null
    ): AiAnalystOrchestrator {
        $runner = new FakeReportRunner();
        $registry = new AiToolRegistry();
        $registry->register(new ListApprovedReportsTool($runner));
        $registry->register(new RunApprovedReportTool($runner));
        $registry->register(new ComparePeriodsTool());
        $flags = new AiAnalystFeatureFlags([
            'AI_ANALYST_ENABLED' => true,
            'AI_ANALYST_BUSINESS_ENABLED' => true,
            'AI_ANALYST_PLANNER_ENABLED' => true,
            'AI_ANALYST_TOOL_EXECUTION_ENABLED' => true,
            'AI_ANALYST_REAL_REPORTS_ENABLED' => false,
        ]);

        return new AiAnalystOrchestrator(
            $flags,
            new AiDeterministicBusinessPlanner($fallback, 2026, $catalog),
            $registry,
            new AiPlanValidator($registry, 8)
        );
    }
}
