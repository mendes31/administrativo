<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class CandidatosListScopeContractTest extends TestCase
{
    public function testMigrationRegistersViewAll(): void
    {
        $source = $this->readProjectFile(
            'database/migrations/20260719230000_register_rh_candidatos_view_all_scope.php'
        );
        self::assertStringContainsString('RhCandidatosViewAll', $source);
        self::assertStringContainsString('grantPageToRhCandidatosLevels', $source);
    }

    public function testServiceAndRepositoryApplyRelatedScope(): void
    {
        $service = $this->readProjectFile('app/adms/Models/Services/RhPermissionService.php');
        self::assertStringContainsString('resolveCandidatosListScope', $service);
        self::assertStringContainsString("'related'", $service);

        $repo = $this->readProjectFile('app/adms/Models/Repository/RhCandidatosRepository.php');
        self::assertStringContainsString('cv_scope', $repo);
        self::assertStringContainsString('scope_mode', $repo);

        $controller = $this->readProjectFile('app/adms/Controllers/rh/RhCandidatos.php');
        self::assertStringContainsString('resolveCandidatosListScope', $controller);
    }

    public function testContractMigrationRevokesNonRhViewAll(): void
    {
        $source = $this->readProjectFile(
            'database/migrations/20260727130000_contract_rh_candidatos_view_all_scope.php'
        );
        self::assertStringContainsString('RhCandidatosViewAll', $source);
        self::assertStringContainsString('permission = 0', $source);
        self::assertStringContainsString('recursos', $source);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
