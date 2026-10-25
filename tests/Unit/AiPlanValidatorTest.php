<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\AiAnalyst\Auth\AiActorContext;
use App\adms\Models\Services\AiAnalyst\Planning\AiPlanValidator;
use App\adms\Models\Services\AiAnalyst\Policy\PolicyDecision;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolDefinition;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolExecutionContext;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolInterface;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolRegistry;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolResult;
use PHPUnit\Framework\TestCase;

final class AiPlanValidatorTest extends TestCase
{
    public function testValidPlanUsesOnlyRegisteredToolAndDeclaredArguments(): void
    {
        $validator = $this->validator();
        $result = $validator->validate([
            'goal' => 'Consultar relatorio',
            'steps' => [[
                'step_id' => 'report_1',
                'tool' => 'report.run_approved',
                'arguments' => ['report_binding' => 'vendas', 'month' => 9, 'year' => 2026],
                'depends_on' => [],
            ]],
        ], 'business');

        self::assertTrue($result->valid, implode(' ', $result->errors));
    }

    public function testGeneratedSqlAndUnknownArgumentsAreRejected(): void
    {
        $validator = $this->validator();
        $result = $validator->validate([
            'goal' => 'Consultar relatorio',
            'steps' => [[
                'step_id' => 'report_1',
                'tool' => 'report.run_approved',
                'arguments' => [
                    'report_binding' => 'vendas',
                    'sql' => 'SELECT * FROM OINV',
                ],
                'depends_on' => [],
            ]],
        ], 'business');

        self::assertFalse($result->valid);
        self::assertNotEmpty($result->errors);
    }

    public function testUnknownToolAndForwardDependencyAreRejected(): void
    {
        $validator = $this->validator();
        $result = $validator->validate([
            'steps' => [
                [
                    'step_id' => 'first',
                    'tool' => 'report.not_registered',
                    'arguments' => [],
                    'depends_on' => ['second'],
                ],
                [
                    'step_id' => 'second',
                    'tool' => 'report.run_approved',
                    'arguments' => ['report_binding' => 'vendas'],
                    'depends_on' => [],
                ],
            ],
        ], 'business');

        self::assertFalse($result->valid);
        self::assertNotEmpty($result->errors);
    }

    private function validator(): AiPlanValidator
    {
        $registry = new AiToolRegistry();
        $registry->register(new class implements AiToolInterface {
            public function definition(): AiToolDefinition
            {
                return new AiToolDefinition(
                    'report.run_approved',
                    'Relatorio homologado',
                    ['business', 'technical'],
                    ['report_binding' => 'string'],
                    ['month' => 'integer', 'year' => 'integer']
                );
            }

            public function authorize(AiActorContext $actor, string $profile, array $arguments): PolicyDecision
            {
                return PolicyDecision::allow();
            }

            public function execute(AiToolExecutionContext $context, array $arguments): AiToolResult
            {
                return new AiToolResult(true, 'ok');
            }
        });

        return new AiPlanValidator($registry);
    }
}
