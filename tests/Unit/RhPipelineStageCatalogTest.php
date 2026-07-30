<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\RhPipelineStageCatalog;
use PHPUnit\Framework\TestCase;

final class RhPipelineStageCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        RhPipelineStageCatalog::clearCache();
    }

    public function testDefaultsContainStableCodesIncludingBancoTalentos(): void
    {
        $codes = array_column(RhPipelineStageCatalog::defaults(), 'code');
        self::assertSame(
            ['candidatado', 'em_entrevista', 'aprovado', 'banco_talentos', 'reprovado', 'desistiu'],
            $codes
        );
    }

    public function testIsValidCodeAcceptsLegacyEmAnalise(): void
    {
        self::assertTrue(RhPipelineStageCatalog::isValidCode('em_entrevista'));
        self::assertTrue(RhPipelineStageCatalog::isValidCode('em_analise'));
        self::assertFalse(RhPipelineStageCatalog::isValidCode('invalido'));
    }

    public function testLabelFallsBackGracefully(): void
    {
        self::assertSame('Candidatado', RhPipelineStageCatalog::label('candidatado'));
        self::assertSame('Em Entrevista', RhPipelineStageCatalog::label('em_analise'));
    }
}
