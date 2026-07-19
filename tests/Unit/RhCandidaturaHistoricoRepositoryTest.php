<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Repository\RhCandidaturaHistoricoRepository;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RhCandidaturaHistoricoRepositoryTest extends TestCase
{
    public function testEventTypeAndOriginConstants(): void
    {
        self::assertSame('vinculada', RhCandidaturaHistoricoRepository::TIPO_VINCULADA);
        self::assertSame('movimentada', RhCandidaturaHistoricoRepository::TIPO_MOVIMENTADA);
        self::assertSame('desvinculada', RhCandidaturaHistoricoRepository::TIPO_DESVINCULADA);
        self::assertSame('backfill', RhCandidaturaHistoricoRepository::TIPO_BACKFILL);

        self::assertSame('pipeline', RhCandidaturaHistoricoRepository::ORIGEM_PIPELINE);
        self::assertSame('entrevista', RhCandidaturaHistoricoRepository::ORIGEM_ENTREVISTA);
        self::assertSame('sync', RhCandidaturaHistoricoRepository::ORIGEM_SYNC);
    }

    public function testRegistrarRequiresMandatoryFields(): void
    {
        $repo = (new ReflectionClass(RhCandidaturaHistoricoRepository::class))
            ->newInstanceWithoutConstructor();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Dados obrigatórios do histórico de candidatura incompletos.');

        $repo->registrar([
            'rh_candidato_id' => 0,
            'tipo_evento' => '',
            'origem' => '',
        ]);
    }
}
