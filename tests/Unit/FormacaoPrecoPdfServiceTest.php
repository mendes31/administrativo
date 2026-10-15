<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\FormacaoPrecoCalculator;
use App\adms\Models\Services\FormacaoPrecoPdfService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FormacaoPrecoPdfService::class)]
final class FormacaoPrecoPdfServiceTest extends TestCase
{
    public function testHtmlIncluiColunasOcultasEPercentuaisReais(): void
    {
        $calc = FormacaoPrecoCalculator::calcularLinha([
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
        $html = (new FormacaoPrecoPdfService())->buildHtml([
            'nome' => 'Physician Choice',
            'icms_padrao' => 0.12,
            'pis_padrao' => 0.0165,
            'cofins_padrao' => 0.076,
            'itens' => [[
                'produto' => 'Probiótico Saúde Vaginal',
                'codigo_produto' => 'PC-01',
                'forecast' => 1000,
                'icms' => 0.12,
                'pis' => 0.0165,
                'cofins' => 0.076,
                'custo_variavel' => 19.42,
                'custo_fixo' => 9.27,
                'frete_pct' => 0.10,
                'comissao_pct' => 0.20,
                'desconto_pct' => 0.10,
                'meta_mk' => 0.35,
                'calc' => $calc,
            ]],
        ]);
        self::assertStringContainsString('Physician Choice', $html);
        self::assertStringContainsString('Fator por dentro', $html);
        self::assertStringContainsString('Lucro projeto', $html);
        self::assertStringContainsString('Receita bruta', $html);
        self::assertStringContainsString('RL real %', $html);
        self::assertStringContainsString('PB real %', $html);
        self::assertStringContainsString('Markup real %', $html);
        self::assertStringContainsString('class="oculto"', $html);
        self::assertStringContainsString('pb-pct', $html);
        self::assertStringContainsString('rl-pct', $html);
        self::assertStringContainsString('mk-pct', $html);
        self::assertStringContainsString('azul — PB', $html);
        self::assertStringNotContainsString('<pagebreak', $html);
    }

    public function testDoisProdutosGeramUmaPaginaCada(): void
    {
        $html = (new FormacaoPrecoPdfService())->buildHtml([
            'nome' => 'Dois produtos',
            'itens' => [
                ['produto' => 'Produto A', 'calc' => []],
                ['produto' => 'Produto B', 'calc' => []],
            ],
        ]);
        self::assertSame(1, substr_count($html, '<pagebreak'));
        self::assertStringContainsString('1. Produto A', $html);
        self::assertStringContainsString('2. Produto B', $html);
        $parts = (new FormacaoPrecoPdfService())->buildParts([
            'nome' => 'Dois produtos',
            'itens' => [
                ['produto' => 'Produto A', 'calc' => []],
                ['produto' => 'Produto B', 'calc' => []],
            ],
        ]);
        self::assertStringContainsString('Formação de preço', $parts['header']);
        self::assertStringContainsString('Dois produtos', $parts['header']);
        self::assertStringNotContainsString('Formação de preço — análise completa', $parts['body']);
        self::assertStringContainsString('<pagebreak', $parts['body']);
    }
}
