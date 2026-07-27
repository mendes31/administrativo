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

    public function testInviteExpandMigrationAndStatuses(): void
    {
        $migration = $this->readProjectFile(
            'database/migrations/20260727160000_expand_rh_entrevista_avaliador_convite_aceite.php'
        );
        self::assertStringContainsString('convidado', $migration);
        self::assertStringContainsString('recusado', $migration);
        self::assertStringContainsString('RhEntrevistasAceitarAvaliacao', $migration);
        self::assertStringContainsString('RhEntrevistasRecusarAvaliacao', $migration);
        self::assertStringContainsString('RhEntrevistasReenviarConviteAvaliador', $migration);

        $repo = $this->readProjectFile(
            'app/adms/Models/Repository/RhEntrevistaAvaliadoresRepository.php'
        );
        self::assertStringContainsString('STATUS_CONVIDADO', $repo);
        self::assertStringContainsString('aceitarConvite', $repo);
        self::assertStringContainsString('recusarConvite', $repo);

        $service = $this->readProjectFile(
            'app/adms/Models/Services/RhEntrevistaAvaliadorConviteService.php'
        );
        self::assertStringContainsString('SendEmailService', $service);
        self::assertStringContainsString('NotificationsRepository', $service);
    }

    public function testEditCreateAndViewSurfaceInviteFlow(): void
    {
        $edit = $this->readProjectFile('app/adms/Controllers/rh/RhEntrevistasEdit.php');
        self::assertStringContainsString('RhEntrevistaAvaliadoresRepository', $edit);
        self::assertStringContainsString('syncPainel', $edit);
        self::assertStringContainsString('RhEntrevistaAvaliadorConviteService', $edit);

        $create = $this->readProjectFile('app/adms/Controllers/rh/RhEntrevistasCreate.php');
        self::assertStringContainsString('syncPainel', $create);
        self::assertStringContainsString('enviarConvites', $create);

        $editView = $this->readProjectFile('app/adms/Views/rh/entrevistas/edit.php');
        self::assertStringContainsString('avaliadores_adicionais', $editView);
        self::assertStringContainsString('recebem convite', mb_strtolower($editView));

        $view = $this->readProjectFile('app/adms/Views/rh/entrevistas/view.php');
        self::assertStringContainsString('Painel de avaliadores', $view);
        self::assertStringContainsString('rh-entrevistas-aceitar-avaliacao', $view);
        self::assertStringContainsString('rh-entrevistas-recusar-avaliacao', $view);
        self::assertStringContainsString('só ganham acesso após aceitar', $view);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
