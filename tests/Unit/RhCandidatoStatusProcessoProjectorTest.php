<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\RhCandidatoStatusProcessoProjector;
use PHPUnit\Framework\TestCase;

final class RhCandidatoStatusProcessoProjectorTest extends TestCase
{
    public function testPriorityPrefersAprovado(): void
    {
        self::assertSame(
            'aprovado',
            RhCandidatoStatusProcessoProjector::fromVinculos('candidatado', [
                'candidatado',
                'aprovado',
                'em_entrevista',
            ])
        );
    }

    public function testEmptyVinculosResetsToCandidatado(): void
    {
        self::assertSame(
            'candidatado',
            RhCandidatoStatusProcessoProjector::fromVinculos('aprovado', [])
        );
    }

    public function testProtectedStatusesAreNotOverwritten(): void
    {
        self::assertSame(
            'contratado',
            RhCandidatoStatusProcessoProjector::fromVinculos('contratado', ['reprovado'])
        );
        self::assertSame(
            'anonimizado',
            RhCandidatoStatusProcessoProjector::fromVinculos('anonimizado', ['aprovado'])
        );
    }

    public function testManualEditCanMarkContratado(): void
    {
        self::assertSame(
            'contratado',
            RhCandidatoStatusProcessoProjector::resolveForManualEdit(
                'em_entrevista',
                ['em_entrevista'],
                true
            )
        );
    }

    public function testNormalizesLegacyCodes(): void
    {
        self::assertSame(
            'em_entrevista',
            RhCandidatoStatusProcessoProjector::fromVinculos('recebido', ['em_analise'])
        );
    }
}
