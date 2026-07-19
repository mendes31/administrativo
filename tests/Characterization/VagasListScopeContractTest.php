<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class VagasListScopeContractTest extends TestCase
{
    public function testMigrationRegistersViewAllAndGrantsFromRhVagas(): void
    {
        $source = $this->readProjectFile(
            'database/migrations/20260719210000_register_rh_vagas_view_all_scope.php'
        );

        self::assertStringContainsString('RhVagasViewAll', $source);
        self::assertStringContainsString('grantPageToRhVagasLevels', $source);
        self::assertStringContainsString('RhVagas', $source);
    }

    public function testPermissionServiceResolvesScopeModes(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Models/Services/RhPermissionService.php'
        );

        self::assertStringContainsString('resolveVagasListScope', $source);
        self::assertStringContainsString('RhVagasViewAll', $source);
        self::assertStringContainsString("'responsible'", $source);
    }

    public function testRepositoryAndControllerApplyScopeFilter(): void
    {
        $repo = $this->readProjectFile('app/adms/Models/Repository/RhVagasRepository.php');
        self::assertStringContainsString('scope_mode', $repo);
        self::assertStringContainsString('responsavel_id = :scope_user_id', $repo);

        $controller = $this->readProjectFile('app/adms/Controllers/rh/RhVagas.php');
        self::assertStringContainsString('resolveVagasListScope', $controller);

        $routes = $this->readProjectFile('routes/LoadPageAdm.php');
        self::assertStringContainsString('RhVagasViewAll', $routes);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
