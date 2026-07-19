<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\RhOffboardingItemCatalog;
use PHPUnit\Framework\TestCase;

final class RhOffboardingItemCatalogTest extends TestCase
{
    public function testDefaultsIncludeRequiredAndOptional(): void
    {
        $items = RhOffboardingItemCatalog::defaults();
        self::assertNotEmpty($items);

        $codes = array_column($items, 'codigo');
        self::assertContains('devolucao_equipamentos', $codes);
        self::assertContains('entrevista_desligamento', $codes);

        $required = array_filter($items, static fn (array $i): bool => !empty($i['obrigatorio']));
        self::assertGreaterThanOrEqual(3, count($required));
    }
}
