<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\RhEntrevistaScorecardCatalog;
use PHPUnit\Framework\TestCase;

final class RhEntrevistaScorecardCatalogTest extends TestCase
{
    public function testDefaultCriteriaHaveStableCodesAndPositiveWeights(): void
    {
        $criteria = RhEntrevistaScorecardCatalog::defaultCriteria();
        self::assertNotEmpty($criteria);

        $codes = [];
        foreach ($criteria as $item) {
            self::assertArrayHasKey('codigo', $item);
            self::assertArrayHasKey('label', $item);
            self::assertArrayHasKey('peso', $item);
            self::assertGreaterThan(0, (int) $item['peso']);
            $codes[] = $item['codigo'];
        }

        self::assertSame($codes, array_unique($codes));
        self::assertContains('COMUNICACAO', $codes);
        self::assertContains('EXPERIENCIA_TECNICA', $codes);
    }

    public function testCalcularNotaPonderadaIgnoresMissingNotes(): void
    {
        $nota = RhEntrevistaScorecardCatalog::calcularNotaPonderada([
            ['peso' => 20, 'nota' => 10],
            ['peso' => 30, 'nota' => 5],
            ['peso' => 50, 'nota' => null],
        ]);

        // (10*20 + 5*30) / 50 = 7
        self::assertSame(7.0, $nota);
    }

    public function testCalcularNotaPonderadaReturnsNullWhenEmpty(): void
    {
        self::assertNull(RhEntrevistaScorecardCatalog::calcularNotaPonderada([]));
        self::assertNull(RhEntrevistaScorecardCatalog::calcularNotaPonderada([
            ['peso' => 10, 'nota' => null],
        ]));
    }
}
