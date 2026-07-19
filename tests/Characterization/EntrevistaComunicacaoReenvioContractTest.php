<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class EntrevistaComunicacaoReenvioContractTest extends TestCase
{
    public function testMigrationAddsSourceAndAclPage(): void
    {
        $migration = $this->readProjectFile(
            'database/migrations/20260719234000_add_rh_entrevista_comunicacao_reenvio.php'
        );

        self::assertStringContainsString('source_comunicacao_id', $migration);
        self::assertStringContainsString('RhEntrevistasResendComunicacao', $migration);
        self::assertStringContainsString('grantPageToRhEntrevistasEditLevels', $migration);
    }

    public function testServiceOnlyResendsFailedOrBlockedAndCreatesNewIntention(): void
    {
        $service = $this->readProjectFile(
            'app/adms/Models/Services/RhEntrevistaComunicacaoReenvioService.php'
        );

        self::assertStringContainsString('STATUS_FAILED', $service);
        self::assertStringContainsString('STATUS_BLOCKED', $service);
        self::assertStringContainsString('hasOpenResendFrom', $service);
        self::assertStringContainsString('source_comunicacao_id', $service);
        self::assertStringContainsString('reenvio', $service);
        self::assertStringContainsString('RhEntrevistaEmailTemplateCatalog::render', $service);
    }

    public function testControllerIsPostCsrfAndViewShowsButton(): void
    {
        $controller = $this->readProjectFile(
            'app/adms/Controllers/rh/RhEntrevistasResendComunicacao.php'
        );
        self::assertStringContainsString("REQUEST_METHOD'] !== 'POST'", $controller);
        self::assertStringContainsString('form_resend_rh_entrevista_comunicacao', $controller);

        $view = $this->readProjectFile('app/adms/Views/rh/entrevistas/view.php');
        self::assertStringContainsString('RhEntrevistasResendComunicacao', $view);
        self::assertStringContainsString('rh-entrevistas-resend-comunicacao', $view);

        $routes = $this->readProjectFile('routes/LoadPageAdm.php');
        self::assertStringContainsString('RhEntrevistasResendComunicacao', $routes);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
