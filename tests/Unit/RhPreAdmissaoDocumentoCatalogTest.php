<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\RhPreAdmissaoDocumentoCatalog;
use PHPUnit\Framework\TestCase;

final class RhPreAdmissaoDocumentoCatalogTest extends TestCase
{
    public function testDefaultsIncludeRequiredIdentityAndBank(): void
    {
        $codes = array_column(RhPreAdmissaoDocumentoCatalog::defaults(), 'codigo');
        self::assertContains('documento_identidade', $codes);
        self::assertContains('dados_bancarios', $codes);
        self::assertNotEmpty(RhPreAdmissaoDocumentoCatalog::defaults());
    }
}
