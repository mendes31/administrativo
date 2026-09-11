<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Helpers\SstEpiTamanhoHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SstEpiTamanhoHelper::class)]
final class SstEpiTamanhoHelperTest extends TestCase
{
    public function testNormalizeRemovePrefixoEEspacos(): void
    {
        self::assertSame('38', SstEpiTamanhoHelper::normalize('nº 38'));
        self::assertSame('GG', SstEpiTamanhoHelper::normalize(' Tam GG '));
        self::assertSame('XXG', SstEpiTamanhoHelper::normalize('xxg'));
        self::assertSame('', SstEpiTamanhoHelper::normalize('  '));
    }

    public function testParseGradePresetsELista(): void
    {
        self::assertSame(['34', '35', '36'], SstEpiTamanhoHelper::parseGrade('34, 35;36'));
        self::assertContains('38', SstEpiTamanhoHelper::parseGrade('calcado'));
        self::assertSame(['PP', 'P', 'M', 'G', 'GG', 'XG', 'XXG'], SstEpiTamanhoHelper::parseGrade('vestuario'));
        self::assertSame([], SstEpiTamanhoHelper::parseGrade(''));
    }

    public function testDetectPreset(): void
    {
        self::assertSame(SstEpiTamanhoHelper::PRESET_NENHUM, SstEpiTamanhoHelper::detectPreset([]));
        self::assertSame(SstEpiTamanhoHelper::PRESET_CALCADO, SstEpiTamanhoHelper::detectPreset(SstEpiTamanhoHelper::calcado()));
        self::assertSame(SstEpiTamanhoHelper::PRESET_VESTUARIO, SstEpiTamanhoHelper::detectPreset(SstEpiTamanhoHelper::vestuario()));
        self::assertSame(SstEpiTamanhoHelper::PRESET_PERSONALIZADA, SstEpiTamanhoHelper::detectPreset(['36', '37', '38']));
    }

    public function testIsAllowedEFormulario(): void
    {
        $grade = ['34', '35', '36'];
        self::assertTrue(SstEpiTamanhoHelper::isAllowed('nº 35', $grade));
        self::assertFalse(SstEpiTamanhoHelper::isAllowed('42', $grade));

        $calcado = SstEpiTamanhoHelper::fromForm(['grade_preset' => 'calcado']);
        self::assertSame(1, $calcado['controla_tamanho']);
        self::assertNotNull($calcado['grade_tamanhos']);

        $off = SstEpiTamanhoHelper::fromForm(['grade_preset' => '']);
        self::assertSame(0, $off['controla_tamanho']);
        self::assertNull($off['grade_tamanhos']);
    }

    public function testMinimoEfetivoUsaOverrideOuPadrao(): void
    {
        self::assertSame(3, SstEpiTamanhoHelper::minimoEfetivo('38', 3, []));
        self::assertSame(8, SstEpiTamanhoHelper::minimoEfetivo('nº 38', 3, ['38' => 8]));
        self::assertSame(3, SstEpiTamanhoHelper::minimoEfetivo('39', 3, ['38' => 8]));
        self::assertSame(0, SstEpiTamanhoHelper::minimoEfetivo('38', 0, []));
    }

    public function testParseMinimosPostIgnoraVazioEZero(): void
    {
        $grade = ['36', '37', '38'];
        $map = SstEpiTamanhoHelper::parseMinimosPost([
            'min_tamanho' => ['36' => '', '37' => '0', '38' => '8'],
        ], $grade);
        self::assertSame(['38' => 8], $map);
    }

    public function testParseMinimosListaAceitaPares(): void
    {
        $grade = SstEpiTamanhoHelper::calcado();
        self::assertSame(
            ['38' => 8, '42' => 5],
            SstEpiTamanhoHelper::parseMinimosLista('38=8, 42:5', $grade)
        );
        self::assertSame([], SstEpiTamanhoHelper::parseMinimosLista('99=4', $grade));
        self::assertSame(['GG' => 6], SstEpiTamanhoHelper::parseMinimosLista('GG=6', SstEpiTamanhoHelper::vestuario()));
    }

    public function testLinhasEstoqueMarcaTamanhoAbaixoDoMinimo(): void
    {
        $linhas = SstEpiTamanhoHelper::linhasEstoquePorTamanho(
            ['37', '38', '39'],
            [
                ['tamanho' => '38', 'saldo' => 2, 'cas' => []],
                ['tamanho' => '39', 'saldo' => 10, 'cas' => []],
            ],
            3,
            ['38' => 8]
        );
        $porTam = [];
        foreach ($linhas as $l) {
            $porTam[$l['tamanho']] = $l;
        }
        self::assertTrue($porTam['37']['estoque_baixo']);
        self::assertSame(3, $porTam['37']['minimo']);
        self::assertSame(0, $porTam['37']['saldo']);
        self::assertTrue($porTam['38']['estoque_baixo']);
        self::assertSame(8, $porTam['38']['minimo']);
        self::assertFalse($porTam['39']['estoque_baixo']);
    }
}
