<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Helpers\SstRiscoCargoMatch;
use App\adms\Models\Services\SstTreinamentosObrigatoriosResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SstTreinamentosObrigatoriosResolver::class)]
final class SstTreinamentosObrigatoriosResolverTest extends TestCase
{
    public function testMatrizPorCargoIncluiSetorViaColaboradores(): void
    {
        $sql = SstRiscoCargoMatch::sqlMatrizParaCargo('rc', 'p.id', null);
        self::assertStringContainsString('rc.adms_position_id = p.id', $sql);
        self::assertStringContainsString('u_sst_rc.user_position_id = p.id', $sql);
    }
}
