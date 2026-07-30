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
        self::assertStringContainsString('PORTAL_INTERNO', $service);
        self::assertStringContainsString('vincularUsuarioConversao', $service);
        self::assertStringContainsString('findActiveByAdmsUserId', $service);

        $controller = $this->readProjectFile('app/adms/Controllers/portal/VagasInternas.php');
        self::assertStringContainsString('RhCandidaturaInternaService', $controller);
        self::assertStringContainsString('form_vagas_internas_candidatar', $controller);
        self::assertStringContainsString('resolveCandidatouMap', $controller);
        self::assertStringContainsString('ja_candidatou', $controller);
    }

    public function testPortalUiSurfacesEntryPoints(): void
    {
        $list = $this->readProjectFile('app/adms/Views/portal/vagas_internas/list.php');
        $view = $this->readProjectFile('app/adms/Views/portal/vagas_internas/view.php');
        $dash = $this->readProjectFile('app/adms/Views/portal/dashboard.php');

        self::assertStringContainsString('Vagas internas', $list);
        self::assertStringContainsString('Candidate-se', $list);
        self::assertStringContainsString('Candidatura enviada', $list);
        self::assertStringContainsString('Ver detalhes', $list);
        self::assertStringContainsString('ja_candidatou', $list);
        self::assertStringContainsString('lgpd_consent', $view);
        self::assertStringContainsString('id="candidatar"', $view);
        self::assertStringContainsString('vagas-internas', $dash);
        self::assertStringContainsString('total_vagas_internas', $dash);
        self::assertStringContainsString('col-md-3', $dash);

        $menu = $this->readProjectFile('app/adms/Views/partials/menu.php');
        self::assertStringContainsString("'url' => \$_ENV['URL_ADM'] . 'vagas-internas'", $menu);
        self::assertStringContainsString("'permission' => 'VagasInternas'", $menu);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
