<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class VagasPublicasCandidaturaContractTest extends TestCase
{
    public function testPublicControllerAcceptsPostWithGuards(): void
    {
        $controller = $this->readProjectFile('app/adms/Controllers/rh/RhVagasPublicas.php');
        self::assertStringContainsString('POST', $controller);
        self::assertStringContainsString('form_vagas_abertas_candidatar', $controller);
        self::assertStringContainsString('RhVagasPublicasCaptchaService', $controller);
        self::assertStringContainsString('WhistleblowingRateLimitService', $controller);
        self::assertStringContainsString('RhCandidaturaPublicaService', $controller);
    }

    public function testServiceDedupesByEmailAndRequiresConsent(): void
    {
        $service = $this->readProjectFile(
            'app/adms/Models/Services/RhCandidaturaPublicaService.php'
        );
        self::assertStringContainsString('findActiveByEmail', $service);
        self::assertStringContainsString('hasVinculoComVaga', $service);
        self::assertStringContainsString('getPublicadaById', $service);
        self::assertStringContainsString('lgpd_consent', $service);
        self::assertStringContainsString('website', $service);
        self::assertStringContainsString('ORIGEM_PORTAL', $service);
        self::assertStringNotContainsString('storeCurriculo', $service);
    }

    public function testViewHasApplicationForm(): void
    {
        $view = $this->readProjectFile('app/adms/Views/rh/public/view.php');
        self::assertStringContainsString('lgpd_consent', $view);
        self::assertStringContainsString('form[email]', $view);
        self::assertStringContainsString('Enviar candidatura', $view);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
