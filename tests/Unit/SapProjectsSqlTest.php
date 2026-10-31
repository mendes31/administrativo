<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\SapProjects\SapProjectsSql;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SapProjectsSqlTest extends TestCase
{
    public function testNormalizeIgnoresEmptyAndUnknownFilters(): void
    {
        $filters = (new SapProjectsSql(''))->normalizeFilters([
            'status' => 's',
            'tipo' => '',
            'projeto' => '5',
            'qualquer' => 'x',
        ]);

        self::assertSame(['projeto' => 5, 'status' => 'S'], $filters);
    }

    public function testNormalizeSwapsInvertedDocumentPeriod(): void
    {
        $filters = (new SapProjectsSql(''))->normalizeFilters(['data_de' => '2026-12-31', 'data_ate' => '2026-01-01']);

        self::assertSame('2026-01-01', $filters['data_de']);
        self::assertSame('2026-12-31', $filters['data_ate']);
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidValues(): iterable
    {
        yield 'injeção em código' => ['cardcode', "C001' OR '1'='1"];
        yield 'projeto não numérico' => ['projeto', '5 OR 1=1'];
        yield 'status com 2 letras' => ['status', 'SS'];
        yield 'data inexistente' => ['data_de', '2026-02-30'];
        yield 'data em outro formato' => ['data_ate', '28/10/2026'];
        yield 'ano fora da faixa' => ['ano_projeto', '1800'];
        yield 'item com espaço e ponto e vírgula' => ['item', 'A; DROP'];
    }

    #[DataProvider('invalidValues')]
    public function testRejectsValuesOutsideTheWhitelistTypes(string $filter, string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SapProjectsSql(''))->normalizeFilters([$filter => $value]);
    }

    public function testProjectQueryOnlyAppliesProjectFilters(): void
    {
        $sql = new SapProjectsSql('');
        $q = $sql->projetos($sql->normalizeFilters([
            'status' => 'S',
            'cardcode' => 'C00001',
            'data_de' => '2026-01-01',
            'fornecedor' => 'F00012',
        ]));

        self::assertStringContainsString('T0."STATUS" = \'S\'', $q);
        self::assertStringContainsString('T0."CARDCODE" = \'C00001\'', $q);
        self::assertStringNotContainsString('TO_DATE(', $q);
        self::assertStringNotContainsString('OP."CardCode" =', $q);
    }

    public function testDocumentsUsePmg4DateAndItemsUseInvoiceDate(): void
    {
        $sql = new SapProjectsSql('');
        $filters = $sql->normalizeFilters(['data_de' => '2026-01-01', 'item' => 'MP-001']);

        $docs = $sql->documentos($filters);
        self::assertStringContainsString(
            "CAST(P4.\"DocDate\" AS DATE) >= TO_DATE('2026-01-01', 'YYYY-MM-DD')",
            $docs
        );
        self::assertStringNotContainsString('L."ItemCode"', $docs);
        self::assertStringNotContainsString('UNION ALL', $docs);

        $itens = $sql->itens($filters, 18);
        self::assertStringContainsString("CAST(OP.\"DocDate\" AS DATE) >= TO_DATE('2026-01-01', 'YYYY-MM-DD')", $itens);
        self::assertStringContainsString("L.\"ItemCode\" = 'MP-001'", $itens);
        self::assertStringContainsString('"PCH1"', $itens);
        self::assertStringNotContainsString('P4."DocDate"', $itens);
    }

    public function testBaseQueriesEndWithDeterministicOrderForPaging(): void
    {
        $sql = new SapProjectsSql('');
        foreach ([$sql->projetos([]), $sql->documentos([]), $sql->itens([])] as $q) {
            self::assertMatchesRegularExpression('/ORDER BY [^;]+$/', $q);
            self::assertStringStartsWith('SELECT', ltrim($q));
        }
    }

    public function testSchemaPrefixesEveryTable(): void
    {
        $q = (new SapProjectsSql('ZZ_TESTE_14_06_26'))->projetos([]);

        self::assertStringContainsString('FROM "ZZ_TESTE_14_06_26"."OPMG" T0', $q);
        self::assertStringContainsString('"ZZ_TESTE_14_06_26"."PMG4" P4', $q);
        self::assertDoesNotMatchRegularExpression('/(?:FROM|JOIN)\s+"(?!ZZ_TESTE_14_06_26")[A-Z0-9]+"\s/', $q);
    }

    public function testInvalidSchemaIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SapProjectsSql('SBO"; DROP');
    }

    public function testDomainDictionariesDriveLabelsInPhp(): void
    {
        $q = (new SapProjectsSql(''))->documentos([]);
        self::assertStringContainsString("WHEN 'S' THEN 'Iniciado'", $q);
        self::assertStringContainsString("WHEN 'P' THEN 'Pausado'", $q);
        self::assertStringContainsString("WHEN 'T' THEN 'Parado'", $q);
        self::assertStringContainsString("WHEN 'F' THEN 'Finalizado'", $q);
        self::assertStringContainsString("WHEN 'N' THEN 'Cancelado'", $q);
        self::assertStringNotContainsString('Tipo não mapeado', $q);
        self::assertSame('Nota Fiscal de Entrada', SapProjectsSql::documentTypeLabel(18));
        self::assertSame('Pedido de compra', SapProjectsSql::documentTypeLabel(22));
        self::assertSame('Pedido de venda', SapProjectsSql::documentTypeLabel(17));
        self::assertSame('Solicitação de compra', SapProjectsSql::documentTypeLabel(1470000113));
        self::assertStringContainsString('Tipo não mapeado (99)', SapProjectsSql::documentTypeLabel(99));
        self::assertSame('Parado', SapProjectsSql::label(SapProjectsSql::STATUS, 'T'));
        self::assertSame('Pausado', SapProjectsSql::label(SapProjectsSql::STATUS, 'P'));
        self::assertSame('X', SapProjectsSql::label(SapProjectsSql::STATUS, 'X'));
    }

    public function testProjetosListSqlStaysShortForGetApi(): void
    {
        $q = (new SapProjectsSql(''))->projetos([]);
        $compact = preg_replace('/\s{2,}/', ' ', str_replace(["\r\n", "\r", "\n", "\t"], ' ', trim($q))) ?? $q;
        $urlLen = strlen('http://x/query?sql=' . rawurlencode($compact));
        self::assertLessThan(7000, $urlLen, 'Listagem de projetos deve caber no GET /query');
        self::assertStringNotContainsString('"PMG1"', $q);
        self::assertStringNotContainsString('"PMG2"', $q);
        self::assertStringContainsString('"PMG4"', $q);
        self::assertStringContainsString('"PMG8"', $q);
        self::assertStringContainsString('MIN("LineID")', $q);
        self::assertStringContainsString('AccInvAP', $q);
        self::assertStringContainsString('"ValorOutrosVinculos"', $q);
        self::assertStringContainsString('"ValorPedidosCompra"', $q);
        self::assertStringContainsString('MAX(OP."DocTotal")', $q);
        self::assertStringContainsString('P4."TYP"=22', $q);
        self::assertStringContainsString('P4."TYP"<>18', $q);
    }

    public function testDocumentHeaderLookupAndItemsArePerObjType(): void
    {
        $sql = new SapProjectsSql('');
        $headers = $sql->documentHeadersByEntries(22, [10, 20]);
        self::assertStringContainsString('"OPOR"', $headers);
        self::assertStringContainsString('IN (10,20)', $headers);

        $itensPo = $sql->itens([], 22);
        self::assertStringContainsString('"POR1"', $itensPo);
        self::assertStringContainsString('"TYP" = 22', $itensPo);
        self::assertStringNotContainsString('UNION ALL', $itensPo);

        self::assertSame('OPRQ', SapProjectsSql::documentSource(1470000113)['header']);
        self::assertSame('ORDR', SapProjectsSql::documentSource(17)['header']);
    }
}
