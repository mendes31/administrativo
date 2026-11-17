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
        [$rangeStart, $rangeEnd] = $this->resolveRange($filters);
        $includeOverdue = $this->includeOverdue($filters);
        $year = (int) $rangeStart->format('Y');
        $month = (int) $rangeStart->format('n');
        $scenario = (string) ($filters['scenario'] ?? 'both');
        $includeOrders = !empty($filters['include_orders']) && (string) $filters['include_orders'] !== '0';
        $branchId = isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== null
            ? (int) $filters['branch_id']
            : null;
        $accountGl = trim((string) ($filters['account'] ?? ''));

        $monthStart = $rangeStart;
        $monthEnd = $rangeEnd;
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

        $historyFrom = $this->cache->earliestOpeningDate() ?: $monthStart->format('Y-m-d');
        $yearEnd = new DateTimeImmutable(sprintf('%04d-12-31', $year));
        $historyTo = ($rangeEnd > $yearEnd ? $rangeEnd : $yearEnd)->format('Y-m-d');

        $dailyRows = $this->cache->getDaily($historyFrom, $historyTo, $glFilter, $branchId);
        $horizonEnd = $rangeEnd;
        $anchor = $today;
        if ($anchor < $monthStart) {
            $anchor = $monthStart;
        }
        if ($anchor > $monthEnd) {
            $anchor = $monthEnd;
        }
        $forecasts = $this->attachPosition(
            $this->prepareForecasts($this->cache->getForecasts(null, null, null, $branchId)),
            $monthStart->format('Y-m-d'),
            $horizonEnd->format('Y-m-d'),
            $includeOverdue
        );
        $inHorizon = [];
        foreach ($forecasts as $fc) {
            if (!empty($fc['in_horizon'])) {
                $inHorizon[] = $fc;
            }
        }
        $investments = $this->investments->getActiveUntil($historyTo);

        $dailyByDate = $this->aggregateDailyByDate($dailyRows, $cashGl);
        $forecastByDate = $this->aggregateForecastByDate($inHorizon);
        $investByMonth = $this->aggregateInvestmentsByMonth($investments, $year);

        $openingMonth = $this->openingAt($monthStart, $dailyRows, $cashGl);
        $aplicacoes = $this->openingAt(
            $cutoff->add(new DateInterval('P1D')),
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

        $orderPack = $this->loadOpenOrders(
            $includeOrders,
            $branchId,
            $monthStart->format('Y-m-d'),
            $horizonEnd->format('Y-m-d')
        );

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
            $scenario,
            $orderPack['by_date']
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
                'date_from' => $rangeStart->format('Y-m-d'),
                'date_to' => $rangeEnd->format('Y-m-d'),
                'include_overdue' => $includeOverdue,
                'scenario' => $scenario,
                'branch_id' => $branchId,
                'account' => $accountGl,
                'include_orders' => $includeOrders,
                'today' => $today->format('Y-m-d'),
                'month_start' => $monthStart->format('Y-m-d'),
                'month_end' => $monthEnd->format('Y-m-d'),
                'horizon_end' => $rangeEnd->format('Y-m-d'),
            ],
            'sync' => $this->cache->getSyncState(),
            'kpis' => $kpis,
            'daily' => $daily,
            'weekly' => $this->buildWeekly($daily),
            'aging' => $this->buildAging($forecasts, $today->format('Y-m-d')),
            'orders' => $orderPack['summary'],
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
            [$rangeStart, $rangeEnd] = $this->resolveRange($filters);
            $todayDt = new DateTimeImmutable('today');
            $rows = $this->attachPosition(
                $this->prepareForecasts($this->cache->getForecasts(null, null, $source, $branchId)),
                $rangeStart->format('Y-m-d'),
                $rangeEnd->format('Y-m-d'),
                $this->includeOverdue($filters)
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
     * O título fica no próprio vencimento. Estoque vencido antes do mês não entra na grade,
     * para não parecer uma saída única no dia de corte. O KPI continua contando esse estoque.
     *
     * @param list<array<string, mixed>> $forecasts
     * @return list<array<string, mixed>>
     */
    /**
     * @param array<string, mixed> $filters
     * @return array{0:DateTimeImmutable,1:DateTimeImmutable}
     */
    private function resolveRange(array $filters): array
    {
        $today = new DateTimeImmutable('today');
        $from = $this->parseIsoDate((string) ($filters['date_from'] ?? ''));
        $to = $this->parseIsoDate((string) ($filters['date_to'] ?? ''));
        if ($from === null || $to === null) {
            $year = (int) ($filters['year'] ?? $today->format('Y'));
            $month = (int) ($filters['month'] ?? $today->format('n'));
            if ($month < 1 || $month > 12) {
                $month = (int) $today->format('n');
            }
            $from = $from ?? new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
            if ($to === null) {
                $horizon = (int) ($filters['horizon'] ?? 30);
                if (!in_array($horizon, [7, 15, 30, 60, 90], true)) {
                    $horizon = 30;
                }
                $to = $today->add(new DateInterval('P' . $horizon . 'D'));
            }
        }
        if ($to < $from) {
            [$from, $to] = [$to, $from];
        }
        if ($from->diff($to)->days > 366) {
            $to = $from->modify('+366 days');
        }
        return [$from, $to];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function includeOverdue(array $filters): bool
    {
        if (!isset($filters['include_overdue'])) {
            return true;
        }
        return !in_array((string) $filters['include_overdue'], ['0', 'false'], true);
    }

    private function parseIsoDate(string $value): ?DateTimeImmutable
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $dt instanceof DateTimeImmutable ? $dt : null;
    }

    private function attachPosition(array $forecasts, string $rangeStart, string $rangeEnd, bool $includeOverdue): array
    {
        foreach ($forecasts as &$row) {
            $due = (string) ($row['due_date'] ?? '');
            $inRange = $due !== '' && $due >= $rangeStart && $due <= $rangeEnd;
            $row['in_horizon'] = $due !== '' && $due <= $rangeEnd && ($includeOverdue || $due >= $rangeStart);
            $row['position_date'] = $inRange ? $due : null;
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
        array $dailyRows,
        array $glAccounts
    ): float {
        $snap = $this->cache->openingOnOrBefore($asOf->format('Y-m-d'));
        $openingDate = $snap['date'];
        $openingMap = $snap['map'];
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
        string $scenario,
        array $orderByDate = []
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
            $ord = $orderByDate[$d] ?? ['ar' => 0.0, 'ap' => 0.0];

            $receitaEf = max(0, $mov['inflow'] - $mov['internal_in']);
            $despesaEf = max(0, $mov['outflow'] - $mov['internal_out']);
            $saldoEf = $mov['inflow'] - $mov['outflow'];
            $inRealized = $d >= $monthStartStr && $d <= $cutoffStr;
            if ($inRealized) {
                $acumEf += $saldoEf;
                $lastBank = $acumEf;
            }

            $receitaPrev = $fc['ar'] + $ord['ar'];
            $despesaPrev = $fc['ap'] + $ord['ap'];
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
                'pedido_receber' => $ord['ar'],
                'pedido_pagar' => $ord['ap'],
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
        $totRecPrev = 0.0;
        $totDesPrev = 0.0;
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
            $totRecPrev += (float) $row['receita_prev'];
            $totDesPrev += (float) $row['despesa_prev'];
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
            'saldo_efetivo_intervalo' => $totRecEf - $totDesEf,
            'a_receber_intervalo' => $totRecPrev,
            'a_pagar_intervalo' => $totDesPrev,
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
            $rows['saldo_inicial'][$m] = $this->openingAt($start, $dailyRows, $cashGl);
            $rows['necessidade'][$m] = $rows['receitas_previstas'][$m] - $rows['despesas_previstas'][$m];
            $sapInv = $this->openingAt($end->add(new DateInterval('P1D')), $dailyRows, $investGl);
            $localInv = $this->investmentBalanceUntil($investments, $end->format('Y-m-d'));
            $rows['aplicacoes'][$m] = $sapInv + $localInv;
            $bankEnd = $this->openingAt($start->modify('first day of next month'), $dailyRows, $cashGl);
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
                $bankNow = $this->openingAt($cutoff->add(new DateInterval('P1D')), $dailyRows, $cashGl);
                $forward = $scenario === 'pv'
                    ? $netPrev
                    : $this->forecastNetFrom($forecastByDate, $year, $m, $anchor->format('Y-m-d'));
                $rows['saldo_acumulado'][$m] = $bankNow + ($scenario === 'ef' ? 0.0 : $forward);
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
            $saldo = $this->openingAt($tomorrow, $dailyRows, [$gl]);
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
     * @param list<array<string, mixed>> $daily
     * @return list<array<string, mixed>>
     */
    private function buildWeekly(array $daily): array
    {
        $weeks = [];
        foreach ($daily as $row) {
            $date = (string) ($row['date'] ?? '');
            if ($date === '') {
                continue;
            }
            $start = (new DateTimeImmutable($date))->modify('monday this week');
            $key = $start->format('Y-m-d');
            if (!isset($weeks[$key])) {
                $weeks[$key] = [
                    'week_start' => $key,
                    'week_end' => $start->modify('+6 days')->format('Y-m-d'),
                    'receita_ef' => 0.0,
                    'despesa_ef' => 0.0,
                    'receita_prev' => 0.0,
                    'despesa_prev' => 0.0,
                    'pedido_receber' => 0.0,
                    'pedido_pagar' => 0.0,
                    'acum_proj' => null,
                ];
            }
            $weeks[$key]['receita_ef'] += (float) ($row['receita_ef'] ?? 0);
            $weeks[$key]['despesa_ef'] += (float) ($row['despesa_ef'] ?? 0);
            $weeks[$key]['receita_prev'] += (float) ($row['receita_prev'] ?? 0);
            $weeks[$key]['despesa_prev'] += (float) ($row['despesa_prev'] ?? 0);
            $weeks[$key]['pedido_receber'] += (float) ($row['pedido_receber'] ?? 0);
            $weeks[$key]['pedido_pagar'] += (float) ($row['pedido_pagar'] ?? 0);
            $weeks[$key]['acum_proj'] = $row['acum_proj'] ?? $weeks[$key]['acum_proj'];
        }
        $out = array_values($weeks);
        usort($out, static fn (array $a, array $b): int => strcmp((string) $a['week_start'], (string) $b['week_start']));
        return $out;
    }

    /**
     * @param list<array<string, mixed>> $forecasts
     * @return array{bands:list<array<string,mixed>>,totals:array{ar:float,ap:float}}
     */
    private function buildAging(array $forecasts, string $today): array
    {
        $bands = [
            'a_vencer' => ['label' => 'A vencer', 'ar' => 0.0, 'ap' => 0.0],
            'd1_30' => ['label' => 'Vencido 1 a 30 dias', 'ar' => 0.0, 'ap' => 0.0],
            'd31_60' => ['label' => 'Vencido 31 a 60 dias', 'ar' => 0.0, 'ap' => 0.0],
            'd61_90' => ['label' => 'Vencido 61 a 90 dias', 'ar' => 0.0, 'ap' => 0.0],
            'd90' => ['label' => 'Vencido acima de 90 dias', 'ar' => 0.0, 'ap' => 0.0],
        ];
        $todayDt = new DateTimeImmutable($today);
        foreach ($forecasts as $fc) {
            $due = (string) ($fc['due_date'] ?? '');
            if ($due === '') {
                continue;
            }
            $amt = (float) ($fc['open_amount'] ?? 0);
            $side = (($fc['source_type'] ?? '') === 'AR') ? 'ar' : 'ap';
            if ($due >= $today) {
                $key = 'a_vencer';
            } else {
                $days = (int) $todayDt->diff(new DateTimeImmutable($due))->days;
                if ($days <= 30) {
                    $key = 'd1_30';
                } elseif ($days <= 60) {
                    $key = 'd31_60';
                } elseif ($days <= 90) {
                    $key = 'd61_90';
                } else {
                    $key = 'd90';
                }
            }
            $bands[$key][$side] += $amt;
        }
        $totals = ['ar' => 0.0, 'ap' => 0.0];
        foreach ($bands as $band) {
            $totals['ar'] += $band['ar'];
            $totals['ap'] += $band['ap'];
        }
        return ['bands' => array_values($bands), 'totals' => $totals];
    }

    /**
     * Pedidos digitados e ainda abertos. Não alteram A receber / A pagar do razão.
     * Na grade, só entra o vencimento dentro do período exibido.
     *
     * @return array{by_date:array<string,array{ar:float,ap:float}>,summary:array<string,mixed>}
     */
    private function loadOpenOrders(bool $include, ?int $branchId, string $windowStart, string $windowEnd): array
    {
        $empty = [
            'by_date' => [],
            'summary' => [
                'included' => false,
                'na_grade_receber' => 0.0,
                'na_grade_pagar' => 0.0,
                'fora_receber' => 0.0,
                'fora_pagar' => 0.0,
                'warning' => '',
                'documents' => [],
            ],
        ];
        if (!$include) {
            return $empty;
        }
        try {
            $rows = (new FinCashFlowSapSyncService())->fetchOpenOrders($branchId);
        } catch (\Throwable $e) {
            $empty['summary']['included'] = true;
            $empty['summary']['warning'] = 'Não foi possível ler os pedidos em aberto no SAP.';
            return $empty;
        }
        $byDate = [];
        $documents = [];
        $summary = $empty['summary'];
        $summary['included'] = true;
        foreach ($rows as $row) {
            $due = (string) ($row['due_date'] ?? '');
            $amt = (float) ($row['open_amount'] ?? 0);
            $isAr = (($row['source_type'] ?? '') === 'AR');
            $documents[] = [
                'due_date' => $due,
                'source_type' => $isAr ? 'AR' : 'AP',
                'doc_num' => (int) ($row['doc_num'] ?? 0),
                'doc_entry' => 0,
                'nf_serial' => '',
                'title_num' => (string) ($row['origin'] ?? 'Pedido'),
                'card_code' => (string) ($row['card_code'] ?? ''),
                'card_name' => (string) ($row['card_name'] ?? ''),
                'installment' => 0,
                'original_amount' => $amt,
                'paid_amount' => 0.0,
                'open_amount' => $amt,
                'line_memo' => 'Pedido em aberto, ainda não faturado',
                'sap_bpl_id' => (int) ($row['sap_bpl_id'] ?? 0),
                'status' => '',
                'is_order' => true,
            ];
            $inWindow = $due !== '' && $due >= $windowStart && $due <= $windowEnd;
            if ($inWindow) {
                if (!isset($byDate[$due])) {
                    $byDate[$due] = ['ar' => 0.0, 'ap' => 0.0];
                }
                if ($isAr) {
                    $byDate[$due]['ar'] += $amt;
                    $summary['na_grade_receber'] += $amt;
                } else {
                    $byDate[$due]['ap'] += $amt;
                    $summary['na_grade_pagar'] += $amt;
                }
            } elseif ($isAr) {
                $summary['fora_receber'] += $amt;
            } else {
                $summary['fora_pagar'] += $amt;
            }
        }
        $summary['documents'] = $documents;
        return ['by_date' => $byDate, 'summary' => $summary];
    }

    /**
     * Previsto do mês a partir do corte. Vencido em dias anteriores não entra na projeção.
     *
     * @param array<string, array{ar:float,ap:float}> $forecastByDate
     */
    private function forecastNetFrom(array $forecastByDate, int $year, int $month, string $fromDate): float
    {
        $prefix = sprintf('%04d-%02d', $year, $month);
        $net = 0.0;
        foreach ($forecastByDate as $date => $fc) {
            if (substr((string) $date, 0, 7) !== $prefix || (string) $date < $fromDate) {
                continue;
            }
            $net += (float) $fc['ar'] - (float) $fc['ap'];
        }
        return $net;
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
