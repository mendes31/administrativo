<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\FinCostCenterSapService;
use App\adms\Models\Services\SapReportApiService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class FinCostCenterHierarchyTest extends TestCase
{
    public function testSelecaoHabilitaSomenteComMaisDeUmModelo(): void
    {
        $service = $this->service();
        $um = $service->assembleHierarchy(
            [$this->row('TIA_PD', 100.0)],
            [['id' => 7, 'nome' => 'Centros de custo']],
            [$this->node(7, 1, 'Centros de custo', 0, 0)],
            [$this->link(7, 1, 'TIA_PD')],
            ''
        );
        self::assertFalse($um['habilitado']);
        self::assertSame('7', $um['modelo']);
        self::assertSame('Centros de custo', $um['modelo_nome']);
        self::assertCount(1, $um['modelos']);

        $dois = $service->assembleHierarchy(
            [$this->row('TIA_PD', 100.0), $this->row('AFR_ALM', 40.0)],
            [
                ['id' => 1, 'nome' => 'Hierarquia Padrão'],
                ['id' => 7, 'nome' => 'Centros de custo'],
            ],
            [
                $this->node(1, 10, 'Hierarquia Padrão', 0, 0),
                $this->node(7, 1, '', 0, 0),
                $this->node(7, 2, 'Almoxarifado', 1, 1),
            ],
            [
                $this->link(1, 10, 'TIA_PD'),
                $this->link(7, 2, 'AFR_ALM'),
            ],
            ''
        );
        self::assertTrue($dois['habilitado']);
        self::assertSame('', $dois['modelo']);
        self::assertSame('Todas', $dois['modelo_nome']);
        self::assertSame(['Centros de custo', 'Hierarquia Padrão'], array_column($dois['modelos'], 'nome'));
        self::assertCount(2, $dois['arvore']);
        self::assertSame('Centros de custo', $dois['arvore'][0]['label']);
        self::assertSame(40.0, $dois['arvore'][0]['valor']);
        self::assertSame('Almoxarifado', $dois['arvore'][0]['filhos'][0]['label']);
        self::assertSame('Hierarquia Padrão', $dois['arvore'][1]['label']);
        self::assertSame(100.0, $dois['arvore'][1]['valor']);
    }

    public function testModeloNovoEntraNaListaECentroForaFicaSeparado(): void
    {
        $service = $this->service();
        $view = $service->assembleHierarchy(
            [
                $this->row('TIA_PD', 100.0),
                $this->row('TIA_ALM', 40.0),
                $this->row('TIA_XX', 15.0),
                $this->row('TIA_ZERO', 0.0),
            ],
            [
                ['id' => 1, 'nome' => 'Hierarquia Padrão'],
                ['id' => 7, 'nome' => 'Centros de custo'],
                ['id' => 9, 'nome' => 'Novo modelo'],
            ],
            [
                $this->node(7, 1, 'Raiz', 0, 0),
                $this->node(7, 2, 'Almoxarifado', 1, 1),
                $this->node(7, 3, 'Pesquisa', 1, 2),
            ],
            [
                $this->link(7, 2, 'TIA_ALM'),
                $this->link(7, 3, 'TIA_PD'),
                $this->link(7, 3, 'TIA_ZERO'),
            ],
            '7'
        );

        self::assertTrue($view['habilitado']);
        self::assertSame('7', $view['modelo']);
        self::assertSame(['Centros de custo', 'Hierarquia Padrão', 'Novo modelo'], array_column($view['modelos'], 'nome'));
        self::assertSame('Centros de custo', $view['arvore'][0]['label']);
        self::assertSame(140.0, $view['arvore'][0]['valor']);
        self::assertSame('Almoxarifado', $view['arvore'][0]['filhos'][0]['filhos'][0]['label']);
        self::assertSame('Pesquisa', $view['arvore'][0]['filhos'][0]['filhos'][1]['label']);
        self::assertSame('TIA_PD', $view['arvore'][0]['filhos'][0]['filhos'][1]['centros'][0]['centro']);
        self::assertSame('Fora do modelo', $view['arvore'][1]['label']);
        self::assertSame('TIA_XX', $view['arvore'][1]['centros'][0]['centro']);
        self::assertSame(15.0, $view['arvore'][1]['valor']);
    }

    public function testModeloInexistenteVoltaParaTodas(): void
    {
        $service = $this->service();
        $view = $service->assembleHierarchy(
            [$this->row('TIA_PD', 10.0)],
            [
                ['id' => 1, 'nome' => 'A'],
                ['id' => 2, 'nome' => 'B'],
            ],
            [
                $this->node(1, 1, 'A', 0, 0),
                $this->node(2, 1, 'B', 0, 0),
            ],
            [
                $this->link(1, 1, 'TIA_PD'),
                $this->link(2, 1, 'TIA_PD'),
            ],
            '99'
        );

        self::assertSame('', $view['modelo']);
        self::assertSame('Todas', $view['modelo_nome']);
        self::assertCount(2, $view['arvore']);
        self::assertSame(10.0, $view['arvore'][0]['valor']);
        self::assertSame(10.0, $view['arvore'][1]['valor']);
    }

    public function testDocumentosFicamAbaixoDaContaIncluindoNotaCancelada(): void
    {
        $nodes = [[
            'filhos' => [[
                'centros' => [[
                    'centro' => 'TIA_TI',
                    'contas' => [[
                        'account' => '4.01.01.04.55',
                        'acct_name' => 'Despesas com Internet',
                        'valor' => 206.15,
                    ]],
                ]],
            ]],
        ]];
        $docs = [
            $this->doc('TIA_TI', '4.01.01.04.55', '273655', '29/09/2026', '42032', 'TIA_TI', '', '', 150.0),
            $this->doc('TIA_TI', '4.01.01.04.55', '273653', '29/09/2026', '42031', 'INTERNET', '', '', 56.15),
            $this->doc('TIA_TI', '5.01.02.01.24', '273806', '30/09/2026', '42057', '', 'XPTO01', 'NOVO SUPLEMENTO', -10000.0),
            $this->doc('TIA_TI', '5.01.02.01.24', '273803', '30/09/2026', '42055', '', 'XPTO01', 'NOVO SUPLEMENTO', 10000.0),
        ];

        $service = $this->service();
        $method = new ReflectionMethod(FinCostCenterSapService::class, 'attachDocumentos');
        $method->setAccessible(true);
        $method->invokeArgs($service, [&$nodes, $docs]);

        $contas = $nodes[0]['filhos'][0]['centros'][0]['contas'];
        self::assertSame('4.01.01.04.55', $contas[0]['account']);
        self::assertSame('273653', $contas[0]['documentos'][0]['trans_id']);
        self::assertSame('INTERNET', $contas[0]['documentos'][0]['regra']);
        self::assertSame('42031', $contas[0]['documentos'][0]['base_ref']);
        self::assertSame('TIA_TI', $contas[0]['documentos'][1]['regra']);
        self::assertSame('5.01.02.01.24', $contas[1]['account']);
        self::assertSame(0.0, $contas[1]['valor']);
        self::assertSame('273803', $contas[1]['documentos'][0]['trans_id']);
        self::assertSame('XPTO01', $contas[1]['documentos'][0]['projeto']);
        self::assertSame('NOVO SUPLEMENTO', $contas[1]['documentos'][0]['nome_projeto']);
        self::assertSame(-10000.0, $contas[1]['documentos'][1]['valor']);
    }

    private function service(): FinCostCenterSapService
    {
        return new FinCostCenterSapService(new class extends SapReportApiService {
            public function __construct()
            {
            }
        });
    }

    /** @return array<string, mixed> */
    private function row(string $centro, float $valor): array
    {
        return [
            'unidade' => 'TIARAJU',
            'centro' => $centro,
            'nome_centro' => 'TIARAJU - ' . $centro,
            'account' => '5.01.02.01.24',
            'acct_name' => 'Conta teste',
            'status' => 'Alocado',
            'regra_revisar' => '',
            'valor' => $valor,
        ];
    }

    /** @return array<string, mixed> */
    private function node(int $template, int $cat, string $nome, int $father, int $order): array
    {
        return [
            'template_id' => $template,
            'cat_id' => $cat,
            'nome' => $nome,
            'father' => $father,
            'vis_order' => $order,
        ];
    }

    /** @return array<string, mixed> */
    private function doc(
        string $centro,
        string $account,
        string $trans,
        string $data,
        string $base,
        string $regra,
        string $projeto,
        string $nomeProjeto,
        float $valor
    ): array {
        return [
            'centro' => $centro,
            'account' => $account,
            'natureza' => 'Conta ' . $account,
            'trans_id' => $trans,
            'line_id' => '0',
            'ref_date' => $data,
            'base_ref' => $base,
            'historico' => 'Notas fiscais de Entrada',
            'regra' => $regra,
            'projeto' => $projeto,
            'nome_projeto' => $nomeProjeto,
            'valor' => $valor,
        ];
    }

    /** @return array<string, mixed> */
    private function link(int $template, int $cat, string $code): array
    {
        return [
            'template_id' => $template,
            'cat_id' => $cat,
            'prc_code' => $code,
            'vis_order' => 0,
        ];
    }
}
