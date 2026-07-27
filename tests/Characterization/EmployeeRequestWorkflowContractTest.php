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
}
