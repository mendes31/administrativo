<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\AiAnalyst\Auth\AiDataScopeResolver;
use PHPUnit\Framework\TestCase;

final class AiDataScopeResolverTest extends TestCase
{
    public function testFullSystemAccessReceivesExplicitAllScope(): void
    {
        self::assertSame(
            ['scope_mode' => 'all', 'branch_ids' => []],
            (new AiDataScopeResolver())->resolve(true)
        );
    }

    public function testRegularUserRemainsFailClosedUntilBranchMappingExists(): void
    {
        self::assertSame(
            ['scope_mode' => 'none', 'branch_ids' => []],
            (new AiDataScopeResolver())->resolve(false)
        );
    }
}
