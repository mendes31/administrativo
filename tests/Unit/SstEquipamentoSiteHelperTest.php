<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Helpers\SstEquipamentoSiteHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SstEquipamentoSiteHelper::class)]
final class SstEquipamentoSiteHelperTest extends TestCase
{
    public function testListaFixaNaoUsaCadastroDeEmpresa(): void
    {
        $opts = SstEquipamentoSiteHelper::options();
        self::assertSame(['laboratorio_tiaraju', 'afra_pharma', 'afra_biotics'], array_keys($opts));
        self::assertSame('Laboratório Tiaraju', $opts['laboratorio_tiaraju']);
        self::assertSame('Afra Pharma', $opts['afra_pharma']);
        self::assertSame('Afra Biotics', $opts['afra_biotics']);
    }

    public function testNormalizaNomeEAliasAntigo(): void
    {
        self::assertSame('laboratorio_tiaraju', SstEquipamentoSiteHelper::normalize('Laboratório Tiaraju'));
        self::assertSame('afra_pharma', SstEquipamentoSiteHelper::normalize('lab_tiaraju_filial'));
        self::assertSame('afra_biotics', SstEquipamentoSiteHelper::normalize('Afra Biotics'));
        self::assertNull(SstEquipamentoSiteHelper::normalize('XYZ'));
        self::assertNull(SstEquipamentoSiteHelper::normalize('tiaraju_farma'));
    }

    public function testLocalizacaoCompoeSiteESala(): void
    {
        self::assertSame(
            'Laboratório Tiaraju — SALA TI',
            SstEquipamentoSiteHelper::formatLocalizacao('laboratorio_tiaraju', 'SALA TI')
        );
        self::assertSame('Afra Pharma', SstEquipamentoSiteHelper::formatLocalizacao('afra_pharma', ''));
        self::assertSame('SALA TI', SstEquipamentoSiteHelper::formatLocalizacao(null, 'SALA TI'));
    }

    public function testFiltroIncluiAliasesDoSite(): void
    {
        $slugs = SstEquipamentoSiteHelper::slugsForFilter('Laboratório Tiaraju');
        self::assertContains('laboratorio_tiaraju', $slugs);
        self::assertContains('lab_tiaraju_matriz', $slugs);
        self::assertSame(
            ['afra_pharma', 'lab_tiaraju_filial'],
            SstEquipamentoSiteHelper::slugsForFilter('afra_pharma')
        );
    }

    public function testOrdemSqlSegueListaFixaDeSites(): void
    {
        $sql = SstEquipamentoSiteHelper::sqlOrderRank('e.empresa_contratante');
        self::assertStringContainsString("WHEN 'laboratorio_tiaraju' THEN 1", $sql);
        self::assertStringContainsString("WHEN 'afra_pharma' THEN 2", $sql);
        self::assertStringContainsString("WHEN 'afra_biotics' THEN 3", $sql);
        self::assertStringContainsString('ELSE 99 END', $sql);
    }
}
