<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class VagasInternasContractTest extends TestCase
{
    public function testMigrationRegistersAuthenticatedPage(): void
    {
        $migration = $this->readProjectFile(
            'database/migrations/20260727180000_register_vagas_internas_portal_page.php'
        );
        self::assertStringContainsString('VagasInternas', $migration);
        self::assertStringContainsString('vagas-internas', $migration);
        self::assertStringContainsString('EmployeePortal', $migration);
    }

    public function testRepositoryAndServiceWireInternalApply(): void
    {
        $repo = $this->readProjectFile('app/adms/Models/Repository/RhVagasRepository.php');
        self::assertStringContainsString('listInternas', $repo);
        self::assertStringContainsString('getInternaById', $repo);
        self::assertStringContainsString("visibilidade IN ('interna', 'ambas')", $repo);

        $service = $this->readProjectFile(
            'app/adms/Models/Services/RhCandidaturaInternaService.php'
        );
        self::assertStringContainsString('portal_interno', $service);
        self::assertStringContainsString('vincularUsuarioConversao', $service);
        self::assertStringContainsString('findActiveByAdmsUserId', $service);

        $controller = $this->readProjectFile('app/adms/Controllers/portal/VagasInternas.php');
        self::assertStringContainsString('RhCandidaturaInternaService', $controller);
        self::assertStringContainsString('form_vagas_internas_candidatar', $controller);
    }

    public function testPortalUiSurfacesEntryPoints(): void
    {
        $list = $this->readProjectFile('app/adms/Views/portal/vagas_internas/list.php');
        $view = $this->readProjectFile('app/adms/Views/portal/vagas_internas/view.php');
        $dash = $this->readProjectFile('app/adms/Views/portal/dashboard.php');

        self::assertStringContainsString('Vagas internas', $list);
        self::assertStringContainsString('lgpd_consent', $view);
        self::assertStringContainsString('vagas-internas', $dash);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
