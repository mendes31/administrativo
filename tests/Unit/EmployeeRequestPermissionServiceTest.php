<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\EmployeeRequestPermissionService;
use PHPUnit\Framework\TestCase;

final class EmployeeRequestPermissionServiceTest extends TestCase
{
    public function testControllerConstants(): void
    {
        self::assertSame('ApproveEmployeeRequestHR', EmployeeRequestPermissionService::CONTROLLER_APPROVE_HR);
        self::assertSame('PendingApprovals', EmployeeRequestPermissionService::CONTROLLER_PENDING_APPROVALS);
    }
}
