<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\SapProjects\SapProjectsFichaPdfService;
use PHPUnit\Framework\TestCase;

final class SapProjectsFichaPdfServiceTest extends TestCase
{
    public function testBuildHtmlIncludesPartnerDocumentAndItem(): void
    {
        $html = (new SapProjectsFichaPdfService())->buildHtml([
            'projeto' => [
                'Chave' => 55,
                'NumProjeto' => 12,
                'NomeProjeto' => 'Obra piloto',
                'CodigoPN' => 'C1',
                'NomePN' => 'Cliente Demo',
                'Status' => 'Iniciado',
                'Tipo' => 'Externo',
                'ValorVinculado' => 100.0,
            ],
            'identificacao' => [
                ['label' => 'Nº projeto', 'key' => 'NumProjeto', 'value' => 12, 'type' => 'int'],
                ['label' => 'Nome', 'key' => 'NomeProjeto', 'value' => 'Obra piloto', 'type' => 'text'],
                ['label' => 'Cliente', 'key' => 'NomePN', 'value' => 'Cliente Demo', 'type' => 'text'],
            ],
            'resumo' => [
                ['label' => 'Orçamento', 'key' => 'Orcamento', 'value' => 50.0, 'type' => 'money'],
                ['label' => 'Faturado C/P', 'key' => 'ResumoFaturadoCP', 'value' => 100.0, 'type' => 'money'],
            ],
            'totais' => [
                'QtdParceiros' => 1,
                'QtdDocumentos' => 1,
                'ValorNoProjeto' => 100.0,
                'ValorTotalNota' => 110.0,
            ],
            'parceiros' => [[
                'Codigo' => 'F9',
                'Nome' => 'Fornecedor X',
                'QtdDocumentos' => 1,
                'ValorNoProjeto' => 100.0,
                'ValorTotalNota' => 110.0,
                'documentos' => [[
                    'TipoDocumento' => 'NF de Entrada',
                    'NumNF' => 55,
                    'NumDocumento' => 88,
                    'DataDocumento' => '2026-10-01',
                    'ValorNoProjeto' => 100.0,
                    'ValorTotalNota' => 110.0,
                    'StatusDocumento' => 'Fechado',
                    'itens' => [[
                        'CodigoItem' => 'ABC',
                        'DescricaoItem' => 'Peça',
                        'Quantidade' => 2,
                        'TotalLinha' => 100.0,
                        'LinhaVinculada' => 'Sim',
                    ]],
                ]],
            ]],
            'aviso_itens' => 'Aviso de teste',
            'generated_at' => '2026-10-29 12:00:00',
        ]);

        self::assertStringContainsString('Dados do projeto selecionado', $html);
        self::assertStringContainsString('AbsEntry 55', $html);
        self::assertStringContainsString('Resumo financeiro (SAP)', $html);
        self::assertStringContainsString('Documentos por parceiro', $html);
        self::assertStringContainsString('Fornecedor X', $html);
        self::assertStringContainsString('NF 55', $html);
        self::assertStringContainsString('ABC', $html);
        self::assertStringContainsString('Aviso de teste', $html);
        self::assertStringContainsString('Cliente Demo', $html);
        self::assertStringContainsString('Obra piloto', $html);
        self::assertStringContainsString('ficha exclusiva do projeto selecionado', $html);
    }
}
