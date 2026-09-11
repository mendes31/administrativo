<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\SstEpisObrigatoriosResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SstEpisObrigatoriosResolver::class)]
final class SstEpisObrigatoriosResolverTest extends TestCase
{
    public function testMesmoEpiCargoEGheContaUmaVezEMantemOrigemDoCargo(): void
    {
        $rows = SstEpisObrigatoriosResolver::uniqueByEpiId([
            ['adms_sst_epi_id' => 5, 'epi_nome' => 'Luva', 'origem' => 'necessidade'],
            ['adms_sst_epi_id' => 5, 'epi_nome' => 'Luva', 'origem' => 'ghe'],
            ['adms_sst_epi_id' => 7, 'epi_nome' => 'Capacete', 'origem' => 'ghe'],
        ]);

        self::assertCount(2, $rows);
        self::assertSame(5, $rows[0]['adms_sst_epi_id']);
        self::assertSame('necessidade+ghe', $rows[0]['origem']);
        self::assertSame(7, $rows[1]['adms_sst_epi_id']);
        self::assertSame('ghe', $rows[1]['origem']);
    }
}
