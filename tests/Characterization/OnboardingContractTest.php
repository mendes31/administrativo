<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class OnboardingContractTest extends TestCase
{
    public function testMigrationCreatesPlanAndItems(): void
    {
        $migration = $this->readProjectFile(
            'database/migrations/20260719240000_create_rh_onboarding.php'
        );
        self::assertStringContainsString('rh_onboarding_planos', $migration);
        self::assertStringContainsString('rh_onboarding_itens', $migration);
        self::assertStringContainsString('RhOnboardingView', $migration);
    }

    public function testConversionSeedsOnboarding(): void
    {
        $service = $this->readProjectFile(
            'app/adms/Models/Services/RhConversaoAdmissaoService.php'
        );
        self::assertStringContainsString('RhOnboardingService', $service);
        self::assertStringContainsString('criarAPartirDaConversao', $service);
    }

    public function testCatalogHasAccessAndEquipment(): void
    {
        $catalog = $this->readProjectFile(
            'app/adms/Models/Services/RhOnboardingItemCatalog.php'
        );
        self::assertStringContainsString('conta_acesso', $catalog);
        self::assertStringContainsString('equipamentos', $catalog);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
