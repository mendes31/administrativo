<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Contrato do histórico imutável de candidatura (Fase 1 Expand).
 */
#[CoversNothing]
final class CandidaturaHistoricoContractTest extends TestCase
{
    public function testHistoricoRepositoryIsAppendOnly(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Models/Repository/RhCandidaturaHistoricoRepository.php'
        );

        self::assertStringContainsString('function registrar', $source);
        self::assertStringContainsString('function listByCandidato', $source);
        self::assertStringNotContainsString('function update', $source);
        self::assertStringNotContainsString('function delete', $source);
        self::assertStringContainsString('INSERT INTO rh_candidaturas_historico', $source);
    }

    public function testMigrationCreatesAppendOnlyTableWithBackfill(): void
    {
        $source = $this->readProjectFile(
            'database/migrations/20260719140000_create_rh_candidaturas_historico.php'
        );

        self::assertStringContainsString('rh_candidaturas_historico', $source);
        self::assertStringContainsString("tipo_evento = 'backfill'", $source);
        self::assertStringContainsString('fk_rh_cand_hist_candidato', $source);
        self::assertStringNotContainsString('updated_at', $source);
    }

    public function testEntrevistaEditPassesOrigemAndEntrevistaId(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Controllers/rh/RhEntrevistasEdit.php'
        );

        self::assertStringContainsString('ORIGEM_ENTREVISTA', $source);
        self::assertStringContainsString('atualizarStatusVinculo(', $source);
        self::assertStringContainsString('forEntrevistaResultado', $source);
    }

    public function testPipelineControllerRequiresStructuredMotivo(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Controllers/rh/RhAtualizarStatusCandidatura.php'
        );

        self::assertStringContainsString('motivo_codigo', $source);
        self::assertStringContainsString('RhCandidaturaMotivoCatalog::isValidForStatus', $source);
        self::assertStringContainsString('requiresObservacao', $source);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
