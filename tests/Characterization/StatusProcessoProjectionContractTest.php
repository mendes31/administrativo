<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class StatusProcessoProjectionContractTest extends TestCase
{
    public function testEditAndCreateDoNotAllowFreeStatusEdit(): void
    {
        $editCtrl = $this->readProjectFile('app/adms/Controllers/rh/RhCandidatosEdit.php');
        $createCtrl = $this->readProjectFile('app/adms/Controllers/rh/RhCandidatosCreate.php');
        $editView = $this->readProjectFile('app/adms/Views/rh/candidatos/edit.php');
        $createView = $this->readProjectFile('app/adms/Views/rh/candidatos/create.php');

        self::assertStringContainsString('RhCandidatoStatusProcessoProjector::resolveForManualEdit', $editCtrl);
        self::assertStringContainsString("status_processo'] = 'candidatado'", $createCtrl);
        self::assertStringContainsString('marcar_contratado', $editView);
        self::assertStringContainsString('type="hidden" name="form[status_processo]"', $createView);
        self::assertStringNotContainsString('<select name="form[status_processo]"', $editView);
        self::assertStringNotContainsString('<select name="form[status_processo]"', $createView);
    }

    public function testRepositoryUsesProjector(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Models/Repository/RhCandidatosRepository.php'
        );

        self::assertStringContainsString('RhCandidatoStatusProcessoProjector::fromVinculos', $source);
        self::assertStringContainsString('function listStatusVinculosAtivos', $source);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
