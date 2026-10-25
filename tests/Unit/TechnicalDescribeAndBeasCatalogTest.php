<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\AiAnalyst\AiAnalystOrchestrator;
use App\adms\Models\Services\AiAnalyst\Auth\AiActorContext;
use App\adms\Models\Services\AiAnalyst\Catalog\AiColumnsResolverSelector;
use App\adms\Models\Services\AiAnalyst\Catalog\AiTechnicalCatalogService;
use App\adms\Models\Services\AiAnalyst\Catalog\HanaTableColumnsResolver;
use App\adms\Models\Services\AiAnalyst\Catalog\LocalCatalogColumnsResolver;
use App\adms\Models\Services\AiAnalyst\Feature\AiAnalystFeatureFlags;
use App\adms\Models\Services\AiAnalyst\Planning\AiDeterministicTechnicalPlanner;
use App\adms\Models\Services\AiAnalyst\Planning\AiPlannerInterface;
use App\adms\Models\Services\AiAnalyst\Planning\AiPlannerResult;
use App\adms\Models\Services\AiAnalyst\Planning\AiPlanValidator;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolRegistry;
use App\adms\Models\Services\AiAnalyst\Tools\CatalogDescribeTableTool;
use App\adms\Models\Services\AiAnalyst\Tools\CatalogTechnicalSearchTool;
use PHPUnit\Framework\TestCase;

final class TechnicalDescribeAndBeasCatalogTest extends TestCase
{
    public function testBeasCatalogIsMergedAndSearchable(): void
    {
        $catalog = new AiTechnicalCatalogService();
        $hits = $catalog->search('BEAS_PERS');

        self::assertNotSame([], $hits);
        self::assertSame('BEAS_PERS', $hits[0]['table_name'] ?? null);
        self::assertSame('BEAS', $hits[0]['module'] ?? null);
        self::assertGreaterThanOrEqual(300, count(array_filter(
            $catalog->entries(),
            static fn (array $e): bool => str_starts_with(strtoupper((string) ($e['table_name'] ?? '')), 'BEAS_')
        )));
    }

    public function testDescribeTableReturnsLocalColumnsForBeasHeader(): void
    {
        $result = $this->orchestrator()->handle(
            'quais campos da tabela BEAS_APL_HEADER?',
            'technical',
            new AiActorContext(10, true, [], ['scope_mode' => 'all'])
        );

        self::assertSame('catalog.describe_table', $result['tool']);
        self::assertSame('local-catalog', $result['provider']);
        self::assertStringContainsString('BEAS_APL_HEADER', $result['resposta']);
        self::assertNotSame([], $result['data']['rows'] ?? []);
        self::assertStringContainsString('HANA não foi consultado', $result['resposta']);
    }

    public function testDescribeOitmUsesKeyFieldsWhenDetailedColumnsMissing(): void
    {
        $catalog = new AiTechnicalCatalogService();
        $resolver = new LocalCatalogColumnsResolver($catalog);
        $resolved = $resolver->resolve('OITM');

        self::assertTrue($resolved['ok']);
        self::assertFalse((new LocalCatalogColumnsResolver($catalog))->usesLiveHana());
        $names = array_column($resolved['columns'], 'name');
        self::assertContains('ItemCode', $names);
    }

    public function testHanaFlagOffKeepsLocalResolver(): void
    {
        $catalog = new AiTechnicalCatalogService();
        $flags = new AiAnalystFeatureFlags([
            'AI_ANALYST_ENABLED' => true,
            'AI_ANALYST_HANA_METADATA_ENABLED' => false,
        ]);
        $resolver = (new AiColumnsResolverSelector())->select($flags, $catalog);

        self::assertInstanceOf(LocalCatalogColumnsResolver::class, $resolver);
        self::assertFalse($resolver->usesLiveHana());
    }

    public function testHanaFlagOnUsesStubWithoutSapCalls(): void
    {
        $catalog = new AiTechnicalCatalogService();
        $flags = new AiAnalystFeatureFlags([
            'AI_ANALYST_ENABLED' => true,
            'AI_ANALYST_HANA_METADATA_ENABLED' => true,
        ]);
        $resolver = (new AiColumnsResolverSelector())->select($flags, $catalog);

        self::assertInstanceOf(HanaTableColumnsResolver::class, $resolver);
        $resolved = $resolver->resolve('OITM');
        self::assertStringContainsString('Nenhuma consulta foi enviada ao HANA', $resolved['message']);
        self::assertStringNotContainsString('SapReportApiService', file_get_contents(
            dirname(__DIR__, 2) . '/app/adms/Models/Services/AiAnalyst/Catalog/HanaTableColumnsResolver.php'
        ) ?: '');
    }

    private function orchestrator(): AiAnalystOrchestrator
    {
        $catalog = new AiTechnicalCatalogService();
        $resolver = new LocalCatalogColumnsResolver($catalog);
        $registry = new AiToolRegistry();
        $registry->register(new CatalogTechnicalSearchTool($catalog));
        $registry->register(new CatalogDescribeTableTool($catalog, $resolver));
        $fallback = new class implements AiPlannerInterface {
            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                return new AiPlannerResult(null, 'llm', 'não deveria planejar');
            }
        };
        $flags = new AiAnalystFeatureFlags([
            'AI_ANALYST_ENABLED' => true,
            'AI_ANALYST_TECHNICAL_ENABLED' => true,
            'AI_ANALYST_PLANNER_ENABLED' => true,
            'AI_ANALYST_TOOL_EXECUTION_ENABLED' => true,
        ]);

        return new AiAnalystOrchestrator(
            $flags,
            new AiDeterministicTechnicalPlanner($fallback),
            $registry,
            new AiPlanValidator($registry)
        );
    }
}
