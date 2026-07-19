<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\RhExperienciaService;
use PHPUnit\Framework\TestCase;

final class RhExperienciaServiceTest extends TestCase
{
    public function testDefaultDurations(): void
    {
        self::assertSame(90, RhExperienciaService::DIAS_PADRAO);
        self::assertSame(45, RhExperienciaService::DIAS_PRORROGACAO_PADRAO);
    }
}
