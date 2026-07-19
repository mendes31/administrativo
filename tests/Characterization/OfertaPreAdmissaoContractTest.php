<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class OfertaPreAdmissaoContractTest extends TestCase
{
    public function testMigrationCreatesOfertaAndDocumentTables(): void
    {
        $migration = $this->readProjectFile(
            'database/migrations/20260719238000_create_rh_ofertas_pre_admissao.php'
        );
        self::assertStringContainsString('rh_ofertas', $migration);
        self::assertStringContainsString('rh_pre_admissao_documentos', $migration);
        self::assertStringContainsString('RhOfertasCreate', $migration);
        self::assertStringContainsString('RhOfertasView', $migration);
    }

    public function testServiceDoesNotMarkContratado(): void
    {
        $service = $this->readProjectFile('app/adms/Models/Services/RhOfertaService.php');
        self::assertStringContainsString('STATUS_ACEITA', $service);
        self::assertStringContainsString('seedDocumentos', $service);
        self::assertStringNotContainsString("status_processo", $service);
        self::assertStringNotContainsString("'contratado'", $service);
    }

    public function testCatalogHasRequiredDocs(): void
    {
        $catalog = $this->readProjectFile(
            'app/adms/Models/Services/RhPreAdmissaoDocumentoCatalog.php'
        );
        self::assertStringContainsString('documento_identidade', $catalog);
        self::assertStringContainsString('dados_bancarios', $catalog);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
