<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\crm\CrmSalesFactRepository;
use App\adms\Models\Repository\crm\CrmSalesUsageNatureRepository;
use DateInterval;
use DateTimeImmutable;
use Exception;

/**
 * Dashboard de Vendas CRM — agregações a partir do cache MySQL.
 *
 * O HANA é consultado apenas pelo CrmSalesSapSyncService (CLI/cron/botão).
 */
class CrmSalesDashboardService
{
    /** Limite máximo de meses consultáveis (presets e personalizado). */
    public const MAX_MONTHS = 36;

    private CrmSalesFactRepository $repo;
    private CrmSalesUsageNatureRepository $usageRepo;

    public function __construct(?CrmSalesFactRepository $repo = null, ?CrmSalesUsageNatureRepository $usageRepo = null)
    {
        $this->repo = $repo ?? new CrmSalesFactRepository();
        $this->usageRepo = $usageRepo ?? new CrmSalesUsageNatureRepository();
    }

    /**
     * @param array{
     *   periodo?: int|string,
     *   date_from?: string,
     *   date_to?: string,
     *   vendedor?: string|list<string>|null,
     *   grupo_cliente?: string|list<string>|null,
     *   regiao?: string|list<string>|null,
     *   grupo_item?: string|list<string>|null,
     *   ano_mes?: string|list<string>|null,
     *   card_code?: string|list<string>|null,
     *   item_code?: string|list<string>|null
     * } $filters
     */
    public function getDashboardData(array $filters): array
    {
        if (!$this->repo->tableExists()) {
            throw new Exception(
                'Cache de vendas CRM não instalado. Execute a migration e a sincronização SAP.'
            );
        }

        $range = $this->resolveDateRange($filters);
        $dims = [
            'vendedor' => $this->toList($filters['vendedor'] ?? null),
            'grupo_cliente' => $this->toList($filters['grupo_cliente'] ?? null),
            'regiao' => $this->toList($filters['regiao'] ?? null),
            'grupo_item' => $this->toList($filters['grupo_item'] ?? null),
            'ano_mes' => $this->toList($filters['ano_mes'] ?? null),
            'card_code' => $this->toList($filters['card_code'] ?? null),
            'item_code' => $this->toList($filters['item_code'] ?? null),
        ];

        $from = $range['from']->format('Y-m-d');
        $to = $range['to']->format('Y-m-d');
        $rowCount = $this->repo->countRows();
        $sync = $this->repo->getSyncState();

        if ($rowCount === 0) {
            $meses = $this->listAnoMesInRange($range);
            return [
                'success' => true,
                'source' => 'mysql_cache',
                'cache_empty' => true,
                'periodo' => [
                    'chave' => $range['chave'],
                    'inicio' => $range['from']->format('Y-m'),
                    'fim' => $range['to']->format('Y-m'),
                    'date_from' => $from,
                    'date_to' => $to,
                    'meses' => $meses,
                ],
                'filtros_ativos' => array_filter($dims, static fn ($v) => $v !== null && $v !== []),
                'filtros_opcoes' => [
                    'vendedores' => [],
                    'grupos_cliente' => [],
                    'regioes' => [],
                ],
                'kpis' => $this->emptyKpis(),
                'series' => [
                    'evolucao' => $this->fillMissingMonths([], $meses),
                    'grupo_cliente' => [],
                    'vendedores' => [],
                    'regiao' => [],
                ],
                'top_clientes' => [],
                'top_itens' => [],
                'top_itens_bonificacao' => [],
                'top_itens_brinde' => [],
                'usages_unclassified' => $this->usageRepo->countUnclassified(),
                'sync' => $this->formatSyncMeta($sync, $rowCount),
                'warning' => 'Cache vazio. Na primeira carga use o comando --full no servidor; depois o botão Sync incremental.',
            ];
        }

        $kpisRow = $this->repo->fetchKpis($from, $to, $dims);
        $bruto = (float) ($kpisRow['bruto'] ?? 0);
        $devolucoes = (float) ($kpisRow['devolucoes'] ?? 0);
        $liquido = (float) ($kpisRow['liquido'] ?? 0);
        $clientes = (int) ($kpisRow['clientes_ativos'] ?? 0);
        $qtdDev = (int) ($kpisRow['qtd_devolucoes'] ?? 0);
        $taxa = $bruto > 0 ? ($devolucoes / $bruto * 100) : 0.0;
        $ticket = $clientes > 0 ? ($liquido / $clientes) : 0.0;
        $itensVendidos = (float) ($kpisRow['itens_vendidos'] ?? 0);
        $itensFaturados = (float) ($kpisRow['itens_faturados'] ?? 0);
        $itensDevolvidos = (float) ($kpisRow['itens_devolvidos'] ?? 0);
        $valorBonif = (float) ($kpisRow['valor_bonificacoes'] ?? 0);
        $valorBrindes = (float) ($kpisRow['valor_brindes'] ?? 0);
        $qtdBonif = (int) ($kpisRow['qtd_bonificacoes'] ?? 0);
        $qtdBrindes = (int) ($kpisRow['qtd_brindes'] ?? 0);
        $itensBonif = (float) ($kpisRow['itens_bonificados'] ?? 0);
        $itensBrindes = (float) ($kpisRow['itens_brindes'] ?? 0);
        $desconto = (float) ($kpisRow['desconto'] ?? 0);
        $brutoVenda = (float) ($kpisRow['valor_bruto_venda'] ?? 0);
        $pctDesconto = $brutoVenda > 0 ? ($desconto / $brutoVenda * 100) : 0.0;
        $pctBonif = $liquido > 0 ? ($valorBonif / $liquido * 100) : 0.0;
        $pctBrindes = $liquido > 0 ? ($valorBrindes / $liquido * 100) : 0.0;
        $unclassified = $this->usageRepo->countUnclassified();
        $warning = $unclassified > 0
            ? $unclassified . ' utilização(ões) SAP ainda não classificada(s); até classificar, entram como venda. Use CRM → Utilizações de venda SAP.'
            : null;

        $evolucao = $this->repo->fetchGroupSum($from, $to, $dims, 'ano_mes', 'ano_mes', true);
        $porGrupoCliente = $this->repo->fetchGroupSum($from, $to, $dims, 'grupo_cliente', 'grupo_cliente');
        $porVendedor = $this->repo->fetchGroupSum($from, $to, $dims, 'vendedor', 'vendedor');
        $porRegiao = $this->repo->fetchGroupSum($from, $to, $dims, 'regiao', 'regiao');
        $topClientes = $this->repo->fetchTopClientes($from, $to, $dims, 'card_code');
        $topItens = $this->repo->fetchTopItens($from, $to, $dims, 'item_code', 'venda');
        $topItensBonif = $this->repo->fetchTopItens($from, $to, $dims, 'item_code', 'bonificacao');
        $topItensBrinde = $this->repo->fetchTopItens($from, $to, $dims, 'item_code', 'brinde');
        $soldByCode = [];
        foreach ($topItens as $row) {
            $soldByCode[(string) ($row['item_code'] ?? '')] = (float) ($row['quantidade'] ?? 0);
        }
        $topItensBonif = $this->withSoldShare($topItensBonif, $soldByCode);
        $topItensBrinde = $this->withSoldShare($topItensBrinde, $soldByCode);
        $opcoes = $this->repo->fetchFilterOptions($from, $to);

        $meses = $this->listAnoMesInRange($range);
        $evolucaoCompleta = $this->fillMissingMonths($evolucao, $meses);

        return [
            'success' => true,
            'source' => 'mysql_cache',
            'cache_empty' => false,
            'periodo' => [
                'chave' => $range['chave'],
                'inicio' => $range['from']->format('Y-m'),
                'fim' => $range['to']->format('Y-m'),
                'date_from' => $from,
                'date_to' => $to,
                'meses' => $meses,
            ],
            'filtros_ativos' => array_filter($dims, static fn ($v) => $v !== null && $v !== []),
            'filtros_opcoes' => $opcoes,
            'kpis' => [
                'faturamento_liquido' => $liquido,
                'devolucoes' => $devolucoes,
                'taxa_devolucao' => round($taxa, 2),
                'ticket_medio' => $ticket,
                'clientes_ativos' => $clientes,
                'qtd_devolucoes' => $qtdDev,
                'faturamento_bruto' => $bruto,
                'itens_vendidos' => $itensVendidos,
                'itens_faturados' => $itensFaturados,
                'itens_devolvidos' => $itensDevolvidos,
                'valor_bonificacoes' => $valorBonif,
                'valor_brindes' => $valorBrindes,
                'qtd_bonificacoes' => $qtdBonif,
                'qtd_brindes' => $qtdBrindes,
                'itens_bonificados' => $itensBonif,
                'itens_brindes' => $itensBrindes,
                'desconto' => $desconto,
                'valor_bruto_venda' => $brutoVenda,
                'pct_desconto' => round($pctDesconto, 2),
                'pct_bonificacoes' => round($pctBonif, 2),
                'pct_brindes' => round($pctBrindes, 2),
            ] + $this->yoyLiquido($range, $dims, $liquido),
            'series' => [
                'evolucao' => $evolucaoCompleta,
                'grupo_cliente' => $porGrupoCliente,
                'vendedores' => $porVendedor,
                'regiao' => $porRegiao,
            ],
            'top_clientes' => $topClientes,
            'top_itens' => $topItens,
            'top_itens_bonificacao' => $topItensBonif,
            'top_itens_brinde' => $topItensBrinde,
            'usages_unclassified' => $unclassified,
            'sync' => $this->formatSyncMeta($sync, $rowCount),
            'warning' => $warning,
        ];
    }

