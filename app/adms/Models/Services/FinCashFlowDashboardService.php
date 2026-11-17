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
        $horizonEnd = $today->add(new DateInterval('P' . $horizon . 'D'));
        $anchor = $today;
        if ($anchor < $monthStart) {
            $anchor = $monthStart;
        }
        if ($anchor > $monthEnd) {
            $anchor = $monthEnd;
        }
        $forecasts = $this->attachPosition(
            $this->prepareForecasts($this->cache->getForecasts(null, null, null, $branchId)),
            $anchor->format('Y-m-d'),
            $horizonEnd->format('Y-m-d')
        );
        $inHorizon = [];
        foreach ($forecasts as $fc) {
            if (($fc['position_date'] ?? null) !== null) {
                $inHorizon[] = $fc;
            }
        }
        $investments = $this->investments->getActiveUntil($yearEnd->format('Y-m-d'));

        $dailyByDate = $this->aggregateDailyByDate($dailyRows, $cashGl);
        $forecastByDate = $this->aggregateForecastByDate($inHorizon);
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
            $horizonEnd,
            $today,
            $anchor,
            $openingMonth,
            $dailyByDate,
            $forecastByDate,
            $cutoff,
            $scenario
        );

        $kpis = $this->buildKpis(
            $daily,
            $monthEnd,
            $openingMonth,
            $aplicacoes,
            $limits,
            $inHorizon,
            $today,
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
            $month,
            $anchor,
            $cutoff,
            $scenario
        );

        $accountsView = $this->buildAccountsView(
            $allAccounts,
            $openingDate,
            $openingMap,
            $dailyRows,
            $investments,
            $cutoff,
            $accountGl
        );

        return [
            'success' => true,
            'filters' => [
                'year' => $year,
                'month' => $month,
                'horizon' => $horizon,
                'scenario' => $scenario,
                'branch_id' => $branchId,
                'account' => $accountGl,
                'today' => $today->format('Y-m-d'),
                'month_start' => $monthStart->format('Y-m-d'),
                'month_end' => $monthEnd->format('Y-m-d'),
                'horizon_end' => $today->add(new DateInterval('P' . $horizon . 'D'))->format('Y-m-d'),
            ],
            'sync' => $this->cache->getSyncState(),
            'kpis' => $kpis,
            'daily' => $daily,
            'monthly' => $monthly,
            'accounts' => $accountsView,
            'forecast_documents' => $this->mapForecastDocuments($forecasts, $today->format('Y-m-d')),
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
            $year = (int) ($filters['year'] ?? substr($date, 0, 4));
            $month = (int) ($filters['month'] ?? substr($date, 5, 2));
            $horizon = (int) ($filters['horizon'] ?? 30);
            if (!in_array($horizon, [7, 15, 30, 60, 90], true)) {
                $horizon = 30;
            }
            $monthStart = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
            $monthEnd = $monthStart->modify('last day of this month');
            $todayDt = new DateTimeImmutable('today');
            $horizonEnd = $todayDt->add(new DateInterval('P' . $horizon . 'D'));
            $anchor = $todayDt;
            if ($anchor < $monthStart) {
                $anchor = $monthStart;
            }
            if ($anchor > $monthEnd) {
                $anchor = $monthEnd;
            }
            $rows = $this->attachPosition(
                $this->prepareForecasts($this->cache->getForecasts(null, null, $source, $branchId)),
                $anchor->format('Y-m-d'),
                $horizonEnd->format('Y-m-d')
            );
            $today = $todayDt->format('Y-m-d');
            $out = [];
            foreach ($rows as $row) {
                if ((string) ($row['position_date'] ?? '') !== $date) {
                    continue;
                }
                $open = (float) ($row['open_amount'] ?? 0);
                if (abs($open) < 0.005) {
                    continue;
                }
                $out[] = [
                    'document' => $this->formatForecastLabel($row),
                    'nf' => (string) ($row['nf_serial'] ?? ''),
                    'title' => (string) ($row['title_num'] ?? ''),
                    'partner' => $row['card_name'] ?? '',
                    'installment' => $row['installment_id'] ?? 1,
                    'due_date' => $row['due_date'] ?? $date,
                    'original' => (float) ($row['original_amount'] ?? 0),
                    'paid' => (float) ($row['paid_amount'] ?? 0),
                    'amount' => $open,
                    'status' => $this->forecastStatus((string) ($row['due_date'] ?? ''), $open, $today),
                    'origin' => (string) ($row['title_num'] ?? ($source === 'AR' ? 'A receber' : 'A pagar')),
                ];
            }
            return ['success' => true, 'kind' => $kind, 'date' => $date, 'rows' => $out];
        }

        $direction = $kind === 'despesa_ef' ? 'out' : 'in';
        $sync = new FinCashFlowSapSyncService();
        $accountGl = trim((string) ($filters['account'] ?? ''));
        $gl = $this->cashAccountsFor($accountGl);
        $rows = $sync->fetchEffectiveDocuments($date, $direction, $gl, $branchId);
        return ['success' => true, 'kind' => $kind, 'date' => $date, 'rows' => $rows];
    }

    /**
     * Contas de caixa/banco usadas no efetivo. O mesmo conjunto entra no saldo, no diário e no drill.
     *
     * @return list<string>
     */
    private function cashAccountsFor(string $accountGl): array
    {
        $cash = [];
        $fallback = [];
        foreach ($this->accounts->getIncludedInCashFlow() as $acc) {
            $gl = (string) $acc['sap_gl_account'];
            if ($accountGl !== '' && $gl !== $accountGl) {
                continue;
            }
            $fallback[] = $gl;
            $type = (string) ($acc['account_type'] ?? 'BANK');
            if ($type === 'INVESTMENT' || $type === 'TRANSIT') {
                continue;
            }
            $cash[] = $gl;
        }
        return $cash !== [] ? $cash : $fallback;
    }

    /**
     * Normaliza o vencimento uma vez, para KPI, diário, mensal, detalhes e drill.
     *
     * @param list<array<string, mixed>> $forecasts
     * @return list<array<string, mixed>>
     */
    private function prepareForecasts(array $forecasts): array
    {
        $out = [];
        foreach ($forecasts as $row) {
            $open = (float) ($row['open_amount'] ?? 0);
            if (abs($open) < 0.005) {
                continue;
            }
            $row['due_date'] = $this->normalizeForecastDueDate((string) ($row['due_date'] ?? ''));
            $row['open_amount'] = $open;
            $out[] = $row;
        }
        return $out;
    }

    /**
     * Cada título do horizonte entra num único dia.
     * Vencido antes do corte do período vai para esse corte; o restante fica no próprio vencimento.
     * Fora do horizonte, position_date fica nulo e o título não entra em KPI nem na grade.
     *
     * @param list<array<string, mixed>> $forecasts
     * @return list<array<string, mixed>>
     */
    private function attachPosition(array $forecasts, string $anchor, string $horizonEnd): array
    {
        foreach ($forecasts as &$row) {
            $due = (string) ($row['due_date'] ?? '');
            if ($due === '' || $due > $horizonEnd) {
                $row['position_date'] = null;
                continue;
            }
            $row['position_date'] = $due < $anchor ? $anchor : $due;
        }
        unset($row);
        return $forecasts;
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
     * Lista os títulos em aberto (A/R e A/P) para a aba Detalhes.
     *
     * @param list<array<string, mixed>> $forecasts
     * @return list<array<string, mixed>>
     */
    private function mapForecastDocuments(array $forecasts, string $today): array
    {
        $out = [];
        foreach ($forecasts as $fc) {
            $open = (float) ($fc['open_amount'] ?? 0);
            $due = $this->normalizeForecastDueDate((string) ($fc['due_date'] ?? ''));
            $status = $open < -0.005 ? 'credito' : $this->forecastStatus($due, $open, $today);
            if ($status === 'liquidado') {
                continue;
            }
            $out[] = [
                'due_date' => $due,
                'source_type' => (string) ($fc['source_type'] ?? ''),
                'doc_num' => (int) ($fc['doc_num'] ?? 0),
                'doc_entry' => (int) ($fc['doc_entry'] ?? 0),
                'nf_serial' => (string) ($fc['nf_serial'] ?? ''),
                'title_num' => (string) ($fc['title_num'] ?? ''),
                'card_code' => (string) ($fc['card_code'] ?? ''),
                'card_name' => (string) ($fc['card_name'] ?? ''),
                'installment' => (int) ($fc['installment_id'] ?? 1),
                'original_amount' => (float) ($fc['original_amount'] ?? 0),
                'paid_amount' => (float) ($fc['paid_amount'] ?? 0),
                'open_amount' => $open,
                'line_memo' => (string) ($fc['line_memo'] ?? ''),
                'sap_bpl_id' => (int) ($fc['sap_bpl_id'] ?? 0),
                'status' => $status,
            ];
        }
        usort($out, static function (array $a, array $b): int {
            $byDue = strcmp((string) $a['due_date'], (string) $b['due_date']);
            if ($byDue !== 0) {
                return $byDue;
            }
            $byType = strcmp((string) $a['source_type'], (string) $b['source_type']);
            if ($byType !== 0) {
                return $byType;
            }
            $byDoc = ((int) $a['doc_num']) <=> ((int) $b['doc_num']);
            if ($byDoc !== 0) {
                return $byDoc;
            }
            return ((int) $a['installment']) <=> ((int) $b['installment']);
        });
        return $out;
    }

    /**
     * Ano 00–99 gravado como 0022 vira 2022. O restante da data permanece.
     */
    private function normalizeForecastDueDate(string $due): string
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $due, $match) && (int) $match[1] < 100) {
            return sprintf('%04d-%s-%s', (int) $match[1] + 2000, $match[2], $match[3]);
        }
        return $due;
    }

    /**
     * Vencido só se ainda houver saldo aberto e a data de vencimento já passou.
     */
    private function forecastStatus(string $due, float $open, string $today): string
    {
        if (abs($open) < 0.005) {
            return 'liquidado';
        }
        if ($due !== '' && $due < $today) {
            return 'vencido';
        }
        if ($due === $today) {
            return 'hoje';
        }
        return 'prazo';
    }

    /**
     * @param array<string, mixed> $row
     */
    private function formatForecastLabel(array $row): string
    {
        $nf = trim((string) ($row['nf_serial'] ?? ''));
        $title = trim((string) ($row['title_num'] ?? ''));
        $doc = (string) ($row['doc_num'] ?? '');
        $parts = [];
        if ($nf !== '') {
            $parts[] = 'NF ' . $nf;
        }
        if ($title !== '') {
            $parts[] = 'Tít. ' . $title;
        }
        if ($parts === [] && $doc !== '' && $doc !== '0') {
            $parts[] = 'Doc ' . $doc;
        }
        return implode(' · ', $parts);
    }

    /**
     * @param list<array<string, mixed>> $forecasts
     * @return array<string, array{ar:float,ap:float}>
     */
    private function aggregateForecastByDate(array $forecasts): array
    {
        $out = [];
        foreach ($forecasts as $row) {
            $d = (string) ($row['position_date'] ?? '');
            if ($d === '') {
                continue;
            }
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
        DateTimeImmutable $horizonEnd,
        DateTimeImmutable $today,
        DateTimeImmutable $anchor,
        float $opening,
        array $dailyByDate,
        array $forecastByDate,
        DateTimeImmutable $cutoff,
        string $scenario
    ): array {
        $rows = [];
        $acumEf = $opening;
        $acumProj = $opening;
        $lastBank = $opening;
        $cursor = $monthStart;
        $calendarEnd = $horizonEnd > $monthEnd ? $horizonEnd : $monthEnd;
        $todayStr = $today->format('Y-m-d');
        $cutoffStr = $cutoff->format('Y-m-d');
        $anchorStr = $anchor->format('Y-m-d');
        $monthStartStr = $monthStart->format('Y-m-d');
        $monthEndStr = $monthEnd->format('Y-m-d');

        while ($cursor <= $calendarEnd) {
            $d = $cursor->format('Y-m-d');
            $mov = $dailyByDate[$d] ?? ['inflow' => 0.0, 'outflow' => 0.0, 'internal_in' => 0.0, 'internal_out' => 0.0];
            $fc = $forecastByDate[$d] ?? ['ar' => 0.0, 'ap' => 0.0];

            $receitaEf = max(0, $mov['inflow'] - $mov['internal_in']);
            $despesaEf = max(0, $mov['outflow'] - $mov['internal_out']);
            $saldoEf = $mov['inflow'] - $mov['outflow'];
            $inRealized = $d >= $monthStartStr && $d <= $cutoffStr;
            if ($inRealized) {
                $acumEf += $saldoEf;
                $lastBank = $acumEf;
            }

            $receitaPrev = $fc['ar'];
            $despesaPrev = $fc['ap'];
            $saldoPrev = $receitaPrev - $despesaPrev;

            if ($scenario === 'ef') {
                $acumProj = $lastBank;
            } elseif ($scenario === 'pv') {
                $acumProj += $saldoPrev;
            } elseif ($d < $anchorStr) {
                $acumProj = $lastBank;
            } elseif ($d === $anchorStr) {
                $acumProj = $lastBank + $saldoPrev;
            } else {
                $acumProj += $saldoPrev;
            }

            $rows[] = [
                'date' => $d,
                'day' => (int) $cursor->format('j'),
                'weekday' => (int) $cursor->format('N'),
                'outside_month' => $d < $monthStartStr || $d > $monthEndStr,
                'is_today' => $d === $todayStr,
                'is_effective' => $inRealized,
                'receita_ef' => $receitaEf,
                'despesa_ef' => $despesaEf,
                'saldo_ef' => $saldoEf,
                'acum_ef' => $inRealized ? $acumEf : null,
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
        DateTimeImmutable $monthEnd,
        float $opening,
        float $aplicacoes,
        float $limits,
        array $forecasts,
        DateTimeImmutable $today,
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
        $monthEndStr = $monthEnd->format('Y-m-d');

        foreach ($daily as $row) {
            $totRecEf += (float) $row['receita_ef'];
            $totDesEf += (float) $row['despesa_ef'];
            if (!empty($row['is_effective'])) {
                $acumEf = (float) $row['acum_ef'];
            }
            if ($row['date'] === $monthEndStr) {
                $projFim = (float) $row['acum_proj'];
            }
            if ((float) $row['acum_proj'] < $menor) {
                $menor = (float) $row['acum_proj'];
                $menorData = $row['date'];
            }
            if ($row['date'] === $todayStr) {
                $recHoje = (float) $row['receita_prev'];
                $pagHoje = (float) $row['despesa_prev'];
            }
        }

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
            if ($isAr) {
                $aReceber += $amt;
            } else {
                $aPagar += $amt;
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
        int $currentMonth,
        DateTimeImmutable $anchor,
        DateTimeImmutable $cutoff,
        string $scenario
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
            $rows['saldo_financeiro'][$m] += (float) $row['inflow'] - (float) $row['outflow'];
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

        $anchorMonth = (int) $anchor->format('n');
        $anchorYear = (int) $anchor->format('Y');
        $running = 0.0;
        for ($m = 1; $m <= 12; $m++) {
            $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $m));
            $end = $start->modify('last day of this month');
            $rows['saldo_inicial'][$m] = $this->openingAt($start, $openingDate, $openingMap, $dailyRows, $cashGl);
            $rows['necessidade'][$m] = $rows['receitas_previstas'][$m] - $rows['despesas_previstas'][$m];
            $sapInv = $this->openingAt($end->add(new DateInterval('P1D')), $openingDate, $openingMap, $dailyRows, $investGl);
            $localInv = $this->investmentBalanceUntil($investments, $end->format('Y-m-d'));
            $rows['aplicacoes'][$m] = $sapInv + $localInv;
            $bankEnd = $this->openingAt($start->modify('first day of next month'), $openingDate, $openingMap, $dailyRows, $cashGl);
            $netPrev = $scenario === 'ef' ? 0.0 : $rows['necessidade'][$m];
            $isPast = $year < $anchorYear || ($year === $anchorYear && $m < $anchorMonth);
            $isAnchor = $year === $anchorYear && $m === $anchorMonth;
            if ($scenario === 'pv') {
                $rows['saldo_acumulado'][$m] = $rows['saldo_inicial'][$m] + $netPrev;
                $running = $rows['saldo_acumulado'][$m];
            } elseif ($isPast) {
                $rows['saldo_acumulado'][$m] = $bankEnd;
                $running = $bankEnd;
            } elseif ($isAnchor) {
                $bankNow = $this->openingAt($cutoff->add(new DateInterval('P1D')), $openingDate, $openingMap, $dailyRows, $cashGl);
                $rows['saldo_acumulado'][$m] = $bankNow + $netPrev;
                $running = $rows['saldo_acumulado'][$m];
            } else {
                $running += $rows['saldo_financeiro'][$m] + $netPrev;
                $rows['saldo_acumulado'][$m] = $running;
            }
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
        DateTimeImmutable $cutoff,
        string $accountGl
    ): array {
        $tomorrow = $cutoff->add(new DateInterval('P1D'));
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
