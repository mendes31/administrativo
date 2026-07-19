<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class VagasPublicacaoContractTest extends TestCase
{
    public function testMigrationAddsPublicadaDefaultOff(): void
    {
        $migration = $this->readProjectFile(
            'database/migrations/20260719235000_add_rh_vagas_publicacao_flag.php'
        );

        self::assertStringContainsString("'default' => 0", $migration);
        self::assertStringContainsString('publicada', $migration);
        self::assertStringContainsString('publicado_em', $migration);
        self::assertStringContainsString('idx_rh_vagas_publicacao', $migration);
    }

    public function testRepositoryAndValidationEnforceOpenStatus(): void
    {
        $repo = $this->readProjectFile('app/adms/Models/Repository/RhVagasRepository.php');
        self::assertStringContainsString('normalizePublicadaFlag', $repo);
        self::assertStringContainsString("status !== 'aberta'", $repo);
        self::assertStringContainsString('publicada = 0', $repo);

        $validation = $this->readProjectFile(
            'app/adms/Controllers/Services/Validation/ValidationRhVagaService.php'
        );
        self::assertStringContainsString('publicada', $validation);
        self::assertStringContainsString('status Aberta', $validation);
    }

    public function testAdminUiExposesToggleAndFilterWithoutPublicRoute(): void
    {
        $create = $this->readProjectFile('app/adms/Views/rh/vagas/create.php');
        $edit = $this->readProjectFile('app/adms/Views/rh/vagas/edit.php');
        $list = $this->readProjectFile('app/adms/Views/rh/vagas/list.php');
        $controller = $this->readProjectFile('app/adms/Controllers/rh/RhVagas.php');

        self::assertStringContainsString('form[publicada]', $create);
        self::assertStringContainsString('form[publicada]', $edit);
        self::assertStringContainsString('name="publicada"', $list);
        self::assertStringContainsString("'publicada'", $controller);

        $routes = $this->readProjectFile('routes/LoadPageAdm.php');
        self::assertStringNotContainsString('RhVagasPublic', $routes);
        self::assertStringNotContainsString('portal-vagas', $routes);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
