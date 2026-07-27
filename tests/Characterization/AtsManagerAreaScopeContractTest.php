<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class AtsManagerAreaScopeContractTest extends TestCase
{
    public function testIsManagerOfVagaExistsAndUsedInCanEdit(): void
    {
        $service = $this->read('app/adms/Models/Services/RhPermissionService.php');
        self::assertStringContainsString('function isManagerOfVaga', $service);
        self::assertStringContainsString('isManagerOfVaga($vaga)', $service);
        self::assertStringContainsString('getAllSubordinates', $service);
        self::assertStringContainsString('user_department_id', $service);
        self::assertStringContainsString('area_id', $service);
    }

    public function testCandidatoAccessUsesManagerOfVagaNotGlobalManager(): void
    {
        $service = $this->read('app/adms/Models/Services/RhCandidatoPermissionService.php');
        self::assertStringContainsString('isManagerOfAnyVagaDoCandidato', $service);
        self::assertStringContainsString('isManagerOfVaga', $service);
        self::assertStringNotContainsString('if (RhPermissionService::isManager())', $service);
    }

    private function read(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source);

        return $source;
    }
}
