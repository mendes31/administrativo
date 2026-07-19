<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class EntrevistaAvaliadoresContractTest extends TestCase
{
    public function testMigrationCreatesPainelAndBackfillsPrincipal(): void
    {
        $source = $this->readProjectFile(
            'database/migrations/20260719180000_create_rh_entrevista_avaliadores.php'
        );

        self::assertStringContainsString('rh_entrevista_avaliadores', $source);
        self::assertStringContainsString('uq_rh_entrevista_avaliador', $source);
        self::assertStringContainsString('entrevistador_id', $source);
        self::assertStringContainsString('principal', $source);
    }

    public function testSyncIsAdditiveWithoutInviteOrAcl(): void
    {
        $repo = $this->readProjectFile(
            'app/adms/Models/Repository/RhEntrevistaAvaliadoresRepository.php'
        );

        self::assertStringContainsString('syncPainel', $repo);
        self::assertStringContainsString('STATUS_REMOVIDO', $repo);
        self::assertStringNotContainsString('SendEmail', $repo);
        self::assertStringNotContainsString('canManageEntrevista', $repo);
    }

    public function testEditAndViewSurfacePainel(): void
    {
        $edit = $this->readProjectFile('app/adms/Controllers/rh/RhEntrevistasEdit.php');
        self::assertStringContainsString('RhEntrevistaAvaliadoresRepository', $edit);
        self::assertStringContainsString('syncPainel', $edit);

        $editView = $this->readProjectFile('app/adms/Views/rh/entrevistas/edit.php');
        self::assertStringContainsString('avaliadores_adicionais', $editView);
        self::assertStringContainsString('não envia convite', $editView);

        $view = $this->readProjectFile('app/adms/Views/rh/entrevistas/view.php');
        self::assertStringContainsString('Painel de avaliadores', $view);
        self::assertStringContainsString('não concede acesso automático', $view);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
