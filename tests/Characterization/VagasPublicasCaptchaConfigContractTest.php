<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class VagasPublicasCaptchaConfigContractTest extends TestCase
{
    public function testMigrationCreatesIndependentConfig(): void
    {
        $migration = $this->readProjectFile(
            'database/migrations/20260719237000_create_rh_vagas_publicas_config_captcha.php'
        );
        self::assertStringContainsString('rh_vagas_publicas_config', $migration);
        self::assertStringContainsString('captcha_enabled', $migration);
        self::assertStringContainsString('RhVagasPublicasConfig', $migration);
        self::assertStringContainsString('rh-vagas-publicas-config', $migration);
    }

    public function testServiceReadsOwnRepositoryNotWhistleblowing(): void
    {
        $service = $this->readProjectFile(
            'app/adms/Models/Services/RhVagasPublicasCaptchaService.php'
        );
        self::assertStringContainsString('RhVagasPublicasConfigRepository', $service);
        self::assertStringNotContainsString('WhistleblowingConfigRepository', $service);
    }

    public function testAdminConfigViewHasToggleAndKeys(): void
    {
        $view = $this->readProjectFile('app/adms/Views/rh/vagas/publicasConfig.php');
        self::assertStringContainsString('captcha_enabled', $view);
        self::assertStringContainsString('captcha_site_key', $view);
        self::assertStringContainsString('captcha_secret_key', $view);
        self::assertStringContainsString('Ativar CAPTCHA', $view);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
