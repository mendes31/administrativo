<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class OffboardingContractTest extends TestCase
{
    public function testMigrationCreatesTablesAndPages(): void
    {
        $migration = $this->readProjectFile(
            'database/migrations/20260719243000_create_rh_offboarding.php'
        );
        self::assertStringContainsString('rh_offboarding_planos', $migration);
        self::assertStringContainsString('rh_offboarding_itens', $migration);
        self::assertStringContainsString('RhOffboardings', $migration);
        self::assertStringContainsString('RhOffboardingsCreate', $migration);
        self::assertStringContainsString('RhOffboardingsView', $migration);
    }

    public function testServiceAppliesDesligamentoOnUsers(): void
    {
        $service = $this->readProjectFile(
            'app/adms/Models/Services/RhOffboardingService.php'
        );
        self::assertStringContainsString('aplicarDesligamentoUsuario', $service);
        self::assertStringContainsString('concluir', $service);
    }

    public function testCatalogHasRequiredItems(): void
    {
        $catalog = $this->readProjectFile(
            'app/adms/Models/Services/RhOffboardingItemCatalog.php'
        );
        self::assertStringContainsString('devolucao_equipamentos', $catalog);
        self::assertStringContainsString('revogar_acessos', $catalog);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
