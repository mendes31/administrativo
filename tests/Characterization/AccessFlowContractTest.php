<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class AccessFlowContractTest extends TestCase
{
    public function testLoginStillLoadsOrganizationalAndAccessData(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Models/Repository/LoginRepository.php'
        );

        self::assertStringContainsString('INNER JOIN adms_departments', $source);
        self::assertStringContainsString('INNER JOIN adms_positions', $source);
        self::assertStringContainsString('adms_users_access_levels', $source);
        self::assertStringContainsString('t0.super_usuario', $source);
        self::assertStringContainsString('t0.status', $source);
    }

    public function testSuccessfulLoginPersistsSessionBeforeRedirect(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Controllers/login/Login.php'
        );

        $savePosition = strpos($source, 'saveSession((int)$result[\'id\'], session_id())');
        $redirectPosition = strpos($source, '$redirectUrl = $this->getRedirectUrlAfterLogin()');

        self::assertNotFalse($savePosition);
        self::assertNotFalse($redirectPosition);
        self::assertLessThan($redirectPosition, $savePosition);
    }

    public function testSessionRepositoryPreservesSingleSessionAndHeartbeatContracts(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Models/Repository/AdmsSessionsRepository.php'
        );

        $invalidatePosition = strpos($source, '$this->invalidateAllSessionsByUserId($userId);');
        $insertPosition = strpos($source, "INSERT INTO {\$this->table}");

        self::assertNotFalse($invalidatePosition);
        self::assertNotFalse($insertPosition);
        self::assertLessThan($insertPosition, $invalidatePosition);
        self::assertStringContainsString('ON DUPLICATE KEY UPDATE', $source);
        self::assertStringContainsString("status = 'ativa'", $source);
    }

    public function testRouterKeepsPublicPageAndAuthenticatedAclPathsDistinct(): void
    {
        $source = $this->readProjectFile('routes/LoadPageAdmAccessLevel.php');

        self::assertStringContainsString("['public_page']", $source);
        self::assertStringContainsString('checkUserPagePermission', $source);
        self::assertStringContainsString("empty(\$_SESSION['user_id'])", $source);
        self::assertStringContainsString('internalAjaxMap', $source);
    }

    public function testEmployeePortalRemainsBoundToAuthenticatedCollaborator(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Controllers/portal/EmployeePortal.php'
        );

        self::assertStringContainsString("\$_SESSION['user_id']", $source);
        self::assertStringContainsString(
            "'employee_id' => \$employeeId",
            $source
        );
        self::assertStringContainsString("'MyPayrollDocuments'", $source);
        self::assertStringContainsString('EmploymentHistoryRepository', $source);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);

        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
