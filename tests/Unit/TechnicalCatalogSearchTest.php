<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\AiAnalyst\AiAnalystOrchestrator;
use App\adms\Models\Services\AiAnalyst\Auth\AiActorContext;
use App\adms\Models\Services\AiAnalyst\Catalog\AiTechnicalCatalogService;
use App\adms\Models\Services\AiAnalyst\Feature\AiAnalystFeatureFlags;
use App\adms\Models\Services\AiAnalyst\Planning\AiDeterministicTechnicalPlanner;
use App\adms\Models\Services\AiAnalyst\Planning\AiPlannerInterface;
use App\adms\Models\Services\AiAnalyst\Planning\AiPlannerResult;
use App\adms\Models\Services\AiAnalyst\Planning\AiPlanValidator;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolRegistry;
use App\adms\Models\Services\AiAnalyst\Tools\CatalogTechnicalSearchTool;
use PHPUnit\Framework\TestCase;

final class TechnicalCatalogSearchTest extends TestCase
{
    public function testItemsTableQuestionResolvesToOitmWithoutLlmOrReports(): void
    {
        $fallback = new class implements AiPlannerInterface {
            public int $calls = 0;

            public function plan(string $message, string $profile, array $tools): AiPlannerResult
            {
                $this->calls++;

                return new AiPlannerResult(null, 'llm', 'não deveria planejar');
            }
        };

        $registry = new AiToolRegistry();
        $registry->register(new CatalogTechnicalSearchTool(new AiTechnicalCatalogService()));
        $flags = new AiAnalystFeatureFlags([
            'AI_ANALYST_ENABLED' => true,
            'AI_ANALYST_TECHNICAL_ENABLED' => true,
            'AI_ANALYST_PLANNER_ENABLED' => true,
            'AI_ANALYST_TOOL_EXECUTION_ENABLED' => true,
        ]);
        $orchestrator = new AiAnalystOrchestrator(
            $flags,
            new AiDeterministicTechnicalPlanner($fallback),
            $registry,
            new AiPlanValidator($registry)
        );

        $result = $orchestrator->handle(
            'qual a tabela de itens no SAP HANA?',
            'technical',
            new AiActorContext(10, true, [], ['scope_mode' => 'all'])
        );

        self::assertSame(0, $fallback->calls);
        self::assertSame('catalog.technical_search', $result['tool']);
        self::assertSame('local-catalog', $result['provider']);
        self::assertStringContainsString('OITM', $result['resposta']);
        self::assertStringContainsString('não foi consultado o HANA', $result['resposta']);
        self::assertStringContainsString('Object Type', $result['resposta']);
        self::assertSame('OITM', $result['data']['rows'][0]['Tabela'] ?? null);
        self::assertSame('4', $result['data']['rows'][0]['ObjType'] ?? null);
    }

    public function testCatalogServiceScoresOitmForItemSynonyms(): void
    {
        $hits = (new AiTechnicalCatalogService())->search('tabela de itens sap hana');

        self::assertNotSame([], $hits);
        self::assertSame('OITM', $hits[0]['table_name']);
        self::assertSame('4', $hits[0]['sap_object_type']);
    }

    public function testImportedWorkbookCoversHundredsOfTables(): void
    {
        $entries = (new AiTechnicalCatalogService())->entries();
        $tables = array_values(array_filter(array_map(
            static fn (array $entry): string => strtoupper((string) ($entry['table_name'] ?? '')),
            $entries
        ), static fn (string $table): bool => $table !== '' && $table !== 'BEAS_*'));

        self::assertGreaterThanOrEqual(300, count($tables));
        self::assertContains('OITM', $tables);
        self::assertContains('OCRD', $tables);
        self::assertContains('OINV', $tables);
    }

    public function testObjectTypeLookupFindsItemsTable(): void
    {
        $hits = (new AiTechnicalCatalogService())->search('object type 4');

        self::assertNotSame([], $hits);
        self::assertSame('OITM', $hits[0]['table_name']);
    }

    public function testPortugueseSalesQuestionReturnsSalesTablesWithoutNoise(): void
    {
        $hits = (new AiTechnicalCatalogService())->search('qual a tabela de vendas?');
        $tables = array_map(
            static fn (array $entry): string => strtoupper((string) ($entry['table_name'] ?? '')),
            $hits
        );

        self::assertContains('OINV', $tables);
        self::assertContains('ORDR', $tables);
        self::assertNotContains('OITM', $tables);
        self::assertNotContains('BEAS_*', $tables);
        self::assertStringContainsString('Nota fiscal', (string) ($hits[0]['title'] ?? ''));
    }

    public function testDraftAndRascunhoResolveToOdrf(): void
    {
        $byRascunho = (new AiTechnicalCatalogService())->search('tabela de rascunhos');
        $byDraft = (new AiTechnicalCatalogService())->search('draft');

        self::assertSame('ODRF', $byRascunho[0]['table_name'] ?? null);
        self::assertSame('ODRF', $byDraft[0]['table_name'] ?? null);
        self::assertStringContainsString('Rascunhos', (string) ($byRascunho[0]['title'] ?? ''));
    }
}
