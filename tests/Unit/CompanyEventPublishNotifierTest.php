<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\CompanyEventPublishNotifier;
use PHPUnit\Framework\TestCase;

final class CompanyEventPublishNotifierTest extends TestCase
{
    public function testPublicationWindowAcceptsOpenEvent(): void
    {
        self::assertTrue(CompanyEventPublishNotifier::isWithinPublicationWindow([
            'publish_at' => null,
            'expire_at' => null,
        ]));
    }

    public function testPublicationWindowRejectsFuturePublishAndPastExpiry(): void
    {
        self::assertFalse(CompanyEventPublishNotifier::isWithinPublicationWindow([
            'publish_at' => date('Y-m-d H:i:s', time() + 3600),
            'expire_at' => null,
        ]));

        self::assertFalse(CompanyEventPublishNotifier::isWithinPublicationWindow([
            'publish_at' => null,
            'expire_at' => date('Y-m-d H:i:s', time() - 60),
        ]));
    }

    public function testFuturePublishDateDefersNotification(): void
    {
        self::assertTrue(CompanyEventPublishNotifier::publicationIsInTheFuture(
            date('Y-m-d H:i:s', time() + 3600)
        ));
        self::assertFalse(CompanyEventPublishNotifier::publicationIsInTheFuture(null));
        self::assertFalse(CompanyEventPublishNotifier::publicationIsInTheFuture(
            date('Y-m-d H:i:s', time() - 60)
        ));
    }

    public function testPublicationWindowAcceptsCurrentRange(): void
    {
        self::assertTrue(CompanyEventPublishNotifier::isWithinPublicationWindow([
            'publish_at' => date('Y-m-d H:i:s', time() - 60),
            'expire_at' => date('Y-m-d H:i:s', time() + 3600),
        ]));
    }
}
