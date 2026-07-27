<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Helpers\AppEnvironmentHelper;
use PHPUnit\Framework\TestCase;

final class AppEnvironmentHelperTest extends TestCase
{
    private array $envBackup = [];

    protected function setUp(): void
    {
        foreach (['APP_ENV', 'DB_NAME', 'DB_HOST', 'URL_ADM', 'APP_NAME'] as $key) {
            $this->envBackup[$key] = $_ENV[$key] ?? null;
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->envBackup as $key => $value) {
            if ($value === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $value;
            }
        }
    }

    public function testDetectsHomologDatabaseAsTestEnvironment(): void
    {
        $_ENV['APP_ENV'] = 'development';
        $_ENV['DB_NAME'] = 'tiaraju04_homologacao';
        $_ENV['DB_HOST'] = '192.168.3.77';
        $_ENV['URL_ADM'] = 'http://192.168.3.77/administrativo/';

        self::assertTrue(AppEnvironmentHelper::isLocalTestEnvironment());
    }

    public function testProductionEnvIsNeverMarkedAsTest(): void
    {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['DB_NAME'] = 'tiaraju04_homologacao';

        self::assertFalse(AppEnvironmentHelper::isLocalTestEnvironment());
        self::assertSame('', AppEnvironmentHelper::inAppTitlePrefix());
    }

    public function testInAppFormattingIncludesDatabaseName(): void
    {
        $_ENV['APP_ENV'] = 'development';
        $_ENV['DB_NAME'] = 'tiaraju04_homologacao';
        $_ENV['APP_NAME'] = 'Tiaraju';
        $_ENV['DB_HOST'] = 'localhost';

        self::assertSame('[TESTE] Solicitação aguardando', AppEnvironmentHelper::formatInAppTitle('Solicitação aguardando'));
        self::assertStringContainsString('base tiaraju04_homologacao', AppEnvironmentHelper::formatInAppMessage('Férias — João'));
        self::assertStringContainsString('NÃO é produção', AppEnvironmentHelper::formatInAppMessage('Férias — João'));
    }
}
