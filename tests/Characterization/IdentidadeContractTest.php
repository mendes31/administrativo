<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class IdentidadeContractTest extends TestCase
{
    public function testMigrationCreatesIdentityTables(): void
    {
        $migration = $this->readProjectFile(
            'database/migrations/20260719244000_create_rh_identidade_pessoa_vinculo_lotacao.php'
        );
        self::assertStringContainsString('rh_pessoas', $migration);
        self::assertStringContainsString('rh_vinculos', $migration);
        self::assertStringContainsString('rh_lotacoes', $migration);
        self::assertStringContainsString('backfillFromUsers', $migration);
        self::assertStringContainsString('RhPessoas', $migration);
    }

    public function testSyncServiceHooksExist(): void
    {
        $sync = $this->readProjectFile(
            'app/adms/Models/Services/RhIdentidadeSyncService.php'
        );
        self::assertStringContainsString('sincronizarDeUsuario', $sync);
        self::assertStringContainsString('tentarSincronizar', $sync);

        $conversao = $this->readProjectFile(
            'app/adms/Models/Services/RhConversaoAdmissaoService.php'
        );
        self::assertStringContainsString('RhIdentidadeSyncService', $conversao);

        $mov = $this->readProjectFile(
            'app/adms/Models/Services/RhMovimentacaoService.php'
        );
        self::assertStringContainsString('RhIdentidadeSyncService', $mov);

        $off = $this->readProjectFile(
            'app/adms/Models/Services/RhOffboardingService.php'
        );
        self::assertStringContainsString('RhIdentidadeSyncService', $off);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
