<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Contrato do go-live SST: portal self-service padrão, rotina no login e checklist.
 */
#[CoversNothing]
final class SstGoLiveContractTest extends TestCase
{
    public function testLoginDispatchesSstMaintenance(): void
    {
        $source = $this->readProjectFile('app/adms/Controllers/login/Login.php');
        self::assertStringContainsString('SstMaintenanceService::ensureUpdated', $source);
    }

    public function testPortalSstPagesAreDefaultInGoLiveMigration(): void
    {
        $source = $this->readProjectFile(
            'database/migrations/20260830120000_sst_portal_self_service_default_pages.php'
        );
        foreach ([
            'MyEpiDeliveries',
            'SignEpiFicha',
            'ViewEpiFichaPdf',
            'MySstTreinamentos',
            'ViewSstTreinamentoCertificadoPdf',
        ] as $controller) {
            self::assertStringContainsString($controller, $source);
        }
        self::assertStringContainsString('default_page = 1', $source);
        self::assertStringContainsString('permission = 1', $source);
    }

    public function testDashboardShowsPilotChecklist(): void
    {
        $controller = $this->readProjectFile('app/adms/Controllers/sst/SstDashboard.php');
        self::assertStringContainsString('SstGoLiveReadinessService', $controller);

        $view = $this->readProjectFile('app/adms/Views/sst/dashboard.php');
        self::assertStringContainsString('secao-piloto', $view);
        self::assertStringContainsString('Primeiros passos', $view);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
