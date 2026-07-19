<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Helpers\UserAccessHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserAccessHelper::class)]
final class UserAccessHelperTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testRecognizesPrimarySuperAdministratorLevel(): void
    {
        $_SESSION['user_access_level_id'] = UserAccessHelper::SUPER_ADMIN_LEVEL_ID;

        self::assertTrue(UserAccessHelper::isSuperAdminLevel());
    }

    public function testRecognizesSuperAdministratorAmongMultipleLevels(): void
    {
        $_SESSION['user_access_level_id'] = 4;
        $_SESSION['adms_user_access_level_ids'] = [4, '1', 7];

        self::assertTrue(UserAccessHelper::isSuperAdminLevel());
    }

    public function testRegularAccessLevelIsNotSuperAdministrator(): void
    {
        $_SESSION['user_access_level_id'] = 4;
        $_SESSION['adms_user_access_level_ids'] = [4, 7];

        self::assertFalse(UserAccessHelper::isSuperAdminLevel());
    }

    public function testMalformedAccessLevelListDoesNotGrantAccess(): void
    {
        $_SESSION['adms_user_access_level_ids'] = '1';

        self::assertFalse(UserAccessHelper::isSuperAdminLevel());
    }

    public function testReadsSuperUserFlagFromSession(): void
    {
        $_SESSION['user_super_usuario'] = 1;

        self::assertTrue(UserAccessHelper::isSuperUserFlag());
    }

    public function testMissingSuperUserFlagDoesNotGrantAccess(): void
    {
        self::assertFalse(UserAccessHelper::isSuperUserFlag());
    }
}
