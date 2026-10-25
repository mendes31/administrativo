<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\AiAnalyst\AiAnalystOrchestrator;
use App\adms\Models\Services\AiAnalyst\Auth\AiActorContext;
use App\adms\Models\Services\AiAnalyst\Feature\AiAnalystFeatureFlags;
use App\adms\Models\Services\AiAnalyst\Planning\AiPlannerInterface;
use App\adms\Models\Services\AiAnalyst\Planning\AiPlannerResult;
use App\adms\Models\Services\AiAnalyst\Planning\AiPlanValidator;
use App\adms\Models\Services\AiAnalyst\Policy\PolicyDecision;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolDefinition;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolExecutionContext;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolInterface;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolRegistry;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolResult;
use PHPUnit\Framework\TestCase;

final class AiAnalystOrchestratorTest extends TestCase
{
    public function testDoesNotCallPlannerWhenPlannerFlagIsOff(): void
    {
        $planner = new class implements AiPlannerInterface {
            public bool $called = false;
            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                $this->called = true;
                return new AiPlannerResult(null, 'test');
            }
        };
        $registry = new AiToolRegistry();
        $flags = new AiAnalystFeatureFlags([
            'AI_ANALYST_ENABLED' => true,
            'AI_ANALYST_BUSINESS_ENABLED' => true,
        ]);
        $orchestrator = new AiAnalystOrchestrator($flags, $planner, $registry, new AiPlanValidator($registry));

        $result = $orchestrator->handle('teste', 'business', new AiActorContext(10, false, []));

        self::assertFalse($planner->called);
        self::assertSame('ai.mode.ready', $result['tool']);
    }

    public function testExecutesOnlyRegisteredAndAuthorizedTool(): void
    {
        $tool = new class implements AiToolInterface {
            public int $executions = 0;
            public function definition(): AiToolDefinition
            {
                return new AiToolDefinition('report.safe', 'Teste', ['business'], ['binding' => 'string']);
            }
            public function authorize(AiActorContext $actor, string $profile, array $arguments): PolicyDecision
            {
                return $actor->canUseLegacyTool('report.run')
                    ? PolicyDecision::allow()
                    : PolicyDecision::deny('sem ACL');
            }
            public function execute(AiToolExecutionContext $context, array $arguments): AiToolResult
            {
                $this->executions++;
                return new AiToolResult(true, 'Relatório concluído.', ['rows' => [['total' => 10]]]);
            }
        };
        $planner = new class implements AiPlannerInterface {
            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                return new AiPlannerResult([
                    'goal' => 'Relatório homologado',
                    'steps' => [[
                        'step_id' => 's1',
                        'tool' => 'report.safe',
                        'arguments' => ['binding' => 'faturamento'],
                        'depends_on' => [],
                    ]],
                ], 'fake-llm');
            }
        };
        $registry = new AiToolRegistry();
        $registry->register($tool);
        $flags = new AiAnalystFeatureFlags([
            'AI_ANALYST_ENABLED' => true,
            'AI_ANALYST_BUSINESS_ENABLED' => true,
            'AI_ANALYST_PLANNER_ENABLED' => true,
            'AI_ANALYST_TOOL_EXECUTION_ENABLED' => true,
        ]);
        $orchestrator = new AiAnalystOrchestrator($flags, $planner, $registry, new AiPlanValidator($registry));

        $result = $orchestrator->handle(
            'faturamento',
            'business',
            new AiActorContext(10, false, ['report.run'])
        );

        self::assertSame(1, $tool->executions);
        self::assertSame('Relatório concluído.', $result['resposta']);
        self::assertSame('business', $result['mode']);
        self::assertSame([['total' => 10]], $result['data']['rows']);
    }

    public function testInvalidPlanCannotExecuteTool(): void
    {
        $tool = new class implements AiToolInterface {
            public int $executions = 0;
            public function definition(): AiToolDefinition
            {
                return new AiToolDefinition('report.safe', 'Teste', ['business']);
            }
            public function authorize(AiActorContext $actor, string $profile, array $arguments): PolicyDecision
            {
                return PolicyDecision::allow();
            }
            public function execute(AiToolExecutionContext $context, array $arguments): AiToolResult
            {
                $this->executions++;
                return new AiToolResult(true, 'não deveria executar');
            }
        };
        $planner = new class implements AiPlannerInterface {
            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                return new AiPlannerResult([
                    'goal' => 'SELECT * FROM OINV',
                    'steps' => [[
                        'step_id' => 's1',
                        'tool' => 'report.safe',
                        'arguments' => [],
                        'depends_on' => [],
                    ]],
                ], 'malicious-llm');
            }
        };
        $registry = new AiToolRegistry();
        $registry->register($tool);
        $flags = new AiAnalystFeatureFlags([
            'AI_ANALYST_ENABLED' => true,
            'AI_ANALYST_BUSINESS_ENABLED' => true,
            'AI_ANALYST_PLANNER_ENABLED' => true,
            'AI_ANALYST_TOOL_EXECUTION_ENABLED' => true,
        ]);
        $orchestrator = new AiAnalystOrchestrator($flags, $planner, $registry, new AiPlanValidator($registry));

        $result = $orchestrator->handle('ataque', 'business', new AiActorContext(10, false, []));

        self::assertSame(0, $tool->executions);
        self::assertSame('ai.plan.denied', $result['tool']);
    }
}
