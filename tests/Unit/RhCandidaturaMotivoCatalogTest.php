<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\RhCandidaturaMotivoCatalog;
use PHPUnit\Framework\TestCase;

final class RhCandidaturaMotivoCatalogTest extends TestCase
{
    public function testValidatesMotivoForTargetStatus(): void
    {
        self::assertTrue(RhCandidaturaMotivoCatalog::isValidForStatus('aprovado', 'PERFIL_ADEQUADO'));
        self::assertFalse(RhCandidaturaMotivoCatalog::isValidForStatus('aprovado', 'DESISTENCIA_CANDIDATO'));
        self::assertFalse(RhCandidaturaMotivoCatalog::isValidForStatus('aprovado', ''));
    }

    public function testOutroRequiresObservacao(): void
    {
        self::assertTrue(RhCandidaturaMotivoCatalog::requiresObservacao('OUTRO'));
        self::assertFalse(RhCandidaturaMotivoCatalog::requiresObservacao('TRIAGEM_OK'));
    }

    public function testEntrevistaResultadoMapsToMotivo(): void
    {
        self::assertSame('APROVADO_ENTREVISTA', RhCandidaturaMotivoCatalog::forEntrevistaResultado('aprovado'));
        self::assertSame('REPROVADO_ENTREVISTA', RhCandidaturaMotivoCatalog::forEntrevistaResultado('reprovado'));
        self::assertNull(RhCandidaturaMotivoCatalog::forEntrevistaResultado('agendado'));
    }

    public function testLabelFallsBackToCodigo(): void
    {
        self::assertSame('Triagem aprovada', RhCandidaturaMotivoCatalog::label('TRIAGEM_OK'));
        self::assertSame('CODIGO_DESCONHECIDO', RhCandidaturaMotivoCatalog::label('CODIGO_DESCONHECIDO'));
        self::assertSame('-', RhCandidaturaMotivoCatalog::label(null));
    }
}
