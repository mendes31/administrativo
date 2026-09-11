<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Helpers\SstRiscoCargoMatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SstRiscoCargoMatch::class)]
final class SstRiscoCargoMatchTest extends TestCase
{
    public function testNormalizeIdTrataVazioEZeroComoNulo(): void
    {
        self::assertNull(SstRiscoCargoMatch::normalizeId(null));
        self::assertNull(SstRiscoCargoMatch::normalizeId(''));
        self::assertNull(SstRiscoCargoMatch::normalizeId(0));
        self::assertNull(SstRiscoCargoMatch::normalizeId('0'));
        self::assertSame(13, SstRiscoCargoMatch::normalizeId('13'));
    }

    public function testHasScopeExigeCargoOuDepartamento(): void
    {
        self::assertFalse(SstRiscoCargoMatch::hasScope(null, null));
        self::assertTrue(SstRiscoCargoMatch::hasScope(2, null));
        self::assertTrue(SstRiscoCargoMatch::hasScope(null, 13));
        self::assertTrue(SstRiscoCargoMatch::hasScope(2, 13));
    }

    public function testSqlUsuarioExigePeloMenosUmEixoEComparaOQueEstiverPreenchido(): void
    {
        $sql = SstRiscoCargoMatch::sqlUsuario('rc', 'u');
        self::assertStringContainsString('NULLIF(rc.adms_position_id, 0) IS NOT NULL OR NULLIF(rc.adms_department_id, 0) IS NOT NULL', $sql);
        self::assertStringContainsString('rc.adms_position_id = NULLIF(u.user_position_id, 0)', $sql);
        self::assertStringContainsString('rc.adms_department_id = NULLIF(u.user_department_id, 0)', $sql);
        self::assertStringNotContainsString('adms_users u INNER', $sql);
    }

    public function testSqlMatrizSemDepartamentoContaSetorNosCargosComColaborador(): void
    {
        $sql = SstRiscoCargoMatch::sqlMatrizParaCargo('rc', 'p.id', null);
        self::assertStringContainsString('rc.adms_position_id = p.id', $sql);
        self::assertStringContainsString('adms_users', $sql);
        self::assertStringContainsString('u_sst_rc.user_department_id = rc.adms_department_id', $sql);
        self::assertStringContainsString('u_sst_rc.user_position_id = p.id', $sql);
        self::assertStringNotContainsString(':dep', $sql);
    }

    public function testSqlMatrizComDepartamentoRespeitaSetorEAceitaSoCargo(): void
    {
        $sql = SstRiscoCargoMatch::sqlMatrizParaCargo('rc', ':pid', 13);
        self::assertStringContainsString('rc.adms_position_id = :pid', $sql);
        self::assertStringContainsString('rc.adms_department_id = :dep', $sql);
        self::assertStringContainsString('NULLIF(rc.adms_department_id, 0) IS NULL', $sql);
    }
}
