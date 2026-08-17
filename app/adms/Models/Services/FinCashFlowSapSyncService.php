<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\cashFlow\FinCashAccountRepository;
use App\adms\Models\Repository\cashFlow\FinCashFlowCacheRepository;
use DateInterval;
use DateTimeImmutable;
use Exception;
use Throwable;

/**
 * Sincroniza contas, saldos, movimentos efetivos e títulos previstos do SAP B1
 * para o cache MySQL do Dashboard de Fluxo de Caixa.
 */
class FinCashFlowSapSyncService
{
    public const LOOKBACK_MONTHS = 18;
    public const INCREMENTAL_OVERLAP_DAYS = 7;

    private SapReportApiService $sap;
    private FinCashAccountRepository $accounts;
    private FinCashFlowCacheRepository $cache;

    public function __construct(
        ?SapReportApiService $sap = null,
        ?FinCashAccountRepository $accounts = null,
        ?FinCashFlowCacheRepository $cache = null
    ) {
        $this->sap = $sap ?? new SapReportApiService();
        $this->accounts = $accounts ?? new FinCashAccountRepository();
        $this->cache = $cache ?? new FinCashFlowCacheRepository();
    }

    public function needsDailyRefresh(): bool
    {
        if (!$this->cache->tableExists()) {
            return false;
        }
        if ($this->cache->countDailyRows() === 0) {
            return true;
        }
        $state = $this->cache->getSyncState();
        $last = $state['last_success_at'] ?? null;
        if ($last === null || $last === '') {
            return true;
        }
        $lastDay = substr((string) $last, 0, 10);
        $hoje = (new DateTimeImmutable('today'))->format('Y-m-d');
        return $lastDay < $hoje;
    }

