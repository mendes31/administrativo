<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class PersonnelRequestContractTest extends TestCase
{
    public function testMigrationCreatesTableAndVagaFk(): void
    {
        $source = $this->readProjectFile(
            'database/migrations/20260719160000_create_rh_personnel_requests.php'
        );

        self::assertStringContainsString('rh_personnel_requests', $source);
        self::assertStringContainsString('personnel_request_id', $source);
        self::assertStringContainsString('RhPersonnelRequests', $source);
        self::assertStringContainsString('pending_approval', $source);
    }

    public function testServiceBlocksSelfApprovalAndDoubleConvert(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Models/Services/RhPersonnelRequestService.php'
        );

        self::assertStringContainsString('própria requisição', $source);
        self::assertStringContainsString('já foi convertida', $source);
        self::assertStringContainsString('beginTransaction()', $source);
        self::assertStringContainsString('FOR UPDATE', $this->readProjectFile(
            'app/adms/Models/Repository/RhPersonnelRequestsRepository.php'
        ));
    }

    public function testRoutesAndControllersExist(): void
    {
        $routes = $this->readProjectFile('routes/LoadPageAdm.php');
        self::assertStringContainsString('RhPersonnelRequests', $routes);
        self::assertStringContainsString('RhPersonnelRequestsConvert', $routes);

        foreach ([
            'app/adms/Controllers/rh/RhPersonnelRequests.php',
            'app/adms/Controllers/rh/RhPersonnelRequestsCreate.php',
            'app/adms/Controllers/rh/RhPersonnelRequestsView.php',
            'app/adms/Controllers/rh/RhPersonnelRequestsApprove.php',
            'app/adms/Controllers/rh/RhPersonnelRequestsReject.php',
            'app/adms/Controllers/rh/RhPersonnelRequestsConvert.php',
        ] as $file) {
            self::assertFileExists(PROJECT_ROOT . '/' . $file);
        }
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