    /**
     * Listagem de notas (sem parcelas) para o drill-down do dashboard.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getInvoicesData(array $filters, int $page = 1, int $perPage = 200): array
    {
        if (!$this->repo->tableExists()) {
            throw new Exception(
                'Cache de vendas CRM não instalado. Execute a migration e a sincronização SAP.'
            );
        }

        $range = $this->resolveDateRange($filters);
        $dims = [
            'vendedor' => $this->toList($filters['vendedor'] ?? null),
            'grupo_cliente' => $this->toList($filters['grupo_cliente'] ?? null),
            'regiao' => $this->toList($filters['regiao'] ?? null),
            'grupo_item' => $this->toList($filters['grupo_item'] ?? null),
            'ano_mes' => $this->toList($filters['ano_mes'] ?? null),
            'card_code' => $this->toList($filters['card_code'] ?? null),
            'item_code' => $this->toList($filters['item_code'] ?? null),
        ];

        $origem = trim((string) ($filters['origem'] ?? ''));
        $natureza = $this->repo->normalizeNatureza($filters['natureza'] ?? 'venda');
        $hasDimDrill = ($dims['vendedor'] ?? []) !== []
            || ($dims['card_code'] ?? []) !== []
            || ($dims['item_code'] ?? []) !== [];
        $hasNatureDrill = in_array($natureza, ['bonificacao', 'brinde'], true);
        $hasDrill = $hasDimDrill || $hasNatureDrill;

        $from = $range['from']->format('Y-m-d');
        $to = $range['to']->format('Y-m-d');
        $perPage = max(1, min(500, $perPage));
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $hasDoc = $this->repo->hasDocNumColumns();
        $result = $hasDoc && $hasDrill
            ? $this->repo->fetchInvoices($from, $to, $dims, $perPage, $offset, $natureza)
            : [
                'rows' => [],
                'total_rows' => 0,
                'total_valor' => 0.0,
                'total_quantidade' => 0.0,
                'qtd_venda' => 0,
                'qtd_devolucao' => 0,
                'qtd_nfs' => 0,
            ];

        $totalRows = (int) $result['total_rows'];
        $pages = $perPage > 0 ? (int) max(1, (int) ceil($totalRows / $perPage)) : 1;
        if ($page > $pages) {
            $page = $pages;
        }

        $warning = null;
        if (!$hasDoc) {
            $warning = 'O cache ainda não tem número de nota. Rode php scripts/sync_crm_sales_sap.php --full após a migration.';
        } elseif (!$hasDrill) {
            $warning = 'Abra esta tela com duplo clique em um vendedor, cliente, item ou nos cards de Bonificação/Brindes no Dashboard de Vendas SAP.';
        } elseif ($this->repo->countRows() === 0) {
            $warning = 'Cache vazio. Na primeira carga use o comando --full no servidor.';
        }

        $titulo = 'Notas fiscais';
        if ($natureza === 'bonificacao' && !$hasDimDrill) {
            $titulo = 'Notas de bonificação';
        } elseif ($natureza === 'brinde' && !$hasDimDrill) {
            $titulo = 'Notas de brinde';
        } elseif ($origem === 'vendedor' && !empty($dims['vendedor'])) {
            $titulo = 'Notas de ' . implode(', ', $dims['vendedor']);
        } elseif ($origem === 'card_code' && !empty($dims['card_code'])) {
            $titulo = 'Notas do cliente';
        } elseif ($origem === 'item_code' && !empty($dims['item_code'])) {
            $titulo = $natureza === 'bonificacao'
                ? 'Bonificação do item'
                : ($natureza === 'brinde' ? 'Brinde do item' : 'Notas do item');
        } elseif ($natureza === 'bonificacao') {
            $titulo = 'Notas de bonificação';
        } elseif ($natureza === 'brinde') {
            $titulo = 'Notas de brinde';
        }

        return [
            'success' => true,
            'titulo' => $titulo,
            'origem' => $origem,
            'natureza' => $natureza,
            'escopo_item' => !empty($dims['item_code']),
            'has_doc_num' => $hasDoc,
            'has_drill' => $hasDrill,
            'periodo' => [
                'chave' => $range['chave'],
                'date_from' => $from,
                'date_to' => $to,
            ],
            'filtros' => array_filter($dims, static fn ($v) => $v !== null && $v !== []),
            'rows' => $result['rows'],
            'total_rows' => $totalRows,
            'total_valor' => (float) $result['total_valor'],
            'total_quantidade' => (float) $result['total_quantidade'],
            'qtd_venda' => (int) ($result['qtd_venda'] ?? 0),
            'qtd_devolucao' => (int) ($result['qtd_devolucao'] ?? 0),
            'qtd_nfs' => (int) ($result['qtd_nfs'] ?? 0),
            'page' => $page,
            'per_page' => $perPage,
            'pages' => $pages,
            'warning' => $warning,
        ];
    }

    /**
     * @return array<string, int|float>
     */
    private function emptyKpis(): array
    {
        return [
            'faturamento_liquido' => 0,
            'devolucoes' => 0,
            'taxa_devolucao' => 0,
            'ticket_medio' => 0,
            'clientes_ativos' => 0,
            'qtd_devolucoes' => 0,
            'faturamento_bruto' => 0,
            'itens_vendidos' => 0,
            'itens_faturados' => 0,
            'itens_devolvidos' => 0,
            'valor_bonificacoes' => 0,
            'valor_brindes' => 0,
            'qtd_bonificacoes' => 0,
            'qtd_brindes' => 0,
            'itens_bonificados' => 0,
            'itens_brindes' => 0,
            'desconto' => 0,
            'valor_bruto_venda' => 0,
            'pct_desconto' => 0,
            'pct_bonificacoes' => 0,
            'pct_brindes' => 0,
            'liquido_ano_anterior' => 0,
            'yoy_pct' => null,
            'yoy_disponivel' => false,
        ];
    }

