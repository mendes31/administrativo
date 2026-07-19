<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class ConversaoAdmissaoContractTest extends TestCase
{
    public function testMigrationCreatesAuditTableAndPage(): void
    {
        $migration = $this->readProjectFile(
            'database/migrations/20260719239000_create_rh_conversoes_admissao.php'
        );
        self::assertStringContainsString('rh_conversoes_admissao', $migration);
        self::assertStringContainsString('adms_user_id', $migration);
        self::assertStringContainsString('RhOfertasConvert', $migration);
    }

    public function testServiceRequiresAcceptedOfferAndApprovedDocs(): void
    {
        $service = $this->readProjectFile(
            'app/adms/Models/Services/RhConversaoAdmissaoService.php'
        );
        self::assertStringContainsString('STATUS_ACEITA', $service);
        self::assertStringContainsString('assertDocumentosObrigatoriosAprovados', $service);
        self::assertStringContainsString('forcarStatusProcesso', $service);
        self::assertStringContainsString("'contratado'", $service);
        self::assertStringContainsString('createUser', $service);
    }

    public function testDoesNotCreatePessoaTable(): void
    {
        $migration = $this->readProjectFile(
            'database/migrations/20260719239000_create_rh_conversoes_admissao.php'
        );
        self::assertStringNotContainsString('CREATE TABLE pessoas', $migration);
        self::assertStringNotContainsString('rh_pessoas', $migration);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
