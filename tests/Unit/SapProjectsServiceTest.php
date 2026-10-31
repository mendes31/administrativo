<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\SapProjects\SapProjectsService;
use App\adms\Models\Services\SapProjects\SapProjectsSql;
use App\adms\Models\Services\SapReportApiService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/** Massa da seção 8 de docs/dashboard-projetos-sap-b1.md. */
final class SapProjectsServiceTest extends TestCase
{
    public function testDashboardMatchesConferenceValues(): void
    {
        $data = $this->service($this->conferenceProjects(), $this->conferenceDocuments())->dashboard([]);

        self::assertSame(3, $data['kpis']['QtdProjetos']);
        self::assertSame(7, $data['kpis']['QtdDocumentos']);
        self::assertSame(115975.81, $data['kpis']['ValorVinculado']);
        self::assertSame(2, $data['kpis']['QtdResumoDesatualizado']);

        $porAno = array_column($this->periodoAno($data), 'ValorNoProjeto', 'Periodo');
        self::assertSame(['2023' => 88425.81, '2026' => 27550.0], $porAno);

        self::assertSame(
            [['AnoInicio' => '2026', 'QtdProjetos' => 3, 'ValorVinculado' => 115975.81, 'Orcamento' => 0.0]],
            $data['por_ano_projeto']
        );
    }

    public function testCanceledInvoiceIsListedButLeftOutOfTotals(): void
    {
        $docs = $this->conferenceDocuments();
        $docs[] = $this->doc(5, 60399, '2026-10-01', 999.0, 'Y');

        $report = $this->service($this->conferenceProjects(), $docs)->report('documentos', []);

        self::assertSame(8, $report['rows_count']);
        self::assertSame(115975.81, $report['totals']['ValorNoProjeto']);
        $canceled = array_values(array_filter($report['rows'], static fn (array $r): bool => $r['NumNF'] === 60399));
        self::assertTrue($canceled[0]['_excluido']);
    }

    public function testPeriodReportCountsAllDocumentsButSumsOnlyValidOnes(): void
    {
        $docs = $this->conferenceDocuments();
        $docs[] = $this->doc(5, 60399, '2026-10-01', 999.0, 'C');

        $report = $this->service($this->conferenceProjects(), $docs)->report('periodo', ['agrupar' => 'ano']);
        $rows = array_column($report['rows'], null, 'Periodo');

        self::assertSame(6, $rows['2026']['QtdDocumentos']);
        self::assertSame(27550.0, $rows['2026']['ValorNoProjeto']);
    }

