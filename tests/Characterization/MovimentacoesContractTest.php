<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class MovimentacoesContractTest extends TestCase
{
    public function testMigrationCreatesTableAndPages(): void
    {
        $migration = $this->readProjectFile(
            'database/migrations/20260719242000_create_rh_movimentacoes.php'
        );
        self::assertStringContainsString('rh_movimentacoes', $migration);
        self::assertStringContainsString('RhMovimentacoes', $migration);
        self::assertStringContainsString('RhMovimentacoesCreate', $migration);
    }

    public function testServiceAppliesLotacaoOnUsers(): void
    {
        $service = $this->readProjectFile(
            'app/adms/Models/Services/RhMovimentacaoService.php'
        );
        self::assertStringContainsString('aplicarLotacaoUsuario', $service);
        self::assertStringContainsString('departamento_id_depois', $service);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