    /**
     * @return array{ran: bool, skipped: bool, reason?: string, result?: array<string, mixed>, error?: string}
     */
    public function syncIfStaleDaily(): array
    {
        if (!$this->needsDailyRefresh()) {
            return ['ran' => false, 'skipped' => true, 'reason' => 'already_synced_today'];
        }

        $lockPath = dirname(__DIR__, 4) . '/storage/cache/fin_cash_flow_sync.lock';
        $lockDir = dirname($lockPath);
        if (!is_dir($lockDir)) {
            @mkdir($lockDir, 0775, true);
        }

        $fh = @fopen($lockPath, 'c+');
        if ($fh === false) {
            return ['ran' => false, 'skipped' => true, 'reason' => 'lock_unavailable'];
        }
        if (!flock($fh, LOCK_EX | LOCK_NB)) {
            fclose($fh);
            return ['ran' => false, 'skipped' => true, 'reason' => 'sync_in_progress'];
        }

        try {
            if (!$this->needsDailyRefresh()) {
                return ['ran' => false, 'skipped' => true, 'reason' => 'already_synced_today'];
            }
            $result = $this->sync('incremental');
            return ['ran' => true, 'skipped' => false, 'result' => $result];
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function sync(string $mode = 'incremental'): array
    {
        @set_time_limit(0);
        ini_set('memory_limit', '512M');

        $mode = strtolower(trim($mode));
        if (!in_array($mode, ['full', 'incremental', 'today', 'accounts'], true)) {
            $mode = 'incremental';
        }

        if (!$this->cache->tableExists() || !$this->accounts->tableExists()) {
            throw new Exception(
                'Tabelas do fluxo de caixa ausentes. Execute: vendor/bin/phinx migrate'
            );
        }

        $range = $this->resolveSyncRange($mode);
        $runId = $this->cache->createSyncRun([
            'sync_mode' => $mode,
            'date_from' => $range['from']->format('Y-m-d'),
            'date_to' => $range['to']->format('Y-m-d'),
            'status' => 'running',
            'started_at' => date('Y-m-d H:i:s'),
        ]);

        $stats = [
            'success' => false,
            'message' => '',
            'sync_mode' => $mode,
            'date_from' => $range['from']->format('Y-m-d'),
            'date_to' => $range['to']->format('Y-m-d'),
            'accounts_upserted' => 0,
            'opening_rows' => 0,
            'daily_rows' => 0,
            'forecast_rows' => 0,
            'rows_fetched' => 0,
            'rows_upserted' => 0,
            'transfers_neutralized' => false,
        ];

        try {
            $stats['accounts_upserted'] = $this->syncAccounts();

            if ($mode !== 'accounts') {
                $opening = $this->fetchOpening($range['from']);
                $stats['opening_rows'] = $this->cache->replaceOpening($range['from'], $opening);

                $daily = $this->fetchDailyMovements($range['from'], $range['to']);
                $internal = $this->fetchInternalTransfers($range['from'], $range['to']);
                if ($internal !== []) {
                    $daily = $this->applyInternalTransfers($daily, $internal);
                    $stats['transfers_neutralized'] = true;
                }
                $this->cache->deleteDailyRange($range['from'], $range['to']);
                $stats['daily_rows'] = $this->cache->insertDailyBatch($daily);

                $forecasts = array_merge(
                    $this->fetchForecasts('AR'),
                    $this->fetchForecasts('AP')
                );
                $stats['forecast_rows'] = $this->cache->replaceForecasts($forecasts);
            }

            $stats['rows_fetched'] = $stats['accounts_upserted'] + $stats['opening_rows']
                + $stats['daily_rows'] + $stats['forecast_rows'];
            $stats['rows_upserted'] = $stats['rows_fetched'];
            $stats['success'] = true;
            $stats['message'] = $this->buildSuccessMessage($stats);

            $now = date('Y-m-d H:i:s');
            $this->cache->finishSyncRun($runId, [
                'date_from' => $stats['date_from'],
                'date_to' => $stats['date_to'],
                'rows_fetched' => $stats['rows_fetched'],
                'rows_upserted' => $stats['rows_upserted'],
                'status' => 'completed',
                'message' => $stats['message'],
                'finished_at' => $now,
            ]);
            $this->cache->saveSyncState([
                'last_mode' => $mode,
                'last_status' => 'success',
                'last_from_date' => $stats['date_from'],
                'last_to_date' => $stats['date_to'],
                'last_success_at' => $now,
                'rows_upserted' => $stats['rows_upserted'],
                'message' => $stats['message'],
                'updated_at' => $now,
            ]);
        } catch (Throwable $e) {
            $stats['message'] = $e->getMessage();
            $now = date('Y-m-d H:i:s');
            $this->cache->finishSyncRun($runId, [
                'date_from' => $stats['date_from'],
                'date_to' => $stats['date_to'],
                'rows_fetched' => $stats['rows_fetched'],
                'rows_upserted' => $stats['rows_upserted'],
                'status' => 'failed',
                'error_log' => $e->getMessage(),
                'message' => $e->getMessage(),
                'finished_at' => $now,
            ]);
            $prev = $this->cache->getSyncState();
            $this->cache->saveSyncState([
                'last_mode' => $mode,
                'last_status' => 'failed',
                'last_from_date' => $stats['date_from'],
                'last_to_date' => $stats['date_to'],
                'last_success_at' => $prev['last_success_at'] ?? null,
                'rows_upserted' => $stats['rows_upserted'],
                'message' => $e->getMessage(),
                'updated_at' => $now,
            ]);
            throw $e;
        }

        return $stats;
    }

    /**
     * @return array{from: DateTimeImmutable, to: DateTimeImmutable}
     */
    private function resolveSyncRange(string $mode): array
    {
        $hoje = new DateTimeImmutable('today');
        if ($mode === 'today' || $mode === 'accounts') {
            return ['from' => $hoje, 'to' => $hoje];
        }
        if ($mode === 'full') {
            $from = (new DateTimeImmutable('first day of this month'))
                ->sub(new DateInterval('P' . (self::LOOKBACK_MONTHS - 1) . 'M'));
            return ['from' => $from, 'to' => $hoje];
        }
        $state = $this->cache->getSyncState();
        $lastSuccess = $state['last_success_at'] ?? null;
        if ($lastSuccess) {
            $from = (new DateTimeImmutable(substr((string) $lastSuccess, 0, 10)))
                ->sub(new DateInterval('P' . self::INCREMENTAL_OVERLAP_DAYS . 'D'));
        } else {
            $from = (new DateTimeImmutable('first day of this month'))
                ->sub(new DateInterval('P' . (self::LOOKBACK_MONTHS - 1) . 'M'));
        }
        return ['from' => $from, 'to' => $hoje];
    }

    private function syncAccounts(): int
    {
        $rows = $this->querySql($this->sqlBankAccounts());
        $count = 0;
        foreach ($rows as $row) {
            $gl = $this->col($row, ['ContaContabil', 'GLAccount', 'AcctCode']);
            if ($gl === '') {
                continue;
            }
            $name = $this->col($row, ['NomeConta', 'AcctName', 'NomeBanco']);
            $bankCode = $this->col($row, ['CodigoBanco', 'BankCode']);
            $type = $this->guessAccountType($name, $bankCode !== '');
            $this->accounts->upsertFromSap([
                'sap_gl_account' => $gl,
                'bank_code' => $bankCode !== '' ? $bankCode : null,
                'branch' => $this->col($row, ['Agencia', 'Branch']) ?: null,
                'bank_account' => $this->col($row, ['ContaBancaria', 'Account']) ?: null,
                'description' => $name !== '' ? $name : $gl,
                'account_type' => $type,
            ]);
            $count++;
        }

        try {
            $cashRows = $this->querySql($this->sqlCashAccounts());
            foreach ($cashRows as $row) {
                $gl = $this->col($row, ['ContaContabil', 'AcctCode']);
                if ($gl === '') {
                    continue;
                }
                $name = $this->col($row, ['NomeConta', 'AcctName']);
                $this->accounts->upsertFromSap([
                    'sap_gl_account' => $gl,
                    'description' => $name !== '' ? $name : $gl,
                    'account_type' => $this->guessAccountType($name, false),
                ]);
                $count++;
            }
        } catch (Throwable) {
            // OACT.Finanse pode não existir em alguns ambientes; DSC1 já cobre bancos.
        }

        return $count;
    }

    /**
     * @return list<array{sap_gl_account:string,balance:float}>
     */
    private function fetchOpening(DateTimeImmutable $from): array
    {
        $rows = $this->querySql($this->sqlOpening($from));
        $out = [];
        foreach ($rows as $row) {
            $gl = $this->col($row, ['ContaContabil', 'Account', 'AcctCode']);
            if ($gl === '') {
                continue;
            }
            $out[] = [
                'sap_gl_account' => $gl,
                'balance' => $this->num($row, ['SaldoInicial', 'Balance']),
            ];
        }
        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchDailyMovements(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $rows = $this->querySql($this->sqlDaily($from, $to));
        $out = [];
        foreach ($rows as $row) {
            $date = $this->normalizeDate($this->col($row, ['Data', 'RefDate']));
            $gl = $this->col($row, ['ContaContabil', 'Account']);
            if ($date === null || $gl === '') {
                continue;
            }
            $key = $date . '|' . $gl . '|' . (int) $this->num($row, ['Filial', 'BPLId']);
            if (!isset($out[$key])) {
                $out[$key] = [
                    'movement_date' => $date,
                    'sap_gl_account' => $gl,
                    'sap_bpl_id' => (int) $this->num($row, ['Filial', 'BPLId']),
                    'inflow' => 0.0,
                    'outflow' => 0.0,
                    'internal_in' => 0.0,
                    'internal_out' => 0.0,
                ];
            }
            $out[$key]['inflow'] += $this->num($row, ['Entradas', 'EntradasBrutas', 'Debit']);
            $out[$key]['outflow'] += $this->num($row, ['Saidas', 'SaidasBrutas', 'Credit']);
        }
        return array_values($out);
    }

    /**
     * @return array<string, array{in:float,out:float}>
     */
    private function fetchInternalTransfers(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        try {
            $rows = $this->querySql($this->sqlInternalTransfers($from, $to));
        } catch (Throwable) {
            return [];
        }
        $map = [];
        foreach ($rows as $row) {
            $date = $this->normalizeDate($this->col($row, ['Data', 'RefDate']));
            $gl = $this->col($row, ['ContaContabil', 'Account']);
            if ($date === null || $gl === '') {
                continue;
            }
            $key = $date . '|' . $gl;
            if (!isset($map[$key])) {
                $map[$key] = ['in' => 0.0, 'out' => 0.0];
            }
            $map[$key]['in'] += $this->num($row, ['EntradaInterna', 'Debit']);
            $map[$key]['out'] += $this->num($row, ['SaidaInterna', 'Credit']);
        }
        return $map;
    }

    /**
     * @param list<array<string, mixed>> $daily
     * @param array<string, array{in:float,out:float}> $internal
     * @return list<array<string, mixed>>
     */
    private function applyInternalTransfers(array $daily, array $internal): array
    {
        foreach ($daily as &$row) {
            $key = $row['movement_date'] . '|' . $row['sap_gl_account'];
            if (!isset($internal[$key])) {
                continue;
            }
            $row['internal_in'] = $internal[$key]['in'];
            $row['internal_out'] = $internal[$key]['out'];
        }
        unset($row);
        return $daily;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchForecasts(string $sourceType): array
    {
        $sql = $sourceType === 'AR' ? $this->sqlAccountsReceivable() : $this->sqlAccountsPayable();
        $rows = $this->querySql($sql);
        $out = [];
        foreach ($rows as $row) {
            $due = $this->normalizeDate($this->col($row, ['DataPrevista', 'DueDate']));
            $open = $this->num($row, ['SaldoAberto', 'OpenAmount']);
            if ($due === null || abs($open) < 0.005) {
                continue;
            }
            $orig = $this->num($row, ['InsTotal', 'ValorOriginal']);
            $paid = $this->num($row, ['PaidToDate', 'ValorPago']);
            $nf = $this->normalizeNf($this->col($row, ['NFe', 'Serial']));
            if ($nf === '') {
                $nf = $this->normalizeNf($this->col($row, ['FolioNum']));
            }
            $out[] = [
                'source_type' => $sourceType,
                'due_date' => $due,
                'sap_bpl_id' => (int) $this->num($row, ['Filial', 'BPLId']),
                'card_code' => $this->col($row, ['CardCode']),
                'card_name' => $this->col($row, ['CardName']),
                'doc_entry' => (int) $this->num($row, ['DocEntry']),
                'doc_num' => (int) $this->num($row, ['DocNum']),
                'nf_serial' => $nf,
                'title_num' => $this->normalizeNf($this->col($row, ['Titulo', 'BoeNum'])),
                'installment_id' => (int) ($this->num($row, ['Parcela', 'InstlmntID']) ?: 1),
                'original_amount' => $orig,
                'paid_amount' => $paid,
                'open_amount' => $open,
            ];
        }
        return $this->attachBoeNumbers($out, $sourceType);
    }

    /**
     * Drill-down de movimentos efetivos (consulta pontual no SAP).
     *
     * @return list<array<string, mixed>>
     */
    public function fetchEffectiveDocuments(string $date, string $direction, array $glAccounts = []): array
    {
        $sql = $this->sqlEffectiveDetail($date, $direction, $glAccounts);
        $rows = $this->querySql($sql);
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'date' => $this->normalizeDate($this->col($row, ['Data', 'RefDate'])) ?? $date,
                'document' => $this->col($row, ['TransId', 'BaseRef', 'Documento']),
                'partner' => $this->col($row, ['ShortName', 'Parceiro', 'ContraPartida']),
                'account' => $this->col($row, ['Account', 'ContaContabil']),
                'account_name' => $this->col($row, ['AcctName', 'NomeConta']),
                'memo' => $this->col($row, ['LineMemo', 'Memo', 'Historico']),
                'amount' => $direction === 'in'
                    ? $this->num($row, ['Debit', 'Valor'])
                    : $this->num($row, ['Credit', 'Valor']),
                'origin' => 'JDT1',
            ];
        }
        return $out;
    }

    private function sqlBankAccounts(): string
    {
        return 'SELECT
            D."BankCode" AS "CodigoBanco",
            D."Branch" AS "Agencia",
            D."Account" AS "ContaBancaria",
            D."GLAccount" AS "ContaContabil",
            A."AcctName" AS "NomeConta"
        FROM DSC1 D
        INNER JOIN OACT A ON A."AcctCode" = D."GLAccount"
        WHERE D."GLAccount" IS NOT NULL';
    }

    private function sqlCashAccounts(): string
    {
        return 'SELECT
            A."AcctCode" AS "ContaContabil",
            A."AcctName" AS "NomeConta"
        FROM OACT A
        WHERE A."Finanse" = \'Y\' AND IFNULL(A."Postable", \'Y\') = \'Y\'';
    }

    private function financialAccountFilter(string $alias = 'T1'): string
    {
        return '('
            . ' EXISTS (SELECT 1 FROM DSC1 D WHERE D."GLAccount" = ' . $alias . '."Account")'
            . ' OR EXISTS (SELECT 1 FROM OACT A WHERE A."AcctCode" = ' . $alias . '."Account" AND A."Finanse" = \'Y\')'
            . ')';
    }

    private function sqlOpening(DateTimeImmutable $from): string
    {
        return 'SELECT
            T1."Account" AS "ContaContabil",
            SUM(T1."Debit" - T1."Credit") AS "SaldoInicial"
        FROM JDT1 T1
        WHERE T1."RefDate" < ' . $this->quoteDate($from) . '
          AND ' . $this->financialAccountFilter('T1') . '
        GROUP BY T1."Account"';
    }

    private function sqlDaily(DateTimeImmutable $from, DateTimeImmutable $to): string
    {
        return 'SELECT
            T1."RefDate" AS "Data",
            T1."Account" AS "ContaContabil",
            IFNULL(T1."BPLId", 0) AS "Filial",
            SUM(T1."Debit") AS "Entradas",
            SUM(T1."Credit") AS "Saidas"
        FROM JDT1 T1
        WHERE T1."RefDate" BETWEEN ' . $this->quoteDate($from) . ' AND ' . $this->quoteDate($to) . '
          AND ' . $this->financialAccountFilter('T1') . '
        GROUP BY T1."RefDate", T1."Account", IFNULL(T1."BPLId", 0)
        ORDER BY T1."RefDate"';
    }

    private function sqlInternalTransfers(DateTimeImmutable $from, DateTimeImmutable $to): string
    {
        $fromQ = $this->quoteDate($from);
        $toQ = $this->quoteDate($to);
        $fin = $this->financialAccountFilter('X');
        return 'SELECT
            T1."RefDate" AS "Data",
            T1."Account" AS "ContaContabil",
            SUM(T1."Debit") AS "EntradaInterna",
            SUM(T1."Credit") AS "SaidaInterna"
        FROM JDT1 T1
        WHERE T1."RefDate" BETWEEN ' . $fromQ . ' AND ' . $toQ . '
          AND T1."TransId" IN (
              SELECT X."TransId"
              FROM JDT1 X
              WHERE X."RefDate" BETWEEN ' . $fromQ . ' AND ' . $toQ . '
                AND ' . $fin . '
              GROUP BY X."TransId"
              HAVING SUM(X."Debit") > 0 AND SUM(X."Credit") > 0
                 AND ABS(SUM(X."Debit") - SUM(X."Credit")) < 0.05
          )
          AND ' . $this->financialAccountFilter('T1') . '
        GROUP BY T1."RefDate", T1."Account"';
    }

    private function sqlAccountsReceivable(): string
    {
        return 'SELECT
            T1."DueDate" AS "DataPrevista",
            IFNULL(T0."BPLId", 0) AS "Filial",
            T0."CardCode" AS "CardCode",
            T0."CardName" AS "CardName",
            T0."DocEntry" AS "DocEntry",
            T0."DocNum" AS "DocNum",
            T0."Serial" AS "NFe",
            T0."FolioNum" AS "FolioNum",
            T1."InstlmntID" AS "Parcela",
            T1."InsTotal" AS "InsTotal",
            T1."PaidToDate" AS "PaidToDate",
            (T1."InsTotal" - T1."PaidToDate") AS "SaldoAberto"
        FROM OINV T0
        INNER JOIN INV6 T1 ON T1."DocEntry" = T0."DocEntry"
        WHERE T0."CANCELED" = \'N\'
          AND IFNULL(T1."Status", \'O\') = \'O\'
          AND (T1."InsTotal" - T1."PaidToDate") <> 0';
    }

    private function sqlAccountsPayable(): string
    {
        return 'SELECT
            T1."DueDate" AS "DataPrevista",
            IFNULL(T0."BPLId", 0) AS "Filial",
            T0."CardCode" AS "CardCode",
            T0."CardName" AS "CardName",
            T0."DocEntry" AS "DocEntry",
            T0."DocNum" AS "DocNum",
            T0."Serial" AS "NFe",
            T0."FolioNum" AS "FolioNum",
            T1."InstlmntID" AS "Parcela",
            T1."InsTotal" AS "InsTotal",
            T1."PaidToDate" AS "PaidToDate",
            (T1."InsTotal" - T1."PaidToDate") AS "SaldoAberto"
        FROM OPCH T0
        INNER JOIN PCH6 T1 ON T1."DocEntry" = T0."DocEntry"
        WHERE T0."CANCELED" = \'N\'
          AND IFNULL(T1."Status", \'O\') = \'O\'
          AND (T1."InsTotal" - T1."PaidToDate") <> 0';
    }

    /**
     * @param list<string> $glAccounts
     */
    private function sqlEffectiveDetail(string $date, string $direction, array $glAccounts): string
    {
        $amountCol = $direction === 'in' ? 'T1."Debit"' : 'T1."Credit"';
        $glFilter = '';
        if ($glAccounts !== []) {
            $quoted = array_map(static fn (string $g): string => "'" . str_replace("'", "''", $g) . "'", $glAccounts);
            $glFilter = ' AND T1."Account" IN (' . implode(',', $quoted) . ')';
        }
        return 'SELECT TOP 200
            T1."RefDate" AS "Data",
            T1."TransId" AS "TransId",
            T1."Account" AS "Account",
            A."AcctName" AS "AcctName",
            T1."ShortName" AS "ShortName",
            T1."LineMemo" AS "LineMemo",
            T1."Debit" AS "Debit",
            T1."Credit" AS "Credit"
        FROM JDT1 T1
        LEFT JOIN OACT A ON A."AcctCode" = T1."Account"
        WHERE T1."RefDate" = \'' . $date . '\'
          AND ' . $amountCol . ' > 0
          AND ' . $this->financialAccountFilter('T1')
          . $glFilter . '
        ORDER BY T1."TransId"';
    }

    /**
     * Preenche o número do boleto (OBOE) quando o SAP tiver título vinculado à parcela.
     * Se a empresa não usar Bill of Exchange, a coluna permanece vazia.
     *
     * @param list<array<string, mixed>> $forecasts
     * @return list<array<string, mixed>>
     */
    private function attachBoeNumbers(array $forecasts, string $sourceType): array
    {
        if ($forecasts === []) {
            return $forecasts;
        }
        try {
            $sql = $sourceType === 'AR' ? $this->sqlBoeReceivable() : $this->sqlBoePayable();
            $map = [];
            foreach ($this->querySql($sql) as $row) {
                $entry = (int) $this->num($row, ['DocEntry']);
                $inst = (int) ($this->num($row, ['Parcela', 'InstId']) ?: 1);
                $boe = $this->normalizeNf($this->col($row, ['BoeNum', 'Titulo']));
                if ($entry <= 0 || $boe === '') {
                    continue;
                }
                $map[$entry . '|' . $inst] = $boe;
            }
            foreach ($forecasts as &$row) {
                if (($row['title_num'] ?? '') !== '') {
                    continue;
                }
                $key = ((int) $row['doc_entry']) . '|' . ((int) $row['installment_id']);
                if (isset($map[$key])) {
                    $row['title_num'] = $map[$key];
                }
            }
            unset($row);
        } catch (Throwable $e) {
            error_log('Fluxo de caixa: boleto (OBOE) indisponível — ' . $e->getMessage());
        }
        return $forecasts;
    }

    private function sqlBoeReceivable(): string
    {
        return 'SELECT
            R2."DocEntry" AS "DocEntry",
            IFNULL(R2."InstId", 1) AS "Parcela",
            B."BoeNum" AS "BoeNum"
        FROM RCT2 R2
        INNER JOIN ORCT R0 ON R0."DocEntry" = R2."DocNum" AND IFNULL(R0."Canceled", \'N\') = \'N\'
        INNER JOIN OBOE B ON B."PmntNum" = R0."DocEntry"
        WHERE R2."InvType" = 13
          AND B."BoeStatus" NOT IN (\'C\', \'L\')';
    }

    private function sqlBoePayable(): string
    {
        return 'SELECT
            V2."DocEntry" AS "DocEntry",
            IFNULL(V2."InstId", 1) AS "Parcela",
            B."BoeNum" AS "BoeNum"
        FROM VPM2 V2
        INNER JOIN OVPM V0 ON V0."DocEntry" = V2."DocNum" AND IFNULL(V0."Canceled", \'N\') = \'N\'
        INNER JOIN OBOE B ON B."PmntNum" = V0."DocEntry"
        WHERE V2."InvType" = 18
          AND B."BoeStatus" NOT IN (\'C\', \'L\')';
    }

    private function normalizeNf(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $value === '0' || $value === '0.0') {
            return '';
        }
        return $value;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function querySql(string $sql): array
    {
        $result = $this->sap->execute($sql);
        $data = $result['data'] ?? [];
        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string> $keys
     */
    private function col(array $row, array $keys): string
    {
        $normalized = [];
        foreach ($row as $k => $v) {
            $normalized[strtolower((string) $k)] = $v;
        }
        foreach ($keys as $key) {
            $lk = strtolower($key);
            if (array_key_exists($lk, $normalized) && $normalized[$lk] !== null && $normalized[$lk] !== '') {
                return trim((string) $normalized[$lk]);
            }
        }
        return '';
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string> $keys
     */
    private function num(array $row, array $keys): float
    {
        $val = $this->col($row, $keys);
        if ($val === '') {
            return 0.0;
        }
        return (float) str_replace(',', '.', $val);
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $raw = substr(trim((string) $value), 0, 10);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }
        try {
            return (new DateTimeImmutable((string) $value))->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    private function guessAccountType(string $name, bool $hasBank): string
    {
        $n = mb_strtolower($name);
        if (str_contains($n, 'aplica') || str_contains($n, 'cdb') || str_contains($n, 'investimento')) {
            return 'INVESTMENT';
        }
        if (str_contains($n, 'caixa') || str_contains($n, 'numerario')) {
            return 'CASH';
        }
        if (str_contains($n, 'transit') || str_contains($n, 'compens')) {
            return 'TRANSIT';
        }
        return $hasBank ? 'BANK' : 'CASH';
    }

    /**
     * @param array<string, mixed> $stats
     */
    private function buildSuccessMessage(array $stats): string
    {
        if (($stats['sync_mode'] ?? '') === 'accounts') {
            return 'Contas financeiras atualizadas: ' . (int) $stats['accounts_upserted'] . '.';
        }
        return sprintf(
            'Sync concluído: %d contas, %d saldos iniciais, %d dias, %d títulos previstos.',
            (int) $stats['accounts_upserted'],
            (int) $stats['opening_rows'],
            (int) $stats['daily_rows'],
            (int) $stats['forecast_rows']
        );
    }

    private function quoteDate(DateTimeImmutable $d): string
    {
        return "'" . $d->format('Y-m-d') . "'";
    }
}
