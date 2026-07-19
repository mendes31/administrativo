<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Controllers\Services\Validation\ValidationLoginService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ValidationLoginService::class)]
final class ValidationLoginServiceTest extends TestCase
{
    private ValidationLoginService $service;

    protected function setUp(): void
    {
        $this->service = new ValidationLoginService();
    }

    public function testAcceptsUsernameAndPassword(): void
    {
        self::assertSame([], $this->service->validate([
            'username' => 'colaborador',
            'password' => 'senha-informada',
        ]));
    }

    public function testRequiresUsername(): void
    {
        self::assertSame(
            ['username' => 'O campo usuário é obrigatório.'],
            $this->service->validate(['password' => 'senha-informada'])
        );
    }

    public function testRequiresPassword(): void
    {
        self::assertSame(
            ['password' => 'O campo senha é obrigatório.'],
            $this->service->validate(['username' => 'colaborador'])
        );
    }

    public function testReturnsBothRequiredFieldErrorsForEmptyPayload(): void
    {
        self::assertSame([
            'username' => 'O campo usuário é obrigatório.',
            'password' => 'O campo senha é obrigatório.',
        ], $this->service->validate([]));
    }
}
