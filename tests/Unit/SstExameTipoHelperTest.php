<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Helpers\SstExameTipoHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SstExameTipoHelper::class)]
final class SstExameTipoHelperTest extends TestCase
{
    public function testConsultaClinicaEEventoDoAso(): void
    {
        self::assertTrue(SstExameTipoHelper::isEventoClinicoAso('Clínico', 'CONSULTA CLINICA'));
        self::assertTrue(SstExameTipoHelper::isEventoClinicoAso('Avaliação Médica', ''));
        self::assertTrue(SstExameTipoHelper::isEventoClinicoAso('Outros', 'CONSULTA CLINICA'));
        self::assertTrue(SstExameTipoHelper::isEventoClinicoAso(null, 'Consulta Clínica'));
        self::assertFalse(SstExameTipoHelper::isEventoClinicoAso('Laboratorial', 'Hemograma'));
        self::assertFalse(SstExameTipoHelper::isEventoClinicoAso('Funcional', 'Audiometria'));
    }
}
