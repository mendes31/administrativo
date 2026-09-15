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
                'usages_unclassified' => $this->usageRepo->countUnclassified(),
                'sync' => $this->formatSyncMeta($sync, $rowCount),
                'warning' => 'Cache vazio. Execute a sincronização (CLI --full ou botão Atualizar agora).',
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
        $topItens = $this->repo->fetchTopItens($from, $to, $dims, 'item_code');
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
                'valor_bonificacoes' => $valorBonif,
                'valor_brindes' => $valorBrindes,
                'qtd_bonificacoes' => $qtdBonif,
                'qtd_brindes' => $qtdBrindes,
                'itens_bonificados' => $itensBonif,
                'itens_brindes' => $itensBrindes,
                'desconto' => $desconto,
                'pct_desconto' => round($pctDesconto, 2),
                'pct_bonificacoes' => round($pctBonif, 2),
                'pct_brindes' => round($pctBrindes, 2),
            ],
            'series' => [
                'evolucao' => $evolucaoCompleta,
                'grupo_cliente' => $porGrupoCliente,
                'vendedores' => $porVendedor,
                'regiao' => $porRegiao,
            ],
            'top_clientes' => $topClientes,
            'top_itens' => $topItens,
            'usages_unclassified' => $unclassified,
            'sync' => $this->formatSyncMeta($sync, $rowCount),
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
            'valor_bonificacoes' => 0,
            'valor_brindes' => 0,
            'qtd_bonificacoes' => 0,
            'qtd_brindes' => 0,
            'itens_bonificados' => 0,
            'itens_brindes' => 0,
            'desconto' => 0,
            'pct_desconto' => 0,
            'pct_bonificacoes' => 0,
            'pct_brindes' => 0,
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
}