    public function testItemsReportRequiresProjectOrPeriod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service([], [])->report('itens', ['status' => 'S']);
    }

    public function testReportDropsFiltersThatDoNotApplyToItsQuery(): void
    {
        $api = new FakeSapProjectsApi($this->conferenceProjects(), []);
        $report = (new SapProjectsService($api, new SapProjectsSql('')))
            ->report('status', ['status' => 'S', 'fornecedor' => 'F00012']);

        self::assertSame(['status' => 'S'], $report['filters']);
        self::assertStringNotContainsString('F00012', implode("\n", $api->queries));
    }

    public function testFetchPagesThroughTheApiLimit(): void
    {
        $docs = [];
        for ($i = 1; $i <= 1203; $i++) {
            $docs[] = $this->doc(5, 70000 + $i, '2026-01-15', 1.0, 'N');
        }
        $api = new FakeSapProjectsApi($this->conferenceProjects(), $docs);

        $report = (new SapProjectsService($api, new SapProjectsSql('')))->report('documentos', []);

        self::assertSame(1203, $report['rows_count']);
        self::assertSame(1203.0, $report['totals']['ValorNoProjeto']);
        $docPages = array_values(array_filter(
            $api->queries,
            static fn (string $q): bool => str_contains($q, '"LinhaVinculo"')
        ));
        self::assertCount(3, $docPages);
    }

    public function testGroupDocumentsByPartnerNestsItemsUnderInvoice(): void
    {
        $service = $this->service([], []);
        $docs = [
            [
                'DocEntry' => 501, 'NumDocumento' => 10, 'Fornecedor' => 'F002', 'NomeFornecedor' => 'Beta Ltda',
            ] + $this->doc(5, 100, '2026-01-10', 80.0, 'N'),
            [
                'DocEntry' => 502, 'NumDocumento' => 11, 'Fornecedor' => 'F001', 'NomeFornecedor' => 'Alfa SA',
            ] + $this->doc(5, 101, '2026-02-10', 20.0, 'N'),
            [
                'DocEntry' => 503, 'NumDocumento' => 12, 'Fornecedor' => 'F001', 'NomeFornecedor' => 'Alfa SA',
            ] + $this->doc(5, 102, '2026-03-10', 999.0, 'Y'),
        ];
        $itens = [
            [
                'DocEntry' => 501, 'NumDocumento' => 10, 'NumNF' => 100, 'CodigoItem' => 'I1',
                'DescricaoItem' => 'Parafuso', 'Quantidade' => 2, 'TotalLinha' => 80.0, 'ConsiderarNoTotal' => 'Sim',
            ],
            [
                'DocEntry' => 502, 'NumDocumento' => 11, 'NumNF' => 101, 'CodigoItem' => 'I2',
                'DescricaoItem' => 'Porca', 'Quantidade' => 1, 'TotalLinha' => 20.0, 'ConsiderarNoTotal' => 'Sim',
            ],
        ];

        $parceiros = $service->groupDocumentsByPartner($docs, $itens);

        self::assertSame(['F001', 'F002'], array_column($parceiros, 'Codigo'));
        self::assertSame(2, $parceiros[0]['QtdDocumentos']);
        self::assertSame(20.0, $parceiros[0]['ValorNoProjeto']);
        self::assertSame(80.0, $parceiros[1]['ValorNoProjeto']);
        self::assertCount(1, $parceiros[1]['documentos'][0]['itens']);
        self::assertSame('I1', $parceiros[1]['documentos'][0]['itens'][0]['CodigoItem']);
        self::assertTrue($parceiros[0]['documentos'][0]['_excluido']);
    }

    public function testProjectDetailBuildsPartnerTreeAndHeader(): void
    {
        $docs = array_map(
            static fn (array $d): array => $d + [
                'DocEntry' => (int) $d['NumNF'],
                'NumDocumento' => (int) $d['NumNF'],
                'Fornecedor' => 'F100',
                'NomeFornecedor' => 'Fornecedor Teste',
            ],
            array_values(array_filter(
                $this->conferenceDocuments(),
                static fn (array $d): bool => (int) $d['Chave'] === 5
            ))
        );
        $itens = [
            [
                'Chave' => 5, 'DocEntry' => 60350, 'NumDocumento' => 60350, 'NumNF' => 60350,
                'CodigoItem' => 'X1', 'DescricaoItem' => 'Item', 'Quantidade' => 1, 'TotalLinha' => 10000.0,
                'ConsiderarNoTotal' => 'Sim',
            ],
        ];
        $api = new FakeSapProjectsApi($this->conferenceProjects(), $docs, $itens);
        $detail = (new SapProjectsService($api, new SapProjectsSql('')))->projectDetail(5);

        self::assertSame(2, $detail['projeto']['NumProjeto']);
        self::assertSame('Projeto 1', $detail['projeto']['NomeProjeto']);
        self::assertSame(1, $detail['totais']['QtdParceiros']);
        self::assertSame(6, $detail['totais']['QtdDocumentos']);
        self::assertSame(105975.81, $detail['totais']['ValorNoProjeto']);
        self::assertSame('F100', $detail['parceiros'][0]['Codigo']);
        self::assertNotEmpty($detail['parceiros'][0]['documentos']);
        self::assertNotEmpty($detail['resumo']);
        self::assertContains('ResumoDesatualizado', array_column($detail['resumo'], 'key'));
        self::assertContains('ValorVinculado', array_column($detail['resumo'], 'key'));
        self::assertNotEmpty($detail['identificacao']);
        self::assertSame(5, (int) $detail['projeto']['Chave']);
        self::assertContains('NomeProjeto', array_column($detail['identificacao'], 'key'));
    }

    public function testProjectDetailSummarySkipsDocumentsAndItems(): void
    {
        $api = new FakeSapProjectsApi($this->conferenceProjects(), $this->conferenceDocuments(), [
            ['Chave' => 5, 'DocEntry' => 1, 'CodigoItem' => 'X', 'LinhaNota' => 1],
        ]);
        $detail = (new SapProjectsService($api, new SapProjectsSql('')))->projectDetail(5, 'summary');

        self::assertTrue($detail['loaded']['summary']);
        self::assertFalse($detail['loaded']['documents']);
        self::assertFalse($detail['loaded']['items']);
        self::assertSame([], $detail['parceiros']);
        self::assertSame(0, $detail['documentos']['rows_count']);
        self::assertSame(0, $detail['itens']['rows_count']);
        self::assertNotEmpty($detail['resumo']);
        foreach ($api->queries as $sql) {
            self::assertStringNotContainsString('"LinhaVinculo"', $sql);
            self::assertStringNotContainsString('"CodigoItem"', $sql);
        }
    }

    public function testProjectDetailSplitsNfFromPurchaseOrders(): void
    {
        $projects = [[
            'Chave' => 5, 'NumProjeto' => 2, 'NomeProjeto' => 'Projeto 1',
            'TipoCod' => 'E', 'StatusCod' => 'S', 'ResumoFaturadoCP' => 100.0,
            'ResumoAbertoCP' => 50.0, 'ResumoTotalCP' => 150.0, 'ResumoDesvioCP' => 150.0,
            'Orcamento' => 0, 'ValorVinculado' => 999, 'ValorNFEntrada' => 999,
            'ValorOutrosVinculos' => 999, 'ValorPedidosCompra' => 999, 'ValorTotalNotas' => 999,
            'QtdDocumentos' => 0, 'ResumoDesatualizado' => 'Sim',
        ]];
        $docs = [
            [
                'Chave' => 5, 'ObjType' => 18, 'DocEntry' => 10, 'NumDocumento' => 10, 'NumNF' => 10,
                'LinhaVinculo' => 1, 'ValorNoProjeto' => 100.0, 'ValorTotalNota' => 110.0,
                'Cancelado' => 'N', 'ConsiderarNoTotal' => 'Sim',
                'Fornecedor' => 'F1', 'NomeFornecedor' => 'Forn',
            ],
            [
                'Chave' => 5, 'ObjType' => 18, 'DocEntry' => 10, 'NumDocumento' => 10, 'NumNF' => 10,
                'LinhaVinculo' => 2, 'ValorNoProjeto' => 0.0, 'ValorTotalNota' => 110.0,
                'Cancelado' => 'N', 'ConsiderarNoTotal' => 'Sim',
                'Fornecedor' => 'F1', 'NomeFornecedor' => 'Forn',
            ],
            [
                'Chave' => 5, 'ObjType' => 22, 'DocEntry' => 20, 'NumDocumento' => 20, 'NumNF' => null,
                'LinhaVinculo' => 3, 'ValorNoProjeto' => 50.0, 'ValorTotalNota' => 50.0,
                'Cancelado' => 'N', 'ConsiderarNoTotal' => 'Sim',
                'Fornecedor' => 'F1', 'NomeFornecedor' => 'Forn',
            ],
        ];
        $detail = (new SapProjectsService(
            new FakeSapProjectsApi($projects, $docs),
            new SapProjectsSql('')
        ))->projectDetail(5);

        $p = $detail['projeto'];
        self::assertSame(150.0, $p['ValorVinculado']);
        self::assertSame(100.0, $p['ValorNFEntrada']);
        self::assertSame(50.0, $p['ValorOutrosVinculos']);
        self::assertSame(50.0, $p['ValorPedidosCompra']);
        self::assertSame(110.0, $p['ValorTotalNotas']);
        self::assertSame('Não', $p['ResumoDesatualizado']);
        self::assertContains('ValorPedidosCompra', array_column($detail['resumo'], 'key'));
        self::assertContains('ValorOutrosVinculos', array_column($detail['resumo'], 'key'));
        $desvio = array_values(array_filter(
            $detail['resumo'],
            static fn (array $f): bool => $f['key'] === 'ResumoDesvioCP'
        ))[0] ?? null;
        self::assertNotNull($desvio);
        self::assertStringContainsString('orç', (string) $desvio['label']);
        // Orçamento 0 − Total C/P 150 => desvio negativo (estouro)
        self::assertSame(-150.0, $desvio['value']);
        self::assertSame('bad', $desvio['tone']);
    }

    public function testBudgetVarianceIsPositiveWhenUnderBudget(): void
    {
        $projects = [[
            'Chave' => 5, 'NumProjeto' => 2, 'NomeProjeto' => 'Projeto 1',
            'TipoCod' => 'E', 'StatusCod' => 'S',
            'Orcamento' => 250000.0, 'ResumoTotalCP' => 124958.33,
            'ResumoFaturadoCP' => 105975.81, 'ResumoAbertoCP' => 18982.52,
            'ResumoDesvioCP' => -125041.67, // valor bruto SAP (tot − orç); serviço inverte
            'ValorVinculado' => 124958.33, 'ValorNFEntrada' => 105975.81, 'ResumoDesatualizado' => 'Não',
        ]];
        $detail = (new SapProjectsService(
            new FakeSapProjectsApi($projects, []),
            new SapProjectsSql('')
        ))->projectDetail(5, 'summary');

        self::assertSame(125041.67, $detail['projeto']['ResumoDesvioCP']);
        self::assertSame('Não', $detail['projeto']['ResumoDesatualizado']);
        $desvio = array_values(array_filter(
            $detail['resumo'],
            static fn (array $f): bool => $f['key'] === 'ResumoDesvioCP'
        ))[0];
        self::assertSame(125041.67, $desvio['value']);
        self::assertSame('good', $desvio['tone']);
    }

    public function testResumoTotalRebuiltFromAbertoAndFaturadoWhenZero(): void
    {
        $projects = [[
            'Chave' => 4, 'NumProjeto' => 4, 'NomeProjeto' => 'XPTO01',
            'TipoCod' => 'E', 'StatusCod' => 'S',
            'Orcamento' => 300000.0,
            'ResumoAbertoCP' => 0.0,
            'ResumoFaturadoCP' => 58500.0,
            'ResumoTotalCP' => 0.0,
            'ValorNFEntrada' => 58500.0,
            'ValorVinculado' => 58500.0,
            'ResumoDesatualizado' => 'Sim',
        ]];
        $detail = (new SapProjectsService(
            new FakeSapProjectsApi($projects, []),
            new SapProjectsSql('')
        ))->projectDetail(4, 'summary');

        self::assertSame(58500.0, $detail['projeto']['ResumoTotalCP']);
        self::assertSame(241500.0, $detail['projeto']['ResumoDesvioCP']);
        self::assertSame('Não', $detail['projeto']['ResumoDesatualizado']);
    }

    /** @return list<array<string, mixed>> */
    private function periodoAno(array $dashboard): array
    {
        $byYear = [];
        foreach ($dashboard['por_periodo'] as $row) {
            $year = substr($row['Periodo'], -4);
            $byYear[$year] = ($byYear[$year] ?? 0.0) + $row['ValorNoProjeto'];
        }

        return array_map(
            static fn (string $y, float $v): array => ['Periodo' => $y, 'ValorNoProjeto' => round($v, 2)],
            array_keys($byYear),
            $byYear
        );
    }

    /**
     * @param list<array<string, mixed>> $projects
     * @param list<array<string, mixed>> $documents
     */
    private function service(array $projects, array $documents): SapProjectsService
    {
        return new SapProjectsService(new FakeSapProjectsApi($projects, $documents), new SapProjectsSql(''));
    }

    /** @return list<array<string, mixed>> */
    private function conferenceProjects(): array
    {
        $base = ['TipoCod' => 'E', 'StatusCod' => 'S', 'Status' => 'Iniciado', 'AnoInicio' => 2026,
            'DataInicio' => '2026-01-10', 'DiasAtraso' => 0, 'Orcamento' => 0];

        return [
            $base + ['Chave' => 2, 'NumProjeto' => 1, 'NomeProjeto' => 'PROJETO TESTE', 'QtdDocumentos' => 0,
                'ValorVinculado' => 0, 'ValorNFEntrada' => 0, 'ResumoFaturadoCP' => 0, 'ResumoDesatualizado' => 'Não'],
            $base + ['Chave' => 5, 'NumProjeto' => 2, 'NomeProjeto' => 'Projeto 1', 'QtdDocumentos' => 6,
                'ValorVinculado' => '105975.81', 'ValorNFEntrada' => 105975.81, 'ResumoFaturadoCP' => 0,
                'ResumoDesatualizado' => 'Sim'],
            $base + ['Chave' => 9, 'NumProjeto' => 3, 'NomeProjeto' => 'Projeto 2 Teste', 'QtdDocumentos' => 1,
                'ValorVinculado' => 10000, 'ValorNFEntrada' => 10000, 'ResumoFaturadoCP' => 0,
                'ResumoDesatualizado' => 'Sim'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function conferenceDocuments(): array
    {
        return [
            $this->doc(5, 13143, '2023-05-02', 50000.00, 'N'),
            $this->doc(5, 13150, '2023-08-20', 38425.81, 'N'),
            $this->doc(5, 60345, '2026-09-01', 1000.00, 'N'),
            $this->doc(5, 60348, '2026-10-28', 50.00, 'N'),
            $this->doc(5, 60350, '2026-10-05', 10000.00, 'N'),
            $this->doc(5, 60351, '2026-10-06', 6500.00, 'N'),
            $this->doc(9, 60354, '2026-10-10', 10000.00, 'N'),
        ];
    }

    /** @return array<string, mixed> */
    private function doc(int $project, int $nf, string $date, float $value, string $canceled): array
    {
        return [
            'Chave' => $project, 'ObjType' => 18, 'TipoDocumento' => 'Nota Fiscal de Entrada',
            'DocEntry' => $nf, 'NumDocumento' => $nf, 'NumNF' => $nf, 'LinhaVinculo' => $nf,
            'DataDocumento' => $date . ' 00:00:00.000', 'AnoDocumento' => (int) substr($date, 0, 4),
            'MesDocumento' => (int) substr($date, 5, 2), 'ValorNoProjeto' => $value, 'ValorTotalNota' => $value,
            'Cancelado' => $canceled, 'ConsiderarNoTotal' => in_array($canceled, ['Y', 'C'], true) ? 'Não' : 'Sim',
        ];
    }
}

/** Responde às consultas base e respeita LIMIT/OFFSET como a API SAP. */
final class FakeSapProjectsApi extends SapReportApiService
{
    /** @var list<string> */
    public array $queries = [];

    /**
     * @param list<array<string, mixed>> $projects
     * @param list<array<string, mixed>> $documents
     * @param list<array<string, mixed>> $items
     */
    public function __construct(
        private readonly array $projects,
        private readonly array $documents,
        private readonly array $items = [],
    ) {
    }

    public function execute(string $sql): array
    {
        $this->queries[] = $sql;
        if (str_contains($sql, '"CodigoItem"') || str_contains($sql, '"LinhaNota"')) {
            $rows = $this->items;
        } elseif (preg_match('/WHERE "DocEntry" IN \(([^)]+)\)/', $sql, $m)) {
            $ids = array_map('intval', explode(',', $m[1]));
            $rows = [];
            foreach ($this->documents as $d) {
                $entry = (int) ($d['DocEntry'] ?? $d['NumNF'] ?? 0);
                if (!in_array($entry, $ids, true)) {
                    continue;
                }
                $rows[] = [
                    'DocEntry' => $entry,
                    'DocNum' => $d['NumDocumento'] ?? $entry,
                    'Serial' => $d['NumNF'] ?? null,
                    'DocDate' => $d['DataDocumento'] ?? null,
                    'CardCode' => $d['Fornecedor'] ?? '',
                    'CardName' => $d['NomeFornecedor'] ?? '',
                    'DocStatus' => 'C',
                    'CANCELED' => $d['Cancelado'] ?? 'N',
                    'DocTotal' => $d['ValorTotalNota'] ?? $d['ValorNoProjeto'] ?? 0,
                ];
            }
        } elseif (str_contains($sql, '"LinhaVinculo"')) {
            $rows = $this->documents;
        } else {
            $rows = $this->projects;
        }
        if (preg_match('/T0\."AbsEntry"\s*=\s*(\d+)/', $sql, $m)) {
            $abs = (int) $m[1];
            $rows = array_values(array_filter(
                $rows,
                static fn (array $r): bool => (int) ($r['Chave'] ?? $r['AbsEntry'] ?? 0) === $abs
            ));
        }
        if (preg_match('/LIMIT (\d+) OFFSET (\d+)$/', $sql, $m)) {
            $rows = array_slice($rows, (int) $m[2], (int) $m[1]);
        }

        return ['data' => $rows, 'rows_count' => count($rows), 'execution_time' => 0.0, 'raw' => []];
    }
}
