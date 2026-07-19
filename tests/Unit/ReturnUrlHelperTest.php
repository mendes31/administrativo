<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Helpers\ReturnUrlHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReturnUrlHelper::class)]
final class ReturnUrlHelperTest extends TestCase
{
    protected function setUp(): void
    {
        $_ENV['URL_ADM'] = 'https://administrativo.test/administrativo/';
        $_SERVER['HTTP_HOST'] = 'administrativo.test';
        unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO'], $_SERVER['REQUEST_URI']);
        $_SESSION = [];
        $_POST = [];
    }

    public function testAcceptsUrlFromAdministrativeApplication(): void
    {
        self::assertSame(
            'https://administrativo.test/administrativo/employee-portal?tab=documentos',
            ReturnUrlHelper::sanitize(
                'https://administrativo.test/administrativo/employee-portal?tab=documentos'
            )
        );
    }

    public function testRejectsExternalHost(): void
    {
        self::assertNull(
            ReturnUrlHelper::sanitize('https://malicioso.test/administrativo/dashboard')
        );
    }

    public function testRejectsPathOutsideAdministrativeApplication(): void
    {
        self::assertNull(
            ReturnUrlHelper::sanitize('https://administrativo.test/outro-sistema')
        );
    }

    public function testRejectsLoginAsReturnDestination(): void
    {
        self::assertNull(
            ReturnUrlHelper::sanitize('https://administrativo.test/administrativo/login')
        );
    }

    public function testConsumePrefersSessionAndRemovesStoredUrl(): void
    {
        $_SESSION['return_url'] = 'https://administrativo.test/administrativo/employee-portal';
        $_POST['return_url'] = 'https://administrativo.test/administrativo/dashboard';

        self::assertSame(
            'https://administrativo.test/administrativo/employee-portal',
            ReturnUrlHelper::consume()
        );
        self::assertArrayNotHasKey('return_url', $_SESSION);
    }

    public function testBuildsDashboardFallbackFromConfiguredBaseUrl(): void
    {
        self::assertSame(
            'https://administrativo.test/administrativo/dashboard',
            ReturnUrlHelper::dashboardFallback()
        );
    }
}
