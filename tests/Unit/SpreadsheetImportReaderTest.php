<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\Imports\SpreadsheetImportReader;
use App\adms\Models\Services\Imports\SstEquipamentosImportProfile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SpreadsheetImportReader::class)]
final class SpreadsheetImportReaderTest extends TestCase
{
    public function testSkipOptionalLabelRowRemovesLabelHeader(): void
    {
        $fields = (new SstEquipamentosImportProfile())->fields();
        $rows = [
            array_values($fields),
            array_keys($fields),
            ['', '', '', 'EXTINTOR', 'Almoxarifado'],
        ];

        $out = SpreadsheetImportReader::skipOptionalLabelRow($rows);

        self::assertSame(array_keys($fields), $out[0]);
        self::assertSame(['', '', '', 'EXTINTOR', 'Almoxarifado'], $out[1]);
        self::assertCount(2, $out);
    }

    public function testSkipOptionalLabelRowKeepsLegacyFieldHeader(): void
    {
        $rows = [
            ['id', 'codigo', 'patrimonio', 'tipo'],
            ['', 'EXT00001', 'P-1', 'EXTINTOR'],
        ];

        $out = SpreadsheetImportReader::skipOptionalLabelRow($rows);

        self::assertSame($rows, $out);
    }

    public function testSuggestFieldMapMatchesKeysAndLabels(): void
    {
        $fields = (new SstEquipamentosImportProfile())->fields();
        $byKey = SpreadsheetImportReader::suggestFieldMap(array_keys($fields), $fields);
        self::assertSame(0, $byKey['id']);
        self::assertSame(array_search('patrimonio', array_keys($fields), true), $byKey['patrimonio']);

        $byLabel = SpreadsheetImportReader::suggestFieldMap(array_values($fields), $fields);
        self::assertSame($byKey['localizacao'], $byLabel['localizacao']);
        self::assertSame($byKey['numero_serie'], $byLabel['numero_serie']);
    }

    public function testLooksLikeFieldHeader(): void
    {
        self::assertTrue(SpreadsheetImportReader::looksLikeFieldHeader(['id', 'codigo', 'data_recarga']));
        self::assertFalse(SpreadsheetImportReader::looksLikeFieldHeader(['ID (chave)', 'Código gerado', 'Patrimônio']));
    }
}