    /**
     * Carteira: Pareto ABC, novos/recorrentes e concentração (sem custo).
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getCarteiraData(array $filters): array
    {
        $base = $this->analyticsBase($filters);
        if ($base['cache_empty']) {
            return $base + [
                'clientes' => [],
                'abc' => $this->emptyAbcResumo(),
                'novos' => 0,
                'recorrentes' => 0,
                'valor_novos' => 0.0,
                'valor_recorrentes' => 0.0,
                'top10_share' => 0.0,
                'top10_valor' => 0.0,
                'clientes_ativos' => 0,
                'faturamento_liquido' => 0.0,
            ];
        }

        $from = $base['from'];
        $to = $base['to'];
        $dims = $base['dims'];
        $rows = $this->withAbc($this->repo->fetchTopClientes($from, $to, $dims));
        $first = $this->repo->fetchFirstSaleDates($dims);
        $novos = 0;
        $recorrentes = 0;
        $valorNovos = 0.0;
        $valorRec = 0.0;
        $total = 0.0;
        foreach ($rows as &$row) {
            $code = (string) ($row['card_code'] ?? '');
            $primeira = (string) ($first[$code] ?? '');
            $novo = $primeira !== '' && $primeira >= $from && $primeira <= $to;
            $row['primeira'] = $primeira;
            $row['tipo_carteira'] = $novo ? 'novo' : 'recorrente';
            $liq = (float) ($row['liquido'] ?? 0);
            $total += $liq > 0 ? $liq : 0;
            if ($novo) {
                $novos++;
                $valorNovos += $liq;
            } else {
                $recorrentes++;
                $valorRec += $liq;
            }
        }
        unset($row);

        $top10Valor = 0.0;
        foreach (array_slice($rows, 0, 10) as $row) {
            $v = (float) ($row['liquido'] ?? 0);
            if ($v > 0) {
                $top10Valor += $v;
            }
        }

        return $base + [
            'clientes' => $rows,
            'abc' => $this->summarizeAbc($rows),
            'novos' => $novos,
            'recorrentes' => $recorrentes,
            'valor_novos' => $valorNovos,
            'valor_recorrentes' => $valorRec,
            'top10_share' => $total > 0 ? round($top10Valor / $total * 100, 1) : 0.0,
            'top10_valor' => $top10Valor,
            'clientes_ativos' => count($rows),
            'faturamento_liquido' => $total,
        ];
    }

    /**
     * Força de vendas: scorecard por vendedor (sem custo/margem).
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getVendedoresData(array $filters): array
    {
        $base = $this->analyticsBase($filters);
        if ($base['cache_empty']) {
            return $base + ['vendedores' => [], 'faturamento_liquido' => 0.0];
        }

        $rows = $this->repo->fetchSellerScorecard($base['from'], $base['to'], $base['dims']);
        $total = 0.0;
        foreach ($rows as &$row) {
            $liq = (float) ($row['liquido'] ?? 0);
            $fat = (float) ($row['faturas'] ?? 0);
            $dev = (float) ($row['devolucao'] ?? 0);
            $desc = (float) ($row['desconto'] ?? 0);
            $bruto = (float) ($row['valor_bruto'] ?? 0);
            $cli = (int) ($row['clientes'] ?? 0);
            $total += $liq;
            $row['pct_desconto'] = $bruto > 0 ? round($desc / $bruto * 100, 2) : 0.0;
            $row['taxa_devolucao'] = $fat > 0 ? round($dev / $fat * 100, 2) : 0.0;
            $row['ticket'] = $cli > 0 ? ($liq / $cli) : 0.0;
        }
        unset($row);
        foreach ($rows as &$row) {
            $liq = (float) ($row['liquido'] ?? 0);
            $row['pct_share'] = $total > 0 ? round($liq / $total * 100, 2) : 0.0;
        }
        unset($row);

        return $base + [
            'vendedores' => $rows,
            'faturamento_liquido' => $total,
        ];
    }

    /**
     * Produto: ABC de SKU, desconto/devolução e mix 104/106 (sem custo).
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getProdutosData(array $filters): array
    {
        $base = $this->analyticsBase($filters);
        if ($base['cache_empty']) {
            return $base + [
                'itens' => [],
                'abc' => $this->emptyAbcResumo(),
                'mix' => [],
                'faturamento_liquido' => 0.0,
            ];
        }

        $from = $base['from'];
        $to = $base['to'];
        $dims = $base['dims'];
        $itens = $this->withAbc($this->repo->fetchTopItens($from, $to, $dims, null, 'venda'));
        $total = 0.0;
        foreach ($itens as &$row) {
            $liq = (float) ($row['liquido'] ?? 0);
            $dev = (float) ($row['devolucao'] ?? 0);
            $desc = (float) ($row['desconto'] ?? 0);
            $bruto = (float) ($row['valor_bruto'] ?? 0);
            $fat = $liq + $dev;
            $total += $liq > 0 ? $liq : 0;
            $row['pct_desconto'] = $bruto > 0 ? round($desc / $bruto * 100, 2) : 0.0;
            $row['taxa_devolucao'] = $fat > 0 ? round($dev / $fat * 100, 2) : 0.0;
        }
        unset($row);

        return $base + [
            'itens' => $itens,
            'abc' => $this->summarizeAbc($itens),
            'mix' => $this->repo->fetchGroupSum($from, $to, $dims, 'grupo_item', 'grupo_item'),
            'faturamento_liquido' => $total,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function analyticsBase(array $filters): array
    {
        if (!$this->repo->tableExists()) {
            throw new Exception(
                'Cache de vendas CRM não instalado. Execute a migration e a sincronização SAP.'
            );
        }

        $range = $this->resolveDateRange($filters);
        $dims = [
            'vendedor' => $this->toList($filters['vendedor'] ?? null),
            'grupo_cliente' => $this->toList($filters['grupo_cliente'] ?? null),
            'regiao' => $this->toList($filters['regiao'] ?? null),
            'grupo_item' => $this->toList($filters['grupo_item'] ?? null),
            'ano_mes' => $this->toList($filters['ano_mes'] ?? null),
            'card_code' => $this->toList($filters['card_code'] ?? null),
            'item_code' => $this->toList($filters['item_code'] ?? null),
        ];
        $from = $range['from']->format('Y-m-d');
        $to = $range['to']->format('Y-m-d');
        $rowCount = $this->repo->countRows();
        $empty = $rowCount === 0;
        $opcoes = $empty ? ['vendedores' => [], 'grupos_cliente' => [], 'regioes' => []]
            : $this->repo->fetchFilterOptions($from, $to);

        return [
            'success' => true,
            'cache_empty' => $empty,
            'periodo' => [
                'chave' => $range['chave'],
                'inicio' => $range['from']->format('Y-m'),
                'fim' => $range['to']->format('Y-m'),
                'date_from' => $from,
                'date_to' => $to,
            ],
            'filtros' => array_filter($dims, static fn ($v) => $v !== null && $v !== []),
            'filtros_opcoes' => $opcoes,
            'from' => $from,
            'to' => $to,
            'dims' => $dims,
            'warning' => $empty
                ? 'Cache vazio. Na primeira carga use o comando --full no servidor.'
                : null,
        ];
    }

    /**
     * @param array{from: DateTimeImmutable, to: DateTimeImmutable} $range
     * @param array<string, string|list<string>|null> $dims
     * @return array{liquido_ano_anterior: float, yoy_pct: float|null, yoy_disponivel: bool}
     */
    private function yoyLiquido(array $range, array $dims, float $liquido): array
    {
        $fromAnt = $range['from']->modify('-1 year');
        $toAnt = $range['to']->modify('-1 year');
        $row = $this->repo->fetchKpis(
            $fromAnt->format('Y-m-d'),
            $toAnt->format('Y-m-d'),
            $this->dimsShiftedYear($dims)
        );
        $ant = (float) ($row['liquido'] ?? 0);
        $disponivel = abs($ant) >= 0.01;
        return [
            'liquido_ano_anterior' => $ant,
            'yoy_pct' => $disponivel ? round(($liquido - $ant) / $ant * 100, 1) : null,
            'yoy_disponivel' => $disponivel,
        ];
    }

