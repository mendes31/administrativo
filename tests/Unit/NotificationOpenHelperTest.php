<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Helpers\NotificationOpenHelper;
use PHPUnit\Framework\TestCase;

final class NotificationOpenHelperTest extends TestCase
{
    private const BASE = 'http://192.168.3.77/administrativo';

    public function testRewritesForeignHostOntoCurrentInstallation(): void
    {
        $url = NotificationOpenHelper::urlOnCurrentHost(
            'https://tiaraju.com.br/administrativo/timeline/post/309?focus=body',
            self::BASE
        );

        self::assertSame(
            'http://192.168.3.77/administrativo/timeline/post/309?focus=body',
            $url
        );
    }

    public function testKeepsRelativePathOnCurrentHost(): void
    {
        $url = NotificationOpenHelper::urlOnCurrentHost(
            'timeline?post=309&focus=body',
            self::BASE
        );

        self::assertSame(
            'http://192.168.3.77/administrativo/timeline?post=309&focus=body',
            $url
        );
    }

    public function testForeignRefererIsAnotherInstallation(): void
    {
        self::assertTrue(NotificationOpenHelper::isForeignReferer(
            'http://192.168.3.77/administrativo/view-informativo/60',
            'tiaraju.com.br'
        ));
    }

    public function testSameHostRefererIsNotForeign(): void
    {
        self::assertFalse(NotificationOpenHelper::isForeignReferer(
            'http://192.168.3.77/administrativo/notificacoes',
            '192.168.3.77'
        ));
    }

    public function testExternalSiteRefererDoesNotDiscardSession(): void
    {
        self::assertFalse(NotificationOpenHelper::isForeignReferer(
            'https://mail.google.com/mail/u/0/',
            'tiaraju.com.br'
        ));
    }

    public function testMissingRefererDoesNotDiscardSession(): void
    {
        self::assertFalse(NotificationOpenHelper::isForeignReferer('', 'tiaraju.com.br'));
    }

    public function testEmptyLinkFallsBackToNotifications(): void
    {
        self::assertSame(
            self::BASE . '/notificacoes',
            NotificationOpenHelper::urlOnCurrentHost('', self::BASE)
        );
    }
}
