<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\AiAnalyst\Planning\AiPlannerInterface;
use App\adms\Models\Services\AiAnalyst\Planning\AiPlannerResult;
use App\adms\Models\Services\AiAnalyst\Planning\AiPreferLlmPlanner;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolDefinition;
use PHPUnit\Framework\TestCase;

final class AiPreferLlmPlannerTest extends TestCase
{
    public function testBusinessUsesLlmPlanWhenConfigured(): void
    {
        $llm = new class implements AiPlannerInterface {
            public int $calls = 0;

            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                $this->calls++;

                return new AiPlannerResult([
                    'goal' => 'compare_periods',
                    'steps' => [
                        [
                            'step_id' => 's1',
                            'tool' => 'report.run_approved',
                            'arguments' => ['report_binding' => 'sales_summary', 'month' => 1, 'year' => 2024],
                            'depends_on' => [],
                        ],
                        [
                            'step_id' => 's2',
                            'tool' => 'report.run_approved',
                            'arguments' => ['report_binding' => 'sales_summary', 'month' => 1, 'year' => 2025],
                            'depends_on' => [],
                        ],
                        [
                            'step_id' => 's3',
                            'tool' => 'report.run_approved',
                            'arguments' => ['report_binding' => 'sales_summary', 'month' => 1, 'year' => 2026],
                            'depends_on' => [],
                        ],
                        [
                            'step_id' => 's4',
                            'tool' => 'analysis.compare',
                            'arguments' => ['step_ids' => ['s1', 's2', 's3']],
                            'depends_on' => ['s1', 's2', 's3'],
                        ],
                    ],
                ], 'gemini');
            }
        };
        $fallback = new class implements AiPlannerInterface {
            public int $calls = 0;

            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                $this->calls++;

                return new AiPlannerResult(['goal' => 'clarify', 'steps' => []], 'local-simulated');
            }
        };

        $planner = new AiPreferLlmPlanner($llm, $fallback, static fn (): bool => true);
        $result = $planner->plan('compare 3 janeiros', 'business', [
            new AiToolDefinition('report.run_approved', 'x', ['business'], ['report_binding' => 'string']),
        ]);

        self::assertSame(1, $llm->calls);
        self::assertSame(1, $fallback->calls); // probe year_series antes do LLM
        self::assertSame('gemini', $result->provider);
        self::assertCount(4, $result->plan['steps'] ?? []);
    }

    public function testYearSeriesSkipsLlmAndUsesDeterministicPlan(): void
    {
        $llm = new class implements AiPlannerInterface {
            public int $calls = 0;

            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                $this->calls++;

                return new AiPlannerResult([
                    'goal' => 'compare_periods',
                    'steps' => [],
                ], 'gemini');
            }
        };
        $fallback = new class implements AiPlannerInterface {
            public int $calls = 0;

            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                $this->calls++;

                return new AiPlannerResult([
                    'goal' => 'year_series',
                    'steps' => [[
                        'step_id' => 's1',
                        'tool' => 'report.run_approved',
                        'arguments' => ['report_binding' => 'sales_summary', 'year' => 2025],
                        'depends_on' => [],
                    ]],
                ], 'local-simulated');
            }
        };

        $result = (new AiPreferLlmPlanner($llm, $fallback, static fn (): bool => true))
            ->plan('compare os 12 meses de 2025', 'business', []);

        self::assertSame(0, $llm->calls);
        self::assertSame(1, $fallback->calls);
        self::assertSame('year_series', $result->plan['goal'] ?? null);
        self::assertSame(2025, $result->plan['steps'][0]['arguments']['year'] ?? null);
    }

    public function testBusinessFallsBackWhenLlmUnavailable(): void
    {
        $llm = new class implements AiPlannerInterface {
            public int $calls = 0;

            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                $this->calls++;

                return new AiPlannerResult(null, 'none', 'sem llm');
            }
        };
        $fallback = new class implements AiPlannerInterface {
            public int $calls = 0;

            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                $this->calls++;

                return new AiPlannerResult(['goal' => 'clarify', 'steps' => []], 'local-simulated');
            }
        };

        $planner = new AiPreferLlmPlanner($llm, $fallback, static fn (): bool => false);
        $result = $planner->plan('compare vendas', 'business', []);

        self::assertSame(0, $llm->calls);
        self::assertSame(1, $fallback->calls);
        self::assertSame('local-simulated', $result->provider);
    }

    public function testBusinessFallsBackWhenLlmReturnsNullPlan(): void
    {
        $llm = new class implements AiPlannerInterface {
            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                return new AiPlannerResult(null, 'llm-error', 'falhou');
            }
        };
        $fallback = new class implements AiPlannerInterface {
            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                return new AiPlannerResult(['goal' => 'clarify', 'steps' => []], 'local-simulated');
            }
        };

        $result = (new AiPreferLlmPlanner($llm, $fallback, static fn (): bool => true))
            ->plan('compare vendas', 'business', []);

        self::assertSame('local-simulated', $result->provider);
    }

    public function testNonBusinessAlwaysUsesFallbackChain(): void
    {
        $llm = new class implements AiPlannerInterface {
            public int $calls = 0;

            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                $this->calls++;

                return new AiPlannerResult(['goal' => 'x', 'steps' => []], 'gemini');
            }
        };
        $fallback = new class implements AiPlannerInterface {
            public int $calls = 0;

            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                $this->calls++;

                return new AiPlannerResult(['goal' => 'technical', 'steps' => []], 'local-catalog');
            }
        };

        $result = (new AiPreferLlmPlanner($llm, $fallback, static fn (): bool => true))
            ->plan('campos da OITM', 'technical', []);

        self::assertSame(0, $llm->calls);
        self::assertSame(1, $fallback->calls);
        self::assertSame('local-catalog', $result->provider);
    }
}
