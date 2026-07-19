<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class EntrevistaReagendamentoContractTest extends TestCase
{
    public function testMigrationCreatesAppendOnlyHistory(): void
    {
        $source = $this->readProjectFile(
            'database/migrations/20260719190000_create_rh_entrevista_reagendamentos.php'
        );

        self::assertStringContainsString('rh_entrevista_reagendamentos', $source);
        self::assertStringContainsString('data_hora_anterior', $source);
        self::assertStringContainsString('motivo', $source);
        self::assertStringNotContainsString('updated_at', $source);
    }

    public function testServiceRequiresMotivoOnRescheduleAndUsesLock(): void
    {
        $service = $this->readProjectFile(
            'app/adms/Models/Services/RhCandidaturaMovimentacaoService.php'
        );

        self::assertStringContainsString('lockById', $service);
        self::assertStringContainsString('motivo_reagendamento', $service);
        self::assertStringContainsString('Informe o motivo do reagendamento', $service);
        self::assertStringContainsString('RhEntrevistaReagendamentosRepository', $service);
        self::assertStringContainsString('jaAgendada', $service);
    }

    public function testEditAndViewSurfaceHistory(): void
    {
        $editView = $this->readProjectFile('app/adms/Views/rh/entrevistas/edit.php');
        self::assertStringContainsString('motivo_reagendamento', $editView);
        self::assertStringContainsString('reagendar', $editView);

        $viewCtrl = $this->readProjectFile('app/adms/Controllers/rh/RhEntrevistasView.php');
        self::assertStringContainsString('RhEntrevistaReagendamentosRepository', $viewCtrl);

        $view = $this->readProjectFile('app/adms/Views/rh/entrevistas/view.php');
        self::assertStringContainsString('Histórico de reagendamentos', $view);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
