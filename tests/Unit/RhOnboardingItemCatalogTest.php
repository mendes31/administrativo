<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\RhOnboardingItemCatalog;
use PHPUnit\Framework\TestCase;

final class RhOnboardingItemCatalogTest extends TestCase
{
    public function testDefaultsIncludeRequiredAccessItem(): void
    {
        $codes = array_column(RhOnboardingItemCatalog::defaults(), 'codigo');
        self::assertContains('conta_acesso', $codes);
        self::assertContains('treinamentos_obrigatorios', $codes);
        self::assertNotEmpty(RhOnboardingItemCatalog::defaults());
    }
}
