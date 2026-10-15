<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\FormacaoPrecoCalculator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FormacaoPrecoCalculator::class)]
final class FormacaoPrecoCalculatorTest extends TestCase
{
    public function testImpostoEfetivoPadraoTiaraju(): void
    {
        $imp = FormacaoPrecoCalculator::impostoEfetivo(0.12, 0.0165, 0.076);
        self::assertEqualsWithDelta(0.2014, $imp, 0.0000001);
    }

    public function testTresMetodosConvergemSemDespesasNoProbiotico(): void
    {
        $base = [
            'icms' => 0.12,
            'pis' => 0.0165,
            'cofins' => 0.076,
            'custo_variavel' => 19.42,
            'custo_fixo' => 9.27,
            'frete' => 0,
            'comissao' => 0,
            'desconto' => 0,
            'meta_pb' => 0.2070,
            'meta_rl' => 0.2593,
            'meta_mk' => 0.35,
        ];
        $out = FormacaoPrecoCalculator::calcularLinha($base);
        self::assertEqualsWithDelta(28.69, $out['custo_total'], 0.0001);
        self::assertEqualsWithDelta(48.50, (float) $out['mk']['preco_vendido'], 0.02);
        self::assertEqualsWithDelta(48.50, (float) $out['rl']['preco_vendido'], 0.02);
        self::assertEqualsWithDelta(48.50, (float) $out['pb']['preco_vendido'], 0.02);
        self::assertEqualsWithDelta(10.04, (float) $out['mk']['lucro_unitario'], 0.02);
        self::assertEqualsWithDelta(0.35, (float) $out['mk']['markup'], 0.001);
        self::assertEqualsWithDelta(0.2593, (float) $out['mk']['margem_rl'], 0.001);
        self::assertEqualsWithDelta(0.2070, (float) $out['mk']['margem_pb'], 0.001);
    }

    public function testExemploPraticoMesmaMetaSeparaOsPrecos(): void
    {
        $custo = 2.77;
        $imp = 0.2014;
        $frete = 0.03;
        $com = 0.10;
        $meta = 0.5301;
        $pb = FormacaoPrecoCalculator::precoPelaMeta('pb', $custo, $imp, $frete, $com, $meta);
        $rl = FormacaoPrecoCalculator::precoPelaMeta('rl', $custo, $imp, $frete, $com, $meta);
        $mk = FormacaoPrecoCalculator::precoPelaMeta('mk', $custo, $imp, $frete, $com, $meta);
        self::assertEqualsWithDelta(20.00, $pb, 0.02);
        self::assertEqualsWithDelta(11.29, $rl, 0.02);
        self::assertEqualsWithDelta(6.34, $mk, 0.02);
    }

    public function testTetoPbInvalido(): void
    {
        $out = FormacaoPrecoCalculator::calcularLinha([
            'icms' => 0.12,
            'pis' => 0.0165,
            'cofins' => 0.076,
            'custo_variavel' => 10,
            'custo_fixo' => 0,
            'frete' => 0.03,
            'comissao' => 0.10,
            'meta_pb' => 0.70,
        ]);
        self::assertNull($out['pb']['preco_vendido']);
        self::assertStringContainsString('INVÁLIDO', (string) $out['pb']['status']);
    }

    public function testMarkupMostraAsOutrasPercentagensReaisDoMesmoPreco(): void
    {
        $out = FormacaoPrecoCalculator::calcularLinha([
            'icms' => 0.12,
            'pis' => 0.0165,
            'cofins' => 0.076,
            'custo_variavel' => 19.42,
            'custo_fixo' => 9.27,
            'frete' => 0.10,
            'comissao' => 0.20,
            'desconto' => 0.10,
            'meta_mk' => 0.35,
            'forecast' => 1000,
        ]);
        self::assertEqualsWithDelta(0.35, (float) $out['mk']['markup'], 0.0001);
        self::assertNotNull($out['mk']['margem_rl']);
        self::assertNotNull($out['mk']['margem_pb']);
        self::assertLessThan(0.35, (float) $out['mk']['margem_rl']);
        self::assertLessThan((float) $out['mk']['margem_rl'], (float) $out['mk']['margem_pb']);
        self::assertArrayHasKey('fator_por_dentro', $out);
        self::assertNotNull($out['fator_por_dentro']);
        self::assertNotNull($out['mk']['lucro_projeto']);
        self::assertNotNull($out['mk']['receita_bruta']);
        self::assertSame('OK', $out['mk']['validacao']);
    }

    public function testParsePercentualBrasileiro(): void
    {
        self::assertEqualsWithDelta(0.12, FormacaoPrecoCalculator::parsePercentInput('12,00'), 0.00001);
        self::assertEqualsWithDelta(19.42, FormacaoPrecoCalculator::parseDecimalInput('19,42'), 0.00001);
    }
}
