<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\TestCase;

final class EmployeeRequestWorkflowContractTest extends TestCase
{
    public function testMigrationRegistersWorkflowArtifacts(): void
    {
        $path = dirname(__DIR__, 2) . '/database/migrations/20260721100000_expand_employee_request_workflow.php';
        self::assertFileExists($path);
        $source = (string) file_get_contents($path);
        self::assertStringContainsString('adms_request_type_stages', $source);
        self::assertStringContainsString('adms_approval_delegations', $source);
        self::assertStringContainsString('adms_employee_request_approval_events', $source);
        self::assertStringContainsString('ListApprovalDelegations', $source);
        self::assertStringContainsString('current_approver_user_id', $source);
    }

    public function testEscalateScriptExists(): void
    {
        $path = dirname(__DIR__, 2) . '/scripts/employee_request_escalate.php';
        self::assertFileExists($path);
        $source = (string) file_get_contents($path);
        self::assertStringContainsString('EmployeeRequestWorkflowService', $source);
        self::assertStringContainsString('--dry-run', $source);
    }

    public function testHrApprovalAclMigrationExists(): void
    {
        $path = dirname(__DIR__, 2) . '/database/migrations/20260727100000_register_employee_request_hr_approval_acl.php';
        self::assertFileExists($path);
        $source = (string) file_get_contents($path);
        self::assertStringContainsString('ApproveEmployeeRequestHR', $source);
        self::assertStringContainsString('PendingApprovals', $source);
    }

    public function testHrApprovalUsesPermissionService(): void
    {
        $path = dirname(__DIR__, 2) . '/app/adms/Controllers/portal/ApproveEmployeeRequestHR.php';
        $source = (string) file_get_contents($path);
        self::assertStringContainsString('EmployeeRequestPermissionService', $source);
        self::assertStringContainsString('canApproveAsHr', $source);
    }

    public function testWorkflowNotificationServiceExists(): void
    {
        $path = dirname(__DIR__, 2) . '/app/adms/Models/Services/EmployeeRequestNotificationService.php';
        self::assertFileExists($path);
        $source = (string) file_get_contents($path);
        self::assertStringContainsString('notifyPendingForRequest', $source);
        self::assertStringContainsString('notifyFinalApproved', $source);
        self::assertStringContainsString('notifyRejected', $source);
    }

    public function testWorkflowDispatchesNotifications(): void
    {
        $path = dirname(__DIR__, 2) . '/app/adms/Models/Services/EmployeeRequestWorkflowService.php';
        $source = (string) file_get_contents($path);
        self::assertStringContainsString('EmployeeRequestNotificationService', $source);
        self::assertStringContainsString('notifyPendingForRequest', $source);
    }

    public function testCreateRequestDispatchesNotification(): void
    {
        $path = dirname(__DIR__, 2) . '/app/adms/Controllers/portal/CreateEmployeeRequest.php';
        $source = (string) file_get_contents($path);
        self::assertStringContainsString('EmployeeRequestNotificationService', $source);
        self::assertStringContainsString('notifyPendingForRequest', $source);
    }
}
