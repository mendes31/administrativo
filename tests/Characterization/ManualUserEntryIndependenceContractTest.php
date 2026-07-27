<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Garante que a entrada de colaboradores via cadastro manual permanece
 * independente do processo seletivo (ATS), conforme ADR-0002 / MODELO_IDENTIDADE.
 */
#[CoversNothing]
final class ManualUserEntryIndependenceContractTest extends TestCase
{
    public function testCreateUserControllerDoesNotDependOnAtsConversion(): void
    {
        $source = $this->readProjectFile('app/adms/Controllers/users/CreateUser.php');

        self::assertStringNotContainsString('RhConversaoAdmissaoService', $source);
        self::assertStringNotContainsString('RhOfertas', $source);
        self::assertStringNotContainsString('rh_candidatos', $source);
        self::assertStringContainsString('createUser', $source);
    }

    public function testImportUsersRemainsAvailable(): void
    {
        $controller = $this->readProjectFile('app/adms/Controllers/users/ImportUsers.php');
        self::assertStringContainsString('createUser', $controller);

        $routes = $this->readProjectFile('routes/LoadPageAdm.php');
        self::assertStringContainsString('CreateUser', $routes);
        self::assertStringContainsString('ImportUsers', $routes);
    }

    public function testEmploymentHistoryTableIsDocumentedAndUsed(): void
    {
        $migration = $this->readProjectFile(
            'database/migrations/20251204010000_create_employment_history_table.php'
        );
        self::assertStringContainsString('adms_employment_history', $migration);

        $identity = $this->readProjectFile('docs/04_IDENTIDADE/MODELO_IDENTIDADE.md');
        self::assertStringContainsString('adms_employment_history', $identity);
        self::assertStringContainsString('CreateUser', $identity);
        self::assertStringContainsString('ImportUsers', $identity);
    }

    public function testConversionOffersLinkToExistingUser(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Models/Services/RhConversaoAdmissaoService.php'
        );
        self::assertStringContainsString('vincular', $source);
        self::assertStringContainsString('criar', $source);
    }

    public function testAdr0002KeepsManualEntryGuarantee(): void
    {
        $source = $this->readProjectFile('docs/08_ADR/ADR-0002_PESSOA_E_CONTA.md');
        self::assertStringContainsString('CreateUser', $source);
        self::assertStringContainsString('ImportUsers', $source);
        self::assertStringContainsString('coexistem', $source);
        self::assertStringContainsString('adms_employment_history', $source);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
