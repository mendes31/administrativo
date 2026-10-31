<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\InternalChat\ChatDynamicReportService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class ChatReportCodeSqlWrapTest extends TestCase
{
    public function testPartnerCodeInjectsCardCodeBeforeGroupByOnSalesMonthSql(): void
    {
        $sql = <<<'SQL'
SELECT
    TO_VARCHAR(YEAR("DocDate")) || '/' || LPAD(TO_VARCHAR(MONTH("DocDate")), 2, '0') AS "MesAno",
    SUM("LineTotal") AS "Liquido"
FROM "VW_CRM_VENDAS"
WHERE "DocDate" >= '2024-01-01'
GROUP BY YEAR("DocDate"), MONTH("DocDate")
ORDER BY YEAR("DocDate") DESC, MONTH("DocDate") DESC
SQL;

        $wrapped = $this->wrap($sql, ['CardCode'], 'C00001');

        self::assertNotNull($wrapped);
        self::assertStringContainsString("\"CardCode\" = 'C00001'", (string) $wrapped);
        self::assertMatchesRegularExpression('/AND\s*\(\s*"CardCode"\s*=\s*\'C00001\'\s*\)\s*GROUP\s+BY/i', (string) $wrapped);
        self::assertStringNotContainsString('_chat_f', (string) $wrapped);
    }

    public function testPartnerCodeUsesTableAliasWhenPresent(): void
    {
        $sql = <<<'SQL'
SELECT YEAR(T0."DocDate") AS "Ano", SUM(T0."DocTotal") AS "Total"
FROM OINV T0
GROUP BY YEAR(T0."DocDate")
SQL;

        $wrapped = $this->wrap($sql, [], 'C00001');

        self::assertNotNull($wrapped);
        self::assertStringContainsString('T0."CardCode" = \'C00001\'', (string) $wrapped);
    }

    public function testItemCodeOnSalesInjectsItemCodeBeforeGroupBy(): void
    {
        $sql = <<<'SQL'
SELECT MONTH("DocDate") AS "Mes", SUM("LineTotal") AS "Liquido"
FROM "VW_CRM_VENDAS"
GROUP BY MONTH("DocDate")
SQL;

        $wrapped = $this->wrap($sql, ['ItemCode'], '43000001');

        self::assertNotNull($wrapped);
        self::assertStringContainsString("\"ItemCode\" = '43000001'", (string) $wrapped);
        self::assertMatchesRegularExpression(
            '/(?:WHERE|AND)\s*\(\s*"ItemCode"\s*=\s*\'43000001\'\s*\)\s*GROUP\s+BY/i',
            (string) $wrapped
        );
    }

    public function testFilterCodeLabelIncludesPartnerName(): void
    {
        $service = (new \ReflectionClass(ChatDynamicReportService::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(ChatDynamicReportService::class, 'formatFilterCodeLabel');

        self::assertSame('«C00001»', $method->invoke($service, 'C00001', null));
        self::assertSame(
            '«C00001» (ISABEL CRISTINA BUSS BALK)',
            $method->invoke($service, 'C00001', 'ISABEL CRISTINA BUSS BALK')
        );
    }

    public function testExtractPartnerNameFromRows(): void
    {
        $service = (new \ReflectionClass(ChatDynamicReportService::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(ChatDynamicReportService::class, 'extractPartnerNameFromRows');

        self::assertSame(
            'ISABEL CRISTINA BUSS BALK',
            $method->invoke($service, [['MesAno' => '01/2025', 'RazãoSocial' => 'ISABEL CRISTINA BUSS BALK']])
        );
        self::assertNull($method->invoke($service, [['MesAno' => '01/2025', 'Liquido' => 100]]));
    }

    public function testSalesQueryDoesNotScorePartnerCatalogReport(): void
    {
        $service = (new \ReflectionClass(ChatDynamicReportService::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(ChatDynamicReportService::class, 'scoreMatch');

        $parceiros = [
            'name' => '[FILTRO] Parceiros',
            'chat_tool_name' => 'parceiros',
            'chat_description' => 'Cadastro de parceiros',
            'custom_sql' => 'SELECT T0."CardCode" AS "ParceiroID" FROM OCRD T0',
            'chat_example_prompts' => ['parceiro C00001', 'cliente C00001'],
        ];
        $vendasMes = [
            'name' => 'Vendas por mês',
            'chat_tool_name' => 'vendas_mes',
            'chat_description' => 'Série mensal',
            'custom_sql' => 'SELECT MONTH("DocDate") AS "Mes", SUM("LineTotal") AS "Liquido" FROM "VW_CRM_VENDAS" GROUP BY MONTH("DocDate")',
            'chat_example_prompts' => ['vendas por mes'],
        ];

        self::assertSame(0, $method->invoke($service, 'vendas para clientes c00001', $parceiros, false));
        self::assertGreaterThanOrEqual(70, $method->invoke($service, 'vendas', $vendasMes, true));
    }

    private function wrap(string $sql, array $columns, string $code): ?string
    {
        $service = (new \ReflectionClass(ChatDynamicReportService::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(ChatDynamicReportService::class, 'wrapSqlWithCodeFilter');

        /** @var ?string $result */
        $result = $method->invoke($service, $sql, $columns, $code);

        return $result;
    }
}
