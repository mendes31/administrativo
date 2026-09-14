<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Helpers\SstAsoPrevisaoHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SstAsoPrevisaoHelper::class)]
final class SstAsoPrevisaoHelperTest extends TestCase
{
    public function testPrevisaoUsaValidadeQuandoInformada(): void
    {
        $dt = SstAsoPrevisaoHelper::previsaoEm('2026-10-15', '2025-10-15', 12);
        self::assertNotNull($dt);
        self::assertSame('2026-10-15', $dt->format('Y-m-d'));
    }

    public function testPrevisaoSomaPeriodicidadeQuandoNaoHaValidade(): void
    {
        $dt = SstAsoPrevisaoHelper::previsaoEm(null, '2025-10-15', 12);
        self::assertNotNull($dt);
        self::assertSame('2026-10-15', $dt->format('Y-m-d'));
    }

    public function testNormalizarMesInvalidoUsaMesAtual(): void
    {
        $hoje = new \DateTimeImmutable('2026-09-14');
        self::assertSame('2026-09', SstAsoPrevisaoHelper::normalizarMes('', $hoje));
        self::assertSame('2026-09', SstAsoPrevisaoHelper::normalizarMes('abc', $hoje));
        self::assertSame('2027-03', SstAsoPrevisaoHelper::normalizarMes('2027-03', $hoje));
    }

    public function testMesFiltroVazioSignificaTodos(): void
    {
        self::assertSame('', SstAsoPrevisaoHelper::mesFiltro(''));
        self::assertSame('', SstAsoPrevisaoHelper::mesFiltro('todos'));
        self::assertSame('', SstAsoPrevisaoHelper::mesFiltro('abc'));
        self::assertSame('2027-03', SstAsoPrevisaoHelper::mesFiltro('2027-03'));
    }

    public function testLabelMesEmPortugues(): void
    {
        self::assertSame('outubro de 2026', SstAsoPrevisaoHelper::labelMes('2026-10'));
    }

    public function testSituacaoVencidoAVencerEPrevisto(): void
    {
        $hoje = new \DateTimeImmutable('2026-09-14');
        self::assertSame('vencido', SstAsoPrevisaoHelper::situacao(new \DateTimeImmutable('2026-09-01'), $hoje));
        self::assertSame('a_vencer', SstAsoPrevisaoHelper::situacao(new \DateTimeImmutable('2026-10-01'), $hoje));
        self::assertSame('previsto', SstAsoPrevisaoHelper::situacao(new \DateTimeImmutable('2026-12-01'), $hoje));
    }

    public function testMesesOpcoesComecaNoMesAtual(): void
    {
        $hoje = new \DateTimeImmutable('2026-09-14');
        $opcoes = SstAsoPrevisaoHelper::mesesOpcoes(2, $hoje);
        self::assertCount(3, $opcoes);
        self::assertSame('2026-09', $opcoes[0]['value']);
        self::assertSame('2026-11', $opcoes[2]['value']);
    }
}
