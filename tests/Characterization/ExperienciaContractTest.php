<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class ExperienciaContractTest extends TestCase
{
    public function testMigrationCreatesPeriodTable(): void
    {
        $migration = $this->readProjectFile(
            'database/migrations/20260719241000_create_rh_periodos_experiencia.php'
        );
        self::assertStringContainsString('rh_periodos_experiencia', $migration);
        self::assertStringContainsString('RhExperienciaView', $migration);
        self::assertStringContainsString('data_fim_prevista', $migration);
    }

    public function testConversionCreatesExperience(): void
    {
        $service = $this->readProjectFile(
            'app/adms/Models/Services/RhConversaoAdmissaoService.php'
        );
        self::assertStringContainsString('RhExperienciaService', $service);
        self::assertStringContainsString('experiencia_id', $service);
    }

    public function testServiceSupportsApproveExtendReject(): void
    {
        $service = $this->readProjectFile(
            'app/adms/Models/Services/RhExperienciaService.php'
        );
        self::assertStringContainsString('aprovar', $service);
        self::assertStringContainsString('reprovar', $service);
        self::assertStringContainsString('prorrogar', $service);
        self::assertStringContainsString('DIAS_PADRAO', $service);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
