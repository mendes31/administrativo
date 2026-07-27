<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class EntrevistasListScopeContractTest extends TestCase
{
    public function testMigrationRegistersViewAll(): void
    {
        $source = $this->readProjectFile(
            'database/migrations/20260719220000_register_rh_entrevistas_view_all_scope.php'
        );
        self::assertStringContainsString('RhEntrevistasViewAll', $source);
        self::assertStringContainsString('grantPageToRhEntrevistasLevels', $source);
    }

    public function testServiceAndRepositoryApplyRelatedScope(): void
    {
        $service = $this->readProjectFile('app/adms/Models/Services/RhPermissionService.php');
        self::assertStringContainsString('resolveEntrevistasListScope', $service);
        self::assertStringContainsString("'related'", $service);

        $repo = $this->readProjectFile('app/adms/Models/Repository/RhEntrevistasRepository.php');
        self::assertStringContainsString('rh_entrevista_avaliadores', $repo);
        self::assertStringContainsString('scope_mode', $repo);

        $controller = $this->readProjectFile('app/adms/Controllers/rh/RhEntrevistas.php');
        self::assertStringContainsString('resolveEntrevistasListScope', $controller);
    }

    public function testContractMigrationAndCanViewEntrevista(): void
    {
        $migration = $this->readProjectFile(
            'database/migrations/20260727140000_contract_rh_entrevistas_view_all_scope.php'
        );
        self::assertStringContainsString('RhEntrevistasViewAll', $migration);
        self::assertStringContainsString('permission = 0', $migration);

        $service = $this->readProjectFile('app/adms/Models/Services/RhPermissionService.php');
        self::assertStringContainsString('canViewEntrevista', $service);
        self::assertStringContainsString('isAvaliadorAtivoDaEntrevista', $service);

        $view = $this->readProjectFile('app/adms/Controllers/rh/RhEntrevistasView.php');
        self::assertStringContainsString('canViewEntrevista', $view);
        self::assertStringContainsString('can_manage_entrevista', $view);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
