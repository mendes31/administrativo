<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Helpers\SstEpiCategoriaHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SstEpiCategoriaHelper::class)]
final class SstEpiCategoriaHelperTest extends TestCase
{
    public function testCanonicalizeDeTroncoViraDoTronco(): void
    {
        self::assertSame(
            SstEpiCategoriaHelper::TRONCO,
            SstEpiCategoriaHelper::canonicalize('Proteção de Tronco')
        );
        self::assertSame(
            SstEpiCategoriaHelper::TRONCO,
            SstEpiCategoriaHelper::canonicalize('  Proteção do Tronco  ')
        );
    }

    public function testCanonicalizeAcentoTrocadoNaCabeca(): void
    {
        self::assertSame(
            SstEpiCategoriaHelper::CABECA,
            SstEpiCategoriaHelper::canonicalize('Proteçaõ de Cabeça')
        );
    }

    public function testIsValidAceitaVariacao(): void
    {
        self::assertTrue(SstEpiCategoriaHelper::isValid('Proteção de Tronco'));
        self::assertFalse(SstEpiCategoriaHelper::isValid(''));
        self::assertFalse(SstEpiCategoriaHelper::isValid('Categoria inventada'));
    }
}
