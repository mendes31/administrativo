<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\EmployeeRequestNotificationService;
use PHPUnit\Framework\TestCase;

final class EmployeeRequestNotificationServiceTest extends TestCase
{
    public function testNotificationTypeConstants(): void
    {
        self::assertSame('employee_request', EmployeeRequestNotificationService::ENTITY_TYPE);
        self::assertSame('employee_request_pending_approval', EmployeeRequestNotificationService::TYPE_PENDING);
        self::assertSame('employee_request_approved', EmployeeRequestNotificationService::TYPE_APPROVED);
        self::assertSame('employee_request_rejected', EmployeeRequestNotificationService::TYPE_REJECTED);
        self::assertSame('employee_request_escalated', EmployeeRequestNotificationService::TYPE_ESCALATED);
    }

    public function testMarkNotificationsReadAcceptsInvalidIds(): void
    {
        EmployeeRequestNotificationService::markNotificationsRead(0, 0);
        self::assertTrue(true);
    }

    public function testNotifyPendingForRequestAcceptsInvalidId(): void
    {
        EmployeeRequestNotificationService::notifyPendingForRequest(0);
        self::assertTrue(true);
    }
}
