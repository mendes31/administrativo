<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Central de Importações: rotas, perfis, ACL fechada e ImportUsers intacto.
 */
#[CoversNothing]
final class ImportCenterContractTest extends TestCase
{
    public function testRoutesAndDirectoryAreWired(): void
    {
        $legacy = $this->readProjectFile('routes/LoadPageAdm.php');
        self::assertStringContainsString('ImportUsers', $legacy);
        self::assertStringContainsString('ImportCenterView', $legacy);
        self::assertStringContainsString('ImportCenterCommit', $legacy);
        self::assertStringContainsString('"imports"', $legacy);

        $acl = $this->readProjectFile('routes/LoadPageAdmAccessLevel.php');
        self::assertStringContainsString("'imports'", $acl);
    }

    public function testCatalogDeclaresUsersDepartmentsPositions(): void
    {
        $catalog = $this->readProjectFile('app/adms/Models/Services/Imports/ImportProfileCatalog.php');
        self::assertStringContainsString('UsersImportProfile', $catalog);
        self::assertStringContainsString('DepartmentsImportProfile', $catalog);
        self::assertStringContainsString('PositionsImportProfile', $catalog);
        self::assertStringContainsString('SstRiscosImportProfile', $catalog);
        self::assertStringContainsString('SstTreinamentoNecessidadeImportProfile', $catalog);
        self::assertStringContainsString('SstEquipamentoTiposImportProfile', $catalog);
        self::assertStringContainsString('SstEquipamentosImportProfile', $catalog);
        self::assertStringContainsString('SstEquipamentoChecklistImportProfile', $catalog);
        self::assertStringContainsString('SstGheColaboradoresImportProfile', $catalog);
        self::assertStringContainsString('SstGheTreinamentosImportProfile', $catalog);

        $users = $this->readProjectFile('app/adms/Models/Services/Imports/UsersImportProfile.php');
        self::assertStringContainsString('ImportCenterUsers', $users);
        self::assertStringContainsString("'cpf'", $users);
        self::assertStringContainsString('emptyPolicy', $users);
        self::assertStringContainsString('getByName', $users);
    }

    public function testTemplateWritesLabelRowThenFieldNames(): void
    {
        $source = $this->readProjectFile('app/adms/Controllers/imports/ImportCenterTemplate.php');
        self::assertStringContainsString('array_values($fields)', $source);
        self::assertStringContainsString('array_keys($fields)', $source);
        $reader = $this->readProjectFile('app/adms/Models/Services/Imports/SpreadsheetImportReader.php');
        self::assertStringContainsString('skipOptionalLabelRow', $reader);
    }

    public function testMigrationRegistersClosedAclPagesAndJobsTable(): void
    {
        $source = $this->readProjectFile(
            'database/migrations/20260908120000_create_adms_import_jobs_and_pages.php'
        );
        foreach ([
            'ImportCenter',
            'ImportCenterCreate',
            'ImportCenterMap',
            'ImportCenterView',
            'ImportCenterTemplate',
            'ImportCenterUsers',
            'ImportCenterDepartments',
            'ImportCenterPositions',
            'adms_import_jobs',
            'Administração - Importações',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
        self::assertStringContainsString("'default_page' => 0", $source);
        self::assertStringContainsString("'public_page' => 0", $source);
        self::assertStringContainsString('SELECT 0, al.id', $source);
        self::assertStringNotContainsString('permission = 1', $source);
    }

    public function testSstPermissionPageIsClosedAcl(): void
    {
        $source = $this->readProjectFile(
            'database/migrations/20260908130000_register_import_center_sst_page.php'
        );
        self::assertStringContainsString('ImportCenterSst', $source);
        self::assertStringContainsString('import-center-sst', $source);
        self::assertStringContainsString("'default_page' => 0", $source);
        self::assertStringContainsString("'public_page' => 0", $source);
        self::assertStringContainsString('SELECT 0, al.id', $source);
        self::assertStringNotContainsString('permission = 1', $source);

        $hub = $this->readProjectFile('app/adms/Controllers/imports/ImportCenter.php');
        self::assertStringContainsString('ImportCenterSst', $hub);

        $routes = $this->readProjectFile('routes/LoadPageAdm.php');
        self::assertStringContainsString('ImportCenterSst', $routes);
    }

    public function testCommitAfterDryRunIsWired(): void
    {
        $source = $this->readProjectFile(
            'database/migrations/20260911193000_register_import_center_commit_page.php'
        );
        self::assertStringContainsString('ImportCenterCommit', $source);
        self::assertStringContainsString('import-center-commit', $source);
        self::assertStringContainsString("'default_page' => 0", $source);
        self::assertStringContainsString("'public_page' => 0", $source);
        self::assertStringContainsString('SELECT 0, al.id', $source);
        self::assertStringNotContainsString('permission = 1', $source);

        $runner = $this->readProjectFile('app/adms/Models/Services/Imports/ImportJobRunner.php');
        self::assertStringContainsString('function commitSimulation', $runner);

        $view = $this->readProjectFile('app/adms/Views/imports/view.php');
        self::assertStringContainsString('Registrar importação', $view);
        self::assertStringContainsString('import-center-commit/', $view);
    }

    public function testImportUsersRemainsIndependent(): void
    {
        $controller = $this->readProjectFile('app/adms/Controllers/users/ImportUsers.php');
        self::assertStringContainsString('createUser', $controller);

        $menu = $this->readProjectFile('app/adms/Views/partials/menu.php');
        self::assertStringContainsString('import-users', $menu);
        self::assertStringContainsString('import-center', $menu);
        self::assertStringContainsString("'permission' => 'ImportCenter'", $menu);
    }

    public function testEsocialDraftLockUnchanged(): void
    {
        $policy = $this->readProjectFile('app/adms/Models/Services/SstEsocialPolicy.php');
        self::assertStringContainsString('EVENTO_TREINAMENTO_LEGADO', $policy);
        self::assertStringContainsString('assertGeracaoPermitida', $policy);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
