<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class CandidatoObjectAuthViewAllContractTest extends TestCase
{
    public function testObjectAuthUsesViewAllScopeNotBlanketAcl(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Models/Services/RhCandidatoPermissionService.php'
        );

        self::assertStringContainsString('resolveCandidatosListScope', $source);
        self::assertStringContainsString("['mode'] === 'all'", $source);
        self::assertStringContainsString('isResponsavelDeVagaDoCandidato', $source);
        self::assertStringNotContainsString('RH_CANDIDATO_CONTROLLERS', $source);
        self::assertStringNotContainsString(
            'checkUserAnyPagePermissionForControllers',
            $source
        );
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
