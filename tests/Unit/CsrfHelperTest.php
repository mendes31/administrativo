<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Helpers\CSRFHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CSRFHelper::class)]
final class CsrfHelperTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testGeneratesAndStoresTokenForForm(): void
    {
        $token = CSRFHelper::generateCSRFToken('login');

        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        self::assertSame($token, $_SESSION['csrf_tokens']['login']);
    }

    public function testReusesUnconsumedTokenForSameForm(): void
    {
        $first = CSRFHelper::generateCSRFToken('login');
        $second = CSRFHelper::generateCSRFToken('login');

        self::assertSame($first, $second);
    }

    public function testValidTokenIsConsumedByDefault(): void
    {
        $token = CSRFHelper::generateCSRFToken('login');

        self::assertTrue(CSRFHelper::validateCSRFToken('login', $token));
        self::assertArrayNotHasKey('login', $_SESSION['csrf_tokens']);
        self::assertFalse(CSRFHelper::validateCSRFToken('login', $token));
    }

    public function testTokenCanRemainAvailableForRepeatedAjaxRequests(): void
    {
        $token = CSRFHelper::generateCSRFToken('pipeline');

        self::assertTrue(CSRFHelper::validateCSRFToken('pipeline', $token, false));
        self::assertSame($token, $_SESSION['csrf_tokens']['pipeline']);
    }

    public function testRejectsTokenFromAnotherForm(): void
    {
        $token = CSRFHelper::generateCSRFToken('form-a');

        self::assertFalse(CSRFHelper::validateCSRFToken('form-b', $token));
    }
}
