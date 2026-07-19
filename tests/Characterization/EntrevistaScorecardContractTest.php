<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class EntrevistaScorecardContractTest extends TestCase
{
    public function testMigrationCreatesScorecardTables(): void
    {
        $source = $this->readProjectFile(
            'database/migrations/20260719170000_create_rh_entrevista_scorecards.php'
        );

        self::assertStringContainsString('rh_entrevista_scorecards', $source);
        self::assertStringContainsString('rh_entrevista_scorecard_itens', $source);
        self::assertStringContainsString('uq_rh_scorecard_entrevista_avaliador', $source);
    }

    public function testEditSavesScorecardWithoutDrivingPipeline(): void
    {
        $edit = $this->readProjectFile('app/adms/Controllers/rh/RhEntrevistasEdit.php');
        self::assertStringContainsString('RhEntrevistaScorecardRepository', $edit);
        self::assertStringContainsString('saveForAvaliador', $edit);

        $view = $this->readProjectFile('app/adms/Views/rh/entrevistas/edit.php');
        self::assertStringContainsString('scorecard[itens]', $view);
        self::assertStringContainsString('não altera', $view);
    }

    public function testViewRequiresObjectPermissionAndListsScorecards(): void
    {
        $controller = $this->readProjectFile('app/adms/Controllers/rh/RhEntrevistasView.php');
        self::assertStringContainsString('canManageEntrevista', $controller);
        self::assertStringContainsString('listByEntrevista', $controller);

        $view = $this->readProjectFile('app/adms/Views/rh/entrevistas/view.php');
        self::assertStringContainsString('Scorecards', $view);
        self::assertStringContainsString('nota_ponderada', $view);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