    /**
     * @param array<string, string|list<string>|null> $dims
     * @return array<string, string|list<string>|null>
     */
    private function dimsShiftedYear(array $dims): array
    {
        $meses = $dims['ano_mes'] ?? null;
        if (!is_array($meses) || $meses === []) {
            return $dims;
        }
        $shifted = [];
        foreach ($meses as $m) {
            $dt = DateTimeImmutable::createFromFormat('!Y-m', (string) $m);
            $shifted[] = $dt ? $dt->modify('-1 year')->format('Y-m') : (string) $m;
        }
        $dims['ano_mes'] = $shifted;
        return $dims;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function withAbc(array $rows, string $valueKey = 'liquido'): array
    {
        $total = 0.0;
        foreach ($rows as $row) {
            $v = (float) ($row[$valueKey] ?? 0);
            if ($v > 0) {
                $total += $v;
            }
        }
        $cum = 0.0;
        foreach ($rows as &$row) {
            $v = (float) ($row[$valueKey] ?? 0);
            if ($v > 0 && $total > 0) {
                $cum += $v;
                $pctAcum = $cum / $total * 100;
                $classe = $pctAcum <= 80 ? 'A' : ($pctAcum <= 95 ? 'B' : 'C');
            } else {
                $pctAcum = $total > 0 ? 100.0 : 0.0;
                $classe = 'C';
            }
            $row['pct_share'] = $total > 0 && $v > 0 ? round($v / $total * 100, 2) : 0.0;
            $row['pct_acum'] = round($pctAcum, 2);
            $row['classe_abc'] = $classe;
        }
        unset($row);
        return $rows;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array<string, array{qtd: int, valor: float, share: float}>
     */
    private function summarizeAbc(array $rows, string $valueKey = 'liquido'): array
    {
        $resumo = $this->emptyAbcResumo();
        $total = 0.0;
        foreach ($rows as $row) {
            $v = (float) ($row[$valueKey] ?? 0);
            if ($v > 0) {
                $total += $v;
            }
            $classe = (string) ($row['classe_abc'] ?? 'C');
            if (!isset($resumo[$classe])) {
                $classe = 'C';
            }
            $resumo[$classe]['qtd']++;
            $resumo[$classe]['valor'] += $v;
        }
        foreach ($resumo as &$item) {
            $item['share'] = $total > 0 ? round($item['valor'] / $total * 100, 1) : 0.0;
        }
        unset($item);
        return $resumo;
    }

    /**
     * @return array<string, array{qtd: int, valor: float, share: float}>
     */
    private function emptyAbcResumo(): array
    {
        return [
            'A' => ['qtd' => 0, 'valor' => 0.0, 'share' => 0.0],
            'B' => ['qtd' => 0, 'valor' => 0.0, 'share' => 0.0],
            'C' => ['qtd' => 0, 'valor' => 0.0, 'share' => 0.0],
        ];
    }

    /**
     * @param array<string, mixed>|null $sync
     * @return array<string, mixed>
     */
    private function formatSyncMeta(?array $sync, int $rowCount): array
    {
        $dates = $this->repo->minMaxDates();
        return [
            'last_success_at' => $sync['last_success_at'] ?? null,
            'last_mode' => $sync['last_mode'] ?? null,
            'last_status' => $sync['last_status'] ?? null,
            'last_source' => $sync['last_source'] ?? null,
            'last_from_date' => $sync['last_from_date'] ?? null,
            'last_to_date' => $sync['last_to_date'] ?? null,
            'rows_cached' => $rowCount,
            'cache_from' => $dates['min'],
            'cache_to' => $dates['max'],
            'has_doc_num' => $this->repo->hasDocNumColumns(),
            'message' => $sync['message'] ?? null,
        ];
    }

    /**
     * Resolve intervalo obrigatório.
     *
     * @return array{from: DateTimeImmutable, to: DateTimeImmutable, chave: string}
     */
    public function resolveDateRange(array $filters): array
    {
        $fromRaw = trim((string) ($filters['date_from'] ?? ''));
        $toRaw = trim((string) ($filters['date_to'] ?? ''));
        $periodoRaw = trim((string) ($filters['periodo'] ?? '12'));
        $chave = $periodoRaw !== '' ? $periodoRaw : '12';

        $hoje = new DateTimeImmutable('today');
        $from = null;
        $to = null;

        $usaCustom = ($fromRaw !== '' && $toRaw !== '') || $chave === 'personalizado';

        if ($usaCustom) {
            if ($fromRaw === '' || $toRaw === '') {
                throw new Exception('Informe data início e data fim para o período personalizado.');
            }
            $from = $this->parseDateYmd($fromRaw, 'Data início');
            $to = $this->parseDateYmd($toRaw, 'Data fim');
            $chave = 'personalizado';
        } else {
            switch ($chave) {
                case 'mes_atual':
                    $from = new DateTimeImmutable('first day of this month');
                    $to = new DateTimeImmutable('last day of this month');
                    break;
                case 'mes_anterior':
                    $from = new DateTimeImmutable('first day of last month');
                    $to = new DateTimeImmutable('last day of last month');
                    break;
                case 'ano_atual':
                    $from = new DateTimeImmutable($hoje->format('Y') . '-01-01');
                    $to = $hoje;
                    break;
                case 'ano_anterior':
                    $anoAnt = (int) $hoje->format('Y') - 1;
                    $from = new DateTimeImmutable($anoAnt . '-01-01');
                    $to = new DateTimeImmutable($anoAnt . '-12-31');
                    break;
                case '3':
                case '6':
                case '12':
                case '24':
                case '36':
                    $n = (int) $chave;
                    $to = new DateTimeImmutable('last day of this month');
                    $from = (new DateTimeImmutable('first day of this month'))
                        ->sub(new DateInterval('P' . ($n - 1) . 'M'));
                    break;
                default:
                    $chave = '12';
                    $to = new DateTimeImmutable('last day of this month');
                    $from = (new DateTimeImmutable('first day of this month'))
                        ->sub(new DateInterval('P11M'));
                    break;
            }
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $maxFrom = $to
            ->sub(new DateInterval('P' . self::MAX_MONTHS . 'M'))
            ->modify('first day of this month');
        if ($from < $maxFrom) {
            $from = $maxFrom;
        }

        return ['from' => $from, 'to' => $to, 'chave' => $chave];
    }

    private function parseDateYmd(string $value, string $label): DateTimeImmutable
    {
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();
        $hasErrors = is_array($errors)
            && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0);

        if (!$dt || $hasErrors || $dt->format('Y-m-d') !== $value) {
            throw new Exception($label . ' inválida. Use o formato AAAA-MM-DD.');
        }

        return $dt;
    }

    /**
     * @param array{from: DateTimeImmutable, to: DateTimeImmutable} $range
     * @return list<string>
     */
    private function listAnoMesInRange(array $range): array
    {
        $meses = [];
        $cursor = $range['from']->modify('first day of this month');
        $end = $range['to']->modify('first day of this month');
        while ($cursor <= $end) {
            $meses[] = $cursor->format('Y-m');
            $cursor = $cursor->add(new DateInterval('P1M'));
        }
        return $meses;
    }

    /**
     * @param list<array{label: string, valor: float}> $series
     * @param list<string> $meses
     * @return list<array{label: string, valor: float}>
     */
    private function fillMissingMonths(array $series, array $meses): array
    {
        $map = [];
        foreach ($series as $row) {
            $map[$row['label']] = $row['valor'];
        }
        $out = [];
        foreach ($meses as $m) {
            $out[] = ['label' => $m, 'valor' => (float) ($map[$m] ?? 0)];
        }
        return $out;
    }

    /**
     * @return list<string>|null
     */
    private function toList(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_array($value)) {
            $value = [$value];
        }
        $out = [];
        foreach ($value as $item) {
            if ($item === null || is_array($item)) {
                continue;
            }
            $s = trim((string) $item);
            if ($s !== '') {
                $out[$s] = $s;
            }
        }
        $list = array_values($out);
        return $list === [] ? null : $list;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param array<string, float> $soldByCode
     * @return list<array<string, mixed>>
     */
    private function withSoldShare(array $rows, array $soldByCode): array
    {
        foreach ($rows as &$row) {
            $code = (string) ($row['item_code'] ?? '');
            $sold = (float) ($soldByCode[$code] ?? 0);
            $qty = (float) ($row['quantidade'] ?? 0);
            $row['qtd_venda_sku'] = $sold;
            $row['pct_qtd_vs_venda'] = $sold > 0 ? round($qty / $sold * 100, 1) : null;
        }
        unset($row);
        return $rows;
    }
}
