<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class PipelineStagesCatalogContractTest extends TestCase
{
    public function testMigrationSeedsFiveStableCodes(): void
    {
        $source = $this->readProjectFile(
            'database/migrations/20260719150000_create_rh_pipeline_stages.php'
        );

        self::assertStringContainsString('rh_pipeline_stages', $source);
        foreach (['candidatado', 'em_entrevista', 'aprovado', 'reprovado', 'desistiu'] as $code) {
            self::assertStringContainsString("'{$code}'", $source);
        }
    }

    public function testPipelineAndVagaConsumeCatalog(): void
    {
        $pipelineCtrl = $this->readProjectFile('app/adms/Controllers/rh/RhVagasPipeline.php');
        $vagaCtrl = $this->readProjectFile('app/adms/Controllers/rh/RhVagasView.php');
        $pipelineView = $this->readProjectFile('app/adms/Views/rh/vagas/pipeline.php');
        $statusCtrl = $this->readProjectFile('app/adms/Controllers/rh/RhAtualizarStatusCandidatura.php');

        self::assertStringContainsString('RhPipelineStageCatalog::all()', $pipelineCtrl);
        self::assertStringContainsString('RhPipelineStageCatalog::all()', $vagaCtrl);
        self::assertStringContainsString('pipeline_stages', $pipelineView);
        self::assertStringContainsString('RhPipelineStageCatalog::isValidCode', $statusCtrl);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
