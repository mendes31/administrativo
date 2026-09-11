<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Helpers\NotificationSocialTypeHelper;
use PHPUnit\Framework\TestCase;

final class NotificationSocialTypeHelperTest extends TestCase
{
    public function testClassifiesTimelineEngagementAsSocial(): void
    {
        self::assertTrue(NotificationSocialTypeHelper::isSocial('timeline_reaction'));
        self::assertTrue(NotificationSocialTypeHelper::isSocial('timeline_comment_reaction'));
        self::assertTrue(NotificationSocialTypeHelper::isSocial('timeline_comment'));
        self::assertTrue(NotificationSocialTypeHelper::isSocial('timeline_share'));
        self::assertTrue(NotificationSocialTypeHelper::isSocial('timeline_mention'));
    }

    public function testKeepsOfficialAndAckNotificationsOutOfBulkRead(): void
    {
        self::assertFalse(NotificationSocialTypeHelper::isSocial('payroll_document'));
        self::assertFalse(NotificationSocialTypeHelper::isSocial('payroll_document_reminder'));
        self::assertFalse(NotificationSocialTypeHelper::isSocial('sst_epi_ficha_sign'));
        self::assertFalse(NotificationSocialTypeHelper::isSocial('evaluation_assignment'));
        self::assertFalse(NotificationSocialTypeHelper::isSocial('informativo'));
        self::assertFalse(NotificationSocialTypeHelper::isSocial('policy'));
        self::assertFalse(NotificationSocialTypeHelper::isSocial('projeto_etapa'));
    }
}
