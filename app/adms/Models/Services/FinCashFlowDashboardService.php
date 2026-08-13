<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\cashFlow\FinCashAccountRepository;
use App\adms\Models\Repository\cashFlow\FinCashFlowCacheRepository;
use App\adms\Models\Repository\cashFlow\FinCashInvestmentRepository;
use DateInterval;
use DateTimeImmutable;

/**
 * Consolida o cache MySQL + lançamentos locais de aplicações para o dashboard.
 */
class FinCashFlowDashboardService
{
    private FinCashAccountRepository $accounts;
    private FinCashFlowCacheRepository $cache;
    private FinCashInvestmentRepository $investments;

    public function __construct(
        ?FinCashAccountRepository $accounts = null,
        ?FinCashFlowCacheRepository $cache = null,
        ?FinCashInvestmentRepository $investments = null
    ) {
        $this->accounts = $accounts ?? new FinCashAccountRepository();
        $this->cache = $cache ?? new FinCashFlowCacheRepository();
        $this->investments = $investments ?? new FinCashInvestmentRepository();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getDashboardData(array $filters): array
    {
        $year = (int) ($filters['year'] ?? date('Y'));
        $month = (int) ($filters['month'] ?? date('n'));
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        $horizon = (int) ($filters['horizon'] ?? 30);
        if (!in_array($horizon, [7, 15, 30, 60, 90], true)) {
            $horizon = 30;
        }
        $scenario = (string) ($filters['scenario'] ?? 'both');
        $branchId = isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== null
            ? (int) $filters['branch_id']
            : null;
        $accountGl = trim((string) ($filters['account'] ?? ''));

        $monthStart = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $monthEnd = $monthStart->modify('last day of this month');
        $today = new DateTimeImmutable('today');
        $cutoff = $today < $monthEnd ? $today : $monthEnd;

        $allAccounts = $this->accounts->getIncludedInCashFlow();
        $glFilter = [];
        foreach ($allAccounts as $acc) {
            $gl = (string) $acc['sap_gl_account'];
            if ($accountGl !== '' && $gl !== $accountGl) {
                continue;
            }
            $glFilter[] = $gl;
        }

        $cashGl = [];
        $investGl = [];
        foreach ($allAccounts as $acc) {
            $gl = (string) $acc['sap_gl_account'];
            if ($accountGl !== '' && $gl !== $accountGl) {
                continue;
            }
            $type = (string) ($acc['account_type'] ?? 'BANK');
            if ($type === 'INVESTMENT') {
                $investGl[] = $gl;
            } elseif (in_array($type, ['BANK', 'CASH'], true)) {
                $cashGl[] = $gl;
            } elseif ($type !== 'TRANSIT') {
                $cashGl[] = $gl;
            }
        }
        if ($cashGl === [] && $glFilter !== []) {
            $cashGl = $glFilter;
        }

        $openingDate = $this->cache->latestOpeningDate();
        $openingMap = $openingDate ? $this->cache->getOpeningMap($openingDate) : [];
        $historyFrom = $openingDate ?: $monthStart->format('Y-m-d');
        $yearEnd = new DateTimeImmutable(sprintf('%04d-12-31', $year));
        $historyTo = $yearEnd->format('Y-m-d');

        $dailyRows = $this->cache->getDaily($historyFrom, $historyTo, $glFilter, $branchId);
        $forecasts = $this->cache->getForecasts(null, null, null, $branchId);
        $investments = $this->investments->getActiveUntil($yearEnd->format('Y-m-d'));

        $dailyByDate = $this->aggregateDailyByDate($dailyRows, $cashGl);
        $forecastByDate = $this->aggregateForecastByDate($forecasts);
        $investByMonth = $this->aggregateInvestmentsByMonth($investments, $year);

        $openingMonth = $this->openingAt($monthStart, $openingDate, $openingMap, $dailyRows, $cashGl);
        $aplicacoes = $this->openingAt(
            $cutoff->add(new DateInterval('P1D')),
            $openingDate,
            $openingMap,
            $dailyRows,
            $investGl
        ) + $this->investmentBalanceUntil($investments, $cutoff->format('Y-m-d'));

        $limits = 0.0;
        foreach ($allAccounts as $acc) {
            if ($accountGl !== '' && (string) $acc['sap_gl_account'] !== $accountGl) {
                continue;
            }
            $limits += (float) ($acc['credit_limit'] ?? 0);
        }

        $daily = $this->buildDailyCalendar(
            $monthStart,
            $monthEnd,
            $today,
            $openingMonth,
            $dailyByDate,
            $forecastByDate,
            $cutoff
        );

        $kpis = $this->buildKpis(
            $daily,
            $openingMonth,
            $aplicacoes,
            $limits,
            $forecasts,
            $today,
            $horizon,
            $cutoff
        );

        $monthly = $this->buildMonthly(
            $year,
            $openingDate,
            $openingMap,
            $dailyRows,
            $forecastByDate,
            $investments,
            $cashGl,
            $investGl,
            $limits,
            $month
        );

        $accountsView = $this->buildAccountsView(
            $allAccounts,
            $openingDate,
            $openingMap,
            $dailyRows,
            $investments,
            $today,
            $accountGl
        );

        $forecastPeriod = [];
        foreach ($forecasts as $fc) {
            $due = (string) ($fc['due_date'] ?? '');
            if ($due < $monthStart->format('Y-m-d') || $due > $monthEnd->format('Y-m-d')) {
                continue;
            }
            $forecastPeriod[] = [
                'due_date' => $due,
                'source_type' => $fc['source_type'] ?? '',
                'document' => 'NF ' . ($fc['doc_num'] ?? ''),
                'partner' => $fc['card_name'] ?? '',
                'installment' => $fc['installment_id'] ?? 1,
                'open_amount' => (float) ($fc['open_amount'] ?? 0),
            ];
        }

        return [
            'success' => true,
            'filters' => [
                'year' => $year,
                'month' => $month,
                'horizon' => $horizon,
                'scenario' => $scenario,
                'branch_id' => $branchId,
                'account' => $accountGl,
            ],
            'sync' => $this->cache->getSyncState(),
            'kpis' => $kpis,
            'daily' => $daily,
            'monthly' => $monthly,
            'accounts' => $accountsView,
            'forecast_period' => $forecastPeriod,
            'investments_monthly' => $this->buildInvestmentTables($investByMonth, $year),
            'account_options' => array_map(static function (array $a): array {
                return [
                    'gl' => $a['sap_gl_account'],
                    'label' => ($a['description'] ?: $a['sap_gl_account']) . ' (' . $a['sap_gl_account'] . ')',
                    'type' => $a['account_type'],
                ];
            }, $allAccounts),
            'branches' => $this->cache->distinctBranches(),
            'empty_cache' => $this->cache->countDailyRows() === 0,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getDrillDown(array $filters): array
    {
        $date = trim((string) ($filters['date'] ?? ''));
        $kind = trim((string) ($filters['kind'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return ['success' => false, 'error' => 'Data inválida.', 'rows' => []];
        }

        $branchId = isset($filters['branch_id']) && $filters['branch_id'] !== ''
            ? (int) $filters['branch_id']
            : null;

        if (in_array($kind, ['receita_prev', 'despesa_prev'], true)) {
            $source = $kind === 'receita_prev' ? 'AR' : 'AP';
            $rows = $this->cache->getForecasts($date, $date, $source, $branchId);
            $out = [];
            foreach ($rows as $row) {
                $out[] = [
                    'document' => 'NF ' . ($row['doc_num'] ?? ''),
                    'partner' => $row['card_name'] ?? '',
                    'installment' => $row['installment_id'] ?? 1,
                    'due_date' => $row['due_date'] ?? $date,
                    'original' => (float) ($row['original_amount'] ?? 0),
                    'paid' => (float) ($row['paid_amount'] ?? 0),
                    'amount' => (float) ($row['open_amount'] ?? 0),
                    'status' => 'Aberto',
                    'origin' => $source === 'AR' ? 'OINV/INV6' : 'OPCH/PCH6',
                ];
            }
            return ['success' => true, 'kind' => $kind, 'date' => $date, 'rows' => $out];
        }

        $direction = $kind === 'despesa_ef' ? 'out' : 'in';
        $sync = new FinCashFlowSapSyncService();
        $accountGl = trim((string) ($filters['account'] ?? ''));
        $gl = $accountGl !== '' ? [$accountGl] : [];
        $rows = $sync->fetchEffectiveDocuments($date, $direction, $gl);
        return ['success' => true, 'kind' => $kind, 'date' => $date, 'rows' => $rows];
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param list<string> $cashGl
     * @return array<string, array{inflow:float,outflow:float,internal_in:float,internal_out:float}>
     */
    private function aggregateDailyByDate(array $rows, array $cashGl): array
    {
        $allow = array_flip($cashGl);
        $out = [];
        foreach ($rows as $row) {
            $gl = (string) $row['sap_gl_account'];
            if ($allow !== [] && !isset($allow[$gl])) {
                continue;
            }
            $d = (string) $row['movement_date'];
            if (!isset($out[$d])) {
                $out[$d] = ['inflow' => 0.0, 'outflow' => 0.0, 'internal_in' => 0.0, 'internal_out' => 0.0];
            }
            $out[$d]['inflow'] += (float) $row['inflow'];
            $out[$d]['outflow'] += (float) $row['outflow'];
            $out[$d]['internal_in'] += (float) $row['internal_in'];
            $out[$d]['internal_out'] += (float) $row['internal_out'];
        }
        return $out;
    }

    /**
     * @param list<array<string, mixed>> $forecasts
     * @return array<string, array{ar:float,ap:float}>
     */
    private function aggregateForecastByDate(array $forecasts): array
    {
        $out = [];
        foreach ($forecasts as $row) {
            $d = (string) $row['due_date'];
            if (!isset($out[$d])) {
                $out[$d] = ['ar' => 0.0, 'ap' => 0.0];
            }
            $amt = (float) $row['open_amount'];
            if (($row['source_type'] ?? '') === 'AR') {
                $out[$d]['ar'] += $amt;
            } else {
                $out[$d]['ap'] += $amt;
            }
        }
        return $out;
    }

    /**
     * @param list<array<string, mixed>> $investments
     * @return array<string, list<array<string, mixed>>>
     */
    private function aggregateInvestmentsByMonth(array $investments, int $year): array
    {
        $banks = [];
        foreach ($investments as $row) {
            $cat = (string) ($row['category'] ?? 'STANDARD');
            $label = (string) $row['bank_label'];
            $key = $cat . '|' . $label;
            if (!isset($banks[$key])) {
                $banks[$key] = [
                    'label' => $label,
                    'category' => $cat,
                    'running' => 0.0,
                    'months' => array_fill(1, 12, 0.0),
                ];
            }
            $signed = $this->signedInvestment((string) $row['movement_type'], (float) $row['amount']);
            $banks[$key]['running'] += $signed;
            $dt = new DateTimeImmutable((string) $row['movement_date']);
            $m = (int) $dt->format('n');
            $y = (int) $dt->format('Y');
            // Saldo ao fim do mês = acumulado até aquele mês (preenchido depois)
            $banks[$key]['_events'][] = ['y' => $y, 'm' => $m, 'v' => $signed];
        }

        $out = ['STANDARD' => [], 'GUARANTEE' => []];
        foreach ($banks as $bank) {
            $yearFilter = $year;
            $running = 0.0;
            $months = array_fill(1, 12, 0.0);
            $events = $bank['_events'] ?? [];
            usort($events, static fn ($a, $b) => [$a['y'], $a['m']] <=> [$b['y'], $b['m']]);
            foreach ($events as $ev) {
                $running += $ev['v'];
                if ($ev['y'] === $yearFilter) {
                    for ($m = $ev['m']; $m <= 12; $m++) {
                        $months[$m] = $running;
                    }
                } elseif ($ev['y'] < $yearFilter) {
                    for ($m = 1; $m <= 12; $m++) {
                        $months[$m] = $running;
                    }
                }
            }
            $cat = $bank['category'];
            if (!isset($out[$cat])) {
                $out[$cat] = [];
            }
            $out[$cat][] = [
                'label' => $bank['label'],
                'category' => $cat,
                'months' => $months,
                'total' => $running,
            ];
        }
        return $out;
    }

    /**
     * @param list<array<string, mixed>> $dailyRows
     * @param list<string> $glAccounts
     */
    private function openingAt(
        DateTimeImmutable $asOf,
        ?string $openingDate,
        array $openingMap,
        array $dailyRows,
        array $glAccounts
    ): float {
        $allow = array_flip($glAccounts);
        $sum = 0.0;
        foreach ($openingMap as $gl => $bal) {
            if ($allow !== [] && !isset($allow[$gl])) {
                continue;
            }
            $sum += (float) $bal;
        }
        $asOfStr = $asOf->format('Y-m-d');
        foreach ($dailyRows as $row) {
            $gl = (string) $row['sap_gl_account'];
            if ($allow !== [] && !isset($allow[$gl])) {
                continue;
            }
            if ((string) $row['movement_date'] >= $asOfStr) {
                continue;
            }
            if ($openingDate && (string) $row['movement_date'] < $openingDate) {
                continue;
            }
            $sum += ((float) $row['inflow'] - (float) $row['outflow']);
        }
        return $sum;
    }

    /**
     * @param array<string, array{inflow:float,outflow:float,internal_in:float,internal_out:float}> $dailyByDate
     * @param array<string, array{ar:float,ap:float}> $forecastByDate
     * @return list<array<string, mixed>>
     */
    private function buildDailyCalendar(
        DateTimeImmutable $monthStart,
        DateTimeImmutable $monthEnd,
        DateTimeImmutable $today,
        float $opening,
        array $dailyByDate,
        array $forecastByDate,
        DateTimeImmutable $cutoff
    ): array {
        $rows = [];
        $acumEf = $opening;
        $acumProj = $opening;
        $cursor = $monthStart;
        $todayStr = $today->format('Y-m-d');
        $cutoffStr = $cutoff->format('Y-m-d');

        while ($cursor <= $monthEnd) {
            $d = $cursor->format('Y-m-d');
            $mov = $dailyByDate[$d] ?? ['inflow' => 0.0, 'outflow' => 0.0, 'internal_in' => 0.0, 'internal_out' => 0.0];
            $fc = $forecastByDate[$d] ?? ['ar' => 0.0, 'ap' => 0.0];

            $receitaEf = max(0, $mov['inflow'] - $mov['internal_in']);
            $despesaEf = max(0, $mov['outflow'] - $mov['internal_out']);
            $saldoEf = $receitaEf - $despesaEf;
            $isPastOrToday = $d <= $cutoffStr;
            if ($isPastOrToday) {
                $acumEf += $saldoEf;
            }

            $receitaPrev = $fc['ar'];
            $despesaPrev = $fc['ap'];
            $saldoPrev = $receitaPrev - $despesaPrev;

            if ($isPastOrToday) {
                $acumProj = $acumEf;
            } else {
                $acumProj += $saldoPrev;
            }

            $rows[] = [
                'date' => $d,
                'day' => (int) $cursor->format('j'),
                'weekday' => (int) $cursor->format('N'),
                'is_today' => $d === $todayStr,
                'is_effective' => $isPastOrToday,
                'receita_ef' => $receitaEf,
                'despesa_ef' => $despesaEf,
                'saldo_ef' => $saldoEf,
                'acum_ef' => $isPastOrToday ? $acumEf : null,
                'receita_prev' => $receitaPrev,
                'despesa_prev' => $despesaPrev,
                'saldo_prev' => $saldoPrev,
                'acum_proj' => $acumProj,
            ];
            $cursor = $cursor->add(new DateInterval('P1D'));
        }
        return $rows;
    }

    /**
     * @param list<array<string, mixed>> $daily
     * @param list<array<string, mixed>> $forecasts
     * @return array<string, mixed>
     */
    private function buildKpis(
        array $daily,
        float $opening,
        float $aplicacoes,
        float $limits,
        array $forecasts,
        DateTimeImmutable $today,
        int $horizon,
        DateTimeImmutable $cutoff
    ): array {
        $totRecEf = 0.0;
        $totDesEf = 0.0;
        $acumEf = $opening;
        $menor = $opening;
        $menorData = $daily[0]['date'] ?? $today->format('Y-m-d');
        $projFim = $opening;
        $recHoje = 0.0;
        $pagHoje = 0.0;
        $todayStr = $today->format('Y-m-d');

        foreach ($daily as $row) {
            $totRecEf += (float) $row['receita_ef'];
            $totDesEf += (float) $row['despesa_ef'];
            if (!empty($row['is_effective'])) {
                $acumEf = (float) $row['acum_ef'];
            }
            $projFim = (float) $row['acum_proj'];
            if ((float) $row['acum_proj'] < $menor) {
                $menor = (float) $row['acum_proj'];
                $menorData = $row['date'];
            }
            if ($row['date'] === $todayStr) {
                $recHoje = (float) $row['receita_prev'];
                $pagHoje = (float) $row['despesa_prev'];
            }
        }

        $horizonEnd = $today->add(new DateInterval('P' . $horizon . 'D'));
        $aReceber = 0.0;
        $aPagar = 0.0;
        $vencRec = 0.0;
        $vencPag = 0.0;
        foreach ($forecasts as $fc) {
            $due = (string) $fc['due_date'];
            $amt = (float) $fc['open_amount'];
            $isAr = ($fc['source_type'] ?? '') === 'AR';
            if ($due < $todayStr) {
                if ($isAr) {
                    $vencRec += $amt;
                } else {
                    $vencPag += $amt;
                }
            }
            if ($due <= $horizonEnd->format('Y-m-d')) {
                if ($isAr) {
                    $aReceber += $amt;
                } else {
                    $aPagar += $amt;
                }
            }
        }

        $dispPropria = $acumEf + $aplicacoes;
        return [
            'saldo_inicial' => $opening,
            'acumulado_efetivo' => $acumEf,
            'projecao_fim' => $projFim,
            'aplicacoes' => $aplicacoes,
            'limites' => $limits,
            'a_receber' => $aReceber,
            'a_pagar' => $aPagar,
            'disponibilidade_propria' => $dispPropria,
            'disponibilidade_ampliada' => $dispPropria + $limits,
            'necessidade_caixa' => $aReceber - $aPagar,
            'menor_saldo_projetado' => $menor,
            'menor_saldo_data' => $menorData,
            'a_receber_hoje' => $recHoje,
            'a_pagar_hoje' => $pagHoje,
            'vencidos_receber' => $vencRec,
            'vencidos_pagar' => $vencPag,
            'receitas_efetivas' => $totRecEf,
            'despesas_efetivas' => $totDesEf,
            'data_corte' => $cutoff->format('Y-m-d'),
        ];
    }

    /**
     * @param list<array<string, mixed>> $dailyRows
     * @param array<string, array{ar:float,ap:float}> $forecastByDate
     * @param list<array<string, mixed>> $investments
     * @param list<string> $cashGl
     * @param list<string> $investGl
     * @return array<string, mixed>
     */
    private function buildMonthly(
        int $year,
        ?string $openingDate,
        array $openingMap,
        array $dailyRows,
        array $forecastByDate,
        array $investments,
        array $cashGl,
        array $investGl,
        float $limits,
        int $currentMonth
    ): array {
        $rows = [
            'saldo_inicial' => array_fill(1, 12, 0.0),
            'limites' => array_fill(1, 12, $limits),
            'aplicacoes' => array_fill(1, 12, 0.0),
            'receitas_efetivas' => array_fill(1, 12, 0.0),
            'despesas_efetivas' => array_fill(1, 12, 0.0),
            'saldo_financeiro' => array_fill(1, 12, 0.0),
            'receitas_previstas' => array_fill(1, 12, 0.0),
            'despesas_previstas' => array_fill(1, 12, 0.0),
            'necessidade' => array_fill(1, 12, 0.0),
            'saldo_acumulado' => array_fill(1, 12, 0.0),
        ];

        $allowCash = array_flip($cashGl);
        foreach ($dailyRows as $row) {
            $gl = (string) $row['sap_gl_account'];
            if ($allowCash !== [] && !isset($allowCash[$gl])) {
                continue;
            }
            $dt = new DateTimeImmutable((string) $row['movement_date']);
            if ((int) $dt->format('Y') !== $year) {
                continue;
            }
            $m = (int) $dt->format('n');
            $in = max(0, (float) $row['inflow'] - (float) $row['internal_in']);
            $out = max(0, (float) $row['outflow'] - (float) $row['internal_out']);
            $rows['receitas_efetivas'][$m] += $in;
            $rows['despesas_efetivas'][$m] += $out;
        }

        foreach ($forecastByDate as $date => $fc) {
            $dt = new DateTimeImmutable($date);
            if ((int) $dt->format('Y') !== $year) {
                continue;
            }
            $m = (int) $dt->format('n');
            $rows['receitas_previstas'][$m] += $fc['ar'];
            $rows['despesas_previstas'][$m] += $fc['ap'];
        }

        for ($m = 1; $m <= 12; $m++) {
            $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $m));
            $end = $start->modify('last day of this month');
            $rows['saldo_inicial'][$m] = $this->openingAt($start, $openingDate, $openingMap, $dailyRows, $cashGl);
            $rows['saldo_financeiro'][$m] = $rows['receitas_efetivas'][$m] - $rows['despesas_efetivas'][$m];
            $rows['necessidade'][$m] = $rows['receitas_previstas'][$m] - $rows['despesas_previstas'][$m];
            $sapInv = $this->openingAt($end->add(new DateInterval('P1D')), $openingDate, $openingMap, $dailyRows, $investGl);
            $localInv = $this->investmentBalanceUntil($investments, $end->format('Y-m-d'));
            $rows['aplicacoes'][$m] = $sapInv + $localInv;
            $rows['saldo_acumulado'][$m] = $rows['saldo_inicial'][$m] + $rows['saldo_financeiro'][$m];
        }

        return [
            'year' => $year,
            'current_month' => $currentMonth,
            'rows' => $rows,
        ];
    }

    /**
     * @param list<array<string, mixed>> $allAccounts
     * @param list<array<string, mixed>> $dailyRows
     * @param list<array<string, mixed>> $investments
     * @return list<array<string, mixed>>
     */
    private function buildAccountsView(
        array $allAccounts,
        ?string $openingDate,
        array $openingMap,
        array $dailyRows,
        array $investments,
        DateTimeImmutable $today,
        string $accountGl
    ): array {
        $tomorrow = $today->add(new DateInterval('P1D'));
        $out = [];
        foreach ($allAccounts as $acc) {
            $gl = (string) $acc['sap_gl_account'];
            if ($accountGl !== '' && $gl !== $accountGl) {
                continue;
            }
            $saldo = $this->openingAt($tomorrow, $openingDate, $openingMap, $dailyRows, [$gl]);
            $aplic = 0.0;
            if (($acc['account_type'] ?? '') === 'INVESTMENT') {
                $aplic = $saldo;
                $saldo = 0.0;
            }
            $limite = (float) ($acc['credit_limit'] ?? 0);
            $out[] = [
                'gl' => $gl,
                'bank' => $acc['description'] ?: $gl,
                'type' => $acc['account_type'],
                'saldo' => $saldo,
                'aplicacao' => $aplic,
                'limite' => $limite,
                'disponibilidade' => $saldo + $aplic + $limite,
            ];
        }
        return $out;
    }

    /**
     * @param array<string, list<array<string, mixed>>> $investByMonth
     * @return array<string, mixed>
     */
    private function buildInvestmentTables(array $investByMonth, int $year): array
    {
        $tables = [];
        foreach (['STANDARD' => 'Aplicações', 'GUARANTEE' => 'Aplicações (garantia)'] as $cat => $title) {
            $banks = $investByMonth[$cat] ?? [];
            $totals = array_fill(1, 12, 0.0);
            foreach ($banks as $bank) {
                for ($m = 1; $m <= 12; $m++) {
                    $totals[$m] += (float) ($bank['months'][$m] ?? 0);
                }
            }
            $monthRef = ((int) date('Y') === $year) ? (int) date('n') : 12;
            $tables[] = [
                'category' => $cat,
                'title' => $title,
                'banks' => $banks,
                'saldo' => $totals,
                'grand_total' => $totals[$monthRef] ?? 0.0,
            ];
        }
        return ['year' => $year, 'tables' => $tables];
    }

    /**
     * @param list<array<string, mixed>> $investments
     */
    private function investmentBalanceUntil(array $investments, string $until): float
    {
        $sum = 0.0;
        foreach ($investments as $row) {
            if ((string) $row['movement_date'] > $until) {
                continue;
            }
            $sum += $this->signedInvestment((string) $row['movement_type'], (float) $row['amount']);
        }
        return $sum;
    }

    private function signedInvestment(string $type, float $amount): float
    {
        $amount = abs($amount);
        return match ($type) {
            'REDEMPTION' => -$amount,
            default => $amount,
        };
    }
}
